<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CLocalInventory extends Model
{
    protected $table = 'c_local_inventories';

    protected $fillable = [
        'companie_id',
        'sku',
        'product',
        'unit',
        'stock',
        'is_active',
    ];

    public function lots()
    {
        return $this->hasMany(DLocalInventoryLot::class, 'local_inventory_id');
    }
}
