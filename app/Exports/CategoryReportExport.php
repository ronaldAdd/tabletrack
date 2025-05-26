<?php

namespace App\Exports;

use App\Models\ItemCategory;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoryReportExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        $this->startDate = Carbon::createFromFormat('m/d/Y', $this->startDate)->toDateString();
        $this->endDate = Carbon::createFromFormat('m/d/Y', $this->endDate)->toDateString();
    }

    public function headings(): array
    {
        return [
            [__('menu.categoryReport') . ' ' . $this->startDate .' - ' . $this->endDate],
            [
            'Item Category',
            'Quantity Sold',
            'Amount',
            ]
        ];
    }

    public function map($item): array
    {
        return [
            $item->category_name,
            $item->orders->sum('quantity'),
            $item->orders->sum(function($order) { return $order->quantity * $order->price; })
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return $defaultStyle
            ->getFont()
            ->setName('Arial');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill'  => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'f5f5f5'),
            ]],
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ItemCategory::with(['orders' => function ($q) {
            return $q->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereDate('orders.date_time', '>=', $this->startDate)->whereDate('orders.date_time', '<=', $this->endDate)
            ->where('orders.status', 'paid')
            ->select('order_items.*', 'orders.amount_paid');
        }])->get();
    }

}
