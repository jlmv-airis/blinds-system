<?php

namespace App\Http\Controllers;

use App\Models\CWarehouseLevel;
use Illuminate\Http\Request;

class CWarehouseLevelController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CWarehouseLevel  $cWarehouseLevel
     * @return \Illuminate\Http\Response
     */

    public function show(CWarehouseLevel $cWarehouseLevel, $warehouse_id)
    {
        try {
            $levels = CWarehouseLevel::where("warehouse_id", "=", $warehouse_id)->get();
            return response()->json(
                [
                    "success" => true,
                    "levels" => $levels,
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
     * @param  \App\Models\CWarehouseLevel  $cWarehouseLevel
     * @return \Illuminate\Http\Response
     */
    public function edit(CWarehouseLevel $cWarehouseLevel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CWarehouseLevel  $cWarehouseLevel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CWarehouseLevel $cWarehouseLevel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CWarehouseLevel  $cWarehouseLevel
     * @return \Illuminate\Http\Response
     */
    public function destroy(CWarehouseLevel $cWarehouseLevel)
    {
        //
    }
}
