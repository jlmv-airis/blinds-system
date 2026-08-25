<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DLocalInventoryLot extends Model
{
    protected $table = 'd_local_inventory_lots';

    public $timestamps = false;

    protected $fillable = [
        'local_inventory_id',
        'lot',
        'stock',
        'status',
    ];
}
