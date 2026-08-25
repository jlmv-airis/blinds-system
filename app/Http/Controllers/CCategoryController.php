<?php

namespace App\Http\Controllers;

use App\Models\CCategory;
use Illuminate\Http\Request;

class CCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $categories = CCategory::where('is_active',1)->get();
            return response()->json([
                'success'  => true,
                'categories'  => $categories,
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
     * @param  \App\Models\CCategory  $cCategory
     * @return \Illuminate\Http\Response
     */
    public function show(CCategory $cCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CCategory  $cCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(CCategory $cCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CCategory  $cCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CCategory $cCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CCategory  $cCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(CCategory $cCategory)
    {
        //
    }
}
