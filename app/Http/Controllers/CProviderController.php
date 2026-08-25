<?php

namespace App\Http\Controllers;

use App\Models\CProvider;
use Illuminate\Http\Request;
use App\classes\Logs;
use App\Models\DInventory;

class CProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $providers = CProvider::where('is_active',1)->get();
            return response()->json([
                'success'  => true,
                'providers'  => $providers,
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
            $duplicateNomen = CProvider::select('nomen')->where('is_active',1)->where('nomen',$request->nomen)->first();
            if(is_null($duplicateNomen)) {
                // Guardamos product
                $CProvider                 = new CProvider;
                $CProvider->nomen          = $request->nomen;
                $CProvider->provider       = $request->provider;
                $CProvider->company        = $request->company;
                $CProvider->provider_email = $request->provider_email;
                $CProvider->save();
                // LOGS
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->user_id,'Creó un proveedor nuevo',1,6,7,1,'provider_id',$CProvider->id,'Se creó un usuario para el sistema de proveedores');
                // GET PRODUCT
                $provider = CProvider::where('id',$CProvider->id)->first();
                return response()->json([
                    'success'           => true,
                    'is_duplicate'      => false,
                    'provider'  => $provider,
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
     * @param  \App\Models\CProvider  $cProvider
     * @return \Illuminate\Http\Response
     */
    public function show(CProvider $cProvider)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CProvider  $cProvider
     * @return \Illuminate\Http\Response
     */
    public function edit(CProvider $cProvider, $id, $user_id)
    {
        try {
            $cProvider::where('id',$id)->update([ "is_active" => 0, ]);
            // LOGS
            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
            $logs->createMovementLog($user_id,'Desactivo a un proveedor',1,6,7,5,'provider_id',$id,'Se desactivo a un usuario del sistema de proveedores');
            return response()->json([
                'success'  => true,
                'id'  => $id,
            ], 200 );
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
     * @param  \App\Models\CProvider  $cProvider
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CProvider $cProvider)
    {
        try {
            CProvider::where('id',$request->id)
            ->update([
                "provider"      => $request->provider,
                "company"        => $request->company,
                "provider_email" => $request->provider_email,
            ]);
            // LOGS
            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
            $logs->createMovementLog($request->user_id,'Actualizo un proveedor',1,6,7,3,'provider_id',$request->id,'Se actualizo un proveedor');
            // GET PRODUCT
            $provider = CProvider::where('is_active',1)->where('id',$request->id)->first();
            return response()->json([
                'success'  => true,
                'provider'  => $provider,
                'id'  => $request->id,
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
     * @param  \App\Models\CProvider  $cProvider
     * @return \Illuminate\Http\Response
     */
    public function destroy(CProvider $cProvider)
    {
        //
    }

    public function getLabelProviders(CProvider $cProvider)
    {
        try {
            $eBP = [];
            return response()->json([
                'success'  => true,
                'eBP'  => $eBP,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'message' =>  'Error en sistema CDG-001-236',
                'error'   =>  $th,
            ], 400);
        }
    }
}
