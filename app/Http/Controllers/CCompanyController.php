<?php

namespace App\Http\Controllers;

use App\Models\CCompany;
use Illuminate\Http\Request;

class CCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $CCompany = CCompany::where("is_active", "=", "1")->get();
            return response()->json([
                "success" => true,
                "companies" => $CCompany,
            ],200);
        } catch (\Throwable $th) {
            return response()->json([
                "success" => false,
                "error" => $th->getMessage(),
            ],400);
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
     * @param  \App\Models\CCompany  $cCompany
     * @return \Illuminate\Http\Response
     */
    public function show(CCompany $cCompany)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CCompany  $cCompany
     * @return \Illuminate\Http\Response
     */
    public function edit(CCompany $cCompany)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CCompany  $cCompany
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CCompany $cCompany)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CCompany  $cCompany
     * @return \Illuminate\Http\Response
     */
    public function destroy(CCompany $cCompany)
    {
        //
    }
}
