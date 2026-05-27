<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'pool_table_id', 'customer_name', 'billing_type',
        'pricing_rule_id', 'package_id', 'start_time', 'end_time',
        'duration', 'total_price', 'status'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function poolTable() {
        return $this->belongsTo(PoolTable::class);
    }

    public function package() {
        return $this->belongsTo(Package::class);
    }

    public function pricingRule() {
        return $this->belongsTo(PricingRule::class);
    }

    public function orderFnbs() {
    return $this->hasMany(OrderFnb::class, 'transaction_id');
    }
}
