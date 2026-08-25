<?php

namespace App\Http\Controllers;

require_once app_path() . "/fpdf/fpdf.php";
require_once app_path() . "/fpdf/PDF_Code128.php";

use App\Models\DMovement;
use Illuminate\Http\Request;
use App\classes\FPDF;
use PDF_Code128;

class DMovementController extends Controller
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
     * @param  \App\Models\DMovement  $dMovement
     * @return \Illuminate\Http\Response
     */
    public function show(DMovement $dMovement, $id)
    {
        try {
            $DMovement = DMovement::select('d_movements.id','d_movements.movement_id','w1.warehouse as from_warehouse','w2.warehouse as to_warehouse','d_movements.created_at','c_erp_info_users.short_name','c_inventory_products.sku','c_inventory_products.product','d_inventories.section','d_inventories.lot','d_inventories.stock','c_units.unit')
            ->join('e_movements','e_movements.id','d_movements.movement_id')
            ->leftjoin('c_warehouses as w1','w1.id','d_movements.warehouse_from_id')
            ->leftjoin('c_warehouses as w2','w2.id','d_movements.warehouse_to_id')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_movements.user_id')
            ->join('d_inventories','d_inventories.id','d_movements.inventory_id')
            ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
            ->join('c_units','c_units.id','c_inventory_products.unit_id')
            ->where('d_movements.movement_id',"=",$id)
            ->get();
            return   response()->json([
                'success'    => true ,
                'detailsMovements' => $DMovement,
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
     * @param  \App\Models\DMovement  $dMovement
     * @return \Illuminate\Http\Response
     */
    public function edit(DMovement $dMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DMovement  $dMovement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DMovement $dMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DMovement  $dMovement
     * @return \Illuminate\Http\Response
     */
    public function destroy(DMovement $dMovement)
    {
        //
    }

    public function getIndividualLabel(Request $request) {

        $Dmov = DMovement::select('d_inventories.id','d_inventories.product_id','c_inventory_products.product','c_inventory_products.sku','d_inventories.stock','d_inventories.lot')
        ->join('d_inventories','d_inventories.id','d_movements.inventory_id')
        ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
        ->where('d_inventories.id',"=",$request->detail_movement_id)
        ->first();
        $pdf = new FPDF(new PDF_Code128('L','mm',[65,100]));
        return $pdf->createIndividualLabels($Dmov);
    }

    public function getMovementPerProduct($id)
    {
        try {
            $Dmov = DMovement::select('d_movements.id','c_movement_types.movement_type','w1.warehouse as from_warehouse','w2.warehouse as to_warehouse','e_movements.movement_details','d_movements.created_at','c_erp_info_users.short_name')
            ->join('e_movements','e_movements.id','d_movements.movement_id')
            ->join('c_movement_types','c_movement_types.id','e_movements.movement_type_id')
            ->leftjoin('c_warehouses as w1','w1.id','d_movements.warehouse_from_id')
            ->leftjoin('c_warehouses as w2','w2.id','d_movements.warehouse_to_id')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_movements.user_id')
            ->where('d_movements.inventory_id',"=",$id)
            ->orderBy('d_movements.id','DESC')
            ->get();
            return   response()->json([
                'success'    => true ,
                'movementsPerProduct' => $Dmov,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return   response()->json([
                'success'    => false ,
                "error" => $th->getMessage(),
            ], 400);
        }
    }
}
