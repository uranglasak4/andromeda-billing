<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'day_type', 'start_time', 'end_time', 'price_per_hour'];

    public function transactions() {
        return $this->hasMany(Transaction::class);
    }
}