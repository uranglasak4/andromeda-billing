<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'closed_by',
        'pool_table_id',
        'customer_name',
        'billing_type',
        'pricing_rule_id',
        'package_id',
        'start_time',
        'end_time',
        'duration',
        'bill_price',
        'fnb_price',
        'grand_total',
        'payment_method',
        'pay_amount',
        'change_amount',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    // Relasi ke Kasir Pembuka (Shift Start)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke Kasir Penutup (Shift End / Terima Pembayaran)
    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function poolTable()
    {
        return $this->belongsTo(PoolTable::class, 'pool_table_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function pricingRule()
    {
        return $this->belongsTo(PricingRule::class, 'pricing_rule_id');
    }

    public function orderFnbs()
    {
        return $this->hasMany(OrderFnb::class, 'transaction_id');
    }
}
