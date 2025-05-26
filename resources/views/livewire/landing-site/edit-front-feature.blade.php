<div>
    <form wire:submit="editFrontFeature">
        <div class="space-y-4">

            <div class="mb-4">
                <label for="languageSettingid" class="block text-sm font-medium text-gray-700">
                    @lang('modules.settings.language')
                </label>
                <select id="languageSettingid"
                        wire:model.live="languageSettingid"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">@lang('app.selectLanguage')</option>
                    @foreach($languageEnable as $label)
                        <option value="{{ $label->id }}">{{ $label->language_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="featureTitle" class="block text-sm font-medium text-gray-700">
                    @lang('modules.settings.featureTitle')
                </label>
                <input type="text"
                       id="featureTitle"
                       wire:model="featureTitle"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <x-input-error for="featureTitle" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <label for="featureDescription" class="block text-sm font-medium text-gray-700">
                    @lang('modules.settings.featureDescription')
                </label>
                <textarea id="featureDescription"
                          wire:model="featureDescription"
                          rows="3"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                <x-input-error for="featureDescription" class="mt-2" />
            </div>

            <!-- Feature Image -->
            <div class="sm:col-span-2">
                <label for="featureImage" class="block text-sm font-medium text-gray-700">
                    @lang('modules.settings.featureImage')
                </label>
                <input type="file"
                       id="featureImage"
                       wire:model="featureImage"
                       class="mt-1 block w-full text-sm text-gray-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:text-sm file:font-semibold
                              file:bg-indigo-50 file:text-indigo-700
                              hover:file:bg-indigo-100">
                <x-input-error for="featureImage" class="mt-2" />
            </div>

            <!-- Image Preview -->
            @if ($existingImageUrl)
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">
                        @lang('modules.settings.preview')
                    </label>
                    <img src="{{ $existingImageUrl }}" alt="Feature Image" class="w-32 h-32 object-cover rounded mt-2">
                </div>
                @else
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">
                        @lang('modules.settings.preview')
                    </label>
                    <img src="{{ $image_url ?? asset('images/default-feature.png') }}" alt="Feature Image" class="w-32 h-32 object-cover rounded mt-2">
                </div>
            @endif
        </div>

        <div class="flex w-full pb-4 space-x-4 mt-6">
            <x-button>@lang('app.save')</x-button>
            <x-button-cancel wire:click="$dispatch('hideEditFeature')" wire:loading.attr="disabled">
                @lang('app.cancel')
            </x-button-cancel>
        </div>
    </form>
</div>
