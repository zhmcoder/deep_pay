<?php

namespace Andruby\Pay\Controllers;

use Andruby\Login\Models\UcenterMember;
use Andruby\Pay\Services\PayService;
use Andruby\Pay\Services\WechatService;
use Andruby\Pay\Validates\PayValidate;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yansongda\Pay\Pay;
use App\Api\Controllers\PayController;

class WxPayController extends PayController
{
    // 微信支付
    public function pay(Request $request, PayValidate $validate)
    {
        if ($validate->weixin($request->only(['goods_id', 'sku_id']))) {

            $appId = $request->input('app_id');
            $source = $request->input('source', Member::SOURCE_WX);
            $amount = $request->input('amount', 1);
            $orderId = $request->input('order_id');

            $userInfo = $this->userInfo();
            $userInfo['openid'] = UcenterMember::query()->where(['id' => $userInfo['id']])->value('openid');

            $this->wx_pay($userInfo, $source);

            $orderItem = $this->order_item($userInfo, $orderId, $amount);
            $goodsSku = $this->goods_sku($orderItem, $amount);

            $order = $this->order($userInfo['id'], $orderItem, $goodsSku);

            if (!empty($order)) {
                $out_trade_no = $order['out_trade_no'];
                debug_log_info('old out_trade_no: ' . $out_trade_no);
            } else {
                $out_trade_no = PayService::out_trade_no($userInfo['id'], $goodsSku, time());
                debug_log_info('new out_trade_no: ' . $out_trade_no);
            }

            $attach = [
                'user_id' => $userInfo['id'],
                'app_id' => $appId,
                'goods_id' => $goodsSku['goods_id'],
                'sku_id' => $goodsSku['id'],
            ];
            if (env('WX_PAY_DEV') || in_array($userInfo['id'], explode(',', env('WX_PAY_USER_LIST')))) {
                $goodsSku['price'] = 0.01;
            }
            $goodsSku['pay_fee'] = $goodsSku['price'] * $amount;

            $pre_order = WechatService::pre_oder_mini($goodsSku['name'], $goodsSku['pay_fee'], $out_trade_no, $attach, '', $userInfo['openid'], $source);
            if ($pre_order['code'] == 200 && empty($pre_order['data']['code'])) {
                if (empty($order)) {
                    $order = PayService::create_order($userInfo['id'], $out_trade_no, $goodsSku, 0, 0, Order::PAY_WX_PAY, Order::STATUS_WAITING, OrderItem::RES_TYPE_PAY, $appId, $amount);
                } else {
                    Order::query()->where(['out_trade_no' => $order['out_trade_no']])->update(['out_trade_no' => $out_trade_no]);
                }
                if (!empty($order)) {
                    // 生成预订单信息
                    $pre_order['data']['order_sn'] = $order['order_sn'];
                    WechatService::create_pre_order_mini($userInfo['id'], $goodsSku, 1, $pre_order['data'], $out_trade_no);

                    $this->responseJson(self::CODE_SUCCESS_CODE, 'success', $pre_order['data']);
                } else {
                    error_log_info('[wx pay order error]', ['error' => json_encode($order)]);
                    $this->responseJson(self::CODE_SHOW_MSG, '订单生成失败', $pre_order['data']);
                }
            } else {
                error_log_info('[wx pay pre_order error]', ['error' => json_encode($pre_order)]);
                $this->responseJson(self::CODE_SHOW_MSG, '微信预订单生成错误', $pre_order);
            }
        } else {
            $this->responseJson(-1, $validate->message);
        }
    }

    // 微信支付回调
    public function notify($appId)
    {
        DB::beginTransaction();
        try {
            // 支付配置加载
            $config = WechatService::wx_pay_config($appId);

            Pay::config($config);

            $result = Pay::wechat()->callback(null, ['_config' => env('WX_APP_ID')]);
            wx_pay_log("wx notify data = " . json_encode($result));

            // 处理支付回调业务逻辑
            PayService::wx_callback($result);

            DB::commit();
            return Pay::wechat()->success();
        } catch (\Exception $e) {
            //输出错误日志
            error_log_info('[wx notify error]', ['error' => $e->getMessage()]);
            error_log_info('[wx notify error]', ['wx notify data' => request()->getContent()]);

            DB::rollBack();
            $this->responseJson(self::CODE_SHOW_MSG, '微信回调失败');
        }
    }

    public function refund()
    {
        $orderId = request('order_id');
        $refundType = request('refund_type', 2);

        $orderInfo = Order::query()->where(['id' => $orderId])->with('orderItem')->first();

        if (empty($orderInfo)) {
            $this->responseJson(self::CODE_SHOW_MSG, '订单信息错误');
        }

        if ($orderInfo['status'] != Order::STATUS_SUCCESS) {
            $this->responseJson(self::CODE_SHOW_MSG, '订单未支付');
        }

        if ($orderInfo['refund_status'] != Order::NO_REFUND) {
            $this->responseJson(self::CODE_SHOW_MSG, '订单已申请退款');
        }

        Order::query()->where(['id' => $orderId])->update(['refund_status' => Order::YES_REFUND, 'refund_type' => $refundType]);
        $this->responseJson(self::CODE_SUCCESS_CODE, 'success', []);

        /*
        $orderInfo['buy_info'] = $orderInfo['orderItem'][0];
        $result = WechatService::refund($orderInfo);

        if ($result['code'] == 200) {
            // 更新订单
            Order::query()->where(['id' => $orderId])->update(['refund_status' => Order::REFUND_SUCCESS, 'refund_type' => $refundType]);

            $this->responseJson(self::CODE_SUCCESS_CODE, 'success', []);
        } else {
            Order::query()->where(['id' => $orderId])->update(['refund_status' => Order::REFUND_FAIL]);

            $this->responseJson(self::CODE_SHOW_MSG, '退款失败');
        }
        */
    }
}
