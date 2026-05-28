<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitingList extends Model
{
    use HasFactory;

    protected $table = 'waiting_lists';

    protected $fillable = [
        'customer_name',
        'phone_number',
        'otp',
        'tipe',
        'status'
    ];
}
