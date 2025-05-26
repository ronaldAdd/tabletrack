<div>
    <div
        class="mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">

        @if (!in_array('Payment Gateway Integration', restaurant_modules()))
            <x-upgrade-box :title="__('modules.settings.paymentUpgradeHeading')" :text="__('modules.settings.paymentUpgradeInfo')"></x-upgrade-box>
        @else
            <h3 class="mb-4 text-xl font-semibold dark:text-white">@lang('modules.settings.paymentgatewaySettings')</h3>
            <x-help-text class="mb-6">@lang('modules.settings.paymentHelp')</x-help-text>
            {{-- tabs --}}
            <div
                class="text-sm font-medium text-center text-gray-500 border-b border-gray-200 dark:text-gray-400 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px items-center">
                    <li class="me-2">
                        <span wire:click="activeSetting('razorpay')" @class([
                            'inline-flex items-center gap-x-1 cursor-pointer select-none p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300',
                            'border-transparent' => $activePaymentSetting != 'razorpay',
                            'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' =>
                                $activePaymentSetting == 'razorpay',
                        ])>
                            <svg class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24"><defs><linearGradient id="a" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#0d3e8e"/><stop offset="100%" stop-color="#00c3f3"/></linearGradient></defs><path fill="url(#a)" d="m22.436 0-11.91 7.773-1.174 4.276 6.625-4.297L11.65 24h4.391z"/><path fill="#0D3E8E" d="M14.26 10.098 3.389 17.166 1.564 24h9.008z"/></svg>
                            @lang('modules.billing.razorpay')
                            <span @class([
                                'flex w-3 h-3 me-3 rounded-full',
                                'bg-green-500' => $isRazorpayEnabled,
                                'bg-red-500' => !$isRazorpayEnabled,
                            ])></span>
                        </span>
                    </li>

                    <li wire:click="activeSetting('stripe')" class="me-2">
                        <span @class([
                            'inline-flex items-center gap-x-1 cursor-pointer select-none p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300',
                            'border-transparent' => $activePaymentSetting != 'stripe',
                            'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' =>
                                $activePaymentSetting == 'stripe',
                        ])>
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="24" height="24" fill="#6772e5"><path d="M111.328 15.602c0-4.97-2.415-8.9-7.013-8.9s-7.423 3.924-7.423 8.863c0 5.85 3.32 8.8 8.036 8.8 2.318 0 4.06-.528 5.377-1.26V19.22a10.25 10.25 0 0 1-4.764 1.075c-1.9 0-3.556-.67-3.774-2.943h9.497a40 40 0 0 0 .063-1.748zm-9.606-1.835c0-2.186 1.35-3.1 2.56-3.1s2.454.906 2.454 3.1zM89.4 6.712a5.43 5.43 0 0 0-3.801 1.509l-.254-1.208h-4.27v22.64l4.85-1.032v-5.488a5.43 5.43 0 0 0 3.444 1.265c3.472 0 6.64-2.792 6.64-8.957.003-5.66-3.206-8.73-6.614-8.73zM88.23 20.1a2.9 2.9 0 0 1-2.288-.906l-.03-7.2a2.93 2.93 0 0 1 2.315-.96c1.775 0 2.998 2 2.998 4.528.003 2.593-1.198 4.546-2.995 4.546zM79.25.57l-4.87 1.035v3.95l4.87-1.032z" fill-rule="evenodd"/><path d="M74.38 7.035h4.87V24.04h-4.87z"/><path d="m69.164 8.47-.302-1.434h-4.196V24.04h4.848V12.5c1.147-1.5 3.082-1.208 3.698-1.017V7.038c-.646-.232-2.913-.658-4.048 1.43zm-9.73-5.646L54.698 3.83l-.02 15.562c0 2.87 2.158 4.993 5.038 4.993 1.585 0 2.756-.302 3.405-.643v-3.95c-.622.248-3.683 1.138-3.683-1.72v-6.9h3.683V7.035h-3.683zM46.3 11.97c0-.758.63-1.05 1.648-1.05a10.9 10.9 0 0 1 4.83 1.25V7.6a12.8 12.8 0 0 0-4.83-.888c-3.924 0-6.557 2.056-6.557 5.488 0 5.37 7.375 4.498 7.375 6.813 0 .906-.78 1.186-1.863 1.186-1.606 0-3.68-.664-5.307-1.55v4.63a13.5 13.5 0 0 0 5.307 1.117c4.033 0 6.813-1.992 6.813-5.485 0-5.796-7.417-4.76-7.417-6.943zM13.88 9.515c0-1.37 1.14-1.9 2.982-1.9A19.66 19.66 0 0 1 25.6 9.876v-8.27A23.2 23.2 0 0 0 16.862.001C9.762.001 5 3.72 5 9.93c0 9.716 13.342 8.138 13.342 12.326 0 1.638-1.4 2.146-3.37 2.146-2.905 0-6.657-1.202-9.6-2.802v8.378A24.4 24.4 0 0 0 14.973 32C22.27 32 27.3 28.395 27.3 22.077c0-10.486-13.42-8.613-13.42-12.56z" fill-rule="evenodd"/></svg>
                            @lang('modules.billing.stripe')
                            <span @class([
                                'flex w-3 h-3 me-3 rounded-full',
                                'bg-green-500' => $isStripeEnabled,
                                'bg-red-500' => !$isStripeEnabled,
                            ])></span>
                        </span>
                    </li>

                    <li wire:click="activeSetting('flutterwave')" class="me-2">
                        <span @class([
                            'inline-flex items-center gap-x-1 cursor-pointer select-none p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300',
                            'border-transparent' => $activePaymentSetting != 'flutterwave',
                            'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' =>
                                $activePaymentSetting == 'flutterwave',
                        ])>
                            <svg class="w-4 h-4" width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 176 144.7" xml:space="preserve"><path d="M0 31.6c0-9.4 2.7-17.4 8.5-23.1l10 10C7.4 29.6 17.1 64.1 48.8 95.8s66.2 41.4 77.3 30.3l10 10c-18.8 18.8-61.5 5.4-97.3-30.3C14 80.9 0 52.8 0 31.6" style="fill:#009a46"/><path d="M63.1 144.7c-9.4 0-17.4-2.7-23.1-8.5l10-10c11.1 11.1 45.6 1.4 77.3-30.3s41.4-66.2 30.3-77.3l10-10c18.8 18.8 5.4 61.5-30.3 97.3-24.9 24.8-53.1 38.8-74.2 38.8" style="fill:#ff5805"/><path d="M140.5 91.6C134.4 74.1 122 55.4 105.6 39 69.8 3.2 27.1-10.1 8.3 8.6 7 10 8.2 13.3 10.9 16s6.1 3.9 7.4 2.6c11.1-11.1 45.6-1.4 77.3 30.3 15 15 26.2 31.8 31.6 47.3 4.7 13.6 4.3 24.6-1.2 30.1-1.3 1.3-.2 4.6 2.6 7.4s6.1 3.9 7.4 2.6c9.6-9.7 11.2-25.6 4.5-44.7" style="fill:#f5afcb"/><path d="M167.5 8.6C157.9-1 142-2.6 122.9 4c-17.5 6.1-36.2 18.5-52.6 34.9-35.8 35.8-49.1 78.5-30.3 97.3 1.3 1.3 4.7.2 7.4-2.6s3.9-6.1 2.6-7.4c-11.1-11.1-1.4-45.6 30.3-77.3 15-15 31.8-26.2 47.2-31.6 13.6-4.7 24.6-4.3 30.1 1.2 1.3 1.3 4.6.2 7.4-2.6s3.9-5.9 2.5-7.3" style="fill:#ff9b00"/></svg>
                            @lang('modules.billing.flutterwave')
                            <span @class([
                                'flex w-3 h-3 me-3 rounded-full',
                                'bg-green-500' => $isFlutterwaveEnabled,
                                'bg-red-500' => !$isFlutterwaveEnabled,
                            ])></span>
                        </span>
                    </li>

                    <li class="me-2">
                        <span wire:click="activeSetting('offline')" @class([
                            'inline-flex items-center gap-x-1 cursor-pointer select-none p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300',
                            'border-transparent' => $activePaymentSetting != 'offline',
                            'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' =>
                                $activePaymentSetting == 'offline',
                        ])>
                            <svg class="w-5 h-5 text-current" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M8 7V6a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-1M3 18v-7a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/></svg>
                            @lang('modules.billing.offline')
                            <span @class([
                                'flex w-3 h-3 me-3 rounded-full',
                                'bg-green-500' => $paymentGateway->is_offline_payment_enabled || $paymentGateway->is_cash_payment_enabled,
                                'bg-red-500' => !$paymentGateway->is_offline_payment_enabled && !$paymentGateway->is_cash_payment_enabled,
                            ])></span>
                        </span>
                    </li>

                    <li class="me-2">
                        <span wire:click="activeSetting('qr_code')" @class([
                            'inline-flex items-center gap-x-1 cursor-pointer select-none p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300',
                            'border-transparent' => $activePaymentSetting != 'qr_code',
                            'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' =>
                                $activePaymentSetting == 'qr_code',
                        ])>
                            <!-- QR code icon with grey color -->
                            <svg class="w-5 h-5 text-current" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zm10 10h6v6h-6zm0-10h6v6h-6zm-4 10h.01v.01H10zm0 4h.01v.01H10zm-3 2h.01v.01H7zm0-4h.01v.01H7zm-3 2h.01v.01H4zm0-4h.01v.01H4z"/><path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M7 7h.01v.01H7zm10 10h.01v.01H17z"/></svg>
                            @lang('modules.billing.qr_code')
                            <span @class([
                                'flex w-3 h-3 me-3 rounded-full',
                                'bg-green-500' => $paymentGateway->is_qr_payment_enabled,
                                'bg-red-500' => !$paymentGateway->is_qr_payment_enabled,
                            ])></span>
                        </span>
                    </li>




                    <li wire:click="activeSetting('serviceSpecific')" class="me-2">
                        <span @class([
                            'inline-flex items-center gap-x-1 cursor-pointer select-none p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300',
                            'border-transparent' => $activePaymentSetting != 'serviceSpecific',
                            'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' =>
                                $activePaymentSetting == 'serviceSpecific',
                        ])>
                            <svg class="w-5 h-5 current-Color" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                    d="M20 6H10m0 0a2 2 0 1 0-4 0m4 0a2 2 0 1 1-4 0m0 0H4m16 6h-2m0 0a2 2 0 1 0-4 0m4 0a2 2 0 1 1-4 0m0 0H4m16 6H10m0 0a2 2 0 1 0-4 0m4 0a2 2 0 1 1-4 0m0 0H4" />
                            </svg>
                            @lang('modules.billing.generalSettings')
                        </span>
                    </li>

                </ul>
            </div>

            <!-- Razor pay Form -->

            @if ($activePaymentSetting == 'razorpay')
                <form wire:submit="submitFormRazorpay">
                    <div class="grid gap-6">

                        <div class="my-3">
                            <x-label for="razorpayStatus">
                                <div class="flex items-center cursor-pointer">
                                    <x-checkbox name="razorpayStatus" id="razorpayStatus"
                                        wire:model.live='razorpayStatus' />

                                    <div class="ms-2">
                                        @lang('modules.settings.enableRazorpay')
                                    </div>
                                </div>
                            </x-label>
                        </div>

                        @if ($razorpayStatus)
                            <div>
                                <x-label for="razorpayKey" value="Razorpay KEY" />
                                <x-input-password id="razorpayKey" class="block mt-1 w-full"
                                    wire:model='razorpayKey' />
                                <x-input-error for="razorpayKey" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="razorpaySecret" value="Razorpay SECRET" />
                                <x-input-password id="razorpaySecret" class="block mt-1 w-full"
                                    wire:model='razorpaySecret' />
                                <x-input-error for="razorpaySecret" class="mt-2" />
                            </div>
                        @endif

                        <div>
                            <x-button>@lang('app.save')</x-button>
                        </div>
                    </div>
                </form>
            @endif
            <!-- Stripe Form -->
            @if ($activePaymentSetting == 'stripe')
                <form wire:submit="submitFormStripe">
                    <div class="grid gap-6">
                        <div class="my-3">
                            <x-label for="stripeStatus">
                                <div class="flex items-center cursor-pointer">
                                    <x-checkbox name="stripeStatus" id="stripeStatus" wire:model.live='stripeStatus' />

                                    <div class="ms-2">
                                        @lang('modules.settings.enableStripe')
                                    </div>
                                </div>
                            </x-label>
                        </div>

                        @if ($stripeStatus)
                            <div>
                                <x-label for="stripeKey" value="Stripe KEY" />
                                <x-input-password id="stripeKey" class="block mt-1 w-full"
                                    wire:model='stripeKey' />
                                <x-input-error for="stripeKey" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="stripeSecret" value="Stripe SECRET" />
                                <x-input-password id="stripeSecret" class="block mt-1 w-full"
                                    wire:model='stripeSecret' />
                                <x-input-error for="stripeSecret" class="mt-2" />
                            </div>
                        @endif

                        <div>
                            <x-button>@lang('app.save')</x-button>
                        </div>
                    </div>
                </form>
            @endif

            <!-- Flutterwave Form -->
            @if($activePaymentSetting == 'flutterwave')
            <form wire:submit="submitFlutterwaveForm">
                <div class="grid gap-6">

                    <div class="my-3">
                        <x-label for="flutterwaveStatus">
                            <div class="flex items-center cursor-pointer">
                                <x-checkbox name="flutterwaveStatus" id="flutterwaveStatus" wire:model.live='flutterwaveStatus'/>

                                <div class="ms-2">
                                    @lang('modules.settings.enableFlutterwave')
                                </div>
                            </div>
                        </x-label>
                    </div>

                    @if ($flutterwaveStatus)
                        <div>
                            <x-label for="flutterwaveMode" :value="__('modules.settings.selectEnvironment')" required/>
                            <x-select id="flutterwaveMode" class="mt-1 block w-full" wire:model.live="flutterwaveMode">
                                <option value="test">@lang('app.test')</option>
                                <option value="live">@lang('app.live')</option>
                            </x-select>
                            <x-input-error for="flutterwaveMode" class="mt-2"/>
                        </div>

                        @if ($flutterwaveMode == 'live')
                            <div class="grid grid-cols-2 gap-x-4">
                                <div>
                                    <x-label for="liveFlutterwaveKey" :value="__('modules.settings.flutterwaveKey')" required/>
                                    <x-input id="liveFlutterwaveKey" class="block mt-1 w-full" type="text" wire:model='liveFlutterwaveKey'/>
                                    <x-input-error for="liveFlutterwaveKey" class="mt-2"/>
                                </div>

                                <div>
                                    <x-label for="liveFlutterwaveSecret" :value="__('modules.settings.flutterwaveSecret')" required/>
                                    <x-input-password id="liveFlutterwaveSecret" class="block mt-1 w-full" type="text" wire:model='liveFlutterwaveSecret'/>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-4">
                                <div>
                                    <x-label for="liveFlutterwaveHash" :value="__('modules.settings.flutterwaveEncryptionKey')" required/>
                                    <x-input-password id="liveFlutterwaveHash" class="block mt-1 w-full" type="text" wire:model='liveFlutterwaveHash'/>
                                    <x-input-error for="liveFlutterwaveHash" class="mt-2"/>
                                </div>

                                <div>
                                    <x-label for="testFlutterwaveWebhookKey" :value="__('modules.settings.flutterwaveWebhookHash')"/>
                                    <x-input id="testFlutterwaveWebhookKey" class="block mt-1 w-full" type="text" wire:model='testFlutterwaveWebhookKey'/>
                                    <x-input-error for="testFlutterwaveWebhookKey" class="mt-2"/>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-2 gap-x-4">
                                <div>
                                    <x-label for="testFlutterwaveKey" :value="__('modules.settings.testFlutterwaveKey')" required/>
                                    <x-input id="testFlutterwaveKey" class="block mt-1 w-full" type="text" wire:model='testFlutterwaveKey'/>
                                    <x-input-error for="testFlutterwaveKey" class="mt-2"/>
                                </div>

                                <div>
                                    <x-label for="testFlutterwaveSecret" :value="__('modules.settings.testFlutterwaveSecret')" required/>
                                    <x-input-password id="testFlutterwaveSecret" class="block mt-1 w-full" type="text" wire:model='testFlutterwaveSecret'/>
                                    <x-input-error for="testFlutterwaveSecret" class="mt-2"/>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-4">
                                <div>
                                    <x-label for="testFlutterwaveHash" :value="__('modules.settings.testFlutterwaveEncryptionKey')" required/>
                                    <x-input-password id="testFlutterwaveHash" class="block mt-1 w-full" type="text" wire:model='testFlutterwaveHash'/>
                                    <x-input-error for="testFlutterwaveHash" class="mt-2"/>
                                </div>

                                <div>
                                    <x-label for="testFlutterwaveWebhookKey" :value="__('modules.settings.testFlutterwaveWebhookHash')"/>
                                    <x-input id="testFlutterwaveWebhookKey" class="block mt-1 w-full" type="text" wire:model='testFlutterwaveWebhookKey'/>
                                    <x-input-error for="testFlutterwaveWebhookKey" class="mt-2"/>
                                </div>
                            </div>

                        @endif
                        <div class="mt-4">
                            <x-label :value="__('modules.settings.webhookUrl')" class="mb-1"/>
                            <div class="flex items-center">
                                <!-- Webhook URL Input -->
                                <x-input id="webhook-url" class="block w-full" type="text" value="{{ $webhookUrl }}" readonly/>
                                <button id="copy-button" type="button" onclick="copyWebhookUrl()" class="ml-2 px-3 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">
                                    @lang('modules.settings.copyWebhookUrl')
                                </button>
                            </div>
                        </div>

                    @endif

                    <div>
                        <x-button>@lang('app.save')</x-button>
                    </div>
                </div>
            </form>
            @endif

            <!-- Offline Form -->
            @if ($activePaymentSetting == 'offline')
                <form wire:submit="submitFormOffline" class="mt-4">
                    <div class="space-y-6">
                        {{-- Offline Payment Method Toggle --}}
                        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <label for="enableOfflinePayment" class="flex items-center space-x-2">
                                <x-checkbox name="enableOfflinePayment" id="enableOfflinePayment" value="offline" wire:model.live="enableOfflinePayment" />
                                <span>
                                    <span class="font-medium text-gray-900 dark:text-white">@lang('modules.settings.enableOfflinePayment')</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.offlinePaymentDescription')</p>
                                </span>
                            </label>
                        </div>

                        @if ($enableOfflinePayment)
                            {{-- Payment Details Section --}}
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                                <div class="space-y-4">
                                    <div>
                                        <x-label for="paymentDetails" :value="__('modules.settings.offlinePaymentDetails')" class="mb-2" />
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                            @lang('modules.settings.offlinePaymentDetailsDescription')
                                        </p>
                                        <x-textarea
                                            id="paymentDetails"
                                            wire:model="paymentDetails"
                                            class="block w-full"
                                            rows="4"
                                            placeholder="Enter bank account details, payment instructions, or any other relevant information..."
                                            required
                                        ></x-textarea>
                                        <x-input-error for="paymentDetails" class="mt-2" />
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Cash Payment Toggle --}}
                        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <label for="enablePayViaCash" class="flex items-center space-x-2">
                                <x-checkbox name="enablePayViaCash" id="enablePayViaCash" value="cash" wire:model.live="enablePayViaCash" />
                                <span>
                                    <span class="font-medium text-gray-900 dark:text-white">@lang('modules.settings.enablePayViaCash')</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.cashPaymentDescription')</p>
                                </span>
                            </label>
                        </div>
                        {{-- Save Button --}}
                        <div class="flex justify-start">
                            <x-button class="gap-x-2">

                                @lang('app.save')
                            </x-button>
                        </div>
                    </div>
                </form>
            @endif
            {{-- qrcode Form --}}
            @if ($activePaymentSetting == 'qr_code')
                <form wire:submit="submitFormOffline" class="mt-4">
                    <div class="space-y-6">
                        {{-- Enable QR Payment Toggle --}}
                        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <label for="offlinePaymentMethodQr" class="flex items-center space-x-2">
                                <x-checkbox name="offlinePaymentMethodQr" id="offlinePaymentMethodQr" value="qrcode" wire:model.live="enableQrPayment" />
                                <span>
                                    <span class="font-medium text-gray-900 dark:text-white">@lang('modules.settings.enableQrPayment')</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">@lang('modules.settings.qrPaymentDescription')</p>
                                </span>
                            </label>
                        </div>

                        @if ($enableQrPayment)
                            {{-- QR Code Upload Section --}}
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                                <div class="space-y-4">
                                    <div>
                                        <x-label for="qrCodeImage" :value="__('modules.settings.qrCodeImage')" class="mb-2" />
                                        <div class="flex items-center space-x-4">
                                            {{-- Preview Section --}}
                                            <div class="flex-shrink-0">
                                                <div class="relative h-32 w-32">
                                                    @if ($qrCodeImage && is_object($qrCodeImage))
                                                        <img src="{{ $qrCodeImage->temporaryUrl() }}"
                                                             alt="QR Code Preview"
                                                             class="h-32 w-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                                    @elseif (isset($qrCodeImage) && $qrCodeImage)
                                                        <img src="{{ $qrCodeImage }}"
                                                             alt="Existing QR Code"
                                                             class="h-32 w-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                                                    @else
                                                        <div class="h-32 w-32 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                            </svg>
                                                        </div>
                                                    @endif

                                                    {{-- Loading Overlay - Now outside the conditional statements --}}
                                                    <div wire:loading wire:target="qrCodeImage" class="absolute inset-0 bg-gray-900/60 rounded-lg flex items-center justify-center">
                                                        <svg class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Upload Section --}}
                                            <div class="flex-grow">
                                                <label class="flex flex-col items-center px-4 py-6 bg-gray-50 dark:bg-gray-700 text-gray-500 rounded-lg border-2 border-dashed cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                    </svg>
                                                    <span class="text-sm">@lang('modules.settings.uploadQrCode')</span>
                                                    <input type="file" wire:model="qrCodeImage" class="hidden" accept="image/*">
                                                </label>
                                                <x-input-error for="qrCodeImage" class="mt-2" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        @lang('modules.settings.qrCodeRequirements')
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div>
                            <x-button :disabled="$errors->has('qrCodeImage')" wire:loading.attr="disabled" wire:target="qrCodeImage">
                                <span class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>@lang('app.save')</span>
                                </span>
                            </x-button>
                        </div>
                    </div>
                </form>
            @endif
            <!-- general Form -->
            @if ($activePaymentSetting == 'serviceSpecific')
                @if ($isRazorpayEnabled || $isStripeEnabled || $enableOfflinePayment || $enableQrPayment || $isFlutterwaveEnabled)
                    <div class="space-y-6 mt-4">
                        <x-alert type="info" class="flex items-center">
                            <svg class="flex-shrink-0 w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                            </svg>
                            <span>@lang('modules.settings.generalSettingsUseInfo')</span>
                        </x-alert>

                        <form wire:submit="submitFormServiceSpecific">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                                <div class="p-4 space-y-4">
                                    <!-- Dine-in Setting -->
                                    <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <x-checkbox name="enableForDineIn" id="enableForDineIn" wire:model='enableForDineIn' />
                                        <label for="enableForDineIn" class="ms-3 font-medium text-gray-900 dark:text-white cursor-pointer">
                                            @lang('modules.settings.dineInOnlinePaymentRequired')
                                        </label>
                                    </div>

                                    <!-- Delivery Setting -->
                                    <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <x-checkbox name="enableForDelivery" id="enableForDelivery" wire:model='enableForDelivery' />
                                        <label for="enableForDelivery" class="ms-3 font-medium text-gray-900 dark:text-white cursor-pointer">
                                            @lang('modules.settings.deliveryOnlinePaymentRequired')
                                        </label>
                                    </div>

                                    <!-- Pickup Setting -->
                                    <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <x-checkbox name="enableForPickup" id="enableForPickup" wire:model='enableForPickup' />
                                        <label for="enableForPickup" class="ms-3 font-medium text-gray-900 dark:text-white cursor-pointer">
                                            @lang('modules.settings.pickupOnlinePaymentRequired')
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-button>
                                    <span class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>@lang('app.save')</span>
                                    </span>
                                </x-button>
                            </div>
                        </form>
                    </div>
                @else
                    <x-alert type="info" class="my-4 flex items-center">
                        <svg class="flex-shrink-0 w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <span>@lang('modules.settings.enableRazorpayOrStripe')</span>
                    </x-alert>
                @endif
            @endif

        @endif
    </div>

    <script>
        function copyWebhookUrl() {
                let webhookUrl = document.getElementById("webhook-url").value;
                let copyButton = document.getElementById("copy-button");

                // Create a temporary textarea element
                let tempTextArea = document.createElement("textarea");
                tempTextArea.value = webhookUrl;
                document.body.appendChild(tempTextArea);

                // Select and copy the text
                tempTextArea.select();
                tempTextArea.setSelectionRange(0, 99999); // For mobile devices
                document.execCommand("copy");

                // Remove the temporary textarea
                document.body.removeChild(tempTextArea);

                // Change button text to "Copied!"
                copyButton.innerText = "Copied!";

                // Revert text back to original after 2 seconds
                setTimeout(() => {
                    copyButton.innerText = "Copy";
                }, 2000);
            }
    </script>
</div>
