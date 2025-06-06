<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\MenuItem;
use Livewire\Attributes\On;
use App\Exports\ItemReportExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Scopes\AvailableMenuItemScope;

class ItemReport extends Component
{

    public $dateRangeType;
    public $startDate;
    public $endDate;
    public $searchTerm;

    public function mount()
    {
        abort_if(!in_array('Report', restaurant_modules()), 403);
        abort_if((!user_can('Show Reports')), 403);

        $this->dateRangeType = 'currentWeek';
        $this->startDate = now()->startOfWeek()->format('m/d/Y');
        $this->endDate = now()->endOfWeek()->format('m/d/Y');
    }

    public function setDateRange()
    {
        switch ($this->dateRangeType) {
        case 'today':
            $this->startDate = now()->startOfDay()->format('m/d/Y');
            $this->endDate = now()->startOfDay()->format('m/d/Y');
            break;

        case 'lastWeek':
            $this->startDate = now()->subWeek()->startOfWeek()->format('m/d/Y');
            $this->endDate = now()->subWeek()->endOfWeek()->format('m/d/Y');
            break;

        case 'last7Days':
            $this->startDate = now()->subDays(7)->format('m/d/Y');
            $this->endDate = now()->startOfDay()->format('m/d/Y');
            break;

        case 'currentMonth':
            $this->startDate = now()->startOfMonth()->format('m/d/Y');
            $this->endDate = now()->startOfDay()->format('m/d/Y');
            break;

        case 'lastMonth':
            $this->startDate = now()->subMonth()->startOfMonth()->format('m/d/Y');
            $this->endDate = now()->subMonth()->endOfMonth()->format('m/d/Y');
            break;

        case 'currentYear':
            $this->startDate = now()->startOfYear()->format('m/d/Y');
            $this->endDate = now()->startOfDay()->format('m/d/Y');
            break;

        case 'lastYear':
            $this->startDate = now()->subYear()->startOfYear()->format('m/d/Y');
            $this->endDate = now()->subYear()->endOfYear()->format('m/d/Y');
            break;

        default:
            $this->startDate = now()->startOfWeek()->format('m/d/Y');
            $this->endDate = now()->endOfWeek()->format('m/d/Y');
            break;
        }

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
        }
        else {
            return Excel::download(new ItemReportExport($this->startDate, $this->endDate), 'item-report-' . now()->toDateTimeString() . '.xlsx');
        }
    }

    public function render()
    {
        $start = Carbon::createFromFormat('m/d/Y', $this->startDate)->startOfDay()->toDateTimeString();
        $end = Carbon::createFromFormat('m/d/Y', $this->endDate)->endOfDay()->toDateTimeString();

        $query = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)
            ->with(['orders' => function ($q) use ($start, $end) {
                return $q->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereDate('orders.date_time', '>=', $start)->whereDate('orders.date_time', '<=', $end)
                    ->where('orders.status', 'paid');
            }, 'category', 'variations']);

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('item_name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhereHas('category', function ($q) {
                        $q->where('category_name', 'like', '%' . $this->searchTerm . '%');
                    });
            });
        }

        $menuItems = $query->get();

        return view('livewire.reports.item-report', [
            'menuItems' => $menuItems
        ]);
    }

}
