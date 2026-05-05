<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitingList extends Model
{
    use HasFactory;

    // Nama tabel di database (opsional jika sudah jamak/plural)
    protected $table = 'waiting_lists';

    // Kolom yang boleh diisi secara massal (sesuai gambar HeidiSQL Anda)
    protected $fillable = [
        'customer_name',
        'phone_number',
        'status'
    ];
}