<?php

namespace Andruby\Pay\Services;

use Andruby\Pay\Models\CashOutOrder;
use App\Models\Member;
use Andruby\Pay\Models\WxPreorder;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use GuzzleHttp\Exception\GuzzleException;
use Yansongda\Pay\Pay;
use EasyWeChat\Factory;

class WechatService extends PayService
{
    /**
     * 微信支付配置
     * @param string $app_id
     * @return string
     */
    public static function wx_pay_config($app_id = '')
    {
        $app_id = empty($app_id) ? request('app_id', '5nIirNFJloRP4LhYeoeNMjEcSHsSzmT9') : $app_id;
        $config = config('deep_pay.' . $app_id);

        $config['wechat'][env('WX_APP_ID')]['notify_url'] = route('wx_pay.notify', ['app_id' => $app_id]);

        return $config;
    }

    /**
     * 生成预订单信息
     * @param $title
     * @param $price
     * @param $out_trade_no
     * @param $attach
     * @param $notify_url
     * @param $openid
     * @param $source
     * @return mixed
     */
    public static function pre_oder_mini($title, $price, $out_trade_no, $attach, $notify_url, $openid, $source)
    {
        $order = [
            'out_trade_no' => $out_trade_no,
            'amount' => [
                'total' => intval($price * 100), // **单位：分**
                'currency' => 'CNY',
            ],
            'description' => $title,
            '_config' => env('WX_APP_ID'),
        ];
        if (!empty($attach)) {
            $order['attach'] = json_encode($attach);
        }

        try {
            $config = self::wx_pay_config();

            if ($source == Member::SOURCE_WX) { // 微信环境&微信公众号支付
                $order['payer'] = ['openid' => $openid];

                $pay = Pay::wechat($config)->mp($order);
            } else if ($source == Member::SOURCE_H5) { // H5环境&微信H5支付
                $order['scene_info'] = [
                    'payer_client_ip' => get_client_ip(),
                    'h5_info' => [
                        'type' => 'Wap',
                    ]
                ];

                $pay = Pay::wechat($config)->wap($order);
            } else if ($source == Member::SOURCE_PC) { // PC环境&微信扫码支付
                $pay = Pay::wechat($config)->scan($order);
                // 支付二维码
                $pay['qrcode_url'] = QRService::instance()->qrcode($pay['code_url'], 'wx_' . $out_trade_no . '.jpeg', 'wx_pay');
            } else if ($source == Member::SOURCE_MINI) { // 微信小程序
                $order['payer'] = ['openid' => $openid];

                $pay = Pay::wechat($config)->mini($order);
            } else { // app支付
                $pay = Pay::wechat($config)->app($order);
            }

            $status['code'] = 200;
            $status['msg'] = 'success';
            $status['data'] = $pay;

            wx_pay_log('wx per_order = ' . json_encode($status));

        } catch (\Exception $ex) {
            $status['code'] = -1;
            $status['msg'] = $ex->getMessage();

            error_log_info('[wx per_order error]', ['error' => $ex->getMessage()]);
        }

        return $status;
    }

    /**
     * 微信支付预订单
     * @param $user_id
     * @param $goodsSku
     * @param $goods_type
     * @param $pre_order
     * @param $out_trade_no
     * @return bool
     */
    public static function create_pre_order_mini($user_id, $goodsSku, $goods_type, $pre_order, $out_trade_no)
    {
        $app_id = request('app_id', '5nIirNFJloRP4LhYeoeNMjEcSHsSzmT9');
        $appid = env('WX_APP_ID');

        $result['return_code'] = 'SUCCESS';
        $result['return_msg'] = 'OK';

        $result['data'] = json_encode($pre_order);
        $result['goods_id'] = $goodsSku['goods_id'];
        $result['name'] = $goodsSku['name'];
        $result['sku_id'] = $goodsSku['id'];
        $result['out_trade_no'] = $out_trade_no;
        $result['goods_type'] = $goods_type;
        $result['user_id'] = $user_id;
        $result['create_time'] = time();
        $result['pay_fee'] = $goodsSku['price'];

        $result['app_id'] = $app_id;
        $result['channel'] = request('channel');

        $result['appid'] = $pre_order['appId'] ?? '';
        $result['mch_id'] = config('deep_pay.' . $app_id . '.wechat.' . $appid . '.mch_id');
        $result['prepay_id'] = $pre_order['package'] ?? '';
        $result['nonce_str'] = $pre_order['nonceStr'] ?? '';
        $result['package'] = $pre_order['package'] ?? '';
        $result['sign'] = $pre_order['paySign'] ?? '';

        $order_info = WxPreorder::query()->updateOrCreate(['out_trade_no' => $out_trade_no], $result);

        if ($order_info) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 微信v2提现
     * @param $userId
     * @param $openid
     * @param $trade_no
     * @param $title
     * @param $price
     * @param string $user_name
     * @return bool
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws GuzzleException
     */
    public static function v2_transfer($userId, $openid, $trade_no, $title, $price, $user_name = '')
    {
        $config = config('deep_pay.wechat.' . env('WX_APP_ID'));

        $app = Factory::payment($config);

        if (env('WX_PAY_DEV', false)) {
            $price = 100;
        }

        $order = [
            'partner_trade_no' => $trade_no, // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => $openid,
            'check_name' => 'FORCE_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            're_user_name' => $user_name, // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
            'amount' => round($price), // 企业付款金额，单位为分
            'desc' => $title, // 企业付款操作说明信息。必填
        ];
        wx_pay_log('cash order user_id = ' . $userId . ', openid = ' . $openid . ', transfer = ' . json_encode($order));

        $result = $app->transfer->toBalance($order);
        wx_pay_log('cash result user_id = ' . $userId . ', openid = ' . $openid . ', transfer = ' . json_encode($result));

        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            CashOutOrderService::instance()->save_data($userId, $trade_no, $result['payment_no'], $result['payment_time'], $result['result_code'], CashOutOrder::TYPE_WX, $price, json_encode($result));

            return true;
        } else {
            error_log_info('cash error openid = ' . $openid . ' msg = ' . json_encode($result));
            return false;
        }
    }

    /**
     * todo 微信v3提现（账户转账）
     * @param string $openid 小程序openid
     * @param string $title 标题
     * @param string $trade_no 订单号
     * @param integer $price 单位分
     * @return bool
     */
    public static function v3_transfer($openid, $trade_no, $title, $price)
    {
        if (env('WX_PAY_DEV', false)) {
            $price = 100;
        }

        $order = [
            'out_batch_no' => $trade_no,
            'total_amount' => $price,
            'total_num' => 1,
            'batch_name' => $title,
            'batch_remark' => $title,
            'transfer_detail_list' => [
                [
                    'out_detail_no' => $trade_no,
                    'transfer_amount' => $price,
                    'transfer_remark' => $title,
                    'openid' => $openid,
                ],
            ],
            '_config' => env('WX_APP_ID'),
        ];

        $config = self::wx_pay_config();

        $result = Pay::wechat($config)->transfer($order);
        wx_pay_log('cash info openid = ' . $openid . ', transfer = ' . json_encode($result));

        if (!empty($result['out_batch_no']) && !empty($result['batch_id'])) {
            return true;
        } else {
            error_log_info('cash error openid = ' . ' msg = ' . json_encode($result));
            return false;
        }
    }

    // 订单退款(小程序)
    public static function refund($orderInfo, $app_id): array
    {
        $config = self::wx_pay_config($app_id);

        $order = [
            'out_trade_no' => $orderInfo['out_trade_no'],
            'out_refund_no' => PayService::out_trade_no($orderInfo['user_id'], $orderInfo['buy_info'], time()),
            'amount' => [
                'refund' => $orderInfo['pay_fee'] * 100,
                'total' => $orderInfo['pay_fee'] * 100,
                'currency' => 'CNY',
            ],
            '_config' => env('WX_APP_ID'),
        ];

        try {
            $pay = Pay::wechat($config)->refund($order);

            wx_pay_log('[wx refund]', ['data' => $pay]);

            $result['code'] = 200;
            $result['msg'] = 'success';

        } catch
        (\Exception $ex) {
            $result['code'] = -1;
            $result['msg'] = $ex->getMessage();

            error_log_info('[wx refund]', ['data' => $result]);
        }

        return $result;
    }
}
