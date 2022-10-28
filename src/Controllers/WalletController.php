<?php

namespace Andruby\Pay\Controllers;

use Andruby\Pay\Models\AdminBillRecord;
use Andruby\Pay\Services\AdminBillRecordService;
use Andruby\Pay\Services\AliService;
use Andruby\Pay\Services\BillRecordService;
use Andruby\Pay\Services\WechatService;
use Andruby\Pay\Validates\WalletValidate;
use Andruby\Pay\Models\BillRecord;
use App\Admin\Services\GridCacheService;
use App\Models\AdminUser;
use App\Models\Member;
use App\Models\UcenterMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yansongda\Pay\Exception\Exception;
use App\Api\Controllers\BaseController;

class WalletController extends BaseController
{
    // todo 充值
    public function recharge(Request $request, WalletValidate $validate)
    {
        $userInfo = $this->userInfo();
        $request_data = $request->only(['recharge_num']);
        $validate_result = $validate->recharge($request_data);
        if ($validate_result) {
            $recharge_num = $request->input('recharge_num');

            $product['id'] = -1;
            $product['name'] = '余额充值';
            $product['total'] = $recharge_num;
            $product['type'] = -1;

            $attach['user_id'] = $userInfo['id'];
            $attach['token'] = $request->input('token');

            $this->pre_order_mini($userInfo, $product, $attach);
        } else {
            $this->responseJson(self::CODE_ERROR_CODE, $validate->message);
        }
    }

    // 提现信息
    public function cash_info(Request $request, WalletValidate $validate)
    {
        $userInfo = $this->userInfo();

        $validate_result = $validate->cash_info($request->only(['cash_out']));
        if ($validate_result) {
            $cash_out = $request->input('cash_out');
            if ($request->has('cash_out_type')) {
                $cash_out_type = $request->input('cash_out_type', config('deep_pay.cash_out_wechat'));
            } else {
                $cash_out_type = config('deep_pay.cash_out_wechat');
            }

            $userInfo = AdminUser::query()->where('uid', $userInfo['id'])->first();

            if ($cash_out_type == config('deep_pay.cash_out_alipay')) {
                if (empty($userInfo['alipay_id'])) {
                    $this->responseJson(self::CODE_BIND_ALIPAY, '请先绑定支付宝');
                }
            }

            $cashing_num = AdminBillRecordService::instance()->cashing_amount($userInfo['uid']);
            if ($cashing_num > 0) {
                $this->responseJson(self::CODE_SHOW_MSG, '上次提现进行中，完成提现后才可再次提现');
            }

            if ($userInfo['coin'] >= $cash_out) {
                $service_fee = round(ceil($cash_out / 100 * config('deep_pay.user_cash_rate')) / 100, 2);
                $actual_cash = round($cash_out / 100 - $service_fee, 2);

                $data = [
                    'service_fee' => $service_fee,
                    'actual_cash' => $actual_cash,
                    'is_bind_alipay' => !empty($userInfo['alipay_id']) ? 1 : 0,
                ];

                $this->responseJson(self::CODE_SUCCESS_CODE, 'success', $data);
            } else {
                $this->responseJson(self::CODE_SHOW_MSG, '提现金额不能大于余额');
            }
        } else {
            $this->responseJson(self::CODE_ERROR_CODE, $validate->message);
        }
    }

    // 提现
    public function cash_out(Request $request, WalletValidate $validate)
    {
        $userInfo = $this->userInfo();

        $validate_result = $validate->cash_out($request->only(['cash_out']));
        if ($validate_result) {
            $cash_out = $request->input('cash_out');
            $cash_out_type = $request->input('cash_out_type', env('deep_pay.cash_out_wechat'));

            // todo 每天只能提现一次，提现金额大于2元
            if ($cash_out < 1000 && !env('ALI_PAY_DEV', false)) {
                $this->responseJson(self::CODE_SHOW_MSG, '提现金额最少10元');
            }

            $key = date('Y-m-d') . 'wallet_user_id:';
            $walletCount = Cache::get($key . $userInfo['id']);
            if ($walletCount >= config('deep_pay.day_wallet_count')) {
                $this->responseJson(self::CODE_SHOW_MSG, '每天只能提现' . config('deep_pay.day_wallet_count') . '次，请明天再来');
            }

            $cashing_num = AdminBillRecordService::instance()->cashing_amount($userInfo['id']);
            if ($cashing_num > 0) {
                $this->responseJson(self::CODE_SHOW_MSG, '上次提现进行中，完成提现后才可再次提现');
            }

            $userInfo = AdminUser::query()->where('uid', $userInfo['id'])->first();
            $userInfo['id'] = $userInfo['uid'];
            if ($userInfo['coin'] >= $cash_out) {
                $service_fee = ceil($cash_out * config('deep_pay.user_cash_rate') / 100);
                $actual_cash = round($cash_out - $service_fee, 2);

                DB::beginTransaction();
                try {
                    AdminUser::query()->where('uid', $userInfo['id'])->decrement('coin', $cash_out);
                    $adminBillRecord = AdminBillRecordService::instance()->cashOutRecord($userInfo['id'], $cash_out, $userInfo['coin'], $userInfo['coin'] - $cash_out, $service_fee, $actual_cash, AdminBillRecord::CASH_STATUS_SUCCESS, $cash_out_type);

                    $app_name = GridCacheService::instance()->app_name(request('app_id'));
                    $order_title = $app_name . ' 提现';
                    $out_biz_no = md5($request->input('appid') . $userInfo['id'] . time());

                    if ($cash_out_type == config('deep_pay.cash_out_alipay')) {
                        if (empty($userInfo['alipay_id'])) {
                            $this->responseJson(self::CODE_BIND_ALIPAY, '请先绑定支付宝');
                        }
                        try {
                            $transfer = AliService::transfer($userInfo['id'], $out_biz_no, $order_title, $actual_cash, $userInfo['alipay_id']);
                            ali_pay_log('user cash out result = ' . json_encode($transfer));
                        } catch (Exception $exception) {
                            error_log_info('user cash out error msg = ' . $exception->getMessage());
                            DB::rollBack();
                        }
                    } else {
                        try {
                            $openid = UcenterMember::query()->where('id', $userInfo['id'])->value('openid');
                            $transfer = WechatService::v3_transfer($openid, $out_biz_no, $order_title, $actual_cash);
                            wx_pay_log('user cash out result = ' . $transfer);
                        } catch (Exception $exception) {
                            error_log_info('user cash out error msg = ' . $exception->getMessage());
                            DB::rollBack();
                        }
                    }

                    if (isset($transfer) && $transfer) {
                        Cache::put($key . $userInfo['id'], $walletCount + 1, 60 * 60 * 24);
                        DB::commit();

                        $this->responseJson(self::CODE_SUCCESS_CODE, 'success');
                    } else {
                        DB::rollBack();

                        $this->responseJson(self::CODE_SHOW_MSG, '提现失败');
                    }
                } catch (\Exception $e) {
                    //输出错误日志
                    error_log_info('[user cash  error]', ['error' => $e->getMessage()]);

                    DB::rollBack();
                    $this->responseJson(self::CODE_SHOW_MSG, '提现失败');
                }

            } else {
                $this->responseJson(self::CODE_SHOW_MSG, '提现金额不能大于余额');
            }
        } else {
            $this->responseJson(self::CODE_ERROR_CODE, $validate->message);
        }
    }

    // todo 充值回调
    protected function _wx_pay_notify($user_id, $wxOrder, $preOrder, $attach)
    {
        debug_log_info('_wx_pay_notify attach = ' . json_encode($attach));
        BillRecordService::instance()->rechargeRecord($user_id, $preOrder['pay_fee'], $preOrder['out_trade_no']);
        Member::query()->where('id', $user_id)->increment('amount', $preOrder['pay_fee']);
        debug_log_info('_wx_pay_notify');
    }

    // 提现账单
    public function bill_record(Request $request)
    {
        $pageIndex = $request->input('page_index', 1);
        $pageSize = $request->input('page_size', 10);
        $recordType = $request->input('type', AdminBillRecord::TYPE_CASH_OUT);

        $userInfo = $this->userInfo();
        $userId = $userInfo['id'];

        $fields = ['change_amount', 'before_change', 'after_change', 'service_charge', 'record_type', 'cash_out_type', 'record_status', 'cash_day', 'actual_amount', 'updated_at', 'extra'];

        $where = [
            'user_id' => $userId,
            'record_type' => $recordType,
        ];

        $list = AdminBillRecord::query()->where($where)
            ->select($fields)->orderBy('id', 'desc')
            ->offset($pageSize * ($pageIndex - 1))->limit($pageSize)
            ->get()->toArray();

        foreach ($list as &$value) {
            $value['change_amount'] = round($value['change_amount'] / 100, 2);
            $value['before_change'] = round($value['before_change'] / 100, 2);
            $value['after_change'] = round($value['after_change'] / 100, 2);
            $value['service_charge'] = round($value['service_charge'] / 100, 2);
            $value['actual_amount'] = round($value['actual_amount'] / 100, 2);

            $value['record_time'] = date('Y-m-d H:i:s', strtotime($value['updated_at']));
            if ($value['record_type'] == AdminBillRecord::TYPE_RECHARGE) { // 充值
                $value['extra'] = json_decode($value['extra'], true);
                if (is_array($value['extra'])) {
                    $value['extra']['before_frozen'] = round($value['extra']['before_frozen'] / 100, 2);
                    $value['extra']['after_frozen'] = round($value['extra']['after_frozen'] / 100, 2);
                } else {
                    $value['extra'] = null;
                }
            } else if ($value['record_type'] == AdminBillRecord::TYPE_ORDER_REBATE) { // 订单分佣
                $value['extra'] = json_decode($value['extra'], true);
            } else {
                unset($value['extra']);
            }
            unset($value['updated_at']);
        }

        $data['pageIndex'] = $pageIndex;
        $data['pageSize'] = $pageSize;
        $data['items'] = $list;

        $this->responseJson(self::CODE_SUCCESS_CODE, 'success', $data);
    }

    // 提现绑定支付宝二维码
    public function alipay_bind_qrcode(Request $request)
    {
        $user_id = $this->userId();
        $token = $request->input('token');
        $appid = $request->input('app_id');

        $data['alipay_qrcode'] = AliService::alipay_bind_qrcode($user_id, $appid, $token);

        $this->responseJson(self::CODE_SUCCESS_CODE, 'success', $data);
    }
}

