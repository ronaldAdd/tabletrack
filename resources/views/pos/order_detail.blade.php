<div class="lg:w-5/12 flex flex-col bg-white border-l dark:border-gray-700 min-h-screen h-auto pr-4 px-2 py-4 dark:bg-gray-800">
    <div>
        <h2 class="text-lg  dark:text-neutral-200">@lang('modules.order.orderNumber') #{{ $orderDetail->order_number }}</h2>
        <div class="flex gap-3 space-y-1 my-4 justify-between">
            <div class="inline-flex gap-4">
                <div @class(['p-3 rounded-lg tracking-wide bg-skin-base/[0.2] text-skin-base'])>
                    <h3 wire:loading.class.delay='opacity-50'
                        @class(['font-semibold'])>
                        {{ $orderDetail->table->table_code ?? '--' }}
                    </h3>
                </div>
                <div>
                    @if ($orderDetail->customer_id)
                        <div class="font-semibold text-gray-700 dark:text-gray-300">{{ $orderDetail->customer->name }}</div>
                    @else
                     <a href="javascript:;" wire:click="$dispatch('showAddCustomerModal', { id: {{ $orderDetail->id }} })"
                      class="underline text-sm dark:text-gray-300 underline-offset-2">&plus; @lang('modules.order.addCustomerDetails')</a>
                    @endif
                    <div class="font-medium text-gray-600 text-xs dark:text-gray-400">{{ $orderDetail->date_time->translatedFormat('F d, Y H:i A') }}</div>
                </div>

            </div>
            <div>
                <span @class(['text-sm font-medium px-2 py-1 rounded uppercase tracking-wide whitespace-nowrap ',
                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 border border-gray-400' => ($orderDetail->status == 'draft'),
                'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-400 border border-yellow-400' => ($orderDetail->status == 'kot'),
                'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-400 border border-blue-400' => ($orderDetail->status == 'billed'),
                'bg-green-100 text-green-800 dark:bg-gray-700 dark:text-green-400 border border-green-400' => ($orderDetail->status == 'paid'),
                'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-400 border border-red-400' => ($orderDetail->status == 'canceled'),
                ])>
                    @lang('modules.order.' . $orderDetail->status)
                </span>
            </div>

        </div>

        @if ($orderDetail->order_status->value === 'cancelled')
            <span class="inline-block px-2 py-1 my-2 text-xs font-medium text-red-800 bg-red-100 rounded-full">
                @lang('modules.order.info_cancelled')
            </span>
        @else
        <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            @php
                $statuses = match($orderDetail->order_type) {
                    'delivery' => ['placed', 'confirmed', 'preparing', 'out_for_delivery', 'delivered'],
                    'pickup' => ['placed', 'confirmed', 'preparing', 'ready_for_pickup', 'delivered'],
                    default => ['placed', 'confirmed', 'preparing', 'served']
                };

                $currentIndex = array_search($orderDetail->order_status->value, $statuses);
                $currentIndex = $currentIndex !== false ? $currentIndex : 0;
                $nextIndex = min($currentIndex + 1, count($statuses) - 1);
            @endphp

            @if ($orderDetail->order_status->value === 'canceled')
                <div class="flex items-center justify-center">
                    <h3 class="text-lg font-semibold text-red-600 dark:text-red-400">
                        {{ __('modules.order.orderCancelled') }}
                    </h3>
                </div>
            @else
                <div class="flex flex-col space-y-4">
                    <div class="flex items-center justify-between text-gray-900 dark:text-white">
                        <h3 class="text-lg font-semibold">
                            {{ __('modules.order.orderStatus') }}
                        </h3>
                        <span class="px-3 py-1 text-sm font-medium rounded-full"
                            @class([
                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $orderDetail->order_status->value === 'delivered' || $orderDetail->order_status->value === 'served',
                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $orderDetail->order_status->value === 'placed',
                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $orderDetail->order_status->value !== 'delivered' && $orderDetail->order_status->value !== 'served' && $orderDetail->order_status->value !== 'placed',
                            ])>
                            {{ __('modules.order.' . App\Enums\OrderStatus::from($orderDetail->order_status->value)->label()) }}
                        </span>
                    </div>

                    <div class="relative">
                        <div class="relative flex justify-between">
                            @foreach($statuses as $index => $status)
                                <div class="flex flex-col items-center">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center mb-2
                                        @if($index <= $currentIndex)
                                            bg-skin-base text-white
                                        @else
                                            bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500
                                        @endif">
                                        @switch($status)
                                            @case('placed')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                @break
                                            @case('confirmed')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                @break
                                            @case('preparing')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 7.68 7.68" xmlns="http://www.w3.org/2000/svg"><path d="M7.584 3.072 6.72 3.72v1.8a0.961 0.961 0 0 1 -0.96 0.96H1.92a0.961 0.961 0 0 1 -0.96 -0.96v-1.8L0.096 3.072a0.24 0.24 0 0 1 0.288 -0.384L0.96 3.12V2.64a0.481 0.481 0 0 1 0.48 -0.48h4.8a0.481 0.481 0 0 1 0.48 0.48v0.48l0.576 -0.432a0.24 0.24 0 0 1 0.288 0.384M4.8 1.68a0.24 0.24 0 0 0 0.24 -0.24V0.48a0.24 0.24 0 0 0 -0.48 0v0.96a0.24 0.24 0 0 0 0.24 0.24m-0.96 0a0.24 0.24 0 0 0 0.24 -0.24V0.48a0.24 0.24 0 0 0 -0.48 0v0.96a0.24 0.24 0 0 0 0.24 0.24m-0.96 0a0.24 0.24 0 0 0 0.24 -0.24V0.48a0.24 0.24 0 0 0 -0.48 0v0.96a0.24 0.24 0 0 0 0.24 0.24" stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                @break
                                            @case('out_for_delivery')
                                            @case('ready_for_pickup')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                @break
                                            @case('delivered')
                                            @case('served')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                @break
                                        @endswitch
                                    </div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        {{ __('modules.order.' . App\Enums\OrderStatus::from($status)->label()) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if(user_can('Update Order'))
                        <div class="flex justify-end items-center mt-4 space-x-2">
                            @if($orderDetail->order_status->value === 'placed')
                                <x-danger-button class="inline-flex items-center gap-2 dark:text-gray-200" wire:click="cancelOrder">
                                    <span>{{ __('modules.order.cancelOrder') }}</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </x-danger-button>
                            @endif

                            @if($currentIndex < count($statuses) - 1)
                                <x-secondary-button class="inline-flex items-center gap-2" wire:click="$set('orderStatus', '{{ $statuses[$nextIndex] }}')">
                                    <span>{{ __('modules.order.moveTo') }} {{ __('modules.order.' . App\Enums\OrderStatus::from($statuses[$nextIndex])->label()) }}</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </x-secondary-button>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
        @endif

        @if ($orderDetail)
        <div class="flex flex-col rounded ">
            <table class=" flex-1  min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th scope="col"
                            class="p-2 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                            @lang('modules.menu.itemName')
                        </th>
                        <th scope="col"
                            class="p-2 text-xs text-center text-gray-500 uppercase dark:text-gray-400">
                            @lang('modules.order.qty')
                        </th>
                        <th scope="col"
                            class="p-2 text-xs font-medium text-right text-gray-500 uppercase dark:text-gray-400">
                            @lang('modules.order.price')
                        </th>
                        <th scope="col"
                            class="p-2 text-xs font-medium text-right text-gray-500 uppercase dark:text-gray-400">
                            @lang('modules.order.amount')
                        </th>
                        @if (user_can('Delete Order') && $orderDetail->status !== 'paid')
                        <th scope="col"
                            class="p-2 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">
                            @lang('app.action')
                        </th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700" wire:key='menu-item-list-{{ microtime() }}'>

                    @forelse ($orderDetail->items->load('modifierOptions') as $key => $item)
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700" wire:key='menu-item-{{ $key . microtime() }}' wire:loading.class.delay='opacity-10'>
                        <td class="flex flex-col p-2 mr-12 lg:min-w-28">
                            <div class="text-xs text-gray-900 dark:text-white inline-flex items-center">
                                {{ $item->menuItem->item_name }}
                            </div>
                            @if (isset($item->menuItemVariation))
                            <div class="text-xs text-gray-600 dark:text-white inline-flex items-center">
                                {{ $item->menuItemVariation->variation }}
                            </div>
                            @endif
                            @if ($item->modifierOptions->isNotEmpty())
                                <div class="text-xs text-gray-600 dark:text-white mt-1">
                                    @foreach ($item->modifierOptions as $modifier)
                                        <div class="flex items-center justify-between text-xs mb-1 py-0.5 px-1 border-l-2 border-blue-500 bg-gray-200 dark:bg-gray-900 rounded-md">
                                            <span class="text-gray-900 dark:text-white">{{ $modifier->name }}</span>
                                            <span class="text-gray-600 dark:text-gray-300">{{ currency_format($modifier->price) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="p-2 text-base text-gray-900 dark:text-gray-200 whitespace-nowrap text-center">
                            {{ $item->quantity }}
                        </td>

                        <td class="p-2 text-xs font-medium text-gray-700 whitespace-nowrap dark:text-white text-right">
                            {{ currency_format($item->price) }}
                        </td>
                        <td class="p-2 text-xs font-medium text-gray-900 whitespace-nowrap dark:text-white text-right">
                            {{ currency_format($item->amount + $item->modifierOptions->sum('price')) }}
                        </td>
                        @if (user_can('Delete Order') && $orderDetail->status !== 'paid')
                        <td class="p-2 whitespace-nowrap text-right">
                            <button class="rounded text-gray-700 border p-2 dark:text-gray-300" wire:click="deleteOrderItems('{{ $item->id }}')">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                        <td class="p-2 space-x-6" colspan="5">
                            @lang('messages.noItemAdded')
                        </td>
                    </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        <div>
            <div class="h-auto p-4 mt-3 select-none text-center w-full bg-gray-50 rounded space-y-4 dark:bg-gray-700">
                <div class="flex justify-between text-gray-500 text-sm dark:text-neutral-400">
                    <div>
                        @lang('modules.order.totalItem')
                    </div>
                    <div>
                        {{ count($orderDetail->items) }}
                    </div>
                </div>
                <div class="flex justify-between text-gray-500 text-sm dark:text-neutral-400">
                    <div>
                        @lang('modules.order.subTotal')
                    </div>
                    <div>
                        {{ currency_format($orderDetail->sub_total, restaurant()->currency_id) }}
                    </div>
                </div>

                @if (!is_null($orderDetail->discount_amount))
                <div wire:key="discountAmount" class="flex justify-between text-green-500 text-sm dark:text-green-400">
                    <div>
                        @lang('modules.order.discount') @if ($orderDetail->discount_type == 'percent') ({{ rtrim(rtrim($orderDetail->discount_value), '.') }}%) @endif
                    </div>
                    <div>
                        -{{ currency_format($orderDetail->discount_amount, restaurant()->currency_id) }}
                    </div>
                </div>
                @endif

                @foreach ($extraCharges as $charge)
                <div class="flex justify-between text-gray-500 text-sm dark:text-gray-400">
                    <div class="inline-flex items-center gap-x-1">{{ $charge->charge_name }}
                        @if ($charge->charge_type == 'percent')
                            ({{ $charge->charge_value }}%)
                        @endif
                        @if($orderDetail->status !== 'paid' && user_can('Update Order'))
                        <span class="text-red-500 hover:scale-110 active:scale-100 cursor-pointer"
                            wire:click="removeExtraCharge('{{ $charge->id }}', '{{ $orderType }}')">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        @endif
                    </div>
                    <div>
                        {{ currency_format($charge->getAmount($subTotal - ($discountAmount ?? 0)), restaurant()->currency_id) }}
                    </div>
                </div>
                @endforeach

                @if ($orderDetail->tip_amount > 0)
                <div class="flex justify-between text-gray-500 text-sm dark:text-neutral-400">
                    <div>
                        @lang('modules.order.tip')
                    </div>
                    <div>
                        {{ currency_format($orderDetail->tip_amount, restaurant()->currency_id) }}
                    </div>
                </div>
                @endif

                @if ($orderType === 'delivery' && !is_null($deliveryFee))
                    <div class="flex justify-between text-gray-500 dark:text-neutral-400 text-sm">
                        <div>
                            @lang('modules.delivery.deliveryFee')
                        </div>
                        <div>
                            @if($deliveryFee > 0)
                                {{ currency_format($deliveryFee, restaurant()->currency_id) }}
                            @else
                                <span class="text-green-500 font-medium">@lang('modules.delivery.freeDelivery')</span>
                            @endif
                        </div>
                    </div>
                @endif

                @foreach ($orderDetail->taxes as $item)
                <div class="flex justify-between text-gray-500 text-sm dark:text-neutral-400">
                    <div>
                        {{ $item->tax->tax_name }} ({{ $item->tax->tax_percent }}%)
                    </div>
                    <div>
                        {{ currency_format(($item->tax->tax_percent / 100) * ($orderDetail->sub_total - ($orderDetail->discount_amount ?? 0)), restaurant()->currency_id) }}
                    </div>
                </div>
                @endforeach

                <div class="flex justify-between font-medium dark:text-neutral-300">
                    <div>
                        @lang('modules.order.total')
                    </div>
                    <div>
                        {{ currency_format($orderDetail->total, restaurant()->currency_id) }}
                    </div>
                </div>
            </div>

            <div class="h-auto pb-4 pt-3 select-none text-center w-full">
                <div class="flex gap-2">

                    @if ($orderDetail->status == 'billed' && user_can('Update Order'))
                    <button class="rounded bg-green-600 text-white  w-full p-2" wire:click='showPayment({{ $orderDetail->id }})'>
                        @lang('modules.order.addPayment')
                    </button>
                    @endif

                    @if($orderDetail->status == 'paid')
                    <a class="rounded border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 w-full p-2 mt-2 gap-x-1 inline-flex items-center justify-center"
                        href="{{ route('orders.print', $orderDetail->id) }}" target="_blank">
                        <svg class="w-6 h-6 text-current" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z"/>
                        </svg>
                        @lang('app.print')
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </div>

</div>
