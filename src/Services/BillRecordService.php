<?php

namespace Andruby\Pay\Services;

use Andruby\Pay\Models\AdminBillRecord;
use App\Api\Traits\ApiResponseTraits;
use Andruby\Pay\Models\BillRecord;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static BillRecordService instance()
 *
 * Class BillRecordService
 * @package App\Api\Services
 */
class BillRecordService
{
    use ApiResponseTraits;

    public static function __callStatic($method, $params): BillRecordService
    {
        return new self();
    }

    // todo 充值账单
    public function rechargeRecord($user_id, $recharge_num, $out_trade_no)
    {
        $userInfo = Member::query()->where('uid', $user_id)->first();
        $data = [
            'user_id' => $user_id,
            'change_amount' => $recharge_num,
            'before_change' => $userInfo['amount'],
            'after_change' => $userInfo['amount'] + $recharge_num,
            'record_type' => BillRecord::TYPE_RECHARGE,
            'record_status' => BillRecord::CASH_STATUS_SUCCESS,
            'out_trade_no' => $out_trade_no
        ];
        return BillRecord::create($data);
    }

    /**
     * 余额提现账单
     * @param $user_id
     * @param $change_amount
     * @param $before_change
     * @param $after_change
     * @param $service_charge
     * @param $actual_amount
     * @param int $record_status
     * @param int $cash_out_type
     * @return BillRecord|Builder|Model
     */
    public function cashOutRecord($user_id, $change_amount, $before_change, $after_change, $service_charge, $actual_amount, $record_status = BillRecord::CASH_STATUS_ING, $cash_out_type = 2)
    {
        $cash_day = date('Y-m-d', time());
        $data = [
            'user_id' => $user_id,
            'change_amount' => $change_amount,
            'actual_amount' => $actual_amount,
            'before_change' => $before_change,
            'after_change' => $after_change,
            'service_charge' => $service_charge,
            'record_type' => BillRecord::TYPE_CASH_OUT,
            'record_status' => $record_status,
            'cash_day' => $cash_day,
            'cash_out_type' => $cash_out_type
        ];
        return BillRecord::query()->create($data);
    }

    // todo 支付账单
    public function payRecord($user_id, $change_amount, $business_id, $join_info_id)
    {
        $data = [
            'user_id' => $user_id,
            'change_amount' => $change_amount,
            'record_type' => BillRecord::TYPE_ORDER_PAY,
            'record_status' => BillRecord::CASH_STATUS_SUCCESS,
            'business_id' => $business_id,
        ];
        return BillRecord::query()->create($data);
    }

    public function inviteRebate($user_id, $change_amount, $before_change, $after_change, $actual_amount, $extra)
    {
        $data = [
            'user_id' => $user_id,
            'change_amount' => $change_amount,
            'before_change' => $before_change,
            'after_change' => $after_change,
            'actual_amount' => $actual_amount,
            'record_type' => BillRecord::TYPE_INVITE_REBATE,
            'record_status' => BillRecord::CASH_STATUS_SUCCESS,
            'extra' => json_encode($extra),
        ];
        return BillRecord::query()->create($data);
    }

    /**
     * 是否存在提现中账单
     * @param $user_id
     * @return float|int
     */
    public function cashing_amount($user_id)
    {
        $cash_out = BillRecord::query()->where('user_id', $user_id)
            ->where('record_status', '<>', '3')
            ->where('record_type', 2)->value('change_amount');
        if (empty($cash_out)) {
            $cash_out = 0;
        } else {
            $cash_out = round($cash_out / 100, 2);
        }
        return $cash_out;
    }

    // 订单分佣账单
    public function rebateRecord($user_id, $before_change, $after_change, $change_amount, $talent_id, $order_id)
    {
        $data = [
            'user_id' => $user_id,
            'before_change' => $before_change,
            'after_change' => $after_change,
            'change_amount' => $change_amount,
            'record_type' => AdminBillRecord::TYPE_ORDER_REBATE,
            'record_status' => AdminBillRecord::CASH_STATUS_SUCCESS,
            'order_id' => $order_id,
        ];
        return BillRecord::query()->create($data);
    }

    // 订单退款
    public function refundRecord($user_id, $before_change, $after_change, $change_amount, $talent_id, $order_id)
    {
        $data = [
            'user_id' => $user_id,
            'before_change' => $before_change,
            'after_change' => $after_change,
            'change_amount' => $change_amount,
            'record_type' => AdminBillRecord::TYPE_REFUND,
            'record_status' => AdminBillRecord::CASH_STATUS_SUCCESS,
            'order_id' => $order_id,
        ];
        return AdminBillRecord::query()->create($data);
    }
}
