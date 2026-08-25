<?php

namespace App\Http\Controllers;

use App\Models\EWarehouseLocation;
use Illuminate\Http\Request;

class EWarehouseLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $locations = EWarehouseLocation::select('e_warehouse_locations.id','e_warehouse_locations.location','e_warehouse_locations.warehouse_id','c_warehouses.warehouse','e_warehouse_locations.description','e_warehouse_locations.created_at')
            ->join('c_warehouses','c_warehouses.id','=','e_warehouse_locations.warehouse_id')
            ->where('e_warehouse_locations.is_active','=','1')
            ->get();

            return response()->json(
                [
                    "success" => true,
                    "locations" => $locations,
                ],
                200
            );
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(
                [
                    "success" => false,
                    "error" => $th->getMessage(),
                ],
                400
            );
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
     * @param  \App\Models\EWarehouseLocation  $eWarehouseLocation
     * @return \Illuminate\Http\Response
     */
    public function show(EWarehouseLocation $eWarehouseLocation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EWarehouseLocation  $eWarehouseLocation
     * @return \Illuminate\Http\Response
     */
    public function edit(EWarehouseLocation $eWarehouseLocation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EWarehouseLocation  $eWarehouseLocation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EWarehouseLocation $eWarehouseLocation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EWarehouseLocation  $eWarehouseLocation
     * @return \Illuminate\Http\Response
     */
    public function destroy(EWarehouseLocation $eWarehouseLocation)
    {
        //
    }
}
