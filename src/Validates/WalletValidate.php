<?php

namespace Andruby\Pay\Validates;

use App\Api\Validates\Validate;

class WalletValidate extends Validate
{
    public function recharge($request_data)
    {
        $rules = [
            'recharge_num' => 'required|int',
        ];
        $message = [
            'recharge_num.required' => '充值金额不能为空',
            'recharge_num.int' => '充值金额为数字',
        ];
        return $this->validate($request_data, $rules, $message);
    }

    public function cash_info($request_data)
    {
        $rules = [
            'cash_out' => 'required|int',
        ];
        $message = [
            'cash_out.required' => '提现金额不能为空',
            'cash_out.int' => '提现金额为数字',
        ];
        return $this->validate($request_data, $rules, $message);
    }

    public function cash_out($request_data)
    {
        $rules = [
            'cash_out' => 'required|int',
        ];
        $message = [
            'cash_out.required' => '提现金额不能为空',
            'cash_out.int' => '提现金额为数字',
        ];
        return $this->validate($request_data, $rules, $message);
    }

}
