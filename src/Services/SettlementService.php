<?php

namespace Andruby\Pay\Services;

use App\Models\AdminUser;
use App\Models\Goods;

/**
 * @method static SettlementService instance()
 *
 * 结算分佣
 * Class SettlementService
 * @package Andruby\Pay\Services
 */
class SettlementService
{
    public static function __callStatic($method, $params): SettlementService
    {
        return new self();
    }

    public function order_rebate($orderId, $payFee, $orderItem)
    {
        // 供货团长
        $userId = Goods::query()->where(['id' => $orderItem['goods_id']])->value('add_user_id');

        //$rebateRate = config('deep_pay.order_rebate_rate'); // 分佣比例
        //$rebatePrice = $orderPrice * $rebateRate / 100; // 抽佣金额
        // $entryPrice = ($orderPrice - $rebatePrice) * 100;

        $rebatePrice = $orderItem['rebate_price'] * 100;
        $payFee = $payFee * 100 - $rebatePrice;

        // 供货团长入账
        $coin = AdminUser::query()->where(['id' => $userId])->value('coin');
        AdminUser::query()->where(['id' => $userId])->increment('coin', $payFee);
        // 账单
        AdminBillRecordService::instance()->rebateRecord($userId, $coin, $coin + $payFee, $payFee, $userId, $orderId);

        // 帮卖团长入账到分佣金额
        $helpSellId = $orderItem['help_sell_id'];
        if ($helpSellId) {
            $coin = AdminUser::query()->where(['id' => $helpSellId])->value('settlement_coin');
            AdminUser::query()->where(['id' => $helpSellId])->increment('settlement_coin', $rebatePrice);
            // 账单
            BillRecordService::instance()->rebateRecord($helpSellId, $coin, $coin + $rebatePrice, $rebatePrice, $helpSellId, $orderId);
        }

    }
}
