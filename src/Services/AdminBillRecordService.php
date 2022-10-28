<?php

namespace Andruby\Pay\Services;

use App\Api\Traits\ApiResponseTraits;
use Andruby\Pay\Models\AdminBillRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static AdminBillRecordService instance()
 *
 * Class AdminBillRecordService
 * @package App\Api\Services
 */
class AdminBillRecordService
{
    use ApiResponseTraits;

    public static function __callStatic($method, $params): AdminBillRecordService
    {
        return new self();
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
     * @return AdminBillRecord|Builder|Model
     */
    public function cashOutRecord($user_id, $change_amount, $before_change, $after_change, $service_charge, $actual_amount, $record_status = AdminBillRecord::CASH_STATUS_ING, $cash_out_type = 2)
    {
        $cash_day = date('Y-m-d', time());
        $data = [
            'user_id' => $user_id,
            'change_amount' => $change_amount,
            'actual_amount' => $actual_amount,
            'before_change' => $before_change,
            'after_change' => $after_change,
            'service_charge' => $service_charge,
            'record_type' => AdminBillRecord::TYPE_CASH_OUT,
            'record_status' => $record_status,
            'cash_day' => $cash_day,
            'cash_out_type' => $cash_out_type
        ];
        return AdminBillRecord::query()->create($data);
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
            'talent_id' => $talent_id,
            'order_id' => $order_id,
        ];
        return AdminBillRecord::query()->create($data);
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
            'talent_id' => $talent_id,
            'order_id' => $order_id,
        ];
        return AdminBillRecord::query()->create($data);
    }

    /**
     * 是否存在提现中账单
     * @param $user_id
     * @return float|int
     */
    public function cashing_amount($user_id)
    {
        $cash_out = AdminBillRecord::query()->where('user_id', $user_id)
            ->where('record_status', '<>', '3')
            ->where('record_type', 2)->value('change_amount');
        if (empty($cash_out)) {
            $cash_out = 0;
        } else {
            $cash_out = round($cash_out / 100, 2);
        }
        return $cash_out;
    }
}
