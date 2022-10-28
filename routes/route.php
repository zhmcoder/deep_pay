<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'Api',
    'namespace' => 'Andruby\\Pay\\Controllers',
    // 'middleware' => 'App\\Api\\Middleware\\verifySign' // todo 签名
], function (Router $router) {
    // 微信支付
    $router->get('WxPay/pay', 'WxPayController@pay')->name('wx.pay');// 支付宝支付
    $router->post('AliPay/pay', 'AliPayController@pay')->name('AliPay.pay');
    $router->post('Order/refund', 'WxPayController@refund')->name('Order.refund');

    // 提现
    $router->post('Wallet/alipay_bind_qrcode', 'WalletController@alipay_bind_qrcode')->name('wallet.alipay_bind_qrcode');
    $router->post('Wallet/cash_info', 'WalletController@cash_info')->name('wallet.cash_info');
    $router->post('Wallet/cash_out', 'WalletController@cash_out')->name('wallet.cash_out');
    $router->post('Wallet/bill_record', 'WalletController@bill_record')->name('wallet.bill_record');
});

// 无签名接口
Route::group([
    'prefix' => 'Api',
    'namespace' => 'Andruby\\Pay\\Controllers',
], function (Router $router) {
    // 微信&支付宝回调
    $router->post('WxPay/notify/{app_id}', 'WxPayController@notify')->name('wx_pay.notify');
    $router->post('AliPay/notify/{app_id}', 'AliPayController@notify')->name('ali_pay.notify');
    $router->get('AliPay/aliInfo', 'AliPayController@aliInfo')->name('ali_pay.aliInfo');
});

// 后台
Route::group([
    'domain' => config('deep_admin.route.domain'),
    'prefix' => config('deep_admin.route.api_prefix'),
    'namespace' => '\Andruby\Pay\Admin'
], function (Router $router) {
    $router->resource('wx_order/list', 'WxOrderController')->names('wx_order.list');
    $router->get('wx_order/export', 'WxOrderController@export')->name('wx_order.export');
    $router->resource('ali_order/list', 'AliOrderController')->names('ali_order.list');
});
