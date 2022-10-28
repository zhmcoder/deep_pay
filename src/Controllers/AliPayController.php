<?php

namespace Andruby\Pay\Controllers;

use Andruby\ApiToken\ApiToken;
use Andruby\Pay\Services\PayService;
use Andruby\Pay\Services\AliService;
use Andruby\Pay\Validates\PayValidate;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yansongda\Pay\Pay;
use App\Api\Controllers\PayController;

class AliPayController extends PayController
{
    // 支付宝支付
    public function pay(Request $request, PayValidate $validate)
    {
        if ($validate->alipay($request->only(['goods_id', 'type']))) {

            $appId = $request->input('app_id');
            $source = $request->input('source', Member::SOURCE_WX);
            $amount = $request->input('amount', 1);

            $userInfo = $this->userInfo();

            $orderItem = $this->order_item($userInfo);
            $goodsSku = $this->goods_sku($orderItem, $amount);

            $order = $this->order($userInfo['id'], $orderItem, $goodsSku);
            if (!empty($order)) {
                $out_trade_no = $order['out_trade_no'];
            } else {
                $out_trade_no = PayService::out_trade_no($userInfo['id'], $goodsSku, time());
            }

            $attach = [
                'user_id' => $userInfo['id'],
                'app_id' => $appId,
                'goods_id' => $goodsSku['goods_id'],
                'sku_id' => $goodsSku['id'],
            ];
            if (env('ALI_PAY_DEV')) {
                $goodsSku['price'] = 0.01;
            }
            $goodsSku['pay_fee'] = $goodsSku['price'] * $amount;

            $pre_order = AliService::pre_oder_mini($goodsSku['name'], $goodsSku['pay_fee'], $out_trade_no, $attach, '', $source, $goodsSku);
            if (empty($order)) {
                $order = PayService::create_order($userInfo['id'], $out_trade_no, $goodsSku, 0, 0, Order::PAY_ALI_PAY, Order::STATUS_WAITING, OrderItem::RES_TYPE_PAY, $appId, $amount);
            } else {
                Order::query()->where(['out_trade_no' => $order['out_trade_no']])->update(['out_trade_no' => $out_trade_no]);
            }

            if ($source == Member::SOURCE_PC) {
                $pre_order['order_sn'] = $order['order_sn'];
                $this->responseJson(self::CODE_SUCCESS_CODE, 'success', $pre_order);
            }
            return $pre_order;
        } else {
            $this->responseJson(-1, $validate->message);
        }
    }

    // 支付宝回调
    public function notify($appId)
    {
        DB::beginTransaction();
        try {
            // 支付配置加载
            $config = AliService::ali_pay_config($appId);

            Pay::config($config);

            $result = Pay::alipay()->callback(null, ['_config' => env('ALI_APP_ID')]);
            ali_pay_log("ali notify data = " . json_encode($result));

            PayService::ali_callback($result);

            DB::commit();

            return Pay::alipay()->success();
        } catch (\Exception $e) {
            //输出错误日志
            error_log_info('[ali notify error]', ['error' => $e->getMessage()]);
            error_log_info('[ali notify error]', ['ali notify data' => request()->getContent()]);

            DB::rollBack();
            $this->responseJson(self::CODE_SHOW_MSG, '支付宝回调失败');
        }
    }

    // 支付宝账户信息
    public function aliInfo(Request $request, PayValidate $validate)
    {
        debug_log_info('params = ' . json_encode($request->all()));
        $app_id = $request->input('app_id');
        $token = $request->input('token');
        $auth_code = $request->input('auth_code');
        debug_log_info('token = ' . $token);
        $app_name = env('ADMIN_NAME');

        if ($app_id == env('ALI_APP_ID')) {
            $alipay_id = AliService::getAccessToken($auth_code);
            debug_log_info('alipay_id = ' . $alipay_id);
            if ($alipay_id) {
                $msg = $this->bind_user($token, $alipay_id, $app_name);
            } else {
                $msg = '获取支付宝信息失败，请在' . $app_name . '小程序中重新生成二维码扫描绑定～！';
            }
        } else {
            $msg = '支付绑定地址错误，请在' . $app_name . '小程序中重新生成二维码扫描绑定～！';
        }

        return view('bind_alipay', ['msg' => $msg]);
    }

    // 绑定支付宝信息
    private function bind_user($token, $alipay_id, $app_name)
    {
        $user_id = ApiToken::query()->where('api_token', $token)->value('user_id');

        if ($user_id) {
            Member::query()->where('uid', $user_id)->update(['alipay_id' => $alipay_id]);
            return '绑定成功，可在' . $app_name . '中进行提现～！';
        } else {
            return '支付宝绑定二维码失效，请在' . $app_name . '中重新生成二维码～！';
        }
    }
}
