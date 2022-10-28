<?php

namespace Andruby\Pay\Exports;

use Andruby\Pay\Models\WxOrder;
use App\Admin\Services\GridCacheService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// 微信账单 导出
class WxOrderExport implements FromCollection, WithHeadings, WithColumnFormatting, ShouldAutoSize
{
    private $where;

    public function __construct($where = [])
    {
        $this->where = $where;
    }

    public function collection(): \Illuminate\Support\Collection
    {
        $fields = [
            'user_id',
            'out_trade_no',
            'total_fee',
            'app_id',
            'fee_type', // todo
            'update_time as time',
        ];

        $data = WxOrder::query()->where($this->where)->get($fields)->sortByDesc('id');

        foreach ($data as $key => &$value) {
            if ($value['app_id']) {
                $value['app_id'] = GridCacheService::instance()->app_name($value['app_id']);
            }

            $value['total_fee'] = round($value['total_fee'] / 100, 2);
            $value['fee_type'] = date('Y-m-d H:i:s', $value['time']);
        }

        return $data;
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_DATE_DATETIME,
        ];
    }

    public function headings(): array
    {
        return [
            '序号',
            '用户id',
            '订单号',
            '支付金额(元)',
            '应用',
            '支付完成时间'
        ];
    }
}
