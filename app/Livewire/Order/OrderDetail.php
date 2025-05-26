<?php

namespace App\Livewire\Order;

use App\Models\Kot;
use App\Models\Tax;
use App\Models\Order;
use App\Models\Table;
use Livewire\Component;
use App\Models\OrderTax;
use App\Models\OrderItem;
use App\Models\OrderCharge;
use Livewire\Attributes\On;
use App\Models\DeliveryExecutive;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class OrderDetail extends Component
{

    use LivewireAlert;

    public $order;
    public $taxes;
    public $total = 0;
    public $subTotal = 0;
    public $showOrderDetail = false;
    public $showAddCustomerModal = false;
    public $showTableModal = false;
    public $cancelOrderModal = false;
    public $deleteOrderModal = false;
    public $tableNo;
    public $tableId;
    public $orderStatus;
    public $discountAmount = 0;
    public $deliveryExecutives;
    public $deliveryExecutive;
    public $orderProgressStatus;
    public $fromPos = null;

    public function mount()
    {
        $this->total = 0;
        $this->subTotal = 0;
        $this->taxes = Tax::all();
        $this->deliveryExecutives = DeliveryExecutive::where('status', 'available')->get();
        if ($this->order) {
            $this->deliveryExecutive = $this->order->delivery_executive_id;
        }
    }

    #[On('showOrderDetail')]
    public function showOrder($id, $fromPos = null)
    {
        $this->order = Order::with('items', 'items.menuItem', 'items.menuItemVariation')->find($id);
        $this->orderStatus = $this->order->status;
        $this->fromPos = $fromPos;
        $this->orderProgressStatus = $this->order->order_status->value;
        $this->showOrderDetail = true;
    }

    #[On('setTable')]
    public function setTable(Table $table)
    {
        $this->tableNo = $table->table_code;
        $this->tableId = $table->id;

        if ($this->order) {
            $currentOrder = Order::where('id', $this->order->id)->first();

            Table::where('id', $currentOrder->table_id)->update([
                'available_status' => 'available'
            ]);

            $currentOrder->update(['table_id' => $table->id]);

            if ($this->order->date_time->format('d-m-Y') == now()->format('d-m-Y')) {
                Table::where('id', $this->tableId)->update([
                    'available_status' => 'running'
                ]);
            }

            $this->order->fresh();
            $this->dispatch('showOrderDetail', id: $this->order->id);
        }

        $this->dispatch('posOrderSuccess');
        $this->dispatch('refreshOrders');
        $this->dispatch('refreshPos');

        $this->showTableModal = false;
    }

    public function saveOrderStatus()
    {
        if ($this->order) {
            Order::where('id', $this->order->id)->update(['status' => $this->orderStatus]);

            $this->dispatch('posOrderSuccess');
            $this->dispatch('refreshOrders');
            $this->dispatch('refreshPos');
        }
    }

    #[On('showAddCustomerModal')]
    public function showAddCustomer($id)
    {
        $this->order = Order::find($id);
        $this->showAddCustomerModal = true;
    }

    public function deleteOrderItems($id)
    {
        OrderItem::destroy($id);

        if ($this->order) {
            $this->total = 0;
            $this->subTotal = 0;

            foreach ($this->order->items as $value) {
                $this->subTotal = ($this->subTotal + $value->amount);
                $this->total = ($this->total + $value->amount);
            }

            foreach ($this->taxes as $value) {
                $this->total = ($this->total + (($value->tax_percent / 100) * $this->subTotal));
            }

            Order::where('id', $this->order->id)->update([
                'sub_total' => $this->subTotal,
                'total' => $this->total
            ]);

        }

        $this->dispatch('refreshPos');
    }

    public function updatedOrderProgressStatus($value)
    {
        if (empty($this->order) || is_null($value)) {
            return;
        }

        $this->order->update(['order_status' => $value]);
        $this->orderProgressStatus = $value;

        if ($value === 'confirmed') {
            $this->order->kot->each(function ($kot) {
                $kot->update(['status' => 'in_kitchen']);
            });
        }

        $this->dispatch('posOrderSuccess');
        $this->dispatch('refreshOrders');
        $this->dispatch('refreshPos');
    }

    public function saveOrder($action)
    {

        switch ($action) {
        case 'bill':
            $successMessage = __('messages.billedSuccess');
            $status = 'billed';
            $tableStatus = 'running';
            break;

        case 'kot':
            return $this->redirect(route('pos.show', $this->order->table_id), navigate: true);
        }

        $taxes = Tax::all();

        Order::where('id', $this->order->id)->update([
            'date_time' => now(),
            'status' => $status
        ]);

        if ($status == 'billed') {

            foreach ($this->order->kot as $kot) {
                foreach ($kot->items as $item) {
                    $price = (($item->menu_item_variation_id) ? $item->menuItemVariation->price : $item->menuItem->price);
                    OrderItem::create([
                        'order_id' => $this->order->id,
                        'menu_item_id' => $item->menu_item_id,
                        'menu_item_variation_id' => $item->menu_item_variation_id,
                        'quantity' => $item->quantity,
                        'price' => $price,
                        'amount' => ($price * $item->quantity),
                    ]);
                }
            }

            foreach ($taxes as $value) {
                OrderTax::create([
                    'order_id' => $this->order->id,
                    'tax_id' => $value->id
                ]);
            }

            $this->total = 0;
            $this->subTotal = 0;

            foreach ($this->order->load('items')->items as $value) {
                $this->subTotal = ($this->subTotal + $value->amount);
                $this->total = ($this->total + $value->amount);
            }

            foreach ($taxes as $value) {
                $this->total = ($this->total + (($value->tax_percent / 100) * $this->subTotal));
            }

            if ($this->order->discount_type === 'percent') {
                $this->discountAmount = round(($this->subTotal * $this->order->discount_value) / 100, 2);
            } elseif ($this->order->discount_type === 'fixed') {
                $this->discountAmount = min($this->order->discount_value, $this->subTotal);
            }

            $this->total -= $this->discountAmount;

            Order::where('id', $this->orderDetail->id)->update([
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'discount_amount' => $this->discountAmount,
            ]);
        }

        Table::where('id', $this->tableId)->update([
            'available_status' => $tableStatus
        ]);


        $this->alert('success', $successMessage, [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        if ($status == 'billed') {
            $this->dispatch('showOrderDetail', id: $this->order->id);
            $this->dispatch('posOrderSuccess');
            $this->dispatch('refreshOrders');
            $this->dispatch('resetPos');
        }

    }

    public function showPayment($id)
    {
        $this->dispatch('showPaymentModal', id: $id);
    }

    public function cancelOrderStatus($id)
    {
        if ($id) {
            $order = Order::find($id);

            if ($order) {
                $order->update([
                    'status' => 'canceled',
                    'order_status' => 'cancelled',
                ]);

                if ($order->table_id) {
                    Table::where('id', $order->table_id)->update([
                        'available_status' => 'available',
                    ]);
                }

                $this->alert('success', __('messages.orderCanceled'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);

                return $this->redirect(route('pos.index'), navigate: true);
            }
        }
    }

    public function cancelOrder($id)
    {
        $order = Order::find($id);

        if ($order) {
            $order->update([
                'status' => 'canceled',
            ]);
            $order->kot()->delete();
            $order->payments()->delete();

            if ($order->table_id) {
                Table::where('id', $order->table_id)->update([
                    'available_status' => 'available',
                ]);
            }
            $this->cancelOrderModal = false;
            $this->dispatch('showOrderDetail', id: $this->order->id);
            $this->dispatch('posOrderSuccess');
            $this->dispatch('refreshOrders');

            $this->alert('success', __('messages.orderCanceled'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);

            if ($this->fromPos) {
                return $this->redirect(route('pos.index'), navigate: true);
            } else {
                $this->dispatch('resetPos');
            }
        }
    }

    public function paymentReceived($orderId, $status)
    {
        $order = Order::with('payments')->find($orderId);

        if (!$order) {
            $this->alert('error', __('messages.orderNotFound'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        if ($status === "received") {
            $amountPaid = $order->payments->sum('amount');
            $order->update([
                'status' => 'paid',
                'amount_paid' => $amountPaid
            ]);
        } elseif ($status === "not_received") {
            $latestPayment = $order->payments->last();
            if ($latestPayment) {
                $latestPayment->delete();
            }
            $order->update(['status' => 'payment_due']);
        }

        $this->alert('success', __('messages.statusUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        $this->dispatch('showOrderDetail', id: $this->order->id);
        $this->dispatch('refreshOrders');
        $this->dispatch('refreshPos');
    }

    public function deleteOrder($id)
    {
        $order = Order::find($id);

        Order::destroy($id);

        $this->deleteOrderModal = false;
        $this->dispatch('refreshOrders');
        $this->dispatch('refreshPos');

        $this->dispatch('refreshKots');

        $this->showOrderDetail = false;

        $this->alert('success', __('messages.orderDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function saveDeliveryExecutive()
    {
        $this->order->update(['delivery_executive_id' => $this->deliveryExecutive]);
        $this->order->fresh();
        $this->alert('success', __('messages.deliveryExecutiveAssigned'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }


    public function removeCharge($chargeId)
    {
        $charge = OrderCharge::find($chargeId);

        if ($charge) {
            $chargeAmount = $charge->charge->getAmount($this->order->sub_total - ($this->order->discount_amount ?? 0));
            $charge->delete();
            $this->order->refresh();
            $this->total = $this->order->total - $chargeAmount;
            $this->order->update(['total' => $this->total]);
        }
    }

    public function render()
    {
        return view('livewire.order.order-detail');
    }

}
