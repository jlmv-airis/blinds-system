<?php

namespace App\Http\Controllers;

use App\classes\Logs;
use App\Models\CErpInfoUser;
use App\Models\CModule;
use App\Models\CSubmodule;
use App\Models\CUser;
use App\Models\DAccessUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\classes\WebService;
use App\Models\CPriceList;
use App\Models\CTypeNote;
use App\Models\CTypeSchedule;
use App\Models\DClientsNote;
use App\Models\DComplement;
use App\Models\ESchedule;
use Carbon\Carbon;

class CUserController extends Controller
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
        // try {
            $CUser = CUser::select('id', DB::raw("COALESCE(NULLIF(short_name,''), NULLIF(full_name,''), 'Sin nombre') AS short_name"),'user_email','user_img')
            ->get();
            $agents = CErpInfoUser::select('user_id as id','short_name')
            ->where('is_agent',1)
            ->get();
            return response()->json([
                'success'     => true,
                'clients'       => $CUser,
                'agents' => $agents,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'message' =>  'Error en sistema CDG-001-236',
        //         'error'   =>  $th,
        //     ], 403);
        // }
    }

    public function getDataClient(Request $request)
    {
        // try {
            $client = CUser::select('id','short_name','agent_id','full_name','user_email','user_img','company','lada','phone','details')
            ->where('id',$request->client_id)
            ->first();

            // NOTES
            $clientNotes = DClientsNote::select('d_clients_notes.id','d_clients_notes.note', 'd_clients_notes.created_at', 'd_clients_notes.is_active', 'd_clients_notes.client_id', 'd_clients_notes.updated_at','d_clients_notes.type_note_id','c_type_notes.type_note','c_type_notes.color_type_note', 'd_clients_notes.user_id', 'c_erp_info_users.short_name', 'c_erp_info_users.email as user_email', 'c_erp_info_users.user_img', 'c_erp_info_users.color as color_user')
            ->join('c_erp_info_users','c_erp_info_users.user_id','d_clients_notes.user_id')
            ->join('c_type_notes','c_type_notes.id','d_clients_notes.type_note_id')
            ->where('d_clients_notes.client_id', $request->client_id)
            ->orderBy('d_clients_notes.id', 'DESC')
            ->get();
            // 3 LAST NOTES
            $client3LastNotes = DClientsNote::select('d_clients_notes.id','d_clients_notes.note', 'd_clients_notes.created_at', 'd_clients_notes.is_active', 'd_clients_notes.client_id', 'd_clients_notes.updated_at','d_clients_notes.type_note_id','c_type_notes.type_note','c_type_notes.color_type_note', 'd_clients_notes.user_id', 'c_erp_info_users.short_name', 'c_erp_info_users.email as user_email', 'c_erp_info_users.user_img', 'c_erp_info_users.color as color_user')
            ->join('c_erp_info_users','c_erp_info_users.user_id','d_clients_notes.user_id')
            ->join('c_type_notes','c_type_notes.id','d_clients_notes.type_note_id')
            ->where('d_clients_notes.client_id', $request->client_id)
            ->orderBy('d_clients_notes.id', 'DESC')
            ->limit(3)
            ->get();
            //
            $typeNotes = CTypeNote::select('id','type_note','color_type_note')->where('is_active',1)->get();
            $typeSchedules = CTypeSchedule::select('id','type_schedule','type_schedule_icon','type_schedule_color','is_reschedule')->where('is_active',1)->get();
            //
            $schedules = $this->getSchedule($request->client_id,$client->agent_id);
            $afterActivity = $this->getScheduleTime($request->client_id,1);
            $beforeActivity = $this->getScheduleTime($request->client_id,2);
            // COMPLEMENTS
            $complementCatalogs = DComplement::select(DB::raw('CASE type_complement_id WHEN 1 THEN SUM(quantity) END AS catalog '))->where('client_id',$request->client_id)->where('type_complement_id',1)->groupBy('client_id')->first();
            $complementExhi = DComplement::select(DB::raw('CASE type_complement_id WHEN 2 THEN SUM(quantity) END AS exhi '))->where('client_id',$request->client_id)->where('type_complement_id',2)->groupBy('client_id')->first();
            $complementHistory = DComplement::select('d_complements.type_complement_id','d_complements.quantity','d_complements.reason','d_complements.user_id','c_erp_info_users.short_name as created_name','d_complements.created_at')->join('c_erp_info_users','c_erp_info_users.user_id','d_complements.user_id')->where('client_id',$request->client_id)->get();
            $complementCatalogstotal = 0;
            $complementExhitotal = 0;
            if($complementCatalogs !== null ) { $complementCatalogstotal = $complementCatalogs->catalog; }
            if($complementExhi !== null ) { $complementExhitotal = $complementExhi->exhi; }
            return response()->json([
                'success'               => true,
                'client'                => $client,
                'clientNotes'           => $clientNotes,
                'client3LastNotes'      => $client3LastNotes,
                'typeNotes'             => $typeNotes,
                'typeSchedules'         => $typeSchedules,
                'schedules'             => $schedules,
                'afterActivity'         => $afterActivity,
                'beforeActivity'        => $beforeActivity,
                'complementCatalogs'    => $complementCatalogstotal,
                'complementExhi'        => $complementExhitotal,
                'complementHistory'     => $complementHistory,
            ], 200 );
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
            // Creamos al usuario
            $CUser = new CUser;
            $CUser->uid        = $request->uid;
            $CUser->user_email = $request->client_email;
            $CUser->password   = bcrypt($request->uid);
            $CUser->short_name    = $request->short_name;
            $CUser->full_name     = $request->full_name;
            $CUser->phone         = $request->phone;
            $CUser->discount      = $request->discount;
            $CUser->agent_id      = $request->agent_id;
            $CUser->save();
            // Gaurdamos el modulo de inicio por dfefault
            $DAccessUser = new DAccessUser();
            $DAccessUser->user_id          = $CUser->id;
            $DAccessUser->module_id        = 1;
            $DAccessUser->submodule_id     = 0;
            $DAccessUser->submodule_son_id = 0;
            $DAccessUser->save();

            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , type_log_movement_id  ,identifier_number , description
            $logs->createMovementLog($request->user_id,'Se creo un cliente',1,2,3,1,'client_id',$CUser->id,'Se creó un usuario para el cliente');

            $user = CUser::select('id','short_name','user_email','user_img')
            ->where('id',$CUser->id)
            ->first();
            return response()->json([
                'success'  => true,
                'user'  => $user,
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
     * Display the specified resource.
     *
     * @param  \App\Models\CUser  $cUser
     * @return \Illuminate\Http\Response
     */
    public function show(CUser $cUser, $id)
    {
        //
        try {
            $CUser = CUser::select('id','uid','user_email','full_name','short_name','phone','discount','agent_id','user_img','is_active')
            ->where('id',$id)
            ->first();
            $modules = CModule::select('c_modules.id as module_id',DB::raw('0 as submodule_id'),DB::raw('0 as submodule_son_id'),'c_modules.if_sub_menu','c_modules.module as name','d_access_users.id as id_access',DB::raw('CASE WHEN d_access_users.id IS NULL THEN 0 ELSE 1 END AS selected'))
            ->leftJoin('d_access_users', function($join) use ($id) {
                $join->on('d_access_users.module_id','=','c_modules.id');
                $join->on('d_access_users.submodule_id','=',DB::raw('0'));
                $join->on('d_access_users.user_id','=',DB::raw($id));
            })
            ->orderBy('is_order','asc')
            ->get();
            $submodules = CSubmodule::select('c_submodules.module_id','c_submodules.id as submodule_id',DB::raw('0 as submodule_son_id'),'c_submodules.sub_module as name','d_access_users.id as id_access',db::raw('CASE WHEN d_access_users.id IS NULL THEN 0 ELSE 1 END AS selected'))
            ->leftJoin('d_access_users', function($join) use ($id) {
                $join->on('d_access_users.submodule_id','=','c_submodules.id');
                $join->on('d_access_users.submodule_son_id','=',DB::raw('0'));
                $join->on('d_access_users.user_id','=',DB::raw($id));
            })
            ->get();
            $modules = json_decode($modules, true);
            $submodules =  json_decode($submodules, true);
            $clientModules = self::ModulesAccessUpdate($modules,$submodules);

            return response()->json([
                'success'  => true,
                'clientsSelect'  => $CUser,
                'clientModules'  => $clientModules,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }


    public function updatePassclientBD(Request $request) {
        try {
            $uid = CUser::where('id','=',$request->update_user_id)->first();
            CUser::where('uid', $uid->uid)
            ->update([ 'password' => bcrypt($uid->uid) ]);
            return response()->json([
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   => $th
            ], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CUser  $cUser
     * @return \Illuminate\Http\Response
     */
    public function edit(CUser $cUser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CUser  $cUser
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CUser $cUser)
    {
        //
    }
    public function updateModules(Request $request, CUser $CUser)
    {
        //
        try {
            DAccessUser::where('user_id',$request->update_client_id)->delete();
            $dataInsert = array();
            foreach($request->selectedModules as $key => $value) {
                array_push($dataInsert, [
                    'user_id' => $request->update_client_id,
                    'module_id' => $value['module_id'],
                    'submodule_id' => $value['submodule_id'],
                    'submodule_son_id' => $value['submodule_son_id'],
                ]);
            }
            DAccessUser::insert($dataInsert);
            return response()->json([
                'success'  => true,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 403);
        }
    }
    public function updateClientGeneral(Request $request)
    {
        //
        try {
            CUser::where('id',$request->update_client_id)
            ->update([
                "short_name"    => $request->short_name,
                "full_name"     => $request->full_name,
                "phone"         => $request->phone,
                "discount"      => $request->discount,
                "price_list_id" => $request->price_list_id,
                "agent_id"      => $request->agent_id,
            ]);
            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , type_log_movement_id  ,identifier_number , description
            $logs->createMovementLog($request->user_id,'Se actualizó al cliente',1,2,3,3,'client_id',$request->update_client_id,'Actualizacion de datos de cliente en ERP');
            return response()->json([
                'success'  => true,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 403);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CUser  $cUser
     * @return \Illuminate\Http\Response
     */
    public function destroy(CUser $cUser)
    {
        //
    }

    public function getClientsERP($user_id)
    {
        // try {
            $rowData = $this->webService->getClienLS();
            $newUserInsert = [];
            $clientsERP = [];
            $clientList = CUser::where('is_main_account',1)->get();
            $ids1 = array();
            foreach ($rowData->items as $client) {  if( (INT)$client->CLAVE !== 0 AND $client->CLAVE != 'MOSTR' ) { $ids1[] = (INT)$client->CLAVE; } }
            $ids1[] = 985;
            $ids2 = array();
            foreach ($clientList->toArray() as $clientSys) { $ids2[] = $clientSys['id']; }
            $one_not_two = array_diff($ids1,$ids2);
            $one_not_two = array_values($one_not_two);
            if( (INT)COUNT($one_not_two) > 0 ) {
                foreach ($rowData->items as $client) {
                    foreach ($one_not_two as $id_client) {
                        if((INT)$client->CLAVE === (INT)$id_client) {
                            $newUserInsert[] = [
                                'id'             => $client->CLAVE,
                                'full_name'      => $client->NOMBRE,
                                'company'        => $client->NOMBRECOMERCIAL,
                                'emails'         => $client->EMAILPRED,
                                'agent_erp_id'   => $client->CVE_VEND,
                                'is_main_account' => 1,
                            ];
                        }
                    }
                }
            }
            if(count($newUserInsert) != 0 ) { CUser::insert($newUserInsert); }
            $clientList = CUser::where('is_main_account',1)->get();
            $leader =  CErpInfoUser::select('c_erp_info_users.*')->join('c_erp_users','c_erp_users.id','c_erp_info_users.user_id')->where('c_erp_info_users.user_id',$user_id)->first();
            $agents = CErpInfoUser::select('c_erp_info_users.user_id','c_erp_info_users.short_name')->join('c_erp_users','c_erp_users.id','c_erp_info_users.user_id')->where('c_erp_info_users.is_agent',1)->where('c_erp_users.is_active',1)->get();
            $priceLists = CPriceList::where('is_active',1)->get();
            return response()->json([
                'success'    => true,
                'clientsERP' => $clientList,
                'agents'     => $agents,
                'isLeader'   => $leader['is_leader'],
                'priceLists'   => $priceLists,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 403);
        // }
    }

    public function filterClientsERP($filter)
    {
        // try {
            $rowData = $this->webService->getClienLS();
            $clientsERP = [];
            $clientList = CUser::where('is_main_account',1);
            if($filter != 'all-data') {
                $clientList->where('full_name','LIKE',DB::raw('"%'.$filter .'%"'))
                ->orWhere('user_email','LIKE',DB::raw('"%'.$filter .'%"'));
            }
            $clientList = $clientList->get();
            foreach ($rowData->items as $key => $client) {
                if(count($clientList) != 0 ) {
                    foreach ($clientList->toArray() as $clientSys) {
                        if((INT)$client->CLAVE === (INT)$clientSys['id']) {
                            $clientsERP[] = [
                                'id'                  => $clientSys['id'],
                                'user_email'          => $clientSys['user_email'],
                                'short_name'          => $clientSys['full_name'],
                                'full_name'           => $clientSys['full_name'],
                                'company'             => $clientSys['company'],
                                'emails'              => $clientSys['emails'],
                                'phone'               => $clientSys['phone'],
                                'discount'            => $clientSys['discount'],
                                'agent_id'            => $clientSys['agent_id'],
                                'user_img'            => $clientSys['user_img'],
                                'price_list_id'       => $clientSys['price_list_id'],
                                'erp_rfc'             => $client->RFC,
                                'erp_credito'         => $client->CON_CREDITO,
                                'erp_dias_credito'    => $client->DIASCRED,
                                'erp_forma_pago'      => $client->FORMADEPAGOSAT,
                                'erp_registro_fiscal' => $client->REG_FISC,
                            ];
                        }
                    }
                }
            }
            return response()->json([
                'success'    => true,
                'clientsERP' => $clientsERP,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 403);
        // }

    }

    private function ModulesAccess($modules,$submodules) {

        foreach($modules as $key => $value) {

            if((int)$value['if_submodulo'] === 1) {
                $modules[$key] += ['submoduls' => array() ];
                foreach($submodules as $key2 => $value2) {
                    if( (int)$value2['module_id'] == (int)$value['module_id'] ) {
                        array_push($modules[$key]['submoduls'], $submodules[$key2]);
                    }
                }
            }
        }
        return $modules;
    }
    private function ModulesAccessUpdate($modules,$submodules) {
        $idTree = 1;
        foreach($modules as $key => $value) {
            $modules[$key]['id'] = $idTree++;
            if((int)$value['if_sub_menu'] === 1) {
                $modules[$key] += ['children' => array() ];
                foreach($submodules as $key2 => $value2) {
                    $submodules[$key2]['id'] = $idTree++;
                    if( (int)$value2['module_id'] == (int)$value['module_id'] ) {
                        array_push($modules[$key]['children'], $submodules[$key2]);
                    }
                }
            }
        }
        return $modules;
    }
    private function ValidModulesAccess($nameValid,$userID) {

        $valueValid = false;
        $validModule = NULL;
        $module = DB::table('c_modules')->where('name',$nameValid)->first();
        if(!is_null($module)) {
            $validModule = DB::table('d_access_users')->where('module_id',$module->id)->where('submodule_id',0)->where('user_id',$userID)->first();
        } else {
            $subModule = DB::table('c_submodules')->where('name',$nameValid)->first();
            if(!is_null($subModule)) {
                $validModule = DB::table('d_access_users')->where('submodule_id',$subModule->id)->where('user_id',$userID)->first();
            }
        }
        if(!is_null($validModule)){
            $valueValid = true;
        }
        return $valueValid;
    }
    private function ValidModulesChildAccess($nameValid,$userID) {

        $valueValid = false;
        $validModule = NULL;
        $submoduleChild = DB::table('c_erp_submodule_sons')->where('name',$nameValid)->first();
        if(!is_null($submoduleChild)) {
            $validModule = DB::table('d_access_users')->where('submodule_son_id',$submoduleChild->id)->where('user_id',$userID)->where('is_active',1)->first();
        }
        if(!is_null($validModule)){
            $valueValid = true;
        }
        return $valueValid;
    }

    private function getSchedule($client_id,$agent_id)
    {

        $ESchedule = ESchedule::select('e_schedules.id','e_schedules.client_id','c_users.agent_id',DB::raw("CASE WHEN c_users.short_name IS NULL OR c_users.short_name = '' THEN c_users.short_name ELSE c_users.full_name END AS client_name"),'e_schedules.name_activity AS name','c_erp_info_users.id as agent_id','c_erp_info_users.short_name as name_agent','e_schedules.type_schedules_id','c_type_schedules.type_schedule','c_type_schedules.type_schedule_icon',DB::raw(' CASE WHEN e_schedules.client_id = '.$client_id.' THEN c_type_schedules.type_schedule_color ELSE "#c5c5c5" END AS color'),'e_schedules.detail',DB::raw('CASE WHEN e_schedules.start IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.start END AS start, CASE WHEN e_schedules.end IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.end END AS end'),'e_schedules.user_address_id','e_schedules.schedule_status_id', 'c_status_schedules.schedule_status','c_status_schedules.schedule_status_color','e_schedules.created_at','e_schedules.cancellation_description')
        ->join('c_users','c_users.id','e_schedules.client_id')
        ->join('c_status_schedules','c_status_schedules.id','e_schedules.schedule_status_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_schedules.user_address_id')
        ->join('c_type_schedules','c_type_schedules.id','e_schedules.type_schedules_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->where('e_schedules.is_active','1')
        ->where('c_users.agent_id',$agent_id)
        ->orderBy('start', 'asc')
        ->get();

        return $ESchedule;
    }

    private function getScheduleTime($client_id,$opt)
    {
        $dateNow = Carbon::now()->toDateTimeString();
        $ESchedule = ESchedule::select('e_schedules.id','e_schedules.client_id','c_users.agent_id',DB::raw("CASE WHEN c_users.short_name IS NULL OR c_users.short_name = '' THEN c_users.full_name ELSE c_users.short_name END AS client_name"),'e_schedules.name_activity AS name','c_erp_info_users.id as agent_id','c_erp_info_users.short_name as name_agent','e_schedules.type_schedules_id','c_type_schedules.type_schedule','c_type_schedules.type_schedule_icon',DB::raw(' CASE WHEN e_schedules.client_id = '.$client_id.' THEN c_type_schedules.type_schedule_color ELSE "#c5c5c5" END AS color'),'e_schedules.detail',DB::raw('CASE WHEN e_schedules.start IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.start END AS start, CASE WHEN e_schedules.end IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.end END AS end'),'e_schedules.user_address_id','e_schedules.schedule_status_id', 'c_status_schedules.schedule_status','c_status_schedules.schedule_status_color','e_schedules.created_at','e_schedules.cancellation_description')
        ->join('c_users','c_users.id','e_schedules.client_id')
        ->join('c_status_schedules','c_status_schedules.id','e_schedules.schedule_status_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_schedules.user_address_id')
        ->join('c_type_schedules','c_type_schedules.id','e_schedules.type_schedules_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_schedules.user_id');
        if( (INT)$opt === 1) {
            $ESchedule = $ESchedule->where('e_schedules.is_active','1')
            ->where('e_schedules.end','<=',$dateNow)
            ->where('e_schedules.client_id',$client_id)
            ->orderBy('end', 'asc')
            ->limit(3)
            ->get();
        } else {
            $ESchedule = $ESchedule->where('e_schedules.is_active','1')
            ->where('e_schedules.start','>=',$dateNow)
            ->where('e_schedules.client_id',$client_id)
            ->orderBy('start', 'asc')
            ->limit(3)
            ->get();
        }

        return $ESchedule;
    }

}
