<?php

namespace App\Http\Controllers;

use App\Models\CInventoryProduct;
use App\Models\CProvider;
use App\Models\CUnit;
use Illuminate\Http\Request;
use App\classes\Logs;
use App\Models\DInventory;
use App\classes\WebService;
use App\Imports\productsPurchasesImport;
use Maatwebsite\Excel\Facades\Excel;

class CInventoryProductController extends Controller
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
        try {
            $products = CInventoryProduct::select('c_inventory_products.id as product_id','c_inventory_products.sku','c_inventory_products.product','c_inventory_products.category_id','c_categories.category','c_inventory_products.provider_id','c_providers.provider','c_providers.nomen','c_inventory_products.unit_id','c_units.unit','c_inventory_products.cost','c_inventory_products.created_at')
            ->join('c_categories','c_categories.id','c_inventory_products.category_id')
            ->join('c_providers','c_providers.id','c_inventory_products.provider_id')
            ->join('c_units','c_units.id','c_inventory_products.unit_id')
            ->where('c_inventory_products.is_active',1)
            ->get();
            $providers = CProvider::where('is_active',1)->get();
            $units = CUnit::where('is_active',1)->get();
            return response()->json([
                'success'  => true,
                'products'  => $products,
                'providers'  => $providers,
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
        // try {
            // buscamos que el SKU no este repetido
            $duplicateSKU = CInventoryProduct::select('sku')->where('sku',$request->sku)->first();
            if(is_null($duplicateSKU)) {
                // Guardamos product
                $CInventoryProduct = new CInventoryProduct;
                $CInventoryProduct->sku = $request->sku;
                $CInventoryProduct->product = $request->product;
                $CInventoryProduct->category_id = $request->category_id;
                $CInventoryProduct->provider_id = $request->provider_id;
                $CInventoryProduct->unit_id = $request->unit_id;
                $CInventoryProduct->cost = $request->cost;
                $CInventoryProduct->save();
                // LOGS
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->user_id,'Creó un producto nuevo',1,6,7,1,'inventory_product_id',$CInventoryProduct->id,'Se creó un producto para el inventario interno');
                // GET PRODUCT
                $inventoryProduct = self::getIndividualInventoryProduct($CInventoryProduct->id);
                return response()->json([
                    'success'           => true,
                    'is_duplicate'      => false,
                    'inventoryProduct'  => $inventoryProduct,
                ], 200 );
            } else {
                return response()->json([
                    'success'      => true,
                    'is_duplicate' => true,
                ], 200 );
            }
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
     * @param  \App\Models\CInventoryProduct  $cInventoryProduct
     * @return \Illuminate\Http\Response
     */
    public function show(CInventoryProduct $cInventoryProduct,$company_id)
    {
        // try {

            // $newProductInsert = [];
            // if((INT)$company_id === 1) { $rowData = $this->webService->getLSProducts(); }
            // if((INT)$company_id === 3) { $rowData = $this->webService->getLBProducts(); }
            // if((INT)$company_id === 2) { $rowData = $this->webService->getRTProducts(); }
            // // LS
            // $products = CInventoryProduct::where('is_active',1)->where('company_id',$company_id )->get();
            // foreach ($rowData->items as $key => $productERP) {
            //     $productFound = false;
            //     if(count($products) != 0 ) {
            //         foreach ($products->toArray() as $productsys) {
            //             if($productERP->CVE_ART == $productsys['sku']) {
            //                 $productFound = true;
            //             }
            //         }
            //     }
            //     if(!$productFound AND $productERP->CVE_ART != 'prueba') {
            //         $newProductInsert[] = [
            //             'sku'         => $productERP->CVE_ART,
            //             'product'     => $productERP->DESCR,
            //             'company_id'  => 1,
            //             'provider_id' => 1,
            //             'unit_id'     => $this->setUnit($productERP->UNI_MED),
            //             'cost'        => $productERP->ULT_COSTO,
            //         ];
            //     }
            // }
            // if(count($newProductInsert) != 0 ) { CInventoryProduct::insert($newProductInsert); }

            $products = CInventoryProduct::select('c_inventory_products.id as product_id','c_inventory_products.sku','c_inventory_products.product','c_inventory_products.category_id','c_categories.category','c_inventory_products.provider_id','c_providers.provider','c_providers.nomen','c_inventory_products.unit_id','c_units.unit','c_inventory_products.cost','c_inventory_products.created_at')
            ->leftJoin('c_categories','c_categories.id','c_inventory_products.category_id')
            ->leftJoin('c_providers','c_providers.id','c_inventory_products.provider_id')
            ->leftJoin('c_units','c_units.id','c_inventory_products.unit_id')
            ->where('c_inventory_products.is_active',1)
            ->where('c_inventory_products.company_id',$company_id)
            ->get();
            return response()->json([
                'success'  => true,
                'products'  => $products,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'message' =>  'Error en sistema CDG-001-236',
        //         'error'   =>  $th,
        //     ], 400);
        // }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CInventoryProduct  $cInventoryProduct
     * @return \Illuminate\Http\Response
     */
    public function edit(CInventoryProduct $cInventoryProduct, $product_id, $user_id)
    {
        try {
            // Verficamos que  el producto no tenga inventario
            $getInventory = DInventory::where('product_id',$product_id)->get();
            if(count($getInventory->toArray()) == 0) {
                CInventoryProduct::where('id',$product_id)
                ->update([ "is_active" => 0, ]);
                // LOGS
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($user_id,'Desactivo un producto',1,6,7,5,'inventory_product_id',$product_id,'Se desactivo un producto del inventario interno');
                return response()->json([
                    'success'  => true,
                    'is_data'  => false,
                    'product_id'  => $product_id,
                ], 200 );
            } else {
                return response()->json([
                    'success'  => true,
                    'is_data'  => true,
                ], 200 );
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CInventoryProduct  $cInventoryProduct
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CInventoryProduct $cInventoryProduct)
    {
        try {
            CInventoryProduct::where('id',$request->product_id)
            ->update([
                "sku"         => $request->sku,
                "product"     => $request->product,
                "category_id" => $request->category_id,
                "provider_id" => $request->provider_id,
                "unit_id"     => $request->unit_id,
                "cost"        => $request->cost
            ]);
            // LOGS
            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
            $logs->createMovementLog($request->user_id,'Actualizo un producto',1,6,7,3,'inventory_product_id',$request->product_id,'Se actualizo un producto del inventario interno');
            // GET PRODUCT
            $inventoryProduct = self::getIndividualInventoryProduct($request->product_id);
            return response()->json([
                'success'  => true,
                'inventoryProduct'  => $inventoryProduct,
                'product_id'  => $request->product_id,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CInventoryProduct  $cInventoryProduct
     * @return \Illuminate\Http\Response
     */
    public function importFile(Request $request)
    {
        // try {
            $result =  Excel::toArray(new productsPurchasesImport, $request->file('file'));
            $products = array_shift($result);
            return response()->json([
                'success'  => true,
                'products'  => $products,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 400);
        // }
    }

    public function destroy(CInventoryProduct $cInventoryProduct)
    {
        //
    }

    private function getIndividualInventoryProduct($product_id) {
        $CInventoryProduct = CInventoryProduct::select('c_inventory_products.id as product_id','c_inventory_products.sku','c_inventory_products.product','c_inventory_products.category_id','c_categories.category','c_inventory_products.provider_id','c_providers.provider','c_providers.nomen','c_inventory_products.unit_id','c_units.unit','c_inventory_products.cost','c_inventory_products.created_at')
        ->join('c_categories','c_categories.id','c_inventory_products.category_id')
        ->join('c_providers','c_providers.id','c_inventory_products.provider_id')
        ->join('c_units','c_units.id','c_inventory_products.unit_id')
        ->where('c_inventory_products.id',$product_id)
        ->first();
        return $CInventoryProduct;
    }

    private function setUnit($uni_med) {
        $unimed = 0;
        switch ($uni_med) {
            case 'MTR':
                $unimed = 2;
            break;
            case 'JGO':
                $unimed = 6;
            break;
            case 'KG':
                $unimed = 7;
            break;
            case 'kg':
                $unimed = 7;
            break;
            case 'KIT':
                $unimed = 5;
            break;
            case 'PZA':
                $unimed = 3;
            break;
            case 'UN':
                $unimed = 8;
            break;
            case 'ROL':
                $unimed = 9;
            break;
            case 'SET':
                $unimed = 10;
            break;
            case 'MAQ':
                $unimed = 11;
            break;
        }
        return $unimed;
    }
}
