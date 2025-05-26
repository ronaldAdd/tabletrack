<?php

namespace App\Livewire\Forms;

use App\Helper\Files;
use App\Models\ItemCategory;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemVariation;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddMenuItem extends Component
{

    use WithFileUploads, LivewireAlert;

    protected $listeners = ['refreshCategories'];

    public $inputs = [];
    public $i = 0;
    public $showItemPrice = true;
    public $showMenuCategoryModal = false;
    public $hasVariations = false;
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
    public $preparationTime;
    public bool $isAvailable = true;
    public $translationNames = [];
    public $translationDescriptions = [];
    public $currentLanguage;
    public $languages = [];
    public $globalLocale;

    public function mount()
    {
        $this->languages = languages()->pluck('language_name', 'language_code')->toArray();
        $this->translationNames = array_fill_keys(array_keys($this->languages), '');
        $this->translationDescriptions = array_fill_keys(array_keys($this->languages), '');
        $this->globalLocale = global_setting()->locale;
        $this->currentLanguage = $this->globalLocale;
        $this->categoryList = ItemCategory::all();
        $this->menus = Menu::all();
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


        $menuItem = MenuItem::create([
            'item_name' => $this->translationNames[$this->globalLocale],
            'price' => (!$this->hasVariations) ? $this->itemPrice : 0,
            'item_category_id' => $this->itemCategory,
            'description' => $this->translationDescriptions[$this->globalLocale],
            'is_available' => (bool) $this->isAvailable,
            'type' => $this->itemType,
            'menu_id' => $this->menu,
            'preparation_time' => $this->preparationTime
        ]);

        $translations = collect($this->translationNames)
            ->filter(fn($name, $locale) => !empty($name) || !empty($this->translationDescriptions[$locale]))
            ->map(fn($name, $locale) => [
                'locale' => $locale,
                'item_name' => $name,
                'description' => $this->translationDescriptions[$locale]
            ])->values()->all();

        $menuItem->translations()->createMany($translations);

        if ($this->itemImage) {
            $menuItem->update([
                'image' => Files::uploadLocalOrS3($this->itemImage, 'item', width: 350),
            ]);
        }

        if ($this->hasVariations) {
            foreach ($this->variationName as $key => $value) {
                if (!empty($value)) {
                    $this->validate([
                        'variationPrice.' . $key => 'required|numeric'
                    ], [
                        'variationPrice.' . $key . '.required' => __('validation.variationPriceRequired'),
                    ]);

                    MenuItemVariation::create([
                        'variation' => $value,
                        'price' => $this->variationPrice[$key],
                        'menu_item_id' => $menuItem->id
                    ]);
                }
            }
        }

        $this->resetForm();

        $this->dispatch('menuItemAdded');
        $this->dispatch('refreshCategories');

        $this->alert('success', __('messages.menuItemAdded'), [
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
        $this->itemCategory = '';
        $this->itemPrice = '';
        $this->itemDescription = '';
        $this->itemType = 'veg';
        $this->itemImage = null;
        $this->preparationTime = null;
        $this->variationName = [];
        $this->variationPrice = [];
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
        return view('livewire.forms.add-menu-item');
    }
}
