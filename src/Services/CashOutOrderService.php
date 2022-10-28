<?php

namespace Andruby\Pay\Services;

use Andruby\Pay\Models\CashOutOrder;
use App\Api\Traits\ApiResponseTraits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static CashOutOrderService instance()
 *
 * Class CashOutOrderService
 * @package Andruby\Pay\Services
 */
class CashOutOrderService
{
    use ApiResponseTraits;

    /**
     * Magic static call.
     *
     * @param string $method
     * @param array $params
     */
    public static function __callStatic($method, $params): CashOutOrderService
    {
        return new self();
    }

    /**
     * 提现订单
     * @param $userId
     * @param $out_trade_no
     * @param $payment_no
     * @param $payment_time
     * @param $status
     * @param $order_type
     * @param $price
     * @param $json_data
     * @return Builder|Model
     */
    public function save_data($userId, $out_trade_no, $payment_no, $payment_time, $status, $order_type, $price, $json_data)
    {
        $data['user_id'] = $userId;
        $data['out_trade_no'] = $out_trade_no;
        $data['payment_no'] = $payment_no;
        $data['payment_time'] = $payment_time;
        $data['status'] = $status;
        $data['price'] = $price;
        $data['json_data'] = $json_data;
        $data['order_type'] = $order_type;

        return CashOutOrder::query()->create($data);
    }
}
