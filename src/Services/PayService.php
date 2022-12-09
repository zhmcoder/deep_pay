<?php

namespace Andruby\Pay\Services;

use Andruby\DeepGoods\Models\GoodsSku;
use Andruby\DeepGoods\Models\GoodsSkuStock;
use Andruby\Pay\Models\AliOrder;
use App\Api\Services\PrecedenceOrderService;
use App\Models\Address;
use App\Models\Goods;
use App\Models\Member;
use App\Models\OrderItem;
use App\Models\Order;
use Andruby\Pay\Models\WxOrder;
use Andruby\Pay\Models\WxPreorder;
use App\Models\OrderTravel;
use App\Models\PrecedenceOrder;
use App\Services\ChainService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PayService
{
    /**
     * 生成订单号
     * @param $user_id
     * @param $buy_info
     * @param $time
     * @return string
     */
    public static function out_trade_no($user_id, $buy_info, $time): string
    {
        return md5($user_id . $buy_info['goods_id'] . $buy_info['id'] . $time);
    }

    /**
     * 创建订单
     * @param $user_id
     * @param $out_trade_no
     * @param $buy_info
     * @param int $pre_order_id
     * @param int $transaction_id
     * @param int $pay_type
     * @param int $status
     * @param int $type
     * @param null $app_id
     * @return array|null
     */
    public static function create_order($user_id, $out_trade_no, $buy_info, $pre_order_id = 0, $transaction_id = 0, $pay_type = Order::PAY_WX_PAY, $status = Order::STATUS_WAITING, $type = OrderItem::RES_TYPE_PAY, $app_id = null, $amount = 1)
    {
        DB::beginTransaction();
        try {
            $order_sn = date('YmdHis') . $user_id;

            $addressId = request('address_id');
            $addressInfo = [];//Address::query()->find($addressId);
            if (!empty($addressInfo)) {
                $consignee = $addressInfo['consignee'];
                $phone = $addressInfo['phone'];
                $address = $addressInfo['address'];
            } else {
                $consignee = request('consignee');
                $phone = request('cellphone');
                $address = request('address');
            }

            $order = [
                'user_id' => $user_id,
                'pay_amount' => $buy_info['pay_fee'],
                'order_status' => $status,
                'pre_order_id' => $pre_order_id,
                'out_trade_no' => $out_trade_no,
                'transaction_id' => $transaction_id,
                'pay_type' => $pay_type,
                'refund_status' => 1,
                'app_id' => $app_id ?: request('app_id'),
                'order_num' => $order_sn,
                'order_sn' => $order_sn,
                'type' => $type,
                'consignee' => $consignee,
                'cellphone' => $phone,
                'address' => $address,
                'travel_date' => request('travel_date', ''),
                'travel_num' => request('travel_num', 0),
                'breaks' => request('breaks'),
            ];

            $orderInfo = Order::query()->create($order);
            $order_id = $orderInfo['id'];

            // 同行人信息
            $orderTravel = json_decode(request('order_travel'), true);
            if (!empty($orderTravel)) {
                foreach ($orderTravel as $travel) {
                    $data = [
                        'name' => $travel['name'] ?? '',
                        'phone' => $travel['phone'] ?? '',
                        'identity_card' => $travel['identity_card'] ?? '',
                        'order_id' => $order_id,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    OrderTravel::query()->insert($data);
                }
            }

            $orderItemInfo = self::create_order_item($order_id, $user_id, $buy_info, $app_id, $status, $type, $amount);

            self::stock($buy_info, $amount);

            DB::commit();
            return [
                'order_id' => $orderInfo['id'],
                'order_item_id' => $orderItemInfo['id'],
                'order_sn' => $order_sn,
            ];
        } catch (\Exception $e) {
            error_log_info('order created error = ' . $e->getMessage());

            DB::rollBack();
            return null;
        }
    }

    /**
     * 创建订单详情
     * @param $order_id
     * @param $user_id
     * @param $buy_info
     * @param int $pay_status
     * @param int $res_type
     * @param $app_id
     * @return Builder|Model|OrderItem|null
     */
    public static function create_order_item($order_id, $user_id, $buy_info, $app_id, $pay_status = Order::STATUS_WAITING, $res_type = OrderItem::RES_TYPE_PAY, $amount = 1)
    {
        $where = ['id' => request('goods_id')];
        $where[] = ['goods_id', '>', 0];
        $goodsInfo = [];//Goods::query()->where($where)->first();
        $help_sell_id = $help_sell_goods_id = 0;
        if (!empty($goodsInfo)) {
            $help_sell_id = $goodsInfo['add_user_id'];
            $help_sell_goods_id = request('goods_id');
        }

        // 计算佣金
        if ($help_sell_id) {
            if ($buy_info['help_commission_type'] == 2) {
                $rebate_price = round($buy_info['help_commission_price'] * $amount, 2);
            } else {
                $rebate_price = round($buy_info['help_commission_ratio'] * $buy_info['price'] * $amount / 100, 2);
            }
        } else {
            $rebate_price = 0;
        }

        $group_number = OrderItem::query()->where(['goods_id' => $buy_info['goods_id']])->count();

        $orderItem = [
            'order_id' => $order_id,
            'user_id' => $user_id,
            'goods_id' => $buy_info['goods_id'],
            'sku_id' => $buy_info['id'],
            'goods_name' => $buy_info['name'],
            'image_url' => $buy_info['image'] ?? '',
            'goods_type' => $buy_info['goods_type'],
            'price' => $buy_info['price'],
            'amount' => $amount,
            'pay_status' => $pay_status,
            'app_id' => $app_id ?: request('app_id'),
            'res_type' => $res_type,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $orderItemInfo = OrderItem::query()->create($orderItem);
        if ($orderItemInfo['id']) {
            return $orderItemInfo;
        } else {
            return null;
        }
    }

    /**
     * 库存信息
     * @param $buy_info
     * @param $amount
     * @return int|null
     */
    public static function stock($buy_info, $amount)
    {
        $data = GoodsSkuStock::query()->where(['sku_id' => $buy_info['id']])->decrement('quantity', $amount);
        if ($data) {
            GoodsSku::query()->where(['id' => $buy_info['id']])->increment('sold_num', $amount);
            return $data;
        } else {
            return null;
        }
    }

    /**
     * 微信支付回调
     * @param $result
     * @return bool
     * @throws \Exception
     */
    public static function wx_callback($result)
    {
        $resource = $result['resource']['ciphertext'];

        $attach = json_decode($resource['attach'], true);
        $out_trade_no = $resource['out_trade_no'];
        wx_pay_log('wx notify out_trade_no = ' . $out_trade_no);

        // 预付单信息
        $where['out_trade_no'] = $out_trade_no;
        $fields = ['goods_id', 'sku_id', 'goods_type', 'pay_fee'];
        $preOrderInfo = WxPreorder::query()->where($where)->select($fields)->first();
        wx_pay_log('wx notify pre_order data = ' . json_encode($preOrderInfo));
        if (empty($preOrderInfo)) {
            error_log_info('wx notify pre_order = ' . json_encode($preOrderInfo));
            return true;
        }

        $orderInfo = Order::query()->where(['out_trade_no' => $out_trade_no])->first();
        if (empty($orderInfo)) {
            error_log_info('wx notify order_info = ' . json_encode($orderInfo));
            return true;
        }

        if ($orderInfo['status'] == Order::STATUS_SUCCESS) {
            ali_pay_log('wx notify order pay');
            return true;
        }

        // 更新预订单
        WxPreorder::query()->where('out_trade_no', $out_trade_no)->update(['status' => ORDER::STATUS_SUCCESS]);

        // 微信账单
        $wxOrder = [
            'appid' => $resource['appid'],
            'attach' => json_encode($attach),
            'out_trade_no' => $out_trade_no,
            'data' => json_encode($result),
            'user_id' => $attach['user_id'],
            'app_id' => $attach['app_id'],
            'bank_type' => $resource['bank_type'],
            'cash_fee' => $resource['amount']['payer_total'],
            'fee_type' => $resource['amount']['currency'],
            'mch_id' => $resource['mchid'],
            'openid' => $resource['payer']['openid'],
            'total_fee' => $resource['amount']['total'],
            'trade_type' => $resource['trade_type'],
            'trade_state' => $resource['trade_state'],
            'transaction_id' => $resource['transaction_id'],
            'time_end' => date('Y-m-d H:i:s', strtotime($resource['success_time'])),
        ];
        WxOrder::query()->updateOrCreate(['out_trade_no' => $out_trade_no], $wxOrder);
        wx_pay_log('wx notify wx_order data = ' . json_encode($wxOrder));
        if ($resource['trade_state'] != 'SUCCESS') {
            error_log_info('wx notify trade_state = ' . $resource['trade_state']);
            return true;
        }

        // 更新订单
        $order = [
            'pay_amount' => $resource['amount']['total'] / 100,
            'pre_order_id' => $preOrderInfo['id'],
            'transaction_id' => $resource['transaction_id'],
            'order_status' => Order::STATUS_SUCCESS,
            'pay_time' => strtotime($resource['success_time']),
            'pay_type' => Order::PAY_WX_PAY,
        ];
        $orderRes = Order::query()->where(['out_trade_no' => $out_trade_no])->update($order);
        wx_pay_log('wx notify order data = ' . json_encode($orderRes));

        // 更新订单详情
        $orderItem = OrderItem::query()->where(['order_id' => $orderInfo['id']])->first();
        if (empty($orderItem)) {
            error_log_info('wx notify order_item info = ' . json_encode($orderItem));
            return true;
        }

        $orderItemData = [
            'pay_status' => Order::STATUS_SUCCESS,
        ];
        OrderItem::query()->where(['order_id' => $orderInfo['id']])->update($orderItemData);
        wx_pay_log('wx notify order_item data = ' . json_encode($orderItemData));

        // SettlementService::instance()->order_rebate($orderInfo['id'], $orderInfo['pay_fee'], $orderItem);

        return true;
    }

    /**
     * 支付宝支付回调
     * @param $result
     * @return bool
     * @throws \Exception
     */
    public static function ali_callback($result)
    {
        $out_trade_no = $result['out_trade_no'];
        ali_pay_log('ali notify out_trade_no = ' . $out_trade_no);

        $orderInfo = Order::query()->where(['out_trade_no' => $out_trade_no])->first();
        ali_pay_log('ali notify order_info = ' . json_encode($orderInfo));
        if (empty($orderInfo)) {
            error_log_info('ali notify order_info = ' . json_encode($orderInfo));
            return true;
        }
        if ($orderInfo['status'] == Order::STATUS_SUCCESS) {
            ali_pay_log('ali notify order pay');
            return true;
        }

        // 支付宝账单
        $aliOrder = [
            'user_id' => $orderInfo['user_id'],
            'app_id' => $result['app_id'],
            'invoice_amount' => $result['invoice_amount'],
            'fund_bill_list' => json_encode($result['fund_bill_list']),
            'notify_type' => $result['notify_type'],
            'trade_status' => $result['trade_status'],
            'receipt_amount' => $result['receipt_amount'],
            'buyer_pay_amount' => $result['buyer_pay_amount'],
            'seller_id' => $result['seller_id'],
            'gmt_payment' => $result['gmt_payment'],
            'notify_time' => $result['notify_time'],
            'out_trade_no' => $result['out_trade_no'],
            'total_amount' => $result['total_amount'],
            'trade_no' => $result['trade_no'],
            'attach' => $result['passback_params'] ?? '',
            'trade_type' => !empty($result['passback_params']) ? AliOrder::TRADE_TYPE_WAP : AliOrder::TRADE_TYPE_SCAN,
        ];
        AliOrder::query()->updateOrCreate(['out_trade_no' => $out_trade_no], $aliOrder);
        ali_pay_log('ali notify ali_order data = ' . json_encode($aliOrder));
        if ($result['trade_status'] != 'TRADE_SUCCESS') {
            error_log_info('ali notify trade_status = ' . $result['trade_status']);
            return true;
        }

        // 更新订单
        $order = [
            'pay_fee' => $result['total_amount'],
            'status' => Order::STATUS_SUCCESS,
            'pay_time' => $result['gmt_payment'],
            'pay_type' => Order::PAY_ALI_PAY
        ];
        Order::query()->where(['out_trade_no' => $out_trade_no])->update($order);
        ali_pay_log('ali notify order data = ' . json_encode($order));

        // 更新订单详情
        $orderItem = OrderItem::query()->where(['order_id' => $orderInfo['id']])->first();
        if (empty($orderItem)) {
            error_log_info('wx notify order_item info = ' . json_encode($orderItem));
            return true;
        }

        $orderItemData = [
            'pay_status' => Order::STATUS_SUCCESS,
        ];
        OrderItem::query()->where(['order_id' => $orderInfo['id']])->update($orderItemData);
        ali_pay_log('ali notify order_item data = ' . json_encode($orderItemData));

        // 订单分佣
        // SettlementService::instance()->order_rebate($orderInfo['id'], $orderInfo['pay_fee'], $orderItem);

        return true;
    }
}
