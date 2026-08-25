<?php

namespace App\Http\Controllers;

require_once app_path() . "/fpdf/fpdf.php";
require_once app_path() . "/fpdf/PDF_Code128.php";

use App\Models\DPurchase;
use App\Models\EPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\classes\Logs;
use App\classes\FPDF;
use App\Models\DInventory;
use PDF_Code128;

class EPurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // try {
            // Obtenemos movimiento
            $purchases = EPurchase::select('e_purchases.id','e_purchases.provider_id','e_purchases.company_id','e_purchases.company_id','c_providers.provider','e_purchases.status_id','c_status_purchases.status','e_purchases.created_at','e_purchases.detail','c_erp_info_users.short_name')
            ->join('c_providers','c_providers.id','e_purchases.provider_id')
            ->join('c_status_purchases','c_status_purchases.id','e_purchases.status_id')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_purchases.user_id')
            ->orderBy('e_purchases.id','DESC')
            ->get();
            return response()->json([
                'success'   => true,
                'purchases' => $purchases,
            ], 200 );
            return ;
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 400);
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
            $purchasesIDS = [];
            $inventoryAll = [];
            foreach ($request->providers as $provider) {
                $idInserts = 0;
                $idFrom = 0;
                // Guardamos product
                $EPurchase = new EPurchase();
                $EPurchase->provider_id = $provider['provider_id'];
                $EPurchase->company_id  = $request->company_id;
                $EPurchase->user_id     = $request->user_id;
                $EPurchase->detail      = $request->detail;
                $EPurchase->save();
                $purchasesIDS[] = $EPurchase->id;
                // PRODUCTO POR PROVEEDOR
                foreach ($request->products as $product) {
                    if($provider['provider_id'] == $product['provider_id']) {
                        // registros inactivos en inventario
                        $dataInventory = [];
                        $section=1;
                        $lot='';
                        // vemos cual es el numero mayo de la columna SECTION deel inventario apra darle un consecutivo
                        $inventorySelect = DInventory::select(DB::raw("MAX(section) AS max_section"))
                        ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
                        ->where("c_inventory_products.provider_id",$provider['provider_id'])
                        ->groupBy("provider_id")
                        ->first();
                        if(!is_null($inventorySelect)) { $section = ($inventorySelect->max_section + 1); }
                        // Recorremos registros
                        for ( $i=0 ; $i < $product['regs'] ; $i++) {
                            $lot = $provider['nomen'].$section;
                            $dataInventory[] = [
                                "product_id"   => $product['product_id'],
                                "stock"        => $product['stock'],
                                "section"      => $section,
                                "lot"          => $lot,
                                "is_active"    => 0,
                            ];
                            $section++;
                            $idInserts++;
                        }
                        DInventory::insert($dataInventory);
                        if( $idFrom == 0 ) { $idFrom = DB::getPdo()->lastInsertId(); }
                    }
                }
                // LOGS
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->user_id,'Creó una compra nueva',1,6,7,1,'purchase_id',$EPurchase->id,'Se creó una compra nueva para el proveedor');
                // INVENTORY
                $idTo = $idFrom + ($idInserts - 1);
                $inventory = DInventory::select('d_inventories.id','c_inventory_products.sku','c_inventory_products.product','d_inventories.stock','d_inventories.section','d_inventories.lot')
                ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
                ->whereBetween('d_inventories.id', [$idFrom, $idTo])
                ->get();
                // Inseertamos el deetalle del movimiento
                foreach ($inventory as $reg) {
                    $newMV = new DPurchase();
                    $newMV->purchase_id     = $EPurchase->id;
                    $newMV->inventory_id    = $reg['id'];
                    $newMV->save();
                };
                $inventoryAll[] = $inventory;
            }
            // Creamos PDF
            $pdf = new FPDF(new PDF_Code128('L','mm',[65,100]));
            // CALL PURCHASES
            $purchases = EPurchase::select('e_purchases.id','e_purchases.provider_id','e_purchases.company_id','c_providers.provider','e_purchases.status_id','c_status_purchases.status','e_purchases.created_at','e_purchases.detail','c_erp_info_users.short_name')
            ->join('c_providers','c_providers.id','e_purchases.provider_id')
            ->join('c_status_purchases','c_status_purchases.id','e_purchases.status_id')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_purchases.user_id')
            ->whereIn('e_purchases.id',$purchasesIDS)
            ->get();
            return response()->json([
                'success'   => true,
                'pdf'       => $pdf->createLabelsPerProvider($inventoryAll),
                'purchases' => $purchases,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 400);
        // }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EPurchase  $ePurchase
     * @return \Illuminate\Http\Response
     */
    public function show(EPurchase $ePurchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EPurchase  $ePurchase
     * @return \Illuminate\Http\Response
     */
    public function edit(EPurchase $ePurchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EPurchase  $ePurchase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EPurchase $ePurchase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EPurchase  $ePurchase
     * @return \Illuminate\Http\Response
     */
    public function destroy(EPurchase $ePurchase)
    {
        //
    }

    public function getPurchaseLabels(Request $request) {

        $DPurchase = EPurchase::select('d_inventories.id','d_inventories.product_id','c_inventory_products.product','c_inventory_products.sku','d_inventories.stock','d_inventories.lot')
        ->join('d_purchases','d_purchases.purchase_id','e_purchases.id')
        ->join('d_inventories','d_inventories.id','d_purchases.inventory_id')
        ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
        ->where('e_purchases.id',"=",$request->purchase_id)
        ->get();

        $pdf = new FPDF(new PDF_Code128('L','mm',[65,100]));
        return $pdf->createPurchaseLabels($DPurchase);
    }

    public function getPurchaseOrder(Request $request) {


        $purchase = EPurchase::select('e_purchases.id','e_purchases.provider_id','e_purchases.company_id','c_providers.provider','e_purchases.status_id','c_status_purchases.status','e_purchases.created_at','e_purchases.detail','c_erp_info_users.short_name')
        ->join('c_providers','c_providers.id','e_purchases.provider_id')
        ->join('c_status_purchases','c_status_purchases.id','e_purchases.status_id')
        ->join('c_erp_info_users','c_erp_info_users.user_id','e_purchases.user_id')
        ->where('e_purchases.id',"=",$request->purchase_id)
        ->first()
        ->toArray();

        $detailsPurchases = DPurchase::select('d_purchases.id','d_purchases.inventory_id','c_inventory_products.sku','c_inventory_products.product','c_inventory_products.cost','d_inventories.lot','d_inventories.stock','d_purchases.is_complete')
        ->join('d_inventories','d_inventories.id','d_purchases.inventory_id')
        ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
        ->where('d_purchases.purchase_id',$request->purchase_id)
        ->get()
        ->toArray();

        return app(FPDF::class)->createPurchaseOrder($purchase,$detailsPurchases);
    }
}
