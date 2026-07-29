<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'day_type',
        'active_from',
        'active_to',
        'duration_type',
        'duration_value',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function fnbProducts()
    {
        return $this->belongsToMany(FnbProduct::class, 'package_fnb_product')
            ->withPivot('stock')
            ->withTimestamps();
    }
}
