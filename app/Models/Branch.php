<?php

namespace App\Models;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\GeneratesQrCode;


class Branch extends Model
{
    use HasFactory;
    use HasRestaurant;
    use GeneratesQrCode;

    protected $guarded = ['id'];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function getQrCodeFileName(): string
    {
        return 'qrcode-branch-' . $this->id . '-' . $this->restaurant->id . '.png';
    }

    public function getRestaurantId(): int
    {
        return $this->restaurant_id;
    }

    public function generateQrCode()
    {
        // $this->createQrCode(route('table_order', [$this->getRestaurantId()]) . '?branch=' . $this->id);
        $this->createQrCode(route('table_order', [$this->restaurant_id]) . '?branch=' . $this->id . '&hash=' . $this->restaurant->hash . '&from_qr=1');
    }

    public function deliverySetting()
    {
        return $this->hasOne(BranchDeliverySetting::class , 'branch_id');
    }

    public function deliveryFeeTiers()
    {
        return $this->hasMany(DeliveryFeeTier::class);
    }

    public function qRCodeUrl(): Attribute
    {
        return Attribute::get(fn(): string => asset_url_local_s3('qrcodes/' . $this->getQrCodeFileName()));
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
