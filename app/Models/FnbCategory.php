<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FnbCategory extends Model
{
    use HasFactory;

    // Menentukan nama tabel (opsional, tapi bagus untuk memastikan)
    protected $table = 'fnb_categories';

    // Kolom yang boleh diisi secara massal
    protected $fillable = ['name'];

    /**
     * Relasi: Satu Kategori memiliki banyak Produk
     */
    public function products()
    {
        return $this->hasMany(FnbProduct::class, 'fnb_category_id');
    }
}
