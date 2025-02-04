<?php

namespace Andruby\Pay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Andruby\Pay\Models\BillRecord
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $add_user_id
 * @property int|null $edit_user_id
 * @property int|null $del_user_id
 * @property int|null $user_id 用户ID
 * @property int|null $change_amount 变更金额（分）
 * @property int|null $before_change 变更前金额（分）
 * @property int|null $after_change 变更后金额（分）
 * @property int|null $service_charge 手续费（分）
 * @property int|null $record_type 交易类型
 * @property int|null $record_status 账单状态
 * @property string|null $cash_day 提现日期
 * @property int|null $actual_amount 到账金额
 * @property int|null $cash_out_type 提现类型(1支付宝2微信)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord newQuery()
 * @method static \Illuminate\Database\Query\Builder|BillRecord onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereActualAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereAddUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereAfterChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereBeforeChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereCashDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereCashOutType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereChangeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereDelUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereEditUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereRecordStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereRecordType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereServiceCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BillRecord whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|BillRecord withTrashed()
 * @method static \Illuminate\Database\Query\Builder|BillRecord withoutTrashed()
 * @mixin \Eloquent
 */
class BillRecord extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = [];

    const BILL_RECORD_TYPE = [
        1 => '邀请分佣',
        2 => '提现',
        3 => '充值',
        4 => '报名支付',
    ];

    const TYPE_INVITE_REBATE = 1;
    const TYPE_CASH_OUT = 2;
    const TYPE_RECHARGE = 3;
    const TYPE_ORDER_PAY = 4;


    const CASH_STATUS = [
        1 => '审核中',
        2 => '提现中',
        3 => '提现成功',
        4 => '订单退款',
    ];

    const CASH_STATUS_REVIEWING = 1;
    const CASH_STATUS_ING = 2;
    const CASH_STATUS_SUCCESS = 3;
    const CASH_STATUS_REFUND = 4;
}
