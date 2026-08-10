<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderFnb extends Model
{
    use HasFactory;

    protected $table = 'order_fnbs';

    protected $fillable = [
        'transaction_id',
        'fnb_product_id',
        'customer_name',
        'stock',
        'price',
        'subtotal',
        'payment_status',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function fnbProduct()
    {
        return $this->belongsTo(FnbProduct::class, 'fnb_product_id');
    }
}
