<?php

return array(
    'order_pay' => 1, // 支付金额（单位分）

    'order_auto_cancel_time' => env('ORDER_AUTO_CANCEL_TIME', 5 * 60), // 抢订单后自动取消时间（单位秒）

    // 新配置结构
    '5nIirNFJloRP4LhYeoeNMjEcSHsSzmT9' => [ // 小程序 nft
        'wechat' => [
            'wxd1dd50ee648ceea7' => [
                'mch_id' => '',
                'mch_secret_key' => '',
                'mch_secret_cert' => '',
                'mch_public_cert_path' => '',
                'notify_url' => env('APP_URL') . '/WxPay/notify/',
                'mp_app_id' => '',
                'mini_app_id' => '',
                'app_id' => '',
                'sub_mch_id' => '',
                'mode' => \Yansongda\Pay\Pay::MODE_NORMAL,
                'wechat_public_cert_path' => [
                    //'3221D557F8769B1FA581473FBEB95332E4BCB80B' => __DIR__ . '/Cert/wechatPublicKey.crt',
                ],
            ],
        ],
        'alipay' => [
            '2021003127616318' => [
                'app_id' => '2021003127616318',
                'app_secret_cert' => '',
                'app_public_cert_path' => '',
                'alipay_public_cert_path' => '',
                'alipay_root_cert_path' => '',
                'return_url' => env('APP_URL') . '/#/pages/index/productDetail/productDetail?goods_id=',
                'notify_url' => env('APP_URL') . '/AliPay/notify/',
                'mode' => \Yansongda\Pay\Pay::MODE_NORMAL,
                'aes_key' => '',
            ],
        ],
        'logger' => [
            'enable' => true,
            'file' => storage_path('logs/pay.log'),
            'level' => 'debug', // 建议生产环境等级调整为 info，开发环境为 debug
            'type' => 'single', // optional, 可选 daily.
            'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        ],
        'http' => [
            'timeout' => 5.0,
            'connect_timeout' => 5.0,
        ],
    ],

    'cash_out_alipay' => 1, // 支付宝提现（提现类型）
    'cash_out_wechat' => 2, // 微信提现（提现类型）

    'user_cash_rate' => env('USER_CASH_RATE', 2.5), // 提现手续费比例（%）
    'day_wallet_count' => env('DAY_WALLET_COUNT', 1), // 每天提现次数

    'invite_rebate_rate' => env('INVITE_REBATE_RATE', 30), // 邀请分佣比例（30%）
    'invite_cert_count' => env('INVITE_CERT_COUNT', 10), // 邀请注册实名人数（10个）
    'invite_rebate_expire' => env('INVITE_REBATE_EXPIRE', 30), // 邀请分佣期b限（30天）

);
