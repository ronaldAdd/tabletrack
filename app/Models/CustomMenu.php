<?php

namespace App\Models;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomMenu extends Model
{
    //
    use HasFactory;
    use Notifiable;
    // use HasRestaurant;
    protected $guarded = [];

}
