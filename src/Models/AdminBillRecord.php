<?php

namespace Andruby\Pay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\AdminBillRecord
 *
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord newQuery()
 * @method static \Illuminate\Database\Query\Builder|AdminBillRecord onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord query()
 * @method static \Illuminate\Database\Query\Builder|AdminBillRecord withTrashed()
 * @method static \Illuminate\Database\Query\Builder|AdminBillRecord withoutTrashed()
 * @mixin \Eloquent
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
 * @property int|null $talent_id 达人id
 * @property int|null $order_id 订单id
 * @property string|null $out_trade_no 微信交易订单号
 * @property string|null $extra 扩展字段
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereActualAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereAddUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereAfterChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereBeforeChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereCashDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereChangeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereDelUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereEditUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereOutTradeNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereRecordStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereRecordType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereServiceCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereTalentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AdminBillRecord whereUserId($value)
 */
class AdminBillRecord extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = [];

    const BILL_RECORD_TYPE = [
        1 => '订单分佣',
        2 => '提现',
        3 => '充值',
        4 => '订单退款',
    ];

    const TYPE_ORDER_REBATE = 1;
    const TYPE_CASH_OUT = 2;
    const TYPE_RECHARGE = 3;
    const TYPE_REFUND = 4;

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

