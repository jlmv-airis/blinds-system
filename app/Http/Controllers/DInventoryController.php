<?php

namespace App\Http\Controllers;

use App\Models\DInventory;
use Illuminate\Http\Request;

class DInventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $inventory = DInventory::select('d_inventories.id','d_inventories.id as inventory_id','c_inventory_products.sku','c_inventory_products.product','c_inventory_products.category_id','c_categories.category','c_inventory_products.unit_id','c_units.unit','c_inventory_products.cost','c_inventory_products.provider_id','c_providers.provider','d_inventories.warehouse_id','c_warehouses.warehouse','c_warehouses.company_id','c_companies.company','d_inventories.stock','d_inventories.section','d_inventories.lot','d_inventories.location_id','d_warehouse_locations.location','d_warehouse_locations.rack','d_warehouse_locations.x','d_warehouse_locations.y','d_warehouse_locations.level_id','c_warehouse_levels.level','c_warehouse_levels.img_level','d_inventories.created_at')
            ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
            ->join('c_categories','c_categories.id','c_inventory_products.category_id')
            ->join('c_units','c_units.id','c_inventory_products.unit_id')
            ->join('c_providers','c_providers.id','c_inventory_products.provider_id')
            ->join('c_warehouses','c_warehouses.id','d_inventories.warehouse_id')
            ->join('d_warehouse_locations','d_warehouse_locations.id','d_inventories.location_id')
            ->join("c_warehouse_levels", "c_warehouse_levels.id", "d_warehouse_locations.level_id")
            ->join("c_companies", "c_companies.id", "c_warehouses.company_id")
            ->where('d_inventories.is_active',1)
            ->get();
            return response()->json([
                'inventory' => $inventory,
            ], 200 );
            return ;
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }

    public function indexAll()
    {
        try {
            $allInventory = DInventory::select('d_inventories.id','d_inventories.id as inventory_id','c_inventory_products.sku','c_inventory_products.product','c_inventory_products.category_id','c_categories.category','c_inventory_products.unit_id','c_units.unit','c_inventory_products.cost','c_inventory_products.provider_id','c_providers.provider','d_inventories.warehouse_id','c_warehouses.warehouse','c_warehouses.company_id','c_companies.company','d_inventories.stock','d_inventories.section','d_inventories.lot','d_inventories.location_id','d_warehouse_locations.location','d_warehouse_locations.rack','d_warehouse_locations.x','d_warehouse_locations.y','d_warehouse_locations.level_id','c_warehouse_levels.level','c_warehouse_levels.img_level','d_inventories.created_at')
            ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
            ->join('c_categories','c_categories.id','c_inventory_products.category_id')
            ->join('c_units','c_units.id','c_inventory_products.unit_id')
            ->join('c_providers','c_providers.id','c_inventory_products.provider_id')
            ->join('c_warehouses','c_warehouses.id','d_inventories.warehouse_id')
            ->join('d_warehouse_locations','d_warehouse_locations.id','d_inventories.location_id')
            ->join("c_warehouse_levels", "c_warehouse_levels.id", "d_warehouse_locations.level_id")
            ->join("c_companies", "c_companies.id", "c_warehouses.company_id")
            ->get();
            return response()->json([
                'allInventory' => $allInventory,
            ], 200 );
            return ;
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DInventory  $dInventory
     * @return \Illuminate\Http\Response
     */
    public function show(DInventory $dInventory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DInventory  $dInventory
     * @return \Illuminate\Http\Response
     */
    public function edit(DInventory $dInventory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DInventory  $dInventory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DInventory $dInventory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DInventory  $dInventory
     * @return \Illuminate\Http\Response
     */
    public function destroy(DInventory $dInventory)
    {
        //
    }
}
