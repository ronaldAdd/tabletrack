<?php

namespace App\Models;

use Carbon\Language;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;


class FrontReviewSetting extends Model
{
    protected $table = 'front_review_settings';

    protected $guarded = ['id'];


    public function language()
    {
          return $this->belongsTo(LanguageSetting::class, 'language_setting_id');
    }


}
