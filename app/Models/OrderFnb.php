<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderFnb extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'fnb_product_id',
        'customer_name',
        'qty',
        'price',
        'subtotal',
        'payment_status'
    ];

    public function transaction() {
        return $this->belongsTo(Transaction::class);
    }

    public function fnbProduct() {
        return $this->belongsTo(FnbProduct::class, 'fnb_product_id');
    }
}
