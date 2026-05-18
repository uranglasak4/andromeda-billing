<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FnbProduct extends Model
{
    use HasFactory;

    protected $table = 'fnb_products';

    // Daftar kolom yang wajib diisi
    protected $fillable = ['fnb_category_id', 'name', 'price', 'hpp', 'stock', 'min_stock'];

    /**
     * Relasi: Satu Produk dimiliki oleh satu Kategori
     */
    public function category()
    {
        return $this->belongsTo(FnbCategory::class, 'fnb_category_id');
    }
}
