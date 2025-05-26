<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Order;
use App\Models\RestaurantCharge;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Style};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\{FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles};

class SalesReportExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected string $startDate, $endDate, $currency;
    protected array $charges, $taxes;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = Carbon::createFromFormat('m/d/Y', $startDate)->startOfDay()->toDateTimeString();
        $this->endDate = Carbon::createFromFormat('m/d/Y', $endDate)->endOfDay()->toDateTimeString();
        $this->currency = session('currency', '$'); // Default to '$' if session value is not set

        $this->charges = RestaurantCharge::pluck('charge_name')->toArray();
        $this->taxes = Tax::select('tax_name', 'tax_percent')->get()->toArray();
    }

    public function headings(): array
    {
        $taxHeadings = array_map(fn($tax) => "{$tax['tax_name']} ({$tax['tax_percent']}%)", $this->taxes);

        return [
            [__('menu.salesReport') . ' ' . $this->startDate . ' - ' . $this->endDate],
            array_merge(['Date', 'Total Orders'], $this->charges, $taxHeadings, ['Cash', 'UPI', 'Card', 'Razorpay', 'Stripe', 'Flutterwave', 'Delivery Fee', 'Discount', 'Tip' ,'Total Amount'])
        ];
    }

    public function map($item): array
    {
        $mappedItem = [
            $item['date'],
            $item['total_orders'],
        ];

        foreach ($this->charges as $charge) {
            $mappedItem[] = $this->currency . number_format($item[$charge] ?? 0, 2);
        }

        foreach ($this->taxes as $tax) {
            $mappedItem[] = $this->currency . number_format($item[$tax['tax_name']] ?? 0, 2);
        }

        $mappedItem[] = $this->currency . number_format($item['cash_amount'], 2);
        $mappedItem[] = $this->currency . number_format($item['upi_amount'], 2);
        $mappedItem[] = $this->currency . number_format($item['card_amount'], 2);
        $mappedItem[] = $this->currency . number_format($item['razorpay_amount'], 2);
        $mappedItem[] = $this->currency . number_format($item['stripe_amount'], 2);
        $mappedItem[] = $this->currency . number_format($item['flutterwave_amount'], 2);
        $mappedItem[] = $this->currency . number_format($item['delivery_fee'], 2);
        $mappedItem[] = $this->currency . number_format($item['discount_amount'], 2);
        $mappedItem[] = $this->currency . number_format($item['tip_amount'], 2);
        $mappedItem[] = $this->currency . number_format($item['total_amount'], 2);


        return $mappedItem;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f5f5f5']]],
        ];
    }

    public function collection()
    {
        $charges = RestaurantCharge::all()->keyBy('id');
        $taxes = Tax::all()->keyBy('id');

        $query = Order::join('payments', 'orders.id', '=', 'payments.order_id')
            ->whereBetween('orders.date_time', [$this->startDate, $this->endDate])
            ->where('orders.status', 'paid')
            ->select(
                DB::raw('DATE(orders.date_time) as date'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(payments.amount) as total_amount'),
                DB::raw('SUM(orders.tip_amount) as tip_amount'),
                DB::raw('SUM(orders.delivery_fee) as delivery_fee'),
                DB::raw('SUM(DISTINCT orders.discount_amount) as discount_amount'),
                DB::raw('SUM(CASE WHEN payments.payment_method = "cash" THEN payments.amount ELSE 0 END) as cash_amount'),
                DB::raw('SUM(CASE WHEN payments.payment_method = "card" THEN payments.amount ELSE 0 END) as card_amount'),
                DB::raw('SUM(CASE WHEN payments.payment_method = "upi" THEN payments.amount ELSE 0 END) as upi_amount'),
                DB::raw('SUM(CASE WHEN payments.payment_method = "razorpay" THEN payments.amount ELSE 0 END) as razorpay_amount'),
                DB::raw('SUM(CASE WHEN payments.payment_method = "stripe" THEN payments.amount ELSE 0 END) as stripe_amount'),
                DB::raw('SUM(CASE WHEN payments.payment_method = "flutterwave" THEN payments.amount ELSE 0 END) as flutterwave_amount'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data = $query->map(function ($item) use ($charges, $taxes) {
            $row = [
                'date' => $item->date,
                'total_orders' => $item->total_orders,
                'total_amount' => $item->total_amount ?? 0,
                'delivery_fee' => $item->delivery_fee ?? 0,
                'tip_amount' => $item->tip_amount ?? 0,
                'cash_amount' => $item->cash_amount ?? 0,
                'card_amount' => $item->card_amount ?? 0,
                'upi_amount' => $item->upi_amount ?? 0,
                'discount_amount' => $item->discount_amount ?? 0,
                'razorpay_amount' => $item->razorpay_amount ?? 0,
                'stripe_amount' => $item->stripe_amount ?? 0,
                'flutterwave_amount' => $item->flutterwave_amount ?? 0,
            ];

            foreach ($charges as $charge) {
                $row[$charge->charge_name] = DB::table('order_charges')
                    ->join('orders', 'order_charges.order_id', '=', 'orders.id')
                    ->where('order_charges.charge_id', $charge->id)
                    ->where('orders.status', 'paid')
                    ->whereDate('orders.date_time', $item->date)
                    ->where('orders.branch_id', branch()->id)
                    ->join('restaurant_charges', 'order_charges.charge_id', '=', 'restaurant_charges.id')
                    ->sum(DB::raw('IF(restaurant_charges.charge_type = "percent", (restaurant_charges.charge_value / 100) * orders.sub_total, restaurant_charges.charge_value)')) ?? 0;
            }

            foreach ($taxes as $tax) {
                $row[$tax->tax_name] = DB::table('order_taxes')
                    ->join('orders', 'order_taxes.order_id', '=', 'orders.id')
                    ->join('taxes', 'order_taxes.tax_id', '=', 'taxes.id')
                    ->where('order_taxes.tax_id', $tax->id)
                    ->where('orders.status', 'paid')
                    ->whereDate('orders.date_time', $item->date)
                    ->where('orders.branch_id', branch()->id)
                    ->sum(DB::raw('(taxes.tax_percent / 100) * (orders.sub_total - COALESCE(orders.discount_amount, 0))')) ?? 0;
            }

            return collect($row);
        });

        return $data;
    }
}
