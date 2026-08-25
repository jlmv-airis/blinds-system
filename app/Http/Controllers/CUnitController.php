<?php

namespace App\Http\Controllers;

use App\Models\CUnit;
use Illuminate\Http\Request;

class CUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $units = CUnit::where('is_active',1)->get();
            return response()->json([
                'success'  => true,
                'units'  => $units,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'message' =>  'Error en sistema CDG-001-236',
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
     * @param  \App\Models\CUnit  $cUnit
     * @return \Illuminate\Http\Response
     */
    public function show(CUnit $cUnit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CUnit  $cUnit
     * @return \Illuminate\Http\Response
     */
    public function edit(CUnit $cUnit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CUnit  $cUnit
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CUnit $cUnit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CUnit  $cUnit
     * @return \Illuminate\Http\Response
     */
    public function destroy(CUnit $cUnit)
    {
        //
    }
}
