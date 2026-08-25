<?php

namespace App\Http\Controllers;

use App\Models\CWarehouse;
use Illuminate\Http\Request;
use App\classes\Logs;
use App\Models\CCompany;
use App\Models\DInventory;

class CWarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        try {

            $CWarehouse = self::getWarehouses();
            return response()->json([
                "success" => true,
                "warehouses" => $CWarehouse,
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

        // try {
            // buscamos que el SKU no este repetido
            $duplicateWarehouse = CWarehouse::select('warehouse')->where('warehouse',$request->warehouse)->where('company_id',$request->company_id)->first();
            if(is_null($duplicateWarehouse)) {
                // Guardamos product
                $CWarehouse = new CWarehouse;
                $CWarehouse->warehouse   = $request->warehouse;
                $CWarehouse->company_id  = $request->company_id;
                $CWarehouse->description = $request->description;
                $CWarehouse->save();
                // LOGS
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->user_id,'Creó un almacén nuevo',1,6,6,1,'warehouse_id',$CWarehouse->id,'Se creó un almacén para el sistema interno');
                // GET WAREHOUSE
                $warehouse = self::getIndividualWarehouses($CWarehouse->id);
                return response()->json([
                    'success'           => true,
                    'is_duplicate'      => false,
                    'warehouse'  => $warehouse,
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
     * @param  \App\Models\CWarehouse  $cWarehouse
     * @return \Illuminate\Http\Response
     */
    public function show(CWarehouse $cWarehouse, $warehouse_id)
    {
        try {
            //Buscamos la empresa del alamcen
            $company = CWarehouse::where('id',$warehouse_id)->first();
            $CWarehouse = self::getWarehousesPerComopany($company['company_id']);
            return response()->json([
                "success" => true,
                "warehouses" => $CWarehouse,
            ],200);
        } catch (\Throwable $th) {
            return response()->json([
                "success" => false,
                "error" => $th->getMessage(),
            ],400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CWarehouse  $cWarehouse
     * @return \Illuminate\Http\Response
     */
    public function edit(CWarehouse $cWarehouse, $id, $user_id)
    {
        try {
            // Verficamos que  el producto no tenga inventario
            $getInventory = DInventory::where('warehouse_id',$id)->get();
            if(count($getInventory->toArray()) == 0) {
                CWarehouse::where('id',$id)
                ->update([ "is_active" => 0, ]);
                // LOGS
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($user_id,'Elimino un almacén',1,6,6,5,'warehouse_id',$id,'Se Elimino un almacén del sistema interno');
                return response()->json([
                    'success'  => true,
                    'is_data'  => false,
                    'id'  => $id,
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
     * @param  \App\Models\CWarehouse  $cWarehouse
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CWarehouse $cWarehouse)
    {
        try {
            // Verficamos que  el producto no tenga inventario
            $getInventory = DInventory::where('warehouse_id',$request->id)->get();
            if(count($getInventory->toArray()) == 0) {

                CWarehouse::where('id',$request->id)
                ->update([
                    "warehouse"   => $request->warehouse,
                    "description" => $request->description,
                ]);
                // LOGS
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->user_id,'Actualizo un almacén',1,6,7,3,'warehouse_id',$request->id,'Se actualizo un almacén del sistema interno');
                // GET WAREHOUSE
                $warehouse = self::getIndividualWarehouses($request->id);
                return response()->json([
                    'success'  => true,
                    'is_data'  => false,
                    'id'  => $request->id,
                    'warehouse'  => $warehouse,
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
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CWarehouse  $cWarehouse
     * @return \Illuminate\Http\Response
     */
    public function destroy(CWarehouse $cWarehouse)
    {
        //
    }

    public function getWarehousesCompany($company_id)
    {
        //
        try {
            $warehousesCompany = self::getWarehousesPerComopany($company_id);
            return response()->json([
                "success" => true,
                "warehousesCompany" => $warehousesCompany,
            ],200);
        } catch (\Throwable $th) {
            return response()->json([
                "success" => false,
                "error" => $th->getMessage(),
            ],400);
        }
    }

    private function getWarehousesPerComopany($company_id) {
        $CWarehouse = CWarehouse::select('c_warehouses.id','c_warehouses.warehouse','c_warehouses.company_id','c_companies.company','c_warehouses.description','c_warehouses.created_at')
        ->join('c_companies','c_companies.id','c_warehouses.company_id')
        ->where("c_warehouses.is_active", "1")
        ->where("c_warehouses.company_id", $company_id)
        ->get();
        return $CWarehouse;
    }

    private function getWarehouses() {
        $CWarehouse = CWarehouse::select('c_warehouses.id','c_warehouses.warehouse','c_warehouses.company_id','c_companies.company','c_warehouses.description','c_warehouses.created_at')
        ->join('c_companies','c_companies.id','c_warehouses.company_id')
        ->where("c_warehouses.is_active", "1")
        ->get();
        return $CWarehouse;
    }

    private function getIndividualWarehouses($id) {
        $CWarehouse = CWarehouse::select('c_warehouses.id','c_warehouses.warehouse','c_warehouses.company_id','c_companies.company','c_warehouses.description','c_warehouses.created_at')
        ->join('c_companies','c_companies.id','c_warehouses.company_id')
        ->where("c_warehouses.is_active", 1)
        ->where("c_warehouses.id", $id)
        ->first();
        return $CWarehouse;
    }
}
