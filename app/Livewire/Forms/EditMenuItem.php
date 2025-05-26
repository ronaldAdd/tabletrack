<?php

namespace App\Livewire\Forms;

use App\Models\Menu;
use App\Helper\Files;
use Livewire\Component;
use App\Models\MenuItem;
use App\Models\ItemCategory;
use Livewire\WithFileUploads;
use App\Models\MenuItemVariation;
use App\Scopes\AvailableMenuItemScope;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class EditMenuItem extends Component
{
    use WithFileUploads, LivewireAlert;

    protected $listeners = ['refreshCategories'];

    public $inputs = [];
    public int $i = 0;
    public bool $showItemPrice = true;
    public bool $hasVariations = false;
    public $menu;
    public $itemName;
    public $itemCategory;
    public $itemPrice;
    public $itemDescription;
    public $itemType = 'veg';
    public $itemImage;
    public $categoryList = [];
    public $menus = [];
    public $variationName = [];
    public $variationPrice = [];
    public $menuItem;
    public $preparationTime;
    public $isAvailable;
    public $showMenuCategoryModal = false;
    public $translationNames = [];
    public $translationDescriptions = [];
    public $originalTranslations = [];
    public $currentLanguage;
    public $languages = [];
    public $globalLocale;

    public function mount()
    {
        $this->languages = languages()->pluck('language_name', 'language_code')->toArray();
        $this->translationNames = array_fill_keys(array_keys($this->languages), '');
        $this->translationDescriptions = array_fill_keys(array_keys($this->languages), '');
        $this->globalLocale = auth()->user()->locale;
        $this->currentLanguage = $this->globalLocale;
        $this->categoryList = ItemCategory::all();
        $this->menus = Menu::all();
        $this->menu = $this->menuItem->menu_id;
        $this->itemCategory = $this->menuItem->item_category_id;
        $this->itemPrice = $this->menuItem->price;
        $this->preparationTime = $this->menuItem->preparation_time;
        $this->itemType = $this->menuItem->type;
        $this->hasVariations = ($this->menuItem->variations->count() > 0);
        $this->showItemPrice = ($this->menuItem->variations->count() == 0);
        $this->isAvailable = $this->menuItem->is_available;

        foreach ($this->menuItem->translations as $translation) {
            $this->translationNames[$translation->locale] = $translation->item_name;
            $this->translationDescriptions[$translation->locale] = $translation->description;

            $this->originalTranslations[$translation->locale] = [
                'item_name' => $translation->item_name,
                'description' => $translation->description
            ];
        }

        $this->translationNames[$this->globalLocale] = $this->itemName ?: $this->menuItem->item_name;
        $this->translationDescriptions[$this->globalLocale] = $this->itemDescription ?: $this->menuItem->description;

        foreach ($this->menuItem->variations as $key => $value) {
            $this->variationName[$key] = $value->variation;
            $this->variationPrice[$key] = $value->price;
            $this->i = $key + 1;
            array_push($this->inputs, $this->i);
        }

        $this->updatedCurrentLanguage();
        $this->updateTranslation();
    }

    public function addMoreField($i)
    {
        $i = $i + 1;
        $this->i = $i;
        array_push($this->inputs, $i);

        if (count($this->inputs) > 0) {
            $this->showItemPrice = false;
        }
    }

    public function removeField($i)
    {
        unset($this->inputs[$i]);
    }

    public function checkVariations()
    {
        if ($this->hasVariations) {
            $this->showItemPrice = false;

            if (count($this->inputs) == 0) {
                $this->addMoreField($this->i);
            }
        } else {
            $this->showItemPrice = true;
            $this->inputs = [];
            $this->variationName = [];
            $this->variationPrice = [];
            $this->i = 0;
        }
    }

    public function refreshCategories()
    {
        $this->categoryList = ItemCategory::all();
    }

    public function submitForm()
    {
        $this->validate([
            'translationNames.' . $this->globalLocale => 'required',
            'itemPrice' => 'required_if:hasVariations,false',
            'itemCategory' => 'required',
            'menu' => 'required',
            'isAvailable' => 'required|boolean',
        ],[
            'translationNames.' . $this->globalLocale . '.required' => __('validation.itemNameRequired', ['language' => $this->languages[$this->globalLocale]]),
        ]);


        MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->where('id', $this->menuItem->id)->update([
            'item_name' => $this->translationNames[$this->globalLocale],
            'price' => (!$this->hasVariations) ? $this->itemPrice : 0,
            'item_category_id' => $this->itemCategory,
            'description' => $this->translationDescriptions[$this->globalLocale],
            'type' => $this->itemType,
            'preparation_time' => $this->preparationTime,
            'menu_id' => $this->menu,
            'is_available' => $this->isAvailable,
        ]);

        // Efficiently update translations - only update what has changed
        foreach ($this->translationNames as $locale => $name) {
            $description = $this->translationDescriptions[$locale];

            // Skip empty translations
            if (empty($name) && empty($description)) {
                continue;
            }

            $isNew = !isset($this->originalTranslations[$locale]);
            $hasChanged = $isNew ||
                $this->originalTranslations[$locale]['item_name'] !== $name ||
                $this->originalTranslations[$locale]['description'] !== $description;

            if ($hasChanged) {
                if ($isNew) {
                    // Create new translation
                    $this->menuItem->translations()->create([
                        'locale' => $locale,
                        'item_name' => $name,
                        'description' => $description
                    ]);
                } else {
                    // Update existing translation
                    $this->menuItem->translations()
                        ->where('locale', $locale)
                        ->update([
                            'item_name' => $name,
                            'description' => $description
                        ]);
                }
            }
        }

        if ($this->itemImage) {
            $this->menuItem->update([
                'image' => Files::uploadLocalOrS3($this->itemImage, 'item', width: 350),
            ]);
        }

        if ($this->hasVariations) {
            MenuItemVariation::where('menu_item_id', $this->menuItem->id)->delete();

            foreach ($this->inputs as $key => $value) {
                MenuItemVariation::create([
                    'variation' => $this->variationName[$key],
                    'price' => $this->variationPrice[$key],
                    'menu_item_id' => $this->menuItem->id
                ]);
            }
        }

        $this->dispatch('hideEditMenuItem');
        $this->resetForm();
        $this->clearTranslationCache();

        $this->alert('success', __('messages.menuItemUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function resetForm()
    {
        $this->itemName = '';
        $this->menu = '';
        $this->translationNames = array_fill_keys(array_keys($this->languages), '');
        $this->translationDescriptions = array_fill_keys(array_keys($this->languages), '');
        $this->originalTranslations = [];
        $this->itemCategory = '';
        $this->itemPrice = '';
        $this->itemDescription = '';
        $this->itemType = 'veg';
        $this->itemImage = null;
        $this->preparationTime = null;
        $this->variationName = [];
        $this->variationPrice = [];
    }

    public function clearTranslationCache()
    {
        foreach (array_keys($this->languages) as $locale) {
            cache()->forget("menu_item_{$this->menuItem->id}_item_name_{$locale}");
            cache()->forget("menu_item_{$this->menuItem->id}_description_{$locale}");
        }
    }


    public function updateTranslation()
    {
        $this->translationNames[$this->currentLanguage] = $this->itemName;
        $this->translationDescriptions[$this->currentLanguage] = $this->itemDescription;
    }

    public function updatedCurrentLanguage()
    {
        $this->itemName = $this->translationNames[$this->currentLanguage];
        $this->itemDescription = $this->translationDescriptions[$this->currentLanguage];
    }

    public function showMenuCategoryModal()
    {
        $this->dispatch('showMenuCategoryModal');
    }

    public function render()
    {
        return view('livewire.forms.edit-menu-item');
    }
}
