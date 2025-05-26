<?php

namespace App\Livewire\LandingSite;

use App\Models\FrontDetail;
use App\Models\LanguageSetting;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class SaveHeading extends Component
{
    use LivewireAlert;

    public $languageSettingid;
    public $faqHeading;
    public $faqDescription;
    public $frontDetail;
    public $contents = [];

    public function mount()
    {
        $this->loadLanguageContents();

        if (!$this->languageSettingid) {
            $defaultLanguage = LanguageSetting::where('active', 1)->first();
            $this->languageSettingid = $defaultLanguage?->id;
        }

        $this->loadSelectedLanguageContent();
    }

    public function loadLanguageContents()
    {
        $frontDetails = FrontDetail::all();
        foreach ($frontDetails as $detail) {
            $this->contents[$detail->language_setting_id] = [
                'faq_heading' => $detail->faq_heading,
                'faq_description' => $detail->faq_description,
            ];
        }
    }

    public function updatedLanguageSettingid()
    {
        $this->loadSelectedLanguageContent();
    }

    public function loadSelectedLanguageContent()
    {
        if (isset($this->contents[$this->languageSettingid])) {
            $this->faqHeading = $this->contents[$this->languageSettingid]['header_title'] ?? '';
            $this->faqDescription = $this->contents[$this->languageSettingid]['header_description'] ?? '';
        } else {
            $this->faqHeading = '';
            $this->faqDescription = '';
        }
    }

    public function saveHeading()
    {
        $this->validate([
            'languageSettingid' => 'required',
            'faqHeading' => 'required',
            'faqDescription' => 'required',
        ]);

        $frontDetail = FrontDetail::updateOrCreate(
            [
                'language_setting_id' => $this->languageSettingid,
            ],
            [
                'faq_heading' => $this->faqHeading,
                'faq_description' => $this->faqDescription,
            ]
        );

        $this->alert('success', __('messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

}
