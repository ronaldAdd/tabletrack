<div>
    <form wire:submit.prevent="submitDynamicWebPageForm">

        <x-help-text class="mb-6">@lang('modules.settings.addMoreWebPageHelp')</x-help-text>

        <div class="space-y-4">
            <div>
                <x-label for="menu_name" value="{{ __('modules.settings.menuName') }}" />
                <x-input id="menu_name" class="block mt-1 w-full" type="text"
                    placeholder="{{ __('placeholders.menuNamePlaceHolder') }}" wire:model.live="menuName" wire:keyup="generateSlug" required />
                <x-input-error for="menu_name" class="mt-2" />
                @error('menuName')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <x-label for="menu_slug" value="{{ __('modules.settings.menuSlug') }}" />
                <x-input id="menu_slug" class="block mt-1 w-full" type="text"
                    placeholder="{{ __('placeholders.menuSlugPlaceHolder') }}" wire:model="menuSlug" required />
                <x-input-error for="menu_slug" class="mt-2" />
                @error('menuSlug')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <x-label for="menuContent" value="{{ __('modules.settings.menuContent') }}" />

                <input x-ref="menuContent" id="menuContent" name="menuContent" wire:model="menuContent"
                    value="{{ $menuContent }}" type="hidden" />

                <div wire:ignore class="mt-2">
                    <trix-editor class="trix-content text-sm" input="menuContent" data-gramm="false"
                        placeholder="{{ __('placeholders.menuContentPlaceHolder') }}"
                        x-on:trix-change="$wire.set('menuContent', $event.target.value)" x-ref="trixEditor"  x-init="
                            window.addEventListener('reset-trix-editor', () => {
                                $refs.trixEditor.editor.loadHTML('');
                            });" >
                    </trix-editor>
                </div>
                <x-input-error for="menuContent" class="mt-2" />
                @error('menuContent')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <x-button>@lang('app.save')</x-button>
            </div>
        </div>
    </form>
</div>
