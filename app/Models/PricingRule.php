<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
    'name',
    'day_type',
    'active_days', // Tambahkan ini
    'start_time',
    'end_time',
    'price_per_hour',
    'min_charge'   // Tambahkan ini
];

    public function transactions() {
        return $this->hasMany(Transaction::class);
    }
}
