<?php

namespace App\Http\Controllers;

use App\Models\DPurchase;
use App\Models\EScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EScanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        try {
            $scans = EScan::select('e_scans.id','e_scans.scan','e_scans.purchase_id','e_scans.total_records','c_erp_info_users.user_id','c_erp_info_users.short_name','e_scans.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_scans.user_id')
            ->get();
            return   response()->json([
                'success'    => true ,
                'scans' => $scans,
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
            // NAME SCAN
            $scan_name = 'SCAN-PO-'.self::createIDOrder($request->purchase_id);
            $DPurchase = DPurchase::select(DB::raw('COUNT(*) as totalregs'))->where('purchase_id',$request->purchase_id)->first();
            $EScan = new EScan;
            $EScan->scan = $scan_name;
            $EScan->purchase_id = $request->purchase_id;
            $EScan->total_records = $DPurchase->totalregs;
            $EScan->user_id = $request->user_id;
            $EScan->save();
            $scan = EScan::select('e_scans.id','e_scans.scan','e_scans.purchase_id','e_scans.total_records','c_erp_info_users.user_id','c_erp_info_users.short_name','e_scans.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_scans.user_id')
            ->where('e_scans.id','=',$EScan->id)
            ->first();
            return   response()->json([
                'success'    => true ,
                'scan' => $scan,
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
     * Display the specified resource.
     *
     * @param  \App\Models\EScan  $eScan
     * @return \Illuminate\Http\Response
     */
    public function show(EScan $eScan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EScan  $eScan
     * @return \Illuminate\Http\Response
     */
    public function edit(EScan $eScan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EScan  $eScan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EScan $eScan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EScan  $eScan
     * @return \Illuminate\Http\Response
     */
    public function destroy(EScan $eScan)
    {
        //
    }

    private function createIDOrder($id) {
        $idOrder = $id;
        if($idOrder < 10) {
            $idOrder = '00000'.$idOrder;
        } else if($idOrder < 100 ) {
            $idOrder = '0000'.$idOrder;
        } else if($idOrder < 1000 ) {
            $idOrder = '000'.$idOrder;
        } else if($idOrder < 10000) {
            $idOrder = '00'.$idOrder;
        } else if($idOrder < 100000 ) {
            $idOrder = '0'.$idOrder;
        }
        return $idOrder;
    }
}
