<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoolTable extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['table_number', 'relay_channel', 'status', 'is_active'];

    public function transactions() {
        return $this->hasMany(Transaction::class, 'pool_table_id');
    }
}
