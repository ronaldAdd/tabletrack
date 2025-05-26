<?php

namespace App\Livewire\Shop;

use App\Models\Kot;
use App\Models\Tax;
use App\Models\Area;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Table;
use Razorpay\Api\Api;
use App\Models\KotItem;
use App\Models\Payment;
use Livewire\Component;
use App\Models\Customer;
use App\Models\MenuItem;
use App\Models\OrderTax;
use App\Models\OrderItem;
use App\Models\OrderCharge;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use App\Models\ItemCategory;
use App\Models\StripePayment;
use App\Models\ModifierOption;
use App\Events\NewOrderCreated;
use App\Models\RazorpayPayment;
use Illuminate\Validation\Rule;
use App\Models\RestaurantCharge;
use App\Models\MenuItemVariation;
use App\Models\FlutterwavePayment;
use App\Events\SendNewOrderReceived;
use App\Notifications\SendOrderBill;
use Illuminate\Support\Facades\Http;
use App\Scopes\AvailableMenuItemScope;
use App\Models\PaymentGatewayCredential;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class Cart extends Component
{

    use LivewireAlert;

    public $search;
    public $tableID;
    public $filterCategories;
    public $kotList = [];
    public $showVariationModal = false;
    public $showCartVariationModal = false;
    public $showCustomerNameModal = false;
    public $showMenuModal = false;
    public $showPaymentModal = false;
    public $showMenu = true;
    public $showCart = false;
    public $orderItemList = [];
    public $orderItemVariation = [];
    public $orderItemQty = [];
    public $cartItemQty = [];
    public $orderItemAmount = [];
    public $orderItemModifiersPrice = [];
    public $menuItem;
    public $subTotal;
    public $total;
    public $taxes;
    public $customer;
    public $customerName;
    public $customerPhone;
    public $customerAddress;
    public $orderNumber;
    public $paymentGateway;
    public $paymentOrder;
    public $showVeg;
    public $razorpayStatus;
    public $stripeStatus;
    public $cartQty;
    public $restaurantHash;
    public $restaurant;
    public $shopBranch;
    public $orderType;
    public $payNow = false;
    public $offline_payment_status;
    public $menuId;
    public $orderID;
    public $order;
    public $table;
    public $tables;
    public $getTable;
    public $qrCodeImage;
    public $enableQrPayment;
    public $showQrCode = false;
    public $showPaymentDetail = false;
    public $showTableModal = false;
    public $canCreateOrder;
    public $orderBeingProcessed = false;
    public $showModifiersModal = false;
    public $itemModifiersSelected = [];
    public $selectedModifierItem;
    public $showItemDetailModal = false;
    public $selectedItem;
    public $extraCharges;
    public $orderNote;
    public $showItemVariationsModal = false;
    public $showDeliveryAddressModal = false;
    public $addressLat;
    public $addressLng;
    public $deliveryAddress;
    public $deliveryFee = null;
    public $maxPreparationTime;
    public $etaMin;
    public $etaMax;

    public function mount()
    {
        if ($this->tableID) {
            $this->table = Table::where('hash', $this->tableID)->firstOrFail();
            $restaurant = $this->table->branch->restaurant;

            $fetchActiveOrder = Order::where('table_id', $this->table->id)->where('status', 'kot')->whereDate('date_time', '=', now($restaurant->timezone)->toDateString())->first();

            if ($fetchActiveOrder) {
                $this->orderID = $fetchActiveOrder->id;
                $this->order = $fetchActiveOrder;
            }

            $this->restaurant = $restaurant;
            $this->restaurantHash = $restaurant->hash;
        }

        if (!$this->restaurant) {
            abort(404);
        }

        $this->paymentGateway = PaymentGatewayCredential::withoutGlobalScopes()->where('restaurant_id', $this->restaurant->id)->first();
        $this->taxes = Tax::withoutGlobalScopes()->where('restaurant_id', $this->restaurant->id)->get();
        $this->customer = customer();
        $this->orderNumber = Order::generateOrderNumber($this->shopBranch);

        $this->razorpayStatus = (bool)$this->paymentGateway->razorpay_status;
        $this->stripeStatus = (bool)$this->paymentGateway->stripe_status;
        $this->orderType = $this->restaurant->allow_dine_in_orders ? 'dine_in' : ($this->restaurant->allow_customer_delivery_orders ? 'delivery' : 'pickup');

        if (request()->has('current_order')) {
            $this->orderID = request()->get('current_order');
            $this->order = Order::find($this->orderID);
            if ($this->order->status == 'paid') {
                $this->redirect(module_enabled('Subdomain') ? url('/') : route('shop_restaurant', ['hash' => $this->order->branch->restaurant->hash]));
            }
        }

        // Fetch QR code image from database
        $this->qrCodeImage = $this->restaurant->qr_code_image;
        $this->UpdatedOrderType($this->orderType);
    }

    public function filterMenuItems($id)
    {
        $this->menuId = $id;
        $this->menuItems = true;
    }

    public function showItemVariations($id)
    {
        $this->showItemVariationsModal = true;
        $this->menuItem = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->findOrFail($id);
    }

    public function addCartItems($id, $variationCount , $modifierCount)
    {

        if (!$this->canCreateOrder) {
            $this->alert('error', __('messages.CartAddPermissionDenied'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

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

    public function subCartItems($id)
    {
        $this->menuItem = MenuItem::find($id);
        $this->showCartVariationModal = true;
    }

    public function subModifiers($id)
    {
        $this->menuItem = MenuItem::find($id);
        // $this->showModifiersModal = true;
    }

    public function syncCart($id)
    {
        if (!isset($this->orderItemList[$id])) {

            $this->orderItemList[$id] = $this->menuItem;
            $this->orderItemQty[$id] = $this->orderItemQty[$id] ?? 1;
            $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
            $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
            $this->cartItemQty[$id] = isset($this->cartItemQty[$this->menuItem->id]) ? ($this->cartItemQty[$this->menuItem->id] + 1) : 1;
            $this->calculateTotal();

        } else {
            $this->addQty($id);
        }
    }

    #[On('addQty')]
    public function addQty($id)
    {
        $this->showCartVariationModal = false;
        $this->orderItemQty[$id] = isset($this->orderItemQty[$id]) ? ($this->orderItemQty[$id] + 1) : 1;
        $this->cartItemQty[$id] = isset($this->cartItemQty[$id]) ? ($this->cartItemQty[$id] + 1) : 1;
        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
        $this->calculateTotal();
    }

    #[On('subQty')]
    public function subQty($id)
    {
        $this->showCartVariationModal = false;
        $this->orderItemQty[$id] = (isset($this->orderItemQty[$id]) && $this->orderItemQty[$id] > 1) ? ($this->orderItemQty[$id] - 1) : 0;
        $basePrice = $this->orderItemVariation[$id]->price ?? $this->orderItemList[$id]->price;
        $this->orderItemAmount[$id] = $this->orderItemQty[$id] * ($basePrice + ($this->orderItemModifiersPrice[$id] ?? 0));
        $menuID = explode('_', $id);

        if (isset($menuID[0])) {
            $menuID = str_replace('"', '', $menuID[0]);
        }

        $this->cartItemQty[$menuID] = isset($this->cartItemQty[$menuID]) ? ($this->cartItemQty[$menuID] - 1) : 0;

        if ($this->orderItemQty[$id] == 0) {
            unset($this->orderItemList[$id]);
            unset($this->orderItemVariation[$id]);
            unset($this->orderItemAmount[$id]);
            unset($this->orderItemQty[$id]);
        }

        if ($this->cartItemQty[$menuID] == 0) {
            unset($this->cartItemQty[$menuID]);
        }

        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $this->cartQty = 0;

        foreach($this->orderItemQty as $qty) {
            if ($qty > 0) {
                $this->cartQty++;
            }
        }

        if (is_array($this->orderItemAmount)) {
            foreach ($this->orderItemAmount as $key => $value) {
                $modifierTotal = 0;
                $this->subTotal += $value + $modifierTotal;
                $this->total += $value + $modifierTotal;
            }
        }

        $this->dispatch('updateCartCount', count: $this->cartQty);


        $this->total = 0;
        $this->subTotal = 0;

        foreach ($this->orderItemAmount as $value) {
            $this->subTotal = ($this->subTotal + $value);
            $this->total = ($this->total + $value);
        }

        foreach ($this->taxes as $value) {
            $this->total = ($this->total + (($value->tax_percent / 100) * $this->subTotal));
        }

        foreach ($this->extraCharges as $charge) {
            $this->total += $charge->getAmount($this->subTotal);
        }

        $this->total += (float)$this->deliveryFee ?: 0;
    }

    public function UpdatedOrderType($value)
    {
        $mainExtraCharges = RestaurantCharge::withoutGlobalScopes()
            ->whereJsonContains('order_types', $value)
            ->where('is_enabled', true)
            ->where('restaurant_id', $this->restaurant->id)
            ->get();

        // Early return for new orders
        if (!$this->orderID) {
            // Only clear delivery-related fields if the order type is not delivery
            if ($value !== 'delivery') {
                $this->addressLat = null;
                $this->addressLng = null;
                $this->deliveryAddress = null;
                $this->deliveryFee = null;
            }

            $this->calculateMaxPreparationTime();
            $this->extraCharges = $mainExtraCharges;
            $this->calculateTotal();
            return;
        }

        // Early return if no valid order or order is paid
        if (!$this->order || $this->order->status === 'paid') {
            return;
        }

        // Keep existing charges if order type is unchanged, otherwise set new ones
        $this->extraCharges = $this->order->order_type === $value
            ? $this->order->extraCharges
            : $mainExtraCharges;

        $this->calculateTotal();
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
            $this->orderItemVariation[$menuItemVariation->menu_item_id . '_' . $variationId] = $menuItemVariation;
            $this->cartItemQty[$menuItemVariation->menu_item_id] = isset($this->cartItemQty[$menuItemVariation->menu_item_id]) ? ($this->cartItemQty[$menuItemVariation->menu_item_id] + 1) : 1;
            $this->orderItemAmount[$menuItemVariation->menu_item_id . '_' . $variationId] = (1 * (isset($this->orderItemVariation[$menuItemVariation->menu_item_id . '_' . $variationId]) ? $this->orderItemVariation[$menuItemVariation->menu_item_id . '_' . $variationId]->price : $this->orderItemList[$menuItemVariation->menu_item_id . '_' . $variationId]->price));
            $this->syncCart($menuItemVariation->menu_item_id . '_' . $variationId);
        }
    }

    #[On('setCustomer')]
    public function setCustomer($customer)
    {
        $customer = Customer::find($customer['id']);
        $this->customer = $customer;
    }

    public function filterMenu($id = null)
    {
        $this->filterCategories = $id;
        $this->showMenuModal = false;
    }

    #[On('showCartItems')]
    public function showCartItems()
    {
        $this->showCart = true;
        $this->showMenu = false;
    }

    #[On('showMenuItems')]
    public function showMenuItems()
    {
        $this->showCart = false;
        $this->showMenu = true;
    }

    public function submitCustomerName()
    {
        $this->validate([
            'customerName' => 'required',
            'customerPhone' => [
            'required',
            'string',
            Rule::unique('customers', 'phone')->ignore($this->customer->id ?? null),
            ],
        ]);

        $this->customer->name = $this->customerName;
        $this->customer->phone = $this->customerPhone;
        $this->customer->delivery_address = $this->customerAddress;
        $this->customer->save();

        session(['customer' => $this->customer]);
        $this->dispatch('setCustomer', customer: $this->customer);

        $this->showCustomerNameModal = false;

        $this->placeOrder($this->payNow);
    }

    public function selectTableOrder($tableID=null)
    {
        if ($this->getTable) {
            $this->tableID = $tableID;
            $this->getTable = false;
            $this->showTableModal = false;
            $this->placeOrder($this->payNow);
        }
    }

    public function getShouldShowWaiterButtonMobileProperty()
    {
        
        $this->dispatch('refreshComponent');

        if (!$this->restaurant->is_waiter_request_enabled || !$this->restaurant->is_waiter_request_enabled_on_mobile) {
            return false;
        }

        $cameFromQR = request()->query('hash') === $this->restaurant->hash || request()->boolean('from_qr');

        if ($this->restaurant->is_waiter_request_enabled_open_by_qr && !$cameFromQR) {
            return false;
        }

        return true;
    }

    public function getAvailableTable()
    {
        $this->tables = Area::where('branch_id', $this->shopBranch->id)->with(['tables' => function ($query) {
            return $query->where('branch_id', $this->shopBranch->id)->where('status', 'active');
        }])->get();
    }

    public function placeOrder($pay = false, $updateOrder = null, $method = null)
    {
        if ($updateOrder) {
            $this->order = Order::find($updateOrder);

            Payment::create([
                'order_id' => $this->order->id,
                'branch_id' => $this->shopBranch->id,
                'payment_method' => $method,
                'amount' => $this->total,
            ]);

            Order::where('id', $this->order->id)->update([
                'status' => 'pending_verification',
            ]);
            $this->sendNotifications($this->order);

            $this->alert('success', __('messages.orderSaved'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);

            $this->redirect(route('order_success', [$this->order->id]));
            return;
        }

        if ($this->orderType == 'delivery') {
            $deliverySetting = $this->shopBranch->deliverySetting ?? null;
        }

        if ($this->customer && (is_null($this->customer->name) || ($this->orderType == 'delivery' && is_null($this->customerAddress)) && is_null($deliverySetting))) {
            $this->customerName = $this->customer->name;
            $this->customerAddress = $this->customer->delivery_address;
            $this->customerPhone = $this->customer->phone;
            $this->showCustomerNameModal = true;
            $this->payNow = $pay;
            return;
        }

        if ($this->customer && $this->orderType === 'delivery' && empty($this->addressLat) && empty($this->addressLng) && empty($this->deliveryAddress) && isset($deliverySetting)) {
            $this->customerAddress = $this->customer->delivery_address;
            $this->showDeliveryAddressModal = true;
            $this->payNow = $pay;
            return;
        }

        if ($this->orderType == 'dine_in' && $this->getTable) {
            $this->getAvailableTable();
            $this->payNow = $pay;
            $this->showTableModal = true;
            return;
        }

        if (!is_null($this->tableID)) {
            $table = Table::where('hash', $this->tableID)->firstOrFail();
        }

        if ($this->order && ($this->order->status == 'kot' || $this->order->status == 'draft')) {
            $order = $this->order;
            if (!is_null($this->tableID)) {
                $order->update(['table_id' => $table->id]);
            }

        } else {
            $order = Order::create([
                'order_number' => $this->orderNumber,
                'branch_id' => $this->shopBranch->id,
                'table_id' => $table->id ?? null,
                'date_time' => now(),
                'customer_id' => $this->customer->id ?? null,
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'order_type' => $this->orderType,
                'delivery_address' => $this->customerAddress,
                'status' => 'draft',
                'order_status' => $this->restaurant->auto_confirm_orders ? 'confirmed' : 'placed',
                'customer_lat' => $this->addressLat ?? null,
                'customer_lng' => $this->addressLng ?? null,
                'delivery_fee' => $this->deliveryFee ?? 0,
                'delivery_address' => $this->deliveryAddress ?? null,
                'is_within_radius' => true,
                'delivery_started_at' => null,
                'delivered_at' => null,
                'estimated_eta_min' => $this->etaMin ?? null,
                'estimated_eta_max' => $this->etaMax ?? null,
            ]);
        }

        if ($this->customer && $this->orderType === 'delivery' && !empty($this->deliveryAddress) && isset($deliverySetting)) {
            $this->customer->delivery_address = $this->deliveryAddress;
            $this->customer->save();

            session(['customer' => $this->customer]);
        }

        $transactionId = uniqid('TXN_', true) . '_' . random_int(100000, 999999);
        session(['transaction_id' => $transactionId]);

        $kot = Kot::create([
            'branch_id' => $this->shopBranch->id,
            'kot_number' => (Kot::generateKotNumber($this->shopBranch) + 1),
            'order_id' => $order->id,
            'note' => $this->orderNote,
            'transaction_id' => $transactionId
        ]);

        foreach ($this->orderItemList as $key => $value) {

            $kotItem = KotItem::create([
                'kot_id' => $kot->id,
                'menu_item_id' => $this->orderItemVariation[$key]->menu_item_id ?? $this->orderItemList[$key]->id,
                'menu_item_variation_id' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->id : null),
                'quantity' => $this->orderItemQty[$key],
                'transaction_id' => $transactionId
            ]);

            $this->itemModifiersSelected[$key] = $this->itemModifiersSelected[$key] ?? [];
            $kotItem->modifierOptions()->sync($this->itemModifiersSelected[$key]);
        }

        foreach ($this->orderItemList as $key => $value) {
            $orderItem = OrderItem::create([
                'branch_id' => $this->shopBranch->id,
                'order_id' => $order->id,
                'menu_item_id' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->menu_item_id : $this->orderItemList[$key]->id),
                'menu_item_variation_id' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->id : null),
                'quantity' => $this->orderItemQty[$key],
                'price' => (isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $value->price),
                'amount' => $this->orderItemAmount[$key],
                'transaction_id' => $transactionId
            ]);

            $this->itemModifiersSelected[$key] = $this->itemModifiersSelected[$key] ?? [];
                $orderItem->modifierOptions()->sync($this->itemModifiersSelected[$key]);
        }

        foreach ($this->taxes as $key => $value) {
            OrderTax::firstOrCreate([
                'order_id' => $order->id,
                'tax_id' => $value->id
            ]);
        }

        if ($this->orderID) {
            $order->extraCharges()->detach();
        }

        foreach ($this->extraCharges as $key => $value) {
            OrderCharge::create([
                'order_id' => $order->id,
                'charge_id' => $value->id
            ]);
        }

        $this->total = 0;
        $this->subTotal = 0;

        foreach ($order->load('items')->items as $value) {
            $this->subTotal = ($this->subTotal + $value->amount);
            $this->total = ($this->total + $value->amount);
        }

        foreach ($this->taxes as $value) {
            $this->total = ($this->total + (($value->tax_percent / 100) * $this->subTotal));
        }

        foreach ($this->extraCharges as $value) {
            $this->total += $value->getAmount($this->subTotal);
        }

        $this->total += (float)$this->deliveryFee ?: 0;

        $this->total += $order->tip_amount ?? 0;

        Order::where('id', $order->id)->update([
            'sub_total' => $this->subTotal,
            'total' => $this->total
        ]);

        if (!is_null($this->tableID)) {
            $table->available_status = 'running';
            $table->saveQuietly();
        }

        if ($pay) {
            $this->showPaymentModal = true;
            $this->paymentOrder = $order;

        } else {
            Order::where('id', $order->id)->update([
                'status' => 'kot'
            ]);

            $this->sendNotifications($order);

            $this->alert('success', __('messages.orderSaved'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);

            $this->redirect(route('order_success', [$order->id]), true);
        }

    }

    public function initiatePayment($id)
    {
        $total = round($this->total, 2);

        $payment = RazorpayPayment::create([
            'order_id' => $id,
            'amount' => $total
        ]);

        $orderData = [
            'amount' => ($total * 100),
            'currency' => $this->restaurant->currency->currency_code
        ];

        $apiKey = $this->restaurant->paymentGateways->razorpay_key;
        $secretKey = $this->restaurant->paymentGateways->razorpay_secret;

        $api  = new Api($apiKey, $secretKey);
        $razorpayOrder = $api->order->create($orderData);
        $payment->razorpay_order_id = $razorpayOrder->id;
        $payment->save();

        $this->dispatch('paymentInitiated', payment: $payment);
    }

    public function initiateStripePayment($id)
    {
        $payment = StripePayment::create([
            'order_id' => $id,
            'amount' => $this->total
        ]);

        $this->dispatch('stripePaymentInitiated', payment: $payment);
    }

    #[On('razorpayPaymentCompleted')]
    public function razorpayPaymentCompleted($razorpayPaymentID, $razorpayOrderID, $razorpaySignature)
    {
        $payment = RazorpayPayment::where('razorpay_order_id', $razorpayOrderID)
            ->where('payment_status', 'pending')
            ->first();

        if ($payment) {
            $payment->razorpay_payment_id = $razorpayPaymentID;
            $payment->payment_status = 'completed';
            $payment->payment_date = now()->toDateTimeString();
            $payment->razorpay_signature = $razorpaySignature;
            $payment->save();

            $order = Order::find($payment->order_id);
            $order->amount_paid = $this->total;
            $order->status = 'paid';
            $order->save();

            Payment::create([
                'order_id' => $payment->order_id,
                'branch_id' => $this->shopBranch->id,
                'payment_method' => 'razorpay',
                'amount' => $payment->amount,
                'transaction_id' => $razorpayPaymentID
            ]);

            $this->sendNotifications($order);

            $this->alert('success', __('messages.orderSaved'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);

            $this->redirect(route('order_success', $payment->order_id));
        }

    }

    public function initiateFlutterwavePayment($id)
    {
        try {
            $paymentGateway = $this->restaurant->paymentGateways;
            $apiSecret = $paymentGateway->flutterwave_secret;
            $amount = $this->total;
            $tx_ref = "txn_" . time();

            $user = $this->customer ?? $this->restaurant;


            $data = [
                "tx_ref" => $tx_ref,
                "amount" => $amount,
                "currency" => $this->restaurant->currency->currency_code,
                "redirect_url" => route('flutterwave.success'),
                "payment_options" => "card",
                "customer" => [
                    "email" => $user->email ?? 'no-email@example.com',
                    "name" => $user->name ?? 'Guest',
                    "phone_number" => $user->phone ?? '0000000000',
                ],
            ];
            $response = Http::withHeaders([
                "Authorization" => "Bearer $apiSecret",
                "Content-Type" => "application/json"
            ])->post("https://api.flutterwave.com/v3/payments", $data);

            $responseData = $response->json();

            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                FlutterwavePayment::create([
                    'order_id' => $id,
                    'flutterwave_payment_id' => $tx_ref,
                    'amount' => $amount,
                    'payment_status' => 'pending',
                ]);

                return redirect($responseData['data']['link']);
            } else {
                return redirect()->route('flutterwave.failed')->withErrors(['error' => 'Payment initiation failed', 'message' => $responseData]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function hidePaymentModal()
    {

        $this->showPaymentModal = false;
        Order::where('id', $this->paymentOrder->id)->where('status', 'draft')->delete();

        Kot::where('transaction_id', session('transaction_id'))->delete();
        KotItem::where('transaction_id', session('transaction_id'))->delete();
        OrderItem::where('transaction_id', session('transaction_id'))->delete();

        session()->forget('transaction_id');

        $this->paymentOrder = null;
    }

    public function sendNotifications($order)
    {
        NewOrderCreated::dispatch($order);

        SendNewOrderReceived::dispatch($order);
        if ($order->customer_id) {
            try {
                $order->customer->notify(new SendOrderBill($order));
            } catch (\Exception $e) {
                \Log::error('Error sending order bill email: ' . $e->getMessage());
            }
        }
    }

    public function toggleQrCode()
    {
        $this->showQrCode = !$this->showQrCode;
    }

    public function togglePaymenntDetail()
    {
        $this->showPaymentDetail = !$this->showPaymentDetail;
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

        $this->cartItemQty[$keyId] = ($this->cartItemQty[$keyId] ?? 0) + 1;
        $this->itemModifiersSelected[$keyId] = Arr::flatten($modifierIds);

        $modifierTotal = collect($this->itemModifiersSelected[$keyId])
            ->sum(fn($modifierId) => $this->getModifierOptionsProperty()[$modifierId]->price);

            $this->orderItemModifiersPrice[$keyId] = (1 * (isset($this->itemModifiersSelected[$keyId]) ? $modifierTotal : 0));

        $this->syncCart($keyId);
    }

    public function getModifierOptionsProperty()
    {
        return ModifierOption::whereIn('id', collect($this->itemModifiersSelected)->flatten()->all())->get()->keyBy('id');
    }

    public function showItemDetail($id)
    {
        $this->selectedItem = MenuItem::find($id);
        $this->showItemDetailModal = true;
    }

    #[On('selectedDeliveryDetails')]
    public function handleSelectedDeliveryDetails($details)
    {
        $this->addressLat = $details['lat'] ?? null;
        $this->addressLng = $details['lng'] ?? null;
        $this->deliveryAddress = $details['address'] ?? null;
        $this->deliveryFee = $details['deliveryFee'] ?? null;
        $this->etaMin = $details['eta_min'];
        $this->etaMax = $details['eta_max'];

        $this->calculateMaxPreparationTime();
        $this->calculateTotal();
        $this->showDeliveryAddressModal = false;
    }

    public function calculateMaxPreparationTime()
    {
        $this->maxPreparationTime = $this->orderItemList
            ? max(array_map(fn($item) => $item->preparation_time ?? 0, $this->orderItemList))
            : 0;
    }

    public function render()
    {
        $locale = session('locale', app()->getLocale());

        $query = MenuItem::withCount('variations', 'modifierGroups')->with('category')
            ->select('menu_items.*', 'item_categories.category_name')
            ->join('item_categories', 'menu_items.item_category_id', '=', 'item_categories.id')
            ->where('menu_items.branch_id', $this->shopBranch->id);

        if (!empty($this->filterCategories)) {
            $query = $query->where('menu_items.item_category_id', $this->filterCategories);
        }

        if (!empty($this->menuId)) {
            $query = $query->where('menu_items.menu_id', $this->menuId);
        }

        if ($this->showVeg == 1) {
            $query = $query->where('menu_items.type', 'veg');
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
            $q->where('item_name', 'like', '%' . $this->search . '%')
                ->orWhereHas('translations', function ($q) {
                $q->where('item_name', 'like', '%' . $this->search . '%');
                });
            });
        }

        $query = $query->orderBy('item_categories.category_name')
            ->withCount('variations')
            ->withCount('modifierGroups')
            ->get()
            ->groupBy(function ($item) use ($locale) {
                return $item->category->getTranslation('category_name', $locale);
            });


        $categoryList = ItemCategory::withoutGlobalScopes()->whereHas('items')->with(['items' => function ($q) {
            if (!empty($this->menuId)) {
                $q->where('menu_items.menu_id', $this->menuId);
            }

            if ($this->showVeg == 1) {
                return $q->where('menu_items.type', 'veg');
            }
        }])->where('branch_id', $this->shopBranch->id)->get();

        $menuList = Menu::withoutGlobalScopes()->where('branch_id', $this->shopBranch->id)->withCount('items')->get();

        return view('livewire.shop.cart', [
            'menuItems' => $query,
            'categoryList' => $categoryList,
            'menuList' => $menuList
        ]);
    }

}
