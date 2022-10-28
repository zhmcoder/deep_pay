<?php

namespace Andruby\Pay\Services;

use Andruby\Pay\Models\CashOutOrder;
use App\Models\Member;
use Yansongda\Pay\Pay;

class AliService extends PayService
{
    /**
     * 支付宝 支付配置
     * @param string $app_id
     * @return mixed
     */
    public static function ali_pay_config($app_id = '')
    {
        $app_id = empty($app_id) ? request('app_id', '5nIirNFJloRP4LhYeoeNMjEcSHsSzmT9') : $app_id;
        $config = config('deep_pay.' . $app_id);

        $config['alipay'][env('ALI_APP_ID')]['notify_url'] = route('ali_pay.notify', ['app_id' => $app_id]);

        return $config;
    }

    /**
     * 生成支付宝订单信息
     * @param $title
     * @param $price
     * @param $out_trade_no
     * @param $attach
     * @param $notify_url
     * @param $source
     * @param array $goodsSku
     * @return mixed
     */
    public static function pre_oder_mini($title, $price, $out_trade_no, $attach, $notify_url, $source, $goodsSku = [])
    {
        $order = [
            'out_trade_no' => $out_trade_no,
            'total_amount' => $price,
            'subject' => $title,
            '_config' => env('ALI_APP_ID'),
        ];

        $config = self::ali_pay_config();

        if ($source == Member::SOURCE_WX || $source == Member::SOURCE_H5) {
            if (!empty($attach)) {
                $order['passback_params'] = urlencode(http_build_query($attach));
            }

            $config['alipay'][env('ALI_APP_ID')]['return_url'] .= $goodsSku['id'] . '&sku=true';

            $order['quit_url'] = env('APP_URL');
            return Pay::alipay($config)->wap($order);
        } else if ($source == Member::SOURCE_PC) { // 微信PC扫码支付
            $pay = Pay::alipay($config)->scan($order);

            unset($pay['out_trade_no']);
            $pay['qrcode_url'] = QRService::instance()->qrcode($pay['qr_code'], 'ali_' . $out_trade_no . '.jpeg', 'ali_pay');
            return $pay;
        }
    }

    /**
     * 支付宝授权二维码
     * @param $user_id
     * @param $appid
     * @param $token
     * @return mixed
     */
    public static function alipay_bind_qrcode($user_id, $appid, $token)
    {
        $app_id = env('ALI_APP_ID');
        $redirect_uri = route('ali_pay.aliInfo') . '?token=' . $token . '&appid=' . $appid;
        $auth_url = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=' . $app_id . '&scope=auth_base&state=' . '' . '&redirect_uri=' . urlencode($redirect_uri);

        if (!file_exists(storage_path('app/public/alipay'))) {
            mkdir(storage_path('app/public/alipay'));
        }

        return QRService::instance()->qrcode($auth_url, 'ali_' . $user_id . '.jpeg');
    }

    /**
     * 支付宝提现
     * @param $userId
     * @param $out_biz_no
     * @param $order_title
     * @param $price
     * @param $alipay_id
     * @return bool
     */
    public static function transfer($userId, $out_biz_no, $order_title, $price, $alipay_id)
    {
        $price = round($price / 100, 2);

        if (env('ALI_PAY_DEV', false)) {
            $price = 0.1;
        }
        $order = [
            'out_biz_no' => $out_biz_no,
            'trans_amount' => $price, // 元
            'product_code' => 'TRANS_ACCOUNT_NO_PWD',
            'biz_scene' => 'DIRECT_TRANSFER',
            'order_title' => $order_title,
            'payee_info' => [
                'identity' => $alipay_id,
                'identity_type' => 'ALIPAY_USER_ID',
            ],
            'remark' => '用户提现',
            '_config' => env('ALI_APP_ID'),
        ];
        ali_pay_log('cash order user_id = ' . $userId . ', alipay_id = ' . $alipay_id . ', transfer = ' . json_encode($order));

        $config = self::ali_pay_config();

        $result = Pay::alipay($config)->transfer($order);
        ali_pay_log('cash result user_id = ' . $userId . ', alipay_id = ' . $alipay_id . ', transfer = ' . json_encode($result));

        if ($result['code'] == '10000' && !isset($result['sub_code'])) {
            CashOutOrderService::instance()->save_data($userId, $out_biz_no, $result['order_id'], $result['trans_date'], $result['status'], CashOutOrder::TYPE_ALI, $price * 100, json_encode($result));

            return true;
        } else {
            error_log_info('cash error alipay_id = ' . $alipay_id . ' msg = ' . json_encode($result));
            return false;
        }
    }

    /**
     * 支付宝授权获取支付宝信息
     * @param $auth_code
     * @return bool
     */
    public static function getAccessToken($auth_code)
    {
        $order = [
            'code' => $auth_code,
            'grant_type' => 'authorization_code',
            '_config' => env('ALI_APP_ID'),
        ];

        $config = self::ali_pay_config(request('appid'));

        $data = Pay::alipay($config)->api($order);
        $data = $data->toArray();

        if (key_exists('user_id', $data)) {
            $alipay_id = $data['user_id'];
        } else {
            $alipay_id = null;
        }
        return $alipay_id;
    }
}
