<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItemTranslation extends Model
{
    protected $guarded = ['id'];
    public $timestamps = false;

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
