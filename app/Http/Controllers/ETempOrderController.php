<?php

namespace App\Http\Controllers;

require_once app_path() . "/fpdf/fpdf.php";
require_once app_path() . "/fpdf/PDF_Code128.php";

use App\Models\DTempOrder;
use App\Models\ETempOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\classes\FPDF;
use PDF_Code128;

class ETempOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
            $ETempOrder = ETempOrder::orderBy('created_at','DESC')->get()->toArray();
            $DTempOrder = DTempOrder::get()->toArray();
            foreach ($ETempOrder as $key => $order) {
                $ETempOrder[$key]['details'] = [];
                foreach ($DTempOrder as $item) {
                    if($item['temp_order_id'] == $order['id']) {
                        $ETempOrder[$key]['details'][] = $item;
                    }
                }
            }
            return response()->json([
                'success'   => true,
                'tempOrders' => $ETempOrder,
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
            $ETempOrder = new ETempOrder();
            $ETempOrder->order_id    = $request->order_id;
            $ETempOrder->reference   = $request->reference;
            $ETempOrder->client_id   = $request->client_id;
            $ETempOrder->client_name = $request->client_name;
            $ETempOrder->save();

            $tempOrder = ETempOrder::orderBy('created_at','DESC')->first();
            $tempOrder->details = [];
            return response()->json([
                'success'   => true,
                'tempOrder' => $tempOrder,
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
     * @param  \App\Models\ETempOrder  $eTempOrder
     * @return \Illuminate\Http\Response
     */
    public function show(ETempOrder $eTempOrder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ETempOrder  $eTempOrder
     * @return \Illuminate\Http\Response
     */
    public function edit(ETempOrder $eTempOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ETempOrder  $eTempOrder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ETempOrder $eTempOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ETempOrder  $eTempOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(ETempOrder $eTempOrder)
    {
        //
    }

    public function getLabelsAll(Request $request) {

    }

    public function getIndividualLabels(Request $request) {
        $DTempOrder = DTempOrder::select('e_temp_orders.id AS temp_order_id','e_temp_orders.order_id','d_temp_orders.*')
        ->join('e_temp_orders','e_temp_orders.id','d_temp_orders.temp_order_id')
        ->where('d_temp_orders.id',$request->id)
        ->first()
        ->toArray();
        $pdf = new FPDF(new PDF_Code128('L','mm',[65,100]));
        return $pdf->createIndividualItemOrder($DTempOrder);
    }
}
