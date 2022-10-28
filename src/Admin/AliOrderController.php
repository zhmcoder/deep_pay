<?php

namespace Andruby\Pay\Admin;

use Andruby\DeepAdmin\Controllers\ContentController;
use Andruby\DeepAdmin\Grid;

// 支付宝账单
class AliOrderController extends ContentController
{
    protected function getTableName(): string
    {
        return 'ali_orders';
    }

    protected function grid_list(Grid $grid): Grid
    {
        return $grid;
    }
}

