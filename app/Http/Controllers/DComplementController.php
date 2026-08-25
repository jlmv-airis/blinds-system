<?php

namespace App\Http\Controllers;

use App\Models\DComplement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DComplementController extends Controller
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
        // try {
            $DComplement                        = new DComplement();
            $DComplement->user_id               = $request->user_id;
            $DComplement->client_id             = $request->client_id;
            $DComplement->type_complement_id    = $request->type_complement_id;
            $DComplement->quantity              = (INT)$request->valueAssign === 1 ? $request->quantity : ( (INT)0 - (INT)$request->quantity) ;
            $DComplement->reason                = $request->reason;
            $DComplement->save();
            //
            $complementCatalogs = DComplement::select(DB::raw('CASE type_complement_id WHEN 1 THEN SUM(quantity) END AS catalog '))->where('client_id',$request->client_id)->where('type_complement_id',1)->groupBy('client_id')->first();
            $complementExhi = DComplement::select(DB::raw('CASE type_complement_id WHEN 2 THEN SUM(quantity) END AS exhi '))->where('client_id',$request->client_id)->where('type_complement_id',2)->groupBy('client_id')->first();
            $complementHistory = DComplement::select('d_complements.type_complement_id','d_complements.quantity','d_complements.reason','d_complements.user_id','c_erp_info_users.short_name as created_name','d_complements.created_at')->join('c_erp_info_users','c_erp_info_users.user_id','d_complements.user_id')->where('client_id',$request->client_id)->get();
            $complementCatalogstotal = 0;
            $complementExhitotal = 0;
            if($complementCatalogs !== null ) { $complementCatalogstotal = $complementCatalogs->catalog; }
            if($complementExhi !== null ) { $complementExhitotal = $complementExhi->exhi; }
            return response()->json([
                'success'               =>  true ,
                'complementCatalogs'    => $complementCatalogstotal,
                'complementExhi'        => $complementExhitotal,
                'complementHistory'     => $complementHistory,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DComplement  $dComplement
     * @return \Illuminate\Http\Response
     */
    public function show(DComplement $dComplement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DComplement  $dComplement
     * @return \Illuminate\Http\Response
     */
    public function edit(DComplement $dComplement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DComplement  $dComplement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DComplement $dComplement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DComplement  $dComplement
     * @return \Illuminate\Http\Response
     */
    public function destroy(DComplement $dComplement)
    {
        //
    }
}
