<?php

namespace Andruby\Pay\Admin;

use Andruby\DeepAdmin\Actions\BaseAction;
use Andruby\DeepAdmin\Components\Attrs\SelectOption;
use Andruby\DeepAdmin\Components\Form\Select;
use Andruby\DeepAdmin\Controllers\ContentController;
use App\Admin\Services\GridCacheService;
use Andruby\Pay\Exports\WxOrderExport;
use App\Models\UcenterMember;
use Andruby\Pay\Models\WxOrder;
use Maatwebsite\Excel\Facades\Excel;
use Andruby\DeepAdmin\Components\Form\DatePicker;
use Andruby\DeepAdmin\Grid;

// 微信账单
class WxOrderController extends ContentController
{
    protected function getTableName(): string
    {
        return 'wx_orders';
    }

    protected function grid_list(Grid $grid): Grid
    {
        $grid->toolbars(function (Grid\Toolbars $toolbars) {
            $toolbars->addRight(Grid\Tools\ToolButton::make("导出")
                ->icon("el-icon-plus")
                ->requestMethod('get')
                ->isFilterFormData(true)
                ->handler(BaseAction::HANDLER_LINK)
                ->uri(route('wx_order.export'))
            );
        });

        $grid->filter(function ($filter) {
            $trade_type = [];
            foreach (WxOrder::TRADE_TYPE as $key => $value) {
                $trade_type[] = SelectOption::make($key, $value);
            }
            $filter->equal('trade_type', '支付方式')->component(
                Select::make()->filterable()->options($trade_type)->clearable()->style('width:120px;')
            );

            $filter->between('update_time', '订单时间')->time()->component(DatePicker::make()->type("daterange")->style('width:240px;'));
        });

        $grid->column('mobile', "手机号")->customValue(function ($row, $value) {
            return GridCacheService::instance()->get_cache_value(UcenterMember::class, 'ucenter_member_' . $row['user_id'], $row['user_id'], 'id', 'mobile');
        })->width(100);

        $grid->column('total_fee', '支付金额(元)')->customValue(function ($row) {
            return round($row['total_fee'] / 100, 2);
        })->width(100);

        $grid->column('trade_type', '支付方式')->customValue(function ($row, $value) {
            return WxOrder::TRADE_TYPE[$value];
        })->width(100);

        return $grid;
    }


    // 导出
    public function export()
    {
        $day = date('Y-m-d');

        $params = request('params');
        $params = json_decode($params, true);

        $where = [];
        if (!empty($params['user_id'])) {
            $where['user_id'] = $params['user_id'];
        }
        if (!empty($params['out_trade_no'])) {
            $where[] = ['out_trade_no', 'like', '%' . $params['out_trade_no'] . '%'];
        }
        if (!empty($params['app_id'])) {
            $where['app_id'] = $params['app_id'];
        }
        if (!empty($params['update_time'])) {
            $where[] = ['update_time', '>=', strtotime($params['update_time'][0] . ' 00:00:00')];
            $where[] = ['update_time', '<=', strtotime($params['update_time'][1] . ' 23:59:59')];
        } else {
            //默认三个月数据
            $where[] = ['update_time', '>=', time() - 3 * 30 * 24 * 60 * 60];
        }

        $file_name = '微信账单' . $day . '.xlsx';

        return Excel::download(new WxOrderExport($where), $file_name);
    }

}

