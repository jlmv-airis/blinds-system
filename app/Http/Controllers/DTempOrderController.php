<?php

namespace App\Http\Controllers;

use App\Models\DTempOrder;
use Illuminate\Http\Request;

class DTempOrderController extends Controller
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
            $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $input_length = strlen($permitted_chars);
            $key = '';
            for($i = 0; $i < 16; $i++) { $key .= $permitted_chars[mt_rand(0, $input_length - 1)]; }
            # code...
            $ETempOrder = new DTempOrder();
            $ETempOrder->temp_order_id    = $request->temp_order_id;
            $ETempOrder->item_id   = $request->itemID;
            $ETempOrder->article   = $request->article;
            $ETempOrder->width = $request->width;
            $ETempOrder->height = $request->height;
            $ETempOrder->quantity = $request->quantity;;
            $ETempOrder->side = $request->side;
            $ETempOrder->mechanism = $request->mechanism;
            $ETempOrder->key = $key;
            $ETempOrder->save();
            $DTempOrder = DTempOrder::where('id',$ETempOrder->id)->first();
            return response()->json([
                'success'   => true,
                'item' => $DTempOrder,
            ], 200 );
            return ;
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'message' =>  'Error en sistema CDG-001-236',
        //         'error'   =>  $th,
        //     ], 403);
        // }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DTempOrder  $dTempOrder
     * @return \Illuminate\Http\Response
     */
    public function show(DTempOrder $dTempOrder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DTempOrder  $dTempOrder
     * @return \Illuminate\Http\Response
     */
    public function edit(DTempOrder $dTempOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DTempOrder  $dTempOrder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DTempOrder $dTempOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DTempOrder  $dTempOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(DTempOrder $dTempOrder)
    {
        //
    }

    public function getTempItem(Request $request)
    {
        // try {

            $DTempOrder = DTempOrder::select('e_temp_orders.id AS temp_order_id','e_temp_orders.order_id','d_temp_orders.*')
            ->join('e_temp_orders','e_temp_orders.id','d_temp_orders.temp_order_id')
            ->where('e_temp_orders.id',$request->temp_order_id)
            ->where('d_temp_orders.id',$request->item_id)
            ->where('d_temp_orders.key',$request->key)
            ->first();
            return response()->json([
                'success'   => true,
                'item' => $DTempOrder,
            ], 200 );
            return ;
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'message' =>  'Error en sistema CDG-001-236',
        //         'error'   =>  $th,
        //     ], 403);
        // }
    }
}
