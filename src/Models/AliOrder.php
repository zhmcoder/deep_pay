<?php

namespace Andruby\Pay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Andruby\Pay\Models\AliOrder
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $add_user_id
 * @property int|null $edit_user_id
 * @property int|null $del_user_id
 * @property int|null $user_id 用户
 * @property string|null $app_id 应用
 * @property string|null $invoice_amount 支付金额
 * @property string|null $fund_bill_list 资金渠道
 * @property string|null $notify_type 回调类型
 * @property string|null $trade_status 交易状态
 * @property string|null $receipt_amount 实收金额
 * @property string|null $buyer_pay_amount 实付金额
 * @property string|null $appid 应用ID
 * @property string|null $seller_id 卖家id
 * @property string|null $gmt_payment 支付时间
 * @property string|null $notify_time 回调时间
 * @property string|null $out_trade_no 商户订单号
 * @property string|null $total_amount 订单金额
 * @property string|null $trade_no 支付宝交易号
 * @property string|null $buyer_logon_id 买家支付宝账号
 * @property string|null $attach 扩展数据包
 * @property string|null $trade_type 交易类型
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder newQuery()
 * @method static \Illuminate\Database\Query\Builder|AliOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereAddUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereAttach($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereBuyerLogonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereBuyerPayAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereDelUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereEditUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereFundBillList($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereGmtPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereInvoiceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereNotifyTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereNotifyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereOutTradeNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereReceiptAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereSellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereTradeNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereTradeStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereTradeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AliOrder whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|AliOrder withTrashed()
 * @method static \Illuminate\Database\Query\Builder|AliOrder withoutTrashed()
 * @mixin \Eloquent
 */
class AliOrder extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    const TRADE_TYPE_SCAN = 'SCAN'; // 扫码
    const TRADE_TYPE_WAP = 'WAP'; // h5
    const TRADE_TYPE = [
        self::TRADE_TYPE_SCAN => '扫码支付',
        self::TRADE_TYPE_WAP => 'H5支付',
    ];
}
