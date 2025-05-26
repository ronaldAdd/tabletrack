<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\RestaurantCharge;
use App\Exports\SalesReportExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PaymentGatewayCredential;

class SalesReport extends Component
{
    public $dateRangeType = 'currentWeek';
    public $startDate;
    public $endDate;

    public function mount()
    {
        abort_unless(in_array('Report', restaurant_modules()), 403);
        abort_unless(user_can('Show Reports'), 403);
        $this->setDateRange();
    }

    public function setDateRange()
    {
        $ranges = [
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'lastWeek' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'last7Days' => [now()->subDays(7), now()->endOfDay()],
            'currentMonth' => [now()->startOfMonth(), now()->endOfDay()],
            'lastMonth' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'currentYear' => [now()->startOfYear(), now()->endOfDay()],
            'lastYear' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            'currentWeek' => [now()->startOfWeek(), now()->endOfWeek()],
        ];

        [$start, $end] = $ranges[$this->dateRangeType] ?? $ranges['currentWeek'];
        $this->startDate = $start->format('m/d/Y');
        $this->endDate = $end->format('m/d/Y');
    }

    #[On('setStartDate')]
    public function setStartDate($start)
    {
        $this->startDate = $start;
    }

    #[On('setEndDate')]
    public function setEndDate($end)
    {
        $this->endDate = $end;
    }

    public function exportReport()
    {
        if (!in_array('Export Report', restaurant_modules())) {
            $this->dispatch('showUpgradeLicense');
            return;
        }
        return Excel::download(new SalesReportExport($this->startDate, $this->endDate), 'sales-report-' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function render()
    {
        $start = Carbon::createFromFormat('m/d/Y', $this->startDate)->startOfDay()->toDateTimeString();
        $end = Carbon::createFromFormat('m/d/Y', $this->endDate)->endOfDay()->toDateTimeString();

        // Retrieve all taxes and charges
        $charges = RestaurantCharge::all();
        $taxes = Tax::all();

        // Get sales report with charges grouped
        $query = Order::join('payments', 'orders.id', '=', 'payments.order_id')
            ->whereBetween('orders.date_time', [$start, $end])
            ->where('orders.status', 'paid')
            ->select(
                DB::raw('DATE(orders.date_time) as date'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(payments.amount) as total_amount'),
                DB::raw('SUM(DISTINCT orders.discount_amount) as discount_amount'),
                DB::raw('SUM(orders.tip_amount) as tip_amount'),
                DB::raw('SUM(orders.delivery_fee) as delivery_fee'),
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
        // Process taxes and charges dynamically
        $groupedData = $query->map(function ($item) use ($charges, $taxes) {
            $chargeAmounts = [];
            foreach ($charges as $charge) {
                $chargeAmounts[$charge->charge_name] = DB::table('order_charges')
                    ->join('orders', 'order_charges.order_id', '=', 'orders.id')
                    ->join('restaurant_charges', 'order_charges.charge_id', '=', 'restaurant_charges.id')
                    ->where('order_charges.charge_id', $charge->id)
                    ->where('orders.status', 'paid')
                    ->whereDate('orders.date_time', $item->date)
                    ->where('orders.branch_id', branch()->id)
                    ->sum(DB::raw('CASE WHEN restaurant_charges.charge_type = "percent"
                THEN (restaurant_charges.charge_value / 100) * orders.sub_total
                ELSE restaurant_charges.charge_value END'));
            }
            $taxAmounts = [];
            foreach ($taxes as $tax) {
                $taxAmounts[$tax->tax_name] = DB::table('order_taxes')
                    ->join('orders', 'order_taxes.order_id', '=', 'orders.id')
                    ->join('taxes', 'order_taxes.tax_id', '=', 'taxes.id')
                    ->where('order_taxes.tax_id', $tax->id)
                    ->where('orders.status', 'paid')
                    ->where('orders.branch_id', branch()->id)
                    ->whereDate('orders.date_time', $item->date)
                    ->sum(DB::raw('(taxes.tax_percent / 100) * (orders.sub_total - COALESCE(orders.discount_amount, 0))'));
            }
            return [
                'date' => $item->date,
                'total_orders' => $item->total_orders,
                'total_amount' => $item->total_amount,
                'discount_amount' => $item->discount_amount, // Added discount_amount
                'tip_amount' => $item->tip_amount,
                'delivery_fee' => $item->delivery_fee,
                'cash_amount' => $item->cash_amount,
                'card_amount' => $item->card_amount,
                'upi_amount' => $item->upi_amount,
                'razorpay_amount' => $item->razorpay_amount,
                'stripe_amount' => $item->stripe_amount,
                'flutterwave_amount' => $item->flutterwave_amount,
                'charges' => $chargeAmounts,
                'taxes' => $taxAmounts,
            ];
        });

        $paymentGateway = PaymentGatewayCredential::select('stripe_status', 'razorpay_status', 'flutterwave_status')
            ->where('restaurant_id', restaurant()->id)
            ->first();

        return view('livewire.reports.sales-report', [
            'menuItems' => $groupedData,
            'charges' => $charges,
            'taxes' => $taxes,
            'paymentGateway' => $paymentGateway,
        ]);
    }
}
