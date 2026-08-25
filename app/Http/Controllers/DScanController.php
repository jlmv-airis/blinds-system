<?php

namespace App\Http\Controllers;

use App\Models\DScan;
use Illuminate\Http\Request;

class DScanController extends Controller
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
        //Verificamos que el lote no este dupolicado
        $duplicateLot = DScan::select('lot')->where('lot',$request->lot)->first();
        if(is_null($duplicateLot)) {

            $DScan = new DScan;
            $DScan->scan_id     = $request->scan_id;
            $DScan->location_id = $request->location_id;
            $DScan->lot         = $request->lot;
            $DScan->save();
            $detailScan = DScan::select('d_scans.id','d_scans.scan_id','d_scans.lot','d_inventories.stock','d_scans.location_id','d_warehouse_locations.location','d_warehouse_locations.rack','d_warehouse_locations.warehouse_id','c_warehouses.warehouse','d_scans.created_at')
            ->where('d_scans.id','=',$DScan->id)
            ->join('d_inventories','d_inventories.lot','d_scans.lot')
            ->join('d_warehouse_locations','d_warehouse_locations.id','d_scans.location_id')
            ->join('c_warehouses','c_warehouses.id','d_warehouse_locations.warehouse_id')
            ->first();
            return response()->json([
                "success" => true,
                'is_duplicate' => false,
                "detailScan" => $detailScan,
            ],200);
        } else {
            return response()->json([
                'success'      => true,
                'is_duplicate' => true,
            ], 200 );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DScan  $dScan
     * @return \Illuminate\Http\Response
     */
    public function show(DScan $dScan, $id)
    {
        //
        try {
            $detailsScan = DScan::select('d_scans.id','d_scans.scan_id','d_scans.lot','d_inventories.stock','d_scans.location_id','d_warehouse_locations.location','d_warehouse_locations.rack','d_warehouse_locations.warehouse_id','c_warehouses.warehouse','d_scans.created_at')
            ->where('scan_id','=',$id)
            ->join('d_inventories','d_inventories.lot','d_scans.lot')
            ->join('d_warehouse_locations','d_warehouse_locations.id','d_scans.location_id')
            ->join('c_warehouses','c_warehouses.id','d_warehouse_locations.warehouse_id')
            ->get();
            return   response()->json([
                'success'    => true ,
                'detailsScan' => $detailsScan,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return   response()->json([
                'success'    => false ,
                "error" => $th->getMessage(),
            ], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DScan  $dScan
     * @return \Illuminate\Http\Response
     */
    public function edit(DScan $dScan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DScan  $dScan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DScan $dScan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DScan  $dScan
     * @return \Illuminate\Http\Response
     */
    public function destroy(DScan $dScan)
    {
        //
    }
}
