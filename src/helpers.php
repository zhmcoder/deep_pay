<?php

use Illuminate\Support\Facades\Log;

if (!function_exists('deep_pay_info')) {
    function deep_pay_info($message, array $context = array())
    {
        Log::channel('deep_pay_info')->info($message, $context);
    }
}
