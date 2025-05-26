<?php

namespace App\Livewire\Shop;

use App\Models\Order;
use Razorpay\Api\Api;
use App\Models\Branch;
use App\Models\Payment;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\StripePayment;
use App\Models\RazorpayPayment;
use App\Notifications\SendOrderBill;
use Illuminate\Support\Facades\Http;
use App\Models\FlutterwavePayment;
use App\Models\PaymentGatewayCredential;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class OrderDetail extends Component
{
    public $restaurant;
    public $shopBranch;
    public $order;
    public $id;
    public $customer;
    public $orderType;
    public $paymentGateway;
    public $razorpayStatus;
    public $stripeStatus;
    public $flutterwaveStatus;
    public $showPaymentModal = false;
    public $paymentOrder;
    public $showQrCode = false;
    public $showPaymentDetail = false;
    public $qrCodeImage;
    public $total;
    public $canAddTip;
    public $tipAmount;
    public $tipNote;
    public $showTipModal = false;

    use LivewireAlert;

    public function mount()
    {


        $customer = customer();
        $this->order = Order::withoutGlobalScopes()
            ->with(['taxes.tax','items','items.menuItem'])
            ->where('id', $this->id)
            ->when(optional($customer)->id, fn($query) => $query->where('customer_id', $customer->id))
            ->firstOrFail();

        if ($this->order->customer_id && !$customer) {
            abort(404);
        }

        if (!$customer && $this->restaurant->customer_login_required) {
            return redirect()->route('home');
        }

        $this->shopBranch = request()->filled('branch')
            ? Branch::find(request()->branch)
            : $this->restaurant->branches->first();

        $this->customer = $customer;
        $this->orderType = $this->order->order_type;
        $this->paymentOrder = $this->order;

        $this->paymentGateway = PaymentGatewayCredential::withoutGlobalScopes()->where('restaurant_id', $this->restaurant->id)->first();
        $this->razorpayStatus = (bool)$this->paymentGateway->razorpay_status;
        $this->stripeStatus = (bool)$this->paymentGateway->stripe_status;
        $this->flutterwaveStatus = (bool)$this->paymentGateway->flutterwave_status;

        $this->qrCodeImage = $this->restaurant->qr_code_image;
        $this->canAddTip = $this->restaurant->enable_tip_shop && $this->order->status !== 'paid';
        $this->tipAmount = $this->order->tip_amount;
        $this->tipNote = $this->order->tip_note;
    }


    public function InitializePayment()
    {
        $this->total = floatval($this->paymentOrder->total) - floatval($this->paymentOrder->amount_paid ?: 0);
        $this->showPaymentModal = true;
    }

    public function hidePaymentModal()
    {
        $this->showPaymentModal = false;
    }

    public function toggleQrCode()
    {
        $this->showQrCode = !$this->showQrCode;
    }

    public function togglePaymentDetail()
    {
        $this->showPaymentDetail = !$this->showPaymentDetail;
    }

    public function initiatePayment($id)
    {
        $payment = RazorpayPayment::create([
            'order_id' => $id,
            'amount' => $this->total
        ]);

        $orderData = [
            'amount' => (int) round($this->total * 100),
            'currency' => $this->restaurant->currency->currency_code
        ];
        
        $paymentGateway = $this->restaurant->paymentGateways;
        $apiKey = $paymentGateway->razorpay_key;
        $secretKey = $paymentGateway->razorpay_secret;

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
            $order->amount_paid = floatval($order->amount_paid) + $this->total;
            $order->status = 'paid';
            $order->save();

            Payment::updateOrCreate(
                [
                    'order_id' => $payment->order_id,
                    'payment_method' => 'due',
                    'amount' => $payment->amount
                ],
                [
                    'transaction_id' => $razorpayPaymentID,
                    'payment_method' => 'razorpay',
                    'branch_id' => $this->shopBranch->id
                ]
            );

            $this->sendNotifications($order);

            $this->alert('success', __('messages.paymentDoneSuccessfully'), [
                'toast' => false,
                'position' => 'center',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close')
            ]);

            $this->redirect(route('order_success', $payment->order_id));
        }

    }

    public function makePayment($id, $method)
    {
        if (!$id || !$method) {
            return;
        }

        $allowedMethods = ['cash', 'card', 'upi', 'due','others'];

        if (!in_array($method, $allowedMethods)) {
            $this->alert('error', __('messages.invalidPaymentMethod'), [
                'toast' => false,
                'position' => 'center'
            ]);
            return;
        }

        $order = Order::findOrFail($id);
        $order->update([
            'status' => 'pending_verification',
        ]);

        Payment::updateOrCreate(
            [
                'order_id' => $order->id,
                'payment_method' => 'due',
                'amount' => $this->total
            ],
            [
                'payment_method' => $method,
                'branch_id' => $this->shopBranch->id
            ]
        );

        $this->sendNotifications($order);

        $this->alert('success', __('messages.paymentDoneSuccessfully'), [
            'toast' => false,
            'position' => 'center',
            'showCancelButton' => true,
            'cancelButtonText' => __('app.close')
        ]);

        $this->redirect(route('order_success', $order->id));
    }


    public function sendNotifications($order)
    {
        if ($order->customer_id) {
            try {
                $order->customer->notify(new SendOrderBill($order));
            } catch (\Exception $e) {
                \Log::error('Error sending order bill email: ' . $e->getMessage());
            }
        }
    }

    public function addTipModal()
    {
        if ($this->order->status === 'paid') {
            $this->alert('error', __('messages.notHavePermission'), ['toast' => true]);
            return;
        }

        $this->tipAmount = $this->order->tip_amount ?? 0;
        $this->tipNote = $this->order->tip_note ?? '';
        $this->showTipModal = true;
    }

    public function addTip()
    {
        if (!$this->canAddTip) {
            $this->alert('error', __('messages.notHavePermission'), ['toast' => true]);
            return;
        }

        if (!$this->tipAmount || $this->tipAmount <= 0) {
            $this->tipAmount = 0;
        }

        $order = Order::find($this->id);

        $previousTip = floatval($order->tip_amount ?? 0);
        $newTip = floatval($this->tipAmount ?? 0);

        $order->total = floatval($order->total) - $previousTip + $newTip;
        $order->tip_amount = $newTip;
        $order->tip_note = $newTip > 0 ? $this->tipNote : null;
        $order->save();

        $this->order = $order;
        $this->showTipModal = false;

        $message = $newTip > 0 ? __('messages.tipAddedSuccessfully') : __('messages.tipRemovedSuccessfully');
        $this->alert('success', $message, ['toast' => true]);
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


    public function render()
    {
        return view('livewire.shop.order-detail');
    }
}
