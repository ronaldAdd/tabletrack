<?php

namespace App\Livewire\Pos;

use App\Models\Kot;
use App\Models\Tax;
use App\Models\User;
use App\Models\Order;
use App\Models\Table;
use App\Models\KotItem;
use Livewire\Component;
use App\Models\MenuItem;
use App\Models\OrderTax;
use App\Models\OrderItem;
use App\Models\OrderCharge;
use App\Scopes\BranchScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use App\Models\ItemCategory;
use App\Models\ModifierOption;
use App\Events\NewOrderCreated;
use App\Models\RestaurantCharge;
use App\Models\DeliveryExecutive;
use App\Models\MenuItemVariation;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class Pos extends Component
{
    use LivewireAlert;

    protected $listeners = ['refreshPos' => '$refresh'];


    public $categoryList;
    public $search;
    public $filterCategories;
    public $menuItem;
    public $subTotal;
    public $total;
    public $orderNumber;
    public $kotNumber;
    public $tableNo;
    public $tableId;
    public $users;
    public $noOfPax = 1;
    public $selectWaiter;
    public $taxes;
    public $orderNote;
    public $tableOrder;
    public $tableOrderID;
    public $orderType;
    public $kotList = [];
    public $showVariationModal = false;
    public $showKotNote = false;
    public $showTableModal = false;
    public $showErrorModal = true;
    public $showNewKotButton = false;
    public $orderDetail = false;
    public $orderItemList = [];
    public $orderItemVariation = [];
    public $orderItemQty = [];
    public $orderItemAmount = [];
    public $deliveryExecutives;
    public $selectDeliveryExecutive;
    public $orderID;
    public $discountType;
    public $discountValue;
    public $discountAmount;
    public $restaurantSetting;
    public $showDiscountModal = false;
    public $selectedModifierItem;
    public $modifiers;
    public $showModifiersModal = false;
    public $itemModifiersSelected = [];
    public $orderItemModifiersPrice = [];
    public $extraCharges;
    public $discountedTotal;
    public $tipAmount = 0;
    public $orderStatus;
    public $deliveryFee = 0;

    public function mount()
    {
        $this->total = 0;
        $this->subTotal = 0;
        $this->categoryList = ItemCategory::all();
        $this->users = User::withoutGlobalScope(BranchScope::class)
            ->where(function($q) {
                return $q->where('branch_id', branch()->id)
                    ->orWhereNull('branch_id');
            })
            ->where('restaurant_id', restaurant()->id)
            ->get();
        $this->taxes = Tax::all();
        $this->orderNumber = Order::generateOrderNumber(branch());
        $this->selectWaiter = user()->id;
        $this->orderType = 'dine_in';
        $this->deliveryExecutives = DeliveryExecutive::where('status', 'available')->get();
        if ($this->tableOrderID) {
            $this->tableId = $this->tableOrderID;
            $this->tableOrder = Table::find($this->tableOrderID);
            $this->tableNo = $this->tableOrder->table_code;
            $this->orderID = $this->tableOrder->activeOrder ? $this->tableOrder->activeOrder->id : null;

            if ($this->tableOrder->activeOrder) {

                $this->orderNumber = $this->tableOrder->activeOrder->order_number;
                $this->tipAmount = $this->tableOrder->activeOrder->tip_amount;
                $this->deliveryFee = $this->tableOrder->activeOrder->delivery_fee;
                $this->showTableOrder();

                if ($this->orderDetail) {
                    $this->showOrderDetail();
                }

            } elseif ($this->orderDetail) {
                return $this->redirect(route('pos.index'), navigate: true);
            }
        }

        if ($this->orderID) {
            $order = Order::find($this->orderID);
            if ($order->status === 'canceled') {
                return $this->redirect(route('pos.index'), navigate: true);
            }
            $this->orderNumber = $order->order_number;
            $this->noOfPax = $order->number_of_pax;
            $this->selectWaiter = $order->waiter_id ?? null;
            $this->tableNo = $order->table->table_code ?? null;
            $this->tableId = $order->table->id ?? null;
            $this->discountAmount = $order->discount_amount;
            $this->discountValue = $order->discount_type === 'percent' ? rtrim(rtrim($order->discount_value, '0'), '.') : $order->discount_value;
            $this->discountType = $order->discount_type;
            $this->tipAmount = $order->tip_amount;
            $this->deliveryFee = $order->delivery_fee;
            $this->orderStatus = $order->order_status;

            if ($this->orderDetail) {
                $this->orderDetail = $order;
                $this->orderType = $this->orderDetail->order_type;

                $this->selectDeliveryExecutive = $order->delivery_executive_id;
                $this->setupOrderItems();
            }
        }

        $this->UpdatedOrderType($this->orderType);

        if ($this->orderID) {
            $this->extraCharges = ($order->status === 'kot' && !$this->orderDetail) ? [] : $order->extraCharges;
        }
    }

    public function UpdatedOrderType($value)
    {

        $mainExtraCharges = RestaurantCharge::whereJsonContains('order_types', $value)
            ->where('is_enabled', true)
            ->get();

        // Handle new orders or table orders without active orders
        if ((!$this->orderID && !$this->tableOrderID) || ($this->tableOrderID && !$this->tableOrder->activeOrder)) {
            $this->extraCharges = $mainExtraCharges;
            $this->orderStatus = 'preparing';
            $this->calculateTotal();
            return;
        }

        $order = $this->tableOrderID ? $this->tableOrder->activeOrder : Order::find($this->orderID);

        // Early return if no valid order or order is paid
        if (!$order || $order->status === 'paid') {
            return;
        }

        // Keep existing charges if order type is unchanged, otherwise set new ones
        $this->extraCharges = $order->order_type === $value
            ? $order->extraCharges
            : $mainExtraCharges;

        $this->orderStatus = $order->order_status;
        $this->calculateTotal();
    }

    public function updatedOrderStatus($value)
    {
        if ((!$this->orderID && !$this->tableOrderID) || !$this->orderDetail instanceof Order || is_null($value)) {

            return;
        }
        $this->orderDetail->update(['order_status' => $value]);

        if ($value->value === 'confirmed') {
            $this->orderDetail->kot->each(function ($kot) {
                $kot->update(['status' => 'in_kitchen']);
            });
        }
    }

    public function showTableOrder()
    {
        $this->selectWaiter = $this->tableOrder->activeOrder->waiter_id;
        $this->noOfPax = $this->tableOrder->activeOrder->number_of_pax;
    }

    public function showOrderDetail()
    {
        $this->orderDetail = $this->tableOrder->activeOrder;
        $this->orderType = $this->orderDetail->order_type;
        $this->setupOrderItems();
    }

    public function showPayment($id)
    {
        $order = Order::find($id);

        $this->dispatch('showPaymentModal', id: $order->id);
    }

    public function setupOrderItems()
    {
        if ($this->orderDetail) {
            foreach ($this->orderDetail->kot as $kot) {
                $this->kotList['kot_' . $kot->id] = $kot;

                foreach ($kot->items as $item) {
                    $this->orderItemList['"kot_' . $kot->id . '_' . $item->id . '"'] = $item->menuItem;
                    $this->orderItemQty['"kot_' . $kot->id . '_' . $item->id . '"'] = $item->quantity;
                    $this->orderItemModifiersPrice['"kot_' . $kot->id . '_' . $item->id . '"'] = $item->modifierOptions->sum('price');
                    $this->itemModifiersSelected['"kot_' . $kot->id . '_' . $item->id . '"'] = $item->modifierOptions->pluck('id')->toArray();
                    $basePrice = $item->menuItemVariation ? $item->menuItemVariation->price : $item->menuItem->price;
                    $this->orderItemAmount['"kot_' . $kot->id . '_' . $item->id . '"'] = $this->orderItemQty['"kot_' . $kot->id . '_' . $item->id . '"'] * ($basePrice + ($this->orderItemModifiersPrice['"kot_' . $kot->id . '_' . $item->id . '"'] ?? 0));

                    if ($item->menuItemVariation) {
                        $this->orderItemVariation['"kot_' . $kot->id . '_' . $item->id . '"'] = $item->menuItemVariation;
                    }

                }
            }
            $this->calculateTotal();
        }
    }

    public function addCartItems($id, $variationCount, $modifierCount)
    {
        if (!user_can('Create Order')) return;

        if ($this->orderID && $this->orderDetail && $this->orderDetail->status === 'kot') {
            $this->addError('error', __('messages.errorWantToCreateNewKot'));
            $this->showNewKotButton = true;
            $this->showErrorModal = true;
            return;
        }

        $this->dispatch('play_beep');
        $this->menuItem = MenuItem::find($id);

        if ($variationCount > 0) {
            $this->showVariationModal = true;
        } elseif ($modifierCount > 0) {
            $this->selectedModifierItem = $id;
            $this->showModifiersModal = true;
        } else {
            $this->syncCart($id);
        }

    }

    #[On('setTable')]
    public function setTable(Table $table)
    {
        if ($this->tableId) {
            Table::where('id', $this->tableId)->update([
                'available_status' => 'available'
            ]);
        }

        $this->tableNo = $table->table_code;
        $this->tableId = $table->id;

        if ($this->orderID) {
            Order::where('id', $this->orderID)->update(['table_id' => $table->id]);

            if ($this->orderDetail->date_time->format('d-m-Y') == now()->format('d-m-Y')) {
                Table::where('id', $this->tableId)->update([
                    'available_status' => 'running'
                ]);
            }

            $this->orderDetail->fresh();
        }

        $this->showTableModal = false;
    }

    #[On('setPosVariation')]
    public function setPosVariation($variationId)
    {
        $this->showVariationModal = false;
        $menuItemVariation = MenuItemVariation::find($variationId);
        $modifiersAvailable = $menuItemVariation->menuItem->modifiers->count();
        if ($modifiersAvailable) {
            $this->selectedModifierItem = $menuItemVariation->menu_item_id . '_' . $variationId;
            $this->showModifiersModal = true;
        } else {
            $this->orderItemVariation['"' . $menuItemVariation->menu_item_id . '_' . $variationId . '"'] = $menuItemVariation;
            $this->syncCart('"' . $menuItemVariation->menu_item_id . '_' . $variationId . '"');
        }
    }

    public function syncCart($id)
    {
        if (!isset($this->orderItemList[$id])) {
            $this->orderItemList[$id] = $this->menuItem;
            $this->orderItemQty[$id] = $this->orderItemQty[$id] ?? 1;
            $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
            $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
            $this->calculateTotal();
        } else {
            $this->addQty($id);
        }
    }

    public function deleteCartItems($id)
    {
        unset($this->orderItemList[$id]);
        unset($this->orderItemQty[$id]);
        unset($this->orderItemAmount[$id]);
        unset($this->orderItemVariation[$id]);
        unset($this->itemModifiersSelected[$id]);
        unset($this->orderItemModifiersPrice[$id]);
        $this->calculateTotal();
    }

    public function deleteOrderItems($id)
    {
        OrderItem::destroy($id);

        if ($this->orderDetail) {
            $this->total = 0;
            $this->subTotal = 0;

            foreach ($this->orderDetail->items as $value) {
                $this->subTotal = ($this->subTotal + $value->amount);
                $this->total = ($this->total + $value->amount);
            }

            foreach ($this->taxes as $value) {
                $this->total = ($this->total + (($value->tax_percent / 100) * $this->subTotal));
            }

            foreach ($this->extraCharges ?? [] as $value) {
                $this->total += $value->getAmount($this->subTotal);
            }


            Order::where('id', $this->orderDetail->id)->update([
                'sub_total' => $this->subTotal,
                'total' => $this->total
            ]);

        }

        $this->dispatch('refreshPos');
    }


    public function addQty($id)
    {
        if (!user_can('Update Order')) return;

        $this->orderItemQty[$id] = isset($this->orderItemQty[$id]) ? ($this->orderItemQty[$id] + 1) : 1;
        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
        $this->calculateTotal();
    }

    public function subQty($id)
    {
        if (!user_can('Update Order')) return;

        $this->orderItemQty[$id] = (isset($this->orderItemQty[$id]) && $this->orderItemQty[$id] > 1) ? ($this->orderItemQty[$id] - 1) : 1;
        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $this->total = 0;
        $this->subTotal = 0;

        if (is_array($this->orderItemAmount)) {
            foreach ($this->orderItemAmount as $key => $value) {
                $modifierTotal = 0;
                $this->subTotal += $value + $modifierTotal;
                $this->total += $value + $modifierTotal;
            }
        }

        $this->discountedTotal = $this->total;

        if ($this->discountValue > 0 && $this->discountType) {
            if ($this->discountType === 'percent') {
                $this->discountAmount = round(($this->subTotal * $this->discountValue) / 100, 2);
            } elseif ($this->discountType === 'fixed') {
                $this->discountAmount = min($this->discountValue, $this->subTotal);
            }

            $this->total -= $this->discountAmount;
        }

        $this->discountedTotal = $this->total;

        foreach ($this->taxes as $value) {
            $this->total += (($value->tax_percent / 100) * $this->discountedTotal);
        }

        if (!empty($this->orderItemAmount) && $this->extraCharges) {
            foreach ($this->extraCharges ?? [] as $charge) {
                $this->total += $charge->getAmount($this->discountedTotal);
            }
        }

        if ($this->tipAmount > 0) {
            $this->total += $this->tipAmount;
        }

        if ($this->deliveryFee > 0) {
            $this->total += $this->deliveryFee;
        }

    }

    public function addDiscounts()
    {
        $this->validate([
            'discountValue' => 'required|numeric|min:0',
            'discountType' => 'required|in:fixed,percent',
        ]);

        if ($this->discountType === 'percent' && $this->discountValue > 100) {
            $this->alert('error', __('messages.discountPercentError'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        $order = $this->tableOrderID ? $this->tableOrder->activeOrder : $this->orderDetail;

        if ($order) {
            $order->update([
                'discount_type' => $this->discountType,
                'discount_value' => $this->discountValue,
                'discount_amount' => $this->discountAmount,
                'total' => $this->total,
            ]);
        }

        $this->calculateTotal();

        $this->showDiscountModal = false;
    }

    public function removeCurrentDiscount()
    {
        $order = $this->tableOrderID ? $this->tableOrder->activeOrder : $this->orderDetail;

        if ($order) {
            $order->update([
                'discount_type' => null,
                'discount_value' => null,
                'discount_amount' => null,
            ]);
        }

        $this->discountType = null;
        $this->discountValue = null;
        $this->discountAmount = null;
        $this->calculateTotal();
    }

    public function removeExtraCharge($chargeId, $orderType)
    {
        $order = $this->tableOrderID ? $this->tableOrder->activeOrder : $this->orderDetail;

        if ($order) {
            $extraCharge = $this->extraCharges->firstWhere('id', $chargeId);
            if ($extraCharge) {
                $order->extraCharges()->detach($chargeId);
                $this->total -= $extraCharge->getAmount($this->discountedTotal);
                $order->update(['total' => $this->total]);
            }
        }

        $this->extraCharges = $this->extraCharges->filter(function ($charge) use ($chargeId) {
            return $charge->id != $chargeId;
        });

        $this->calculateTotal();
    }



    public function saveOrder($action, $secondAction = null)
    {
        $this->showErrorModal = true;

        $rules = [
            // 'noOfPax' => 'required_if:orderType,dine_in|numeric',
            // 'tableNo' => 'required_if:orderType,dine_in',
            'selectDeliveryExecutive' => 'required_if:orderType,delivery',
            'orderItemList' => 'required',
        ];

        if (!$this->orderID && !$this->tableOrderID) {
            $rules['selectWaiter'] = 'required_if:orderType,dine_in';
        }

        $messages = [
            'noOfPax.required_if' => __('messages.enterPax'),
            'tableNo.required_if' => __('messages.setTableNo'),
            'selectWaiter.required_if' => __('messages.selectWaiter'),
            'orderItemList.required' => __('messages.orderItemRequired'),
        ];

        $this->validate($rules, $messages);

        switch ($action) {
        case 'bill':
            $successMessage = __('messages.billedSuccess');
            $status = 'billed';
            $tableStatus = 'running';
            break;

        case 'kot':
            $successMessage = __('messages.kotGenerated');
            $status = 'kot';
            $tableStatus = 'running';
            break;


        case 'cancel':
            $successMessage = __('messages.orderCanceled');
            $status = 'canceled';
            $tableStatus = 'available';
            break;
        }


        if ((!$this->tableOrderID && !$this->orderID) || ($this->tableOrderID && !$this->tableOrder->activeOrder)) {
            $order = Order::create([
                'order_number' => $this->orderNumber,
                'date_time' => now(),
                'table_id' => $this->tableId,
                'number_of_pax' => $this->noOfPax,
                'discount_type' => $this->discountType,
                'discount_value' => $this->discountValue,
                'discount_amount' => $this->discountAmount,
                'waiter_id' => $this->selectWaiter,
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'order_type' => $this->orderType,
                'delivery_executive_id' => ($this->orderType == 'delivery' ? $this->selectDeliveryExecutive : null),
                'status' => $status,
                'order_status' => $this->orderStatus ?? 'preparing',
            ]);

            if (!empty($this->extraCharges)) {
                $chargesData = collect($this->extraCharges)
                    ->map(fn($charge) => [
                        'charge_id' => $charge->id,
                    ])->toArray();

                $order->charges()->createMany($chargesData);
            }
        } else {

            if ($this->orderID) {
                $this->orderDetail = Order::find($this->orderID);
            }

            $order = ($this->tableOrderID ? $this->tableOrder->activeOrder : $this->orderDetail);

            Order::where('id', $order->id)->update([
                'date_time' => now(),
                'order_type' => $this->orderType,
                'number_of_pax' => $this->noOfPax,
                'waiter_id' => $this->selectWaiter,
                'table_id' => $this->tableId ?? $order->table_id,
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'status' => $status,
                'order_status' => $this->orderStatus ?? 'preparing'
            ]);

            $order->items()->delete();
            $order->taxes()->delete();
        }

        if ($status == 'canceled') {
            $order->delete();

            Table::where('id', $this->tableId)->update([
                'available_status' => $tableStatus
            ]);
            return $this->redirect(route('pos.index'), navigate: true);
        }

        if ($status == 'kot') {
            $kot = Kot::create([
                'kot_number' => (Kot::generateKotNumber($order->branch) + 1),
                'order_id' => $order->id,
                'note' => $this->orderNote
            ]);

            foreach ($this->orderItemList as $key => $value) {
                $kotItem = KotItem::create([
                    'kot_id' => $kot->id,
                    'menu_item_id' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menu_item_id : $this->orderItemList[$key]->id),
                    'menu_item_variation_id' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->id : null),
                    'quantity' => $this->orderItemQty[$key]
                ]);

                $this->itemModifiersSelected[$key] = $this->itemModifiersSelected[$key] ?? [];
                $kotItem->modifierOptions()->sync($this->itemModifiersSelected[$key]);
            }

            if($this->orderID){
                $this->total = 0;
                $this->subTotal = 0;

                foreach ($order->kot as $kot) {
                    foreach ($kot->items as $item) {
                        $menuItemPrice = $item->menuItem->price ?? 0;
                        $this->subTotal += $menuItemPrice * $item->quantity;
                        $this->total += $menuItemPrice * $item->quantity;
                    }
                }

                if ($order->discount_type === 'percent') {
                    $this->discountAmount = round(($this->subTotal * $order->discount_value) / 100, 2);
                } elseif ($order->discount_type === 'fixed') {
                    $this->discountAmount = min($order->discount_value, $this->subTotal);
                }

                $this->discountedTotal = $this->total - $this->discountAmount;

                foreach ($this->taxes as $value) {
                    $this->total = ($this->total + (($value->tax_percent / 100) * $this->discountedTotal));
                }

                foreach ($order->extraCharges ?? [] as $value) {
                    $this->total += $value->getAmount($this->discountedTotal);
                }

                if ($this->tipAmount > 0) {
                    $this->total += $this->tipAmount;
                }

                if ($this->deliveryFee > 0) {
                    $this->total += $this->deliveryFee;
                }

                $this->total -= $this->discountAmount;

                Order::where('id', $order->id)->update([
                    'sub_total' => $this->subTotal,
                    'total' => $this->total,
                    'discount_amount' => $this->discountAmount,
                ]);
            }
        }

        if ($status == 'billed') {

            foreach ($this->orderItemList as $key => $value) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menu_item_id : $this->orderItemList[$key]->id),
                    'menu_item_variation_id' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->id : null),
                    'quantity' => $this->orderItemQty[$key],
                    'price' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $value->price),
                    'amount' => $this->orderItemAmount[$key],
                ]);

                $this->itemModifiersSelected[$key] = $this->itemModifiersSelected[$key] ?? [];
                $orderItem->modifierOptions()->sync($this->itemModifiersSelected[$key]);
            }

            foreach ($this->taxes as $key => $value) {
                OrderTax::create([
                    'order_id' => $order->id,
                    'tax_id' => $value->id
                ]);
            }
            $order->load('charges');

            $validCharges = collect($this->extraCharges ?? [])
                ->filter(fn($charge) => in_array($this->orderType, $charge->order_types));

            $currentChargeIds = $order->charges->pluck('charge_id');
            $validChargeIds = $validCharges->pluck('id');

            // Remove invalid charges and add new valid charges
            $order->charges()->whereNotIn('charge_id', $validChargeIds)->delete();

            $validChargeIds->diff($currentChargeIds)->each(fn($chargeId) =>
                OrderCharge::create(['order_id' => $order->id, 'charge_id' => $chargeId])
            );

            $this->total = 0;
            $this->subTotal = 0;

            foreach ($order->load('items')->items as $value) {
                $this->subTotal = ($this->subTotal + $value->amount);
                $this->total = ($this->total + $value->amount);
            }

            $this->discountedTotal = $this->total;

            if ($order->discount_type === 'percent') {
                $this->discountAmount = round(($this->subTotal * $order->discount_value) / 100, 2);
            } elseif ($order->discount_type === 'fixed') {
                $this->discountAmount = min($order->discount_value, $this->subTotal);
            }

            $this->discountedTotal = $this->total - $this->discountAmount;

            foreach ($this->taxes as $value) {
                $this->total = ($this->total + (($value->tax_percent / 100) * $this->discountedTotal));
            }

            foreach ($this->extraCharges ?? [] as $value) {
                $this->total += $value->getAmount($this->discountedTotal);
            }

            if ($this->tipAmount > 0) {
                $this->total += $this->tipAmount;
            }

            if ($this->deliveryFee > 0) {
                $this->total += $this->deliveryFee;
            }

            $this->total -= $this->discountAmount;

            Order::where('id', $order->id)->update([
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'discount_amount' => $this->discountAmount,
            ]);

            NewOrderCreated::dispatch($order);

            $this->resetPos();

        }

        Table::where('id', $this->tableId)->update([
            'available_status' => $tableStatus
        ]);

        $this->dispatch('posOrderSuccess');

        $this->alert('success', $successMessage, [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        if ($status == 'kot') {
            if($secondAction == 'print'){
                $url = route('kot.print', $kot->id);
                $this->dispatch('print_location', $url);
            }
            if($this->orderID){
                return $this->redirect(route('pos.kot', $order->id) . '?showOrderDetail=true', navigate: true);
            }
            $this->dispatch('resetPos');
            // return $this->redirect(route('kots.index'), navigate: true);
        }

        if ($status == 'billed') {
            // return $this->redirect(route('orders.index'), navigate: true);
            switch ($secondAction) {
                case 'payment':
                    $this->dispatch('showPaymentModal', id: $order->id);
                    break;
                case 'print':
                    $url = route('orders.print', $order->id);
                    $this->dispatch('print_location', $url);
                    break;
                default:
                    $this->dispatch('showOrderDetail', id: $order->id, fromPos: true);
                    break;
            }
        }

    }

    #[On('resetPos')]
    public function resetPos()
    {   
        $this->search = null;
        $this->filterCategories = null;
        $this->menuItem = null;
        $this->subTotal = 0;
        $this->total = 0;
        $this->orderNumber = Order::latest()->first()->order_number + 1;
        $this->discountedTotal = 0;
        $this->tipAmount = 0;
        $this->deliveryFee = 0;
        $this->tableNo = null;
        $this->tableId = null;
        $this->noOfPax = null;
        $this->selectWaiter = user()->id;
        $this->orderItemList = [];
        $this->orderItemVariation = [];
        $this->orderItemQty = [];
        $this->orderItemAmount = [];
        $this->orderType = 'dine_in';
        $this->discountType = null;
        $this->discountValue = null;
        $this->showDiscountModal = false;
        $this->selectedModifierItem = null;
        $this->modifiers = null;
        $this->itemModifiersSelected = [];
        $this->discountAmount = null;
        $this->orderStatus;
        $this->showNewKotButton = false;
    }

    public function showAddDiscount()
    {
        $orderDetail = Order::find($this->orderID);
        $this->discountType = $orderDetail->discount_type ?? $this->discountType ?? 'fixed';
        $this->discountValue = $orderDetail->discount_value ?? $this->discountValue ?? null;
        $this->showDiscountModal = true;
    }


    #[On('closeModifiersModal')]
    public function closeModifiersModal()
    {
        $this->selectedModifierItem = null;
        $this->showModifiersModal = false;
    }

    #[On('setPosModifier')]
    public function setPosModifier($modifierIds)
    {
        $this->showModifiersModal = false;

        $sortNumber = Str::of(implode('', Arr::flatten($modifierIds)))
            ->split(1)->sort()->implode('');

        $keyId = $this->selectedModifierItem . '-' . $sortNumber;
        if (isset(explode('_', $this->selectedModifierItem)[1])) {
            $menuItemVariation = MenuItemVariation::find(explode('_', $this->selectedModifierItem)[1]);
            $this->orderItemVariation[$keyId] = $menuItemVariation;
            $this->selectedModifierItem = explode('_', $this->selectedModifierItem)[0];
            $this->orderItemAmount[$keyId] = 1 * ($this->orderItemVariation[$keyId]->price ?? $this->orderItemList[$keyId]->price);
        }

        $this->itemModifiersSelected[$keyId] = Arr::flatten($modifierIds);
        $this->orderItemQty[$this->selectedModifierItem] = isset($this->orderItemQty[$this->selectedModifierItem]) ? ($this->orderItemQty[$this->selectedModifierItem] + 1) : 1;

        $modifierTotal = collect($this->itemModifiersSelected[$keyId])
            ->sum(fn($modifierId) => $this->getModifierOptionsProperty()[$modifierId]->price);

            $this->orderItemModifiersPrice[$keyId] = (1 * (isset($this->itemModifiersSelected[$keyId]) ? $modifierTotal : 0));

        $this->syncCart($keyId);

    }

    public function getModifierOptionsProperty()
    {
        return ModifierOption::whereIn('id', collect($this->itemModifiersSelected)->flatten()->all())->get()->keyBy('id');
    }

    public function saveDeliveryExecutive()
    {
        $this->orderDetail->update(['delivery_executive_id' => $this->selectDeliveryExecutive]);
        $this->orderDetail->refresh();
        $this->alert('success', __('messages.deliveryExecutiveAssigned'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function cancelOrder()
    {
        if ($this->orderID) {
            $order = Order::find($this->orderID);

            if ($order) {
                $order->update([
                    'status' => 'canceled',
                    'order_status' => 'cancelled',
                ]);

                Table::where('id', $order->table_id)->update([
                    'available_status' => 'available',
                ]);

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

    public function updatedSelectWaiter($value)
    {
        if ($this->orderID) {
            $order = Order::find($this->orderID);

            if ($order) {
                $order->update(['waiter_id' => $value]);
                $this->alert('success', __('messages.waiterUpdated'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);
            }
        }
    }

    public function closeErrorModal()
    {
        $this->showErrorModal = false;
        $this->showNewKotButton = false;
    }

    public function render()
    {

        $query = MenuItem::withCount('variations', 'modifierGroups');

        if (!empty($this->filterCategories)) {
            $query = $query->where('item_category_id', $this->filterCategories);
        }

        $query = $query->search('item_name', $this->search)->get();

        return view('livewire.pos.pos', [
            'menuItems' => $query
        ]);
    }

}
