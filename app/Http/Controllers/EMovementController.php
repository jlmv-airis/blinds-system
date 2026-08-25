<?php

namespace App\Http\Controllers;

require_once app_path() . "/fpdf/fpdf.php";
require_once app_path() . "/fpdf/PDF_Code128.php";

use App\Models\EMovement;
use Illuminate\Http\Request;
use App\classes\FPDF;
use App\Models\CProvider;
use App\Models\DInventory;
use App\Models\DMovement;
use Illuminate\Support\Facades\DB;
use PDF_Code128;

class EMovementController extends Controller
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
            $movements = EMovement::select('e_movements.id','c_movement_types.movement_type','c_movement_types.icon','c_movement_types.color','e_movements.created_at','e_movements.movement_details','c_erp_info_users.short_name')
            ->join('c_movement_types','c_movement_types.id','e_movements.movement_type_id')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_movements.user_id')
            ->orderBy('e_movements.id','DESC')
            ->get();
            return response()->json([
                'movements' => $movements,
            ], 200 );
            return ;
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'message' =>  'Error en sistema CDG-001-236',
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
    public function storeTransfer(Request $request)
    {
        // try {

            //creamos el movimiento en su estructura
            $EMovement = new EMovement();
            $EMovement->movement_type_id = $request->movement_id;
            $EMovement->user_id          = $request->user_id;
            $EMovement->movement_details = $request->description;
            $EMovement->save();
            // almacenamiento en inventario
            $inventoryIDS = [];
            foreach ($request->products as $product) {
                $newMV = new DMovement;
                $newMV->movement_id       = $EMovement->id;
                $newMV->inventory_id      = $product['id'];
                $newMV->warehouse_from_id = $request->warehouse_from_id;
                $newMV->warehouse_to_id = $request->warehouse_to_id;
                $newMV->save();
                // Actualizamos inventraio
                DInventory::where('id','=',$product['id'])
                ->update([
                    'warehouse_id' => $request->warehouse_to_id,
                    'location_id' => $request->location_id,
                ]);
                $inventoryIDS[] = $product['id'];
            };
            // Inventory
            $inventory = DInventory::select('d_inventories.id','d_inventories.id as product_id','c_inventory_products.sku','c_inventory_products.product','c_inventory_products.category_id','c_categories.category','c_inventory_products.unit_id','c_units.unit','c_inventory_products.cost','c_inventory_products.provider_id','c_providers.provider','d_inventories.warehouse_id','c_warehouses.warehouse','d_inventories.stock','d_inventories.section','d_inventories.lot','d_inventories.location_id','d_warehouse_locations.location','d_warehouse_locations.rack','d_warehouse_locations.x','d_warehouse_locations.y','d_warehouse_locations.level_id','c_warehouse_levels.level','c_warehouse_levels.img_level','d_inventories.created_at')
            ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
            ->join('c_categories','c_categories.id','c_inventory_products.category_id')
            ->join('c_units','c_units.id','c_inventory_products.unit_id')
            ->join('c_providers','c_providers.id','c_inventory_products.provider_id')
            ->join('c_warehouses','c_warehouses.id','d_inventories.warehouse_id')
            ->join('d_warehouse_locations','d_warehouse_locations.id','d_inventories.location_id')
            ->join("c_warehouse_levels", "c_warehouse_levels.id", "d_warehouse_locations.level_id")
            ->whereIn('d_inventories.id',$inventoryIDS)
            ->get();
            // Obtenemos movimiento
            $movement = EMovement::select('e_movements.id','c_movement_types.movement_type','c_movement_types.icon','c_movement_types.color','e_movements.created_at','e_movements.movement_details','c_erp_info_users.short_name')
            ->join('c_movement_types','c_movement_types.id','e_movements.movement_type_id')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_movements.user_id')
            ->where('e_movements.id',$EMovement->id)
            ->first();
            return response()->json([
                'success'   => true,
                'inventory' => $inventory,
                'movement' => $movement,
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

    public function storeEntry(Request $request)
    {
        // try {

            //creamos el movimiento en su estructura
            $EMovement = new EMovement();
            $EMovement->movement_type_id = $request->movement_id;
            $EMovement->user_id          = $request->user_id;
            $EMovement->movement_details = $request->description;
            $EMovement->save();
            // almacenamiento en inventario
            $providersReg = [];
            $idInserts = 0;
            $idFrom = 0;
            foreach ($request->products as $product) {
                $providersReg[] = $product['provider_id'];
                $dataInventory = [];
                $section=1;
                $lot='';
                // vemos cual es el numero mayo de la columna SECTION deel inventario apra darle un consecutivo
                $inventorySelect = DInventory::select(DB::raw("MAX(section) AS max_section"))
                ->where("provider_id",$product['provider_id'])
                ->groupBy("provider_id")
                ->first();
                if(!is_null($inventorySelect)) { $section = ($inventorySelect->max_section + 1); }
                //obtenemos la nomenglatura del proveedor
                $provider = CProvider::where('id',$product['provider_id'])->first();
                // Recorremos registros
                for ( $i=0 ; $i < $product['regs'] ; $i++) {
                    $lot = $provider['nomen'].$section;
                    $dataInventory[] = [
                        "product_id"   => $product['product_id'],
                        "location_id"  => $product['location_id'],
                        "warehouse_id" => $product['warehouse_id'],
                        "stock"        => $product['stock'],
                        "provider_id"  => $product['provider_id'],
                        "section"      => $section,
                        "lot"          => $lot,
                    ];
                    $section++;
                    $idInserts++;
                }
                DInventory::insert($dataInventory);
                if( $idFrom == 0 ) { $idFrom = DB::getPdo()->lastInsertId(); }
            }
            // OBTENEMOS RANGOS DE ID
            $idTo = $idFrom + ($idInserts - 1);
            // Creamos PDF
            $inventory = DInventory::select('d_inventories.id','d_inventories.id as product_id','c_inventory_products.sku','c_inventory_products.product','c_inventory_products.category_id','c_categories.category','c_inventory_products.unit_id','c_units.unit','c_inventory_products.cost','c_inventory_products.provider_id','c_providers.provider','d_inventories.warehouse_id','c_warehouses.warehouse','d_inventories.stock','d_inventories.section','d_inventories.lot','d_inventories.location_id','d_warehouse_locations.location','d_warehouse_locations.rack','d_warehouse_locations.x','d_warehouse_locations.y','d_warehouse_locations.level_id','c_warehouse_levels.level','c_warehouse_levels.img_level','d_inventories.is_active')
            ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
            ->join('c_categories','c_categories.id','c_inventory_products.category_id')
            ->join('c_units','c_units.id','c_inventory_products.unit_id')
            ->join('c_providers','c_providers.id','c_inventory_products.provider_id')
            ->join('c_warehouses','c_warehouses.id','d_inventories.warehouse_id')
            ->join('d_warehouse_locations','d_warehouse_locations.id','d_inventories.location_id')
            ->join("c_warehouse_levels", "c_warehouse_levels.id", "d_warehouse_locations.level_id")
            ->whereBetween('d_inventories.id', [$idFrom, $idTo])
            ->get();
            // Inseertamos el deetalle del movimiento
            foreach ($inventory as $reg) {
                $newMV = new DMovement();
                $newMV->movement_id     = $EMovement->id;
                $newMV->inventory_id    = $reg['id'];
                $newMV->warehouse_to_id = 1;
                $newMV->save();
            };
            // Obtenemos movimiento
            $movement = EMovement::select('e_movements.id','c_movement_types.movement_type','c_movement_types.icon','c_movement_types.color','e_movements.created_at','e_movements.movement_details','c_erp_info_users.short_name')
            ->join('c_movement_types','c_movement_types.id','e_movements.movement_type_id')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_movements.user_id')
            ->where('e_movements.id',$EMovement->id)
            ->first();
            return response()->json([
                'success'   => true,
                'inventory' => $inventory,
                'movement' => $movement,
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

    public function storeOutputs(Request $request)
    {
        // try {
            //creamos el movimiento en su estructura
            $EMovement = new EMovement();
            $EMovement->movement_type_id = 3;
            $EMovement->user_id          = $request->user_id;
            $EMovement->movement_details = $request->detail;
            $EMovement->save();
            // almacenamiento en inventario
            $inventoryReg = [];
            foreach ($request->products as $product) {
                $inventoryReg[] = $product['id'];
                $newMV = new DMovement();
                $newMV->movement_id     = $EMovement->id;
                $newMV->inventory_id    = $product['id'];
                $newMV->warehouse_to_id = $product['warehouse_id'];
                $newMV->save();

                if($product['stockOut'] == $product['stock']) {
                    // el stock queda en cero y el lote se desactiva
                    DInventory::where('id',$product['id'])
                    ->update([
                        'stock'     => 0,
                        'is_active' => 0,
                    ]);
                }  else {
                    $res_stock = $product['stock'] - $product['stockOut'];
                    // el stock queda en cero y el lote se desactiva
                    DInventory::where('id',$product['id'])
                    ->update([
                        'stock'     => $res_stock,
                    ]);
                }
            }
            // Obtenemos el nuevo inventario
            $inventory = DInventory::select('d_inventories.id','d_inventories.id as product_id','c_inventory_products.sku','c_inventory_products.product','c_inventory_products.category_id','c_categories.category','c_inventory_products.unit_id','c_units.unit','c_inventory_products.cost','c_inventory_products.provider_id','c_providers.provider','d_inventories.warehouse_id','c_warehouses.warehouse','d_inventories.stock','d_inventories.section','d_inventories.lot','d_inventories.location_id','d_warehouse_locations.location','d_warehouse_locations.rack','d_warehouse_locations.x','d_warehouse_locations.y','d_warehouse_locations.level_id','c_warehouse_levels.level','c_warehouse_levels.img_level','d_inventories.is_active')
            ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id','d_inventories.is_active')
            ->join('c_categories','c_categories.id','c_inventory_products.category_id')
            ->join('c_units','c_units.id','c_inventory_products.unit_id')
            ->join('c_providers','c_providers.id','c_inventory_products.provider_id')
            ->join('c_warehouses','c_warehouses.id','d_inventories.warehouse_id')
            ->join('d_warehouse_locations','d_warehouse_locations.id','d_inventories.location_id')
            ->join("c_warehouse_levels", "c_warehouse_levels.id", "d_warehouse_locations.level_id")
            ->whereIn('d_inventories.id', $inventoryReg)
            ->get();
            // Obtenemos movimiento
            $movement = EMovement::select('e_movements.id','c_movement_types.movement_type','c_movement_types.icon','c_movement_types.color','e_movements.created_at','e_movements.movement_details','c_erp_info_users.short_name')
            ->join('c_movement_types','c_movement_types.id','e_movements.movement_type_id')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_movements.user_id')
            ->where('e_movements.id',$EMovement->id)
            ->first();
            return response()->json([
                'success'   => true,
                'inventory' => $inventory,
                'movement' => $movement,
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
     * @param  \App\Models\EMovement  $eMovement
     * @return \Illuminate\Http\Response
     */
    public function show(EMovement $eMovement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EMovement  $eMovement
     * @return \Illuminate\Http\Response
     */
    public function edit(EMovement $eMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EMovement  $eMovement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EMovement $eMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EMovement  $eMovement
     * @return \Illuminate\Http\Response
     */
    public function destroy(EMovement $eMovement)
    {
        //
    }

    public function getLabels(Request $request) {

        $Dmov = EMovement::select('d_inventories.id','d_inventories.product_id','c_inventory_products.product','c_inventory_products.sku','d_inventories.stock','d_inventories.lot')
        ->join('d_movements','d_movements.movement_id','e_movements.id')
        ->join('d_inventories','d_inventories.id','d_movements.inventory_id')
        ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
        ->where('e_movements.id',"=",$request->movement_id)
        ->get();

        $pdf = new FPDF(new PDF_Code128('L','mm',[65,100]));
        return $pdf->createCutLabels($Dmov);
    }

    public function saveProductsLabels(Request $request)
    {

        // try {

            //creamos el movimiento en su estructura
            $EMovement = new EMovement();
            $EMovement->movement_type_id = 1;
            $EMovement->user_id          = $request->user_id;
            $EMovement->movement_details = 'Entrada de productos al inventario.';
            $EMovement->save();
            // almacenamiento en inventario
            $providersReg = [];
            $idInserts = 0;
            $idFrom = 0;
            foreach ($request->products as $product) {
                $providersReg[] = $product['provider_id'];
                $dataInventory = [];
                $section=1;
                $lot='';
                // vemos cual es el numero mayo de la columna SECTION deel inventario apra darle un consecutivo
                $inventorySelect = DInventory::select(DB::raw("MAX(section) AS max_section"))
                ->where("provider_id",$product['provider_id'])
                ->groupBy("provider_id")
                ->first();
                if(!is_null($inventorySelect)) { $section = ($inventorySelect->max_section + 1); }
                //obtenemos la nomenglatura del proveedor
                $provider = CProvider::where('id',$product['provider_id'])->first();
                // Recorremos registros
                for ( $i=0 ; $i < $product['regs'] ; $i++) {
                    $lot = $provider['nomen'].$section;
                    $dataInventory[] = [
                        "product_id"   => $product['product_id'],
                        "location_id"  => 1,
                        "warehouse_id" => 1,
                        "stock"        => $product['stock'],
                        "provider_id"  => $product['provider_id'],
                        "section"      => $section,
                        "lot"          => $lot,
                    ];
                    $section++;
                    $idInserts++;
                }
                DInventory::insert($dataInventory);
                if( $idFrom == 0 ) { $idFrom = DB::getPdo()->lastInsertId(); }
            }
            // OBTENEMOS RANGOS DE ID
            $idTo = $idFrom + ($idInserts - 1);
            // Creamos PDF
            $pdf = new FPDF(new PDF_Code128('L','mm',[65,100]));
            $inventory = DInventory::select('d_inventories.id','d_inventories.id as product_id','c_inventory_products.sku','c_inventory_products.product','c_inventory_products.category_id','c_categories.category','c_inventory_products.unit_id','c_units.unit','c_inventory_products.cost','c_inventory_products.provider_id','c_providers.provider','d_inventories.warehouse_id','c_warehouses.warehouse','d_inventories.stock','d_inventories.section','d_inventories.lot','d_inventories.is_active')
            ->join('c_inventory_products','c_inventory_products.id','d_inventories.product_id')
            ->join('c_categories','c_categories.id','c_inventory_products.category_id')
            ->join('c_units','c_units.id','c_inventory_products.unit_id')
            ->join('c_providers','c_providers.id','c_inventory_products.provider_id')
            ->join('c_warehouses','c_warehouses.id','d_inventories.warehouse_id')
            ->whereBetween('d_inventories.id', [$idFrom, $idTo])
            ->get();
            // Inseertamos el deetalle del movimiento
            foreach ($inventory as $reg) {
                $newMV = new DMovement();
                $newMV->movement_id     = $EMovement->id;
                $newMV->inventory_id    = $reg['id'];
                $newMV->warehouse_to_id = 1;
                $newMV->save();
            };
            // Obtenemos movimiento
            $movement = EMovement::select('e_movements.id','c_movement_types.movement_type','c_movement_types.icon','c_movement_types.color','e_movements.created_at','e_movements.movement_details','c_erp_info_users.short_name')
            ->join('c_movement_types','c_movement_types.id','e_movements.movement_type_id')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_movements.user_id')
            ->where('e_movements.id',$EMovement->id)
            ->first();
            return response()->json([
                'success'   => true,
                'pdf'       => $pdf->createLabelsProducts($request,$section),
                'inventory' => $inventory,
                'movement' => $movement,
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

    private function setMovements($EMovement,$DMovement) {
        foreach ($EMovement as $key => $emov) {
            $EMovement[$key]['detail_movements'] = [];
            foreach ($DMovement as $dmov) {
                if($dmov['movement_id'] == $emov['id']) {
                    $EMovement[$key]['detail_movements'][] = $dmov;
                }
            }
        }
        return $EMovement;
    }
}
