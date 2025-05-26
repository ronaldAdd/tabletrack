<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\MenuItem;
use App\Scopes\AvailableMenuItemScope;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemReportExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
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
            [__('menu.itemReport') . ' ' . $this->startDate .' - ' . $this->endDate],
            [
                'Item Name',
                'Item Category Name',
                'Quantity Sold',
                'Selling Price',
                'Total Revenue',
            ]
        ];
    }
    public function map($item): array
    {
        $rows = [];

        // Check if the item has variations
        if ($item->variations->count() > 0) {
            foreach ($item->variations as $variation) {
                $quantitySold = $item->orders->where('menu_item_variation_id', $variation->id)->sum('quantity');
                $rows[] = [
                    $item->item_name . ' (' . $variation->variation . ')',
                    $item->category->category_name,
                    $quantitySold,
                    $variation->price,
                    $variation->price * $quantitySold,
                ];
            }
        } else {
            // If there are no variations, just use the item name and price
            $quantitySold = $item->orders->sum('quantity');
            $rows[] = [
                $item->item_name,
                $item->category->category_name,
                $quantitySold,
                $item->price,
                $item->price * $quantitySold,
            ];
        }

        return $rows;
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
        return MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->with(['orders' => function ($q) {
            return $q->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereDate('orders.date_time', '>=', $this->startDate)
                ->whereDate('orders.date_time', '<=', $this->endDate)
                ->where('orders.status', 'paid');
        }, 'category', 'variations'])->get();
    }
}
