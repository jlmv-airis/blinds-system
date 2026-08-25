<?php

namespace App\Http\Controllers;

use App\Models\DMaterialRequest;
use App\Models\DModulation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DModulationController extends Controller
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
     * @param  \App\Models\DModulation  $dModulation
     * @return \Illuminate\Http\Response
     */
    public function show(DModulation $dModulation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DModulation  $dModulation
     * @return \Illuminate\Http\Response
     */
    public function edit(DModulation $dModulation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DModulation  $dModulation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        // try {
            foreach ($request->articles['details'] as $detail) {
                // if(!is_null($article['join_id'])) {
                    if(count($detail['items']) > 0 ) {
                        // obtenemos la relacion item
                        $relation_item = 0;
                        if( (INT)$detail['is_original'] === 1) {
                            $relation_item = 0;
                            // eliminamos las demas relaciones
                            DMaterialRequest::where('width_lot',$detail['widthLot'])
                            ->where('material_request_id',$request->material_request_id)
                            ->where('article_id',$request->articles['article_id'])
                            ->update([
                                'relation_item' => null
                            ]);
                        } else {
                            $relationAll  = DMaterialRequest::select(DB::raw('MAX(relation_item) as max_relation_item'))->where('material_request_id',$request->material_request_id)->where('width_lot',$detail['widthLot'])->where('article_id',$request->articles['article_id'])->first();
                            if(!is_null($relationAll['max_relation_item'])) {
                                $relation_item = $relationAll['max_relation_item'];
                            }
                        }

                        $relation_item++;
                        foreach ($detail['items'] as $item) {
                            DModulation::where('detail_order_id',$item['detail_order_id'])
                            ->where('type_reg',$item['type_reg'])
                            ->update([ 'join_id' => $item['join_id'] ]);

                            DMaterialRequest::where('id',$item['id'])
                            ->where('type_reg',$item['type_reg'])
                            ->update([
                                'width_lot'     => $detail['widthLot'],
                                'lot'           => $detail['lot_selected'],
                                'relation_item' => $relation_item
                            ]);
                        }
                    }
                // }
            }
            return response()->json([
                'success' =>  true ,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DModulation  $dModulation
     * @return \Illuminate\Http\Response
     */
    public function destroy(DModulation $dModulation)
    {
        //
    }
}
