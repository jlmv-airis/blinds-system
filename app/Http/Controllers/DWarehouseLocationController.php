<?php

namespace App\Http\Controllers;

use App\Models\DWarehouseLocation;
use Illuminate\Http\Request;

class DWarehouseLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        try {

            // buscamos que el SKU no este repetido
            $duplicateSKU = DWarehouseLocation::select('location')->where('location',$request->location)->where('rack',$request->rack)->where('level_id',$request->level_id)->first();
            if(is_null($duplicateSKU)) {
                $DWarehouseLocation = new DWarehouseLocation();
                $DWarehouseLocation->warehouse_id = $request->warehouse_id;
                $DWarehouseLocation->rack         = $request->rack;
                $DWarehouseLocation->location     = $request->location;
                $DWarehouseLocation->level_id     = $request->level_id;
                $DWarehouseLocation->x            = $request->x;
                $DWarehouseLocation->y            = $request->y;
                $DWarehouseLocation->save();

                $location = DWarehouseLocation::select("d_warehouse_locations.id","d_warehouse_locations.rack","d_warehouse_locations.location","d_warehouse_locations.x","d_warehouse_locations.y","c_warehouse_levels.level","d_warehouse_locations.level_id","c_warehouse_levels.img_level")
                ->join("c_warehouse_levels", "c_warehouse_levels.id", "=", "d_warehouse_locations.level_id")
                ->where("d_warehouse_locations.id", "=", $DWarehouseLocation->id)
                ->first();
                return response()->json([
                    "success" => true,
                    'is_duplicate' => false,
                    "location" => $location,
                ],200);
            } else {
                return response()->json([
                    'success'      => true,
                    'is_duplicate' => true,
                ], 200 );
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                    "success" => false,
                    "error" => $th->getMessage(),
                ],400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DWarehouseLocation  $dWarehouseLocation
     * @return \Illuminate\Http\Response
     */
    public function show(DWarehouseLocation $dWarehouseLocation, $warehouse_id)
    {
        try {
            $locations = DWarehouseLocation::select("d_warehouse_locations.id","d_warehouse_locations.rack","d_warehouse_locations.location","d_warehouse_locations.x","d_warehouse_locations.y","c_warehouse_levels.level","d_warehouse_locations.level_id","c_warehouse_levels.img_level")
            ->join("c_warehouse_levels", "c_warehouse_levels.id", "=", "d_warehouse_locations.level_id")
            ->where("d_warehouse_locations.warehouse_id", "=", $warehouse_id)
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
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DWarehouseLocation  $dWarehouseLocation
     * @return \Illuminate\Http\Response
     */
    public function edit(DWarehouseLocation $dWarehouseLocation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DWarehouseLocation  $dWarehouseLocation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DWarehouseLocation $dWarehouseLocation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DWarehouseLocation  $dWarehouseLocation
     * @return \Illuminate\Http\Response
     */
    public function destroy(DWarehouseLocation $dWarehouseLocation)
    {
        //
    }
}
