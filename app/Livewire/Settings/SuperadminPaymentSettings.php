<?php

namespace App\Livewire\Settings;

use App\Models\SuperadminPaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class SuperadminPaymentSettings extends Component
{

    use LivewireAlert;

    public $razorpaySecret;
    public $razorpayKey;
    public $testRazorpaySecret;
    public $testRazorpayKey;
    public $razorpayStatus;
    public $paymentGateway;
    public $stripeSecret;
    public $stripeKey;
    public $testStripeSecret;
    public $testStripeKey;
    public $stripeStatus;
    public $selectRazorpayEnvironment;
    public $selectStripeEnvironment;
    public $activePaymentSetting = 'razorpay';
    public $razorpayWebhookKey;
    public $testRazorpayWebhookKey;
    public $stripeWebhookKey;
    public $testStripeWebhookKey;
    public $webhookUrl;
    public $flutterwaveSecret;
    public $flutterwaveKey;
    public $flutterwaveHash;
    public $testFlutterwaveSecret;
    public $testFlutterwaveKey;
    public $testFlutterwaveHash;
    public $flutterwaveStatus;
    public $selectFlutterwaveEnvironment;
    public $flutterwaveWebhookKey;
    public $testFlutterwaveWebhookKey;

    public function mount()
    {
        $this->paymentGateway = SuperadminPaymentGateway::first();
        $this->setCredentials();
    }

    public function setCredentials()
    {
        $this->selectRazorpayEnvironment = $this->paymentGateway->razorpay_type;
        $this->razorpayStatus = (bool)$this->paymentGateway->razorpay_status;

        $this->razorpayKey = $this->paymentGateway->live_razorpay_key;
        $this->razorpaySecret = $this->paymentGateway->live_razorpay_secret;

        $this->testRazorpayKey = $this->paymentGateway->test_razorpay_key;
        $this->testRazorpaySecret = $this->paymentGateway->test_razorpay_secret;

        $this->selectStripeEnvironment = $this->paymentGateway->stripe_type;
        $this->stripeStatus = (bool)$this->paymentGateway->stripe_status;

        $this->stripeKey = $this->paymentGateway->live_stripe_key;
        $this->stripeSecret = $this->paymentGateway->live_stripe_secret;

        $this->testStripeKey = $this->paymentGateway->test_stripe_key;
        $this->testStripeSecret = $this->paymentGateway->test_stripe_secret;


        $this->razorpayWebhookKey = $this->paymentGateway->razorpay_live_webhook_key;
        $this->testRazorpayWebhookKey = $this->paymentGateway->razorpay_test_webhook_key;

        $this->stripeWebhookKey = $this->paymentGateway->stripe_live_webhook_key;
        $this->testStripeWebhookKey = $this->paymentGateway->stripe_test_webhook_key;

        $this->selectFlutterwaveEnvironment = $this->paymentGateway->flutterwave_type;
        $this->flutterwaveStatus = (bool)$this->paymentGateway->flutterwave_status;

        $this->flutterwaveKey = $this->paymentGateway->live_flutterwave_key;
        $this->flutterwaveSecret = $this->paymentGateway->live_flutterwave_secret;
        $this->flutterwaveHash = $this->paymentGateway->live_flutterwave_hash;

        $this->testFlutterwaveKey = $this->paymentGateway->test_flutterwave_key;
        $this->testFlutterwaveSecret = $this->paymentGateway->test_flutterwave_secret;
        $this->testFlutterwaveHash = $this->paymentGateway->test_flutterwave_hash;
        
        $hash = global_setting()->hash;
        $this->flutterwaveWebhookKey = $this->paymentGateway->flutterwave_live_webhook_key ? $this->paymentGateway->flutterwave_live_webhook_key : substr(md5($hash), 0, 10);
        $this->testFlutterwaveWebhookKey = $this->paymentGateway->flutterwave_test_webhook_key ? $this->paymentGateway->flutterwave_test_webhook_key : substr(md5($hash), 0, 10);

        if ($this->activePaymentSetting === 'stripe') {
            $this->webhookUrl = route('billing.verify-webhook', ['hash' => $hash]);
        }

        if ($this->activePaymentSetting === 'razorpay') {
            $this->webhookUrl = route('billing.save_razorpay-webhook', ['hash' => $hash]);
        }

        if ($this->activePaymentSetting === 'flutterwave') {
            $this->webhookUrl = route('billing.save-flutterwave-webhook', ['hash' => $hash]);
        }

    }

    public function activeSetting($tab)
    {
        $this->activePaymentSetting = $tab;
        $this->setCredentials();
    }

    public function submitFormRazorpay()
    {
        $this->validate([
            'razorpaySecret' => Rule::requiredIf($this->razorpayStatus == true && $this->selectRazorpayEnvironment == 'live'),
            'razorpayKey' => Rule::requiredIf($this->razorpayStatus == true && $this->selectRazorpayEnvironment == 'live'),
            'testRazorpaySecret' => Rule::requiredIf($this->razorpayStatus == true && $this->selectRazorpayEnvironment == 'test'),
            'testRazorpayKey' => Rule::requiredIf($this->razorpayStatus == true && $this->selectRazorpayEnvironment == 'test'),
        ]);

        $configError = 0;

        // Set Razorpay credentials
        $razorKey = $this->selectRazorpayEnvironment == 'live' ? $this->razorpayKey : $this->testRazorpayKey;
        $razorSecret = $this->selectRazorpayEnvironment == 'live' ? $this->razorpaySecret : $this->testRazorpaySecret;

        // Test Razorpay credentials
        if ($this->razorpayStatus) {
            try {
                $response = Http::withBasicAuth($razorKey, $razorSecret)
                    ->get('https://api.razorpay.com/v1/contacts');

                if ($response->successful()) {
                    $this->paymentGateway->update([
                        'razorpay_type' => $this->selectRazorpayEnvironment,
                        'live_razorpay_key' => $this->razorpayKey,
                        'live_razorpay_secret' => $this->razorpaySecret,
                        'test_razorpay_key' => $this->testRazorpayKey,
                        'test_razorpay_secret' => $this->testRazorpaySecret,
                        'razorpay_live_webhook_key' => $this->razorpayWebhookKey,
                        'razorpay_test_webhook_key' => $this->testRazorpayWebhookKey,
                    ]);
                } else {
                    $configError = 1;
                    $this->addError('razorpayKey', 'Invalid Razorpay key or secret.');
                    $this->addError('testRazorpayKey', 'Invalid Razorpay key or secret.');
                }
            } catch (\Exception $e) {
                $this->addError('razorpayKey', 'Invalid Razorpay key or secret.');
                $this->addError('testRazorpayKey', 'Invalid Razorpay key or secret.');
            }
        }

        $this->paymentGateway->update([
            'razorpay_status' => $this->razorpayStatus
        ]);

        $this->paymentGateway->fresh();
        $this->dispatch('settingsUpdated');
        cache()->forget('superadminPaymentGateway');

        if ($configError == 0) {
            $this->alert('success', __('messages.settingsUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }
    }

    // Stripe Form Submission
    public function submitFormStripe()
    {
        $this->validate([
            'stripeSecret' => Rule::requiredIf($this->stripeStatus == true && $this->selectStripeEnvironment == 'live'),
            'stripeKey' => Rule::requiredIf($this->stripeStatus == true && $this->selectStripeEnvironment == 'live'),
            'testStripeSecret' => Rule::requiredIf($this->stripeStatus == true && $this->selectStripeEnvironment == 'test'),
            'testStripeKey' => Rule::requiredIf($this->stripeStatus == true && $this->selectStripeEnvironment == 'test'),
        ]);

        $configError = 0;

        // Set Stripe credentials
        $stripeKey = $this->selectStripeEnvironment == 'live' ? $this->stripeKey : $this->testStripeKey;
        $stripeSecret = $this->selectStripeEnvironment == 'live' ? $this->stripeSecret : $this->testStripeSecret;

        // Test Stripe credentials
        if ($this->stripeStatus) {
            try {
                $response = Http::withToken($stripeSecret)
                    ->get('https://api.stripe.com/v1/customers');

                if ($response->successful()) {
                    $this->paymentGateway->update([
                        'live_stripe_key' => $this->stripeKey,
                        'live_stripe_secret' => $this->stripeSecret,
                        'test_stripe_key' => $this->testStripeKey,
                        'test_stripe_secret' => $this->testStripeSecret,
                        'stripe_type' => $this->selectStripeEnvironment,
                        'stripe_live_webhook_key' => $this->stripeWebhookKey,
                        'stripe_test_webhook_key' => $this->testStripeWebhookKey,
                    ]);
                } else {
                    $configError = 1;
                    $this->addError('stripeKey', 'Invalid Stripe key or secret.');
                    $this->addError('testStripeKey', 'Invalid Stripe key or secret.');
                }
            } catch (\Exception $e) {
                $this->addError('stripeKey', 'Invalid Stripe key or secret.');
                $this->addError('testStripeKey', 'Invalid Stripe key or secret.');
            }
        }

        $this->paymentGateway->update([
            'stripe_status' => $this->stripeStatus
        ]);

        $this->paymentGateway->fresh();
        $this->dispatch('settingsUpdated');
        cache()->forget('superadminPaymentGateway');

        if ($configError == 0) {
            $this->alert('success', __('messages.settingsUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }
    }


    public function submitFormFlutterwave()
    {
        $this->validate([
            'flutterwaveSecret' => Rule::requiredIf($this->flutterwaveStatus == true && $this->selectFlutterwaveEnvironment == 'live'),
            'flutterwaveKey' => Rule::requiredIf($this->flutterwaveStatus == true && $this->selectFlutterwaveEnvironment == 'live'),
            'testFlutterwaveSecret' => Rule::requiredIf($this->flutterwaveStatus == true && $this->selectFlutterwaveEnvironment == 'test'),
            'testFlutterwaveKey' => Rule::requiredIf($this->flutterwaveStatus == true && $this->selectFlutterwaveEnvironment == 'test'),
        ]);

        $configError = 0;

        // Set Flutterwave credentials
        $flutterwaveKey = $this->selectFlutterwaveEnvironment == 'live' ? $this->flutterwaveKey : $this->testFlutterwaveKey;
        $flutterwaveSecret = $this->selectFlutterwaveEnvironment == 'live' ? $this->flutterwaveSecret : $this->testFlutterwaveSecret;

        // Test Flutterwave credentials
        if ($this->flutterwaveStatus) {
            try {
                $response = Http::withToken($flutterwaveSecret)
            ->get('https://api.flutterwave.com/v3/transactions');

                if ($response->successful()) {
                    $this->paymentGateway->update([
                        'flutterwave_type' => $this->selectFlutterwaveEnvironment,
                        'live_flutterwave_key' => $this->flutterwaveKey,
                        'live_flutterwave_secret' => $this->flutterwaveSecret,
                        'live_flutterwave_hash' => $this->flutterwaveHash,
                        'test_flutterwave_key' => $this->testFlutterwaveKey,
                        'test_flutterwave_secret' => $this->testFlutterwaveSecret,
                        'test_flutterwave_hash' => $this->testFlutterwaveHash,
                        'flutterwave_live_webhook_key' => $this->flutterwaveWebhookKey,
                        'flutterwave_test_webhook_key' => $this->testFlutterwaveWebhookKey,
                    ]);
                } else {
                    $configError = 1;
                    $this->addError('flutterwaveKey', 'Invalid Flutterwave key or secret.');
                    $this->addError('testFlutterwaveKey', 'Invalid Flutterwave key or secret.');
                }
            } catch (\Exception $e) {
                $this->addError('flutterwaveKey', 'Invalid Flutterwave key or secret.');
                $this->addError('testFlutterwaveKey', 'Invalid Flutterwave key or secret.');
            }
        }

        $this->paymentGateway->update([
            'flutterwave_status' => $this->flutterwaveStatus
        ]);

        $this->paymentGateway->fresh();
        $this->dispatch('settingsUpdated');
        cache()->forget('superadminPaymentGateway');

        if ($configError == 0) {
            $this->alert('success', __('messages.settingsUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }
    }

    public function render()
    {
        return view('livewire.settings.superadmin-payment-settings');
    }

}
