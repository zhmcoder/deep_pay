<?php

namespace Andruby\Pay\Validates;

use App\Api\Validates\Validate;

class  PayValidate extends Validate
{
    public function weixin($request_data)
    {
        $rules = [
            'goods_id' => 'required|integer',
            'sku_id' => 'required|integer',
        ];
        $message = [
            'goods_id.required' => '商品标识不能为空',
            'goods_id.integer' => '商品标识不正确',
            'sku_id.required' => '子商品标识不能为空',
            'sku_id.integer' => '子商品标识不正确',
        ];
        return $this->validate($request_data, $rules, $message);
    }

    public function Qrcode($request_data)
    {
        $rules = [
            'target_url' => 'required|string',
        ];
        $message = [
            'target_url.required' => '地址不能为空',
            'target_url.string' => '地址不正确',
        ];
        return $this->validate($request_data, $rules, $message);
    }

    public function alipay($request_data)
    {
        $rules = [
            'goods_id' => 'required|integer',
        ];
        $message = [
            'goods_id.required' => '商品标识不能为空',
            'goods_id.integer' => '商品标识不正确',
        ];
        return $this->validate($request_data, $rules, $message);
    }
}
