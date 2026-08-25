<?php

namespace App\Http\Controllers;

use App\Models\BLog;
use Illuminate\Http\Request;

class BLogController extends Controller
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
     * @param  \App\Models\BLog  $bLog
     * @return \Illuminate\Http\Response
     */
    public function show(BLog $bLog , $identifier_number, $identifier_type)
    {
        // try {
            $logs = BLog::select('b_logs.id','b_logs.user_id','c_erp_info_users.short_name','b_logs.log','b_logs.description','b_logs.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','b_logs.user_id')
            ->where('b_logs.identifier_number',$identifier_number)
            ->where('b_logs.identifier_type',$identifier_type)
            ->get();
            return response()->json([
                'success'           =>  true ,
                'logs'              => [
                    'details' => $logs,
                    'identifier_number' => $identifier_number,
                    'identifier_type'   => $identifier_type,
                ]
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BLog  $bLog
     * @return \Illuminate\Http\Response
     */
    public function edit(BLog $bLog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BLog  $bLog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BLog $bLog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BLog  $bLog
     * @return \Illuminate\Http\Response
     */
    public function destroy(BLog $bLog)
    {
        //
    }
}
