<?php

namespace App\Models;

// Perhatikan bagian ini, harus di-import!
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable // GANTI 'Model' menjadi 'Authenticatable'
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function transactions() {
        return $this->hasMany(Transaction::class);
    }
}