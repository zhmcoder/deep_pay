<?php

namespace Andruby\Pay\Models;

use Illuminate\Database\Eloquent\Model;

class WxPreorder extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['return_code', 'return_msg', 'appid', 'mch_id', 'device_info',
        'nonce_str', 'sign', 'result_code', 'err_code', 'err_code_des', 'data', 'trade_type',
        'prepay_id', 'out_trade_no', 'status', 'user_id', 'channel', 'app_id',
        'goods_id', 'sku_id', 'goods_type', 'pay_fee'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    protected $visible = [
        'id', 'return_code', 'return_msg', 'appid', 'mch_id', 'device_info',
        'nonce_str', 'sign', 'result_code', 'err_code', 'err_code_des', 'data', 'trade_type',
        'prepay_id', 'out_trade_no', 'status', 'user_id', 'channel',
        'goods_id', 'sku_id', 'goods_type', 'pay_fee'
    ];

    const STATUS = [
        1 => '待支付',
        2 => '支付成功',
        3 => '退款成功',
    ];

    const STATUS_WAITING_PAY = 1;
    const STATUS_PAYED = 2;
    const STATUS_REFUND = 3;

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'create_time';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'update_time';

    /**
     * 获取当前时间
     *
     * @return int
     */
    public function freshTimestamp()
    {
        return time();
    }

    /**
     * 避免转换时间戳为时间字符串
     *
     * @param DateTime|int $value
     * @return DateTime|int
     */
    public function fromDateTime($value)
    {
        return $value;
    }

}
