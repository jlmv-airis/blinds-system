<?php

namespace App\Http\Controllers;

require_once app_path() . "/fpdf/fpdf.php";
require_once app_path() . "/fpdf/PDF_Code128.php";

use App\Models\DPurchase;
use Illuminate\Http\Request;
use App\classes\FPDF;
use PDF_Code128;
use App\classes\WebService;

class DPurchaseController extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }
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
     * @param  \App\Models\DPurchase  $dPurchase
     * @return \Illuminate\Http\Response
     */
    public function show(DPurchase $dPurchase, $id)
    {

        // try {
            // Obtenemos movimiento
            $detailsPurchases = DPurchase::select('d_purchases.id','d_purchases.inventory_id','c_inventory_products.sku','c_inventory_products.product','d_inventories.lot','d_inventories.stock','d_purchases.is_complete')
            ->join('d_inventories','d_inventories.id','d_purchases.inventory_id')
            ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
            ->where('d_purchases.purchase_id',$id)
            ->get();
            return response()->json([
                'success'   => true,
                'detailsPurchases' => $detailsPurchases,
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
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DPurchase  $dPurchase
     * @return \Illuminate\Http\Response
     */
    public function edit(DPurchase $dPurchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DPurchase  $dPurchase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DPurchase $dPurchase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DPurchase  $dPurchase
     * @return \Illuminate\Http\Response
     */
    public function destroy(DPurchase $dPurchase)
    {
        //
    }

    public function getPurchaseIndividualLabel(Request $request) {

        $DPurchase = DPurchase::select('d_inventories.id','d_inventories.product_id','c_inventory_products.product','c_inventory_products.sku','d_inventories.stock','d_inventories.lot')
        ->join('d_inventories','d_inventories.id','d_purchases.inventory_id')
        ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
        ->where('d_purchases.id',"=",$request->detail_purchase_id)
        ->first();
        $pdf = new FPDF(new PDF_Code128('L','mm',[65,100]));
        return $pdf->createIndividualLabels($DPurchase);
    }

    public function getValidLots($purchase_id) {

        // try {
            $DPurchase = DPurchase::select('d_inventories.id','d_inventories.product_id','c_inventory_products.product','c_inventory_products.sku','d_inventories.stock','d_inventories.lot')
            ->join('d_inventories','d_inventories.id','d_purchases.inventory_id')
            ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
            ->where('d_purchases.purchase_id',"=",$purchase_id)
            ->get();
            // buscamos la existencia del mismo
            $lotes = $this->webService->getLotesRT()->items;
            $lotesError = [];
            // dd($DPurchase);
            foreach ($DPurchase as $item) {
                $findItem = false;
                foreach ($lotes as $value) {
                    if($value->CVE_ART === $item['sku'] AND $value->LOTE === $item['lot'] AND (DOUBLE)$value->CANTIDAD === (DOUBLE)$item['stock'] ) {
                        $findItem = true;
                    }
                }
                if(!$findItem) {
                    $lotesError[] = $item;
                }
            }

            return response()->json([
                'success'   => true,
                'lotes_error' => $lotesError,
            ], 200 );
            return ;
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 400);
        // }

    }
}
