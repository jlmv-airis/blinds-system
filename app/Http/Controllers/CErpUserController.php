<?php

namespace App\Http\Controllers;

use App\classes\Logs;
use App\Models\CCompany;
use App\Models\CDepartment;
use App\Models\CErpInfoUser;
use App\Models\CErpModule;
use App\Models\CErpSubmodule;
use App\Models\CErpUser;
use App\Models\DErpAccessUser;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Dirape\Token\Token;

class CErpUserController extends Controller
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
            $CErpUser = CErpUser::select('c_erp_users.id','c_erp_info_users.short_name','c_erp_info_users.full_name','c_erp_info_users.user_img','c_erp_info_users.token_api')
            ->join('c_erp_info_users','c_erp_info_users.user_id','c_erp_users.id')
            ->get();
            $CDepartment = CDepartment::select('id','department')->get();
            $CCompany = CCompany::select('id','company')->get();
            return response()->json([
                'success'     => true,
                'users'       => $CErpUser,
                'departments' => $CDepartment,
                'companies' => $CCompany,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'message' =>  'Error en sistema CDG-001-236',
                'error'   =>  $th,
            ], 403);
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
        //
        try {
            $is_leader = 0;
            $is_agent = 0;
            if($request->is_leader) { $is_leader = 1; }
            if($request->is_agent) { $is_agent = 1; }
            // Creamos al usuario
            $token_api = (new Token())->Unique('c_erp_users', 'api_token', 60);
            $CErpUser = new CErpUser;
            $CErpUser->uid        = $request->uid;
            $CErpUser->user_email = $request->user_email;
            $CErpUser->password   = bcrypt($request->uid);
            $CErpUser->api_token  = $token_api;
            $CErpUser->save();
            // creamos la informacion del usuario
            $CErpInfoUser                   = new CErpInfoUser();
            $CErpInfoUser->user_id          = $CErpUser->id;
            $CErpInfoUser->short_name       = $request->short_name;
            $CErpInfoUser->full_name        = $request->full_name;
            $CErpInfoUser->email            = $request->user_email;
            $CErpInfoUser->phone            = $request->phone;
            $CErpInfoUser->department_id    = $request->department_id;
            $CErpInfoUser->company_id       = $request->company_id;
            $CErpInfoUser->is_leader        = $is_leader;
            $CErpInfoUser->is_agent         = $is_agent;
            $CErpInfoUser->save();
            // Gaurdamos el modulo de inicio por dfefault
            $DErpAccessUser = new DErpAccessUser;
            $DErpAccessUser->user_id          = $CErpUser->id;
            $DErpAccessUser->module_id        = 1;
            $DErpAccessUser->submodule_id     = 0;
            $DErpAccessUser->submodule_son_id = 0;
            $DErpAccessUser->save();

            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
            $logs->createMovementLog($request->user_id,'Se creo un usuario',1,2,1,1,'user_id',$CErpUser->id,'Se creó un usuario para el sistema interno');

            $CErpUser = CErpUser::select('c_erp_users.id','c_erp_info_users.short_name','c_erp_info_users.full_name','c_erp_info_users.user_img')
            ->join('c_erp_info_users','c_erp_info_users.user_id','c_erp_users.id')
            ->where('user_id',$CErpUser->id)
            ->first();
            return response()->json([
                'success'  => true,
                'user'  => $CErpUser,
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
     * Display the specified resource.
     *
     * @param  \App\Models\CErpUser  $cErpUser
     * @return \Illuminate\Http\Response
     */
    public function show(CErpUser $cErpUser, $id)
    {
        //
        try {
            $CErpUser = CErpUser::select('c_erp_users.id','c_erp_users.uid','c_erp_users.user_email','c_erp_info_users.full_name','c_erp_info_users.short_name','c_erp_info_users.phone','c_erp_info_users.user_img','c_erp_info_users.company_id','c_companies.company','c_erp_info_users.department_id','c_departments.department','c_erp_info_users.is_leader','c_erp_info_users.is_agent','c_erp_users.is_active')
            ->join('c_erp_info_users','c_erp_info_users.user_id','c_erp_users.id')
            ->leftJoin('c_companies','c_companies.id','c_erp_info_users.company_id')
            ->leftJoin('c_departments','c_departments.id','c_erp_info_users.department_id')
            ->where('c_erp_users.id',$id)
            ->first();
            $modules = CErpModule::select('c_erp_modules.id as module_id',DB::raw('0 as submodule_id'),DB::raw('0 as submodule_son_id'),'c_erp_modules.if_sub_menu','c_erp_modules.module as name','d_erp_access_users.id as id_access',db::raw('CASE WHEN d_erp_access_users.id IS NULL THEN 0 ELSE 1 END AS selected'))
            ->leftJoin('d_erp_access_users', function($join) use ($id) {
                $join->on('d_erp_access_users.module_id','=','c_erp_modules.id');
                $join->on('d_erp_access_users.submodule_id','=',DB::raw('0'));
                $join->on('d_erp_access_users.user_id','=',DB::raw($id));
            })
            ->orderBy('is_order','asc')
            ->get();
            $submodules = CErpSubmodule::select('c_erp_submodules.module_id','c_erp_submodules.id as submodule_id',DB::raw('0 as submodule_son_id'),'c_erp_submodules.sub_module as name','d_erp_access_users.id as id_access',db::raw('CASE WHEN d_erp_access_users.id IS NULL THEN 0 ELSE 1 END AS selected'))
            ->leftJoin('d_erp_access_users', function($join) use ($id) {
                $join->on('d_erp_access_users.submodule_id','=','c_erp_submodules.id');
                $join->on('d_erp_access_users.submodule_son_id','=',DB::raw('0'));
                $join->on('d_erp_access_users.user_id','=',DB::raw($id));
            })
            ->get();
            $modules = json_decode($modules, true);
            $submodules =  json_decode($submodules, true);
            $userModules = self::ModulesAccessUpdate($modules,$submodules);

            return response()->json([
                'success'  => true,
                'usersSelect'  => $CErpUser,
                'userModules'  => $userModules,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'message' =>  'Error en sistema CDG-001-236',
                'error'   =>  $th,
            ], 403);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CErpUser  $cErpUser
     * @return \Illuminate\Http\Response
     */
    public function edit(CErpUser $cErpUser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CErpUser  $cErpUser
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CErpUser $cErpUser)
    {
        //
    }
    public function updateModules(Request $request, CErpUser $CErpUser)
    {
        //
        try {
            DErpAccessUser::where('user_id',$request->update_user_id)->delete();
            $dataInsert = array();
            foreach($request->selectedModules as $key => $value) {
                array_push($dataInsert, [
                    'user_id' => $request->update_user_id,
                    'module_id' => $value['module_id'],
                    'submodule_id' => $value['submodule_id'],
                    'submodule_son_id' => $value['submodule_son_id'],
                ]);
            }
            DErpAccessUser::insert($dataInsert);
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

    public function updateUserGeneral(Request $request, CErpUser $CErpUser)
    {
        //
        try {
            $is_leader = 0;
            $is_agent = 0;
            if($request->is_leader) { $is_leader = 1; }
            if($request->is_agent) { $is_agent = 1; }
            CErpInfoUser::where('user_id',$request->update_user_id)
            ->update([
                "full_name" => $request->full_name,
                "short_name" => $request->short_name,
                "leader_id" => $request->leader_id,
                "company_id" => $request->company_id,
                "department_id" => $request->department_id,
                "phone" => $request->phone,
                "is_leader" => $is_leader,
                "is_agent" => $is_agent,
            ]);
            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , type_log_movement_id  ,identifier_number , description
            $logs->createMovementLog($request->user_id,'Se actualizó usuario',1,2,1,3,'user_id',$request->update_user_id,'Actualizacion de datos de usurio en ERP');
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
     * @param  \App\Models\CErpUser  $cErpUser
     * @return \Illuminate\Http\Response
     */
    public function destroy(CErpUser $cErpUser)
    {
        //
    }
    public function inActiveUser(Request $request)
    {
        //
        try {

            CErpUser::where('id',$request->update_user_id)
            ->update([
                "is_active" => $request->is_active,
            ]);
            return response()->json([
                'success'  => true,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }
    function verify(Request $request) {
        $uid = CErpUser::where('uid','=',$request->uid)->first();
        if(is_null($uid)) {
            return response()->json([
                'success'    =>  false ,
            ], 200);
        }else {
            return response()->json([
                'success'    =>  true ,
            ], 200);
        }
    }
    public function login(Request $request)
    {
        // Login alternativo por email + password (bypass Firebase para dev local)
        if ($request->has('email') && !empty($request->email) && !$request->has('uid')) {
            $user = CErpUser::where('user_email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                ], 200);
            }
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario o contraseña incorrectos',
                ], 200);
            }
            if ($user->is_active != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autorizado',
                ], 200);
            }
            $token = JWTAuth::fromUser($user);
            $modules = json_decode($user->accessModules, true);
            $submodules = json_decode($user->accessSubModules, true);
            $Access = self::ModulesAccess($modules, $submodules);
            return response()->json([
                'success'    => true,
                'message'    => 'Iniciaste sesión correctamente',
                'email'      => $user->user_email,
                'uid'        => $user->id,
                'firebase_uid' => $user->uid,
                'short_name' => $user->userDetail->short_name,
                'email_send' => $user->userDetail->email,
                'user_img'   => $user->userDetail->user_img,
                'company_id' => $user->userDetail->company_id,
                'company'    => $user->userDetail->company,
                'department' => $user->userDetail->department,
                'token'      => $token,
                'api_token'  => $user->api_token,
                'access'     => $Access,
            ], 200)->header('Authorization', $token);
        }

        // Login original por uid (Firebase flow)
        $credentials = $request->only('uid','password');
        $token = JWTAuth::attempt($credentials);
        $user = JWTAuth::user();
        if(!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 200);
        }
        if($user->is_active == 1) {
            if($token) {
                $modules = json_decode($user->accessModules, true);
                $submodules =  json_decode($user->accessSubModules, true);
                $Access = self::ModulesAccess($modules,$submodules);
                return response()->json([
                    'success'    =>  true ,
                    'message'    =>  'Iniciaste sesión correctamente',
                    'email'      =>  $user->user_email,
                    'uid'        =>  $user->id,
                    'short_name' =>  $user->userDetail->short_name,
                    'email_send' =>  $user->userDetail->email,
                    'user_img'   =>  $user->userDetail->user_img,
                    'company_id' =>  $user->userDetail->company_id,
                    'company'    =>  $user->userDetail->company,
                    'department' =>  $user->userDetail->department,
                    'token'      =>  $token,
                    'api_token'  =>  $user->api_token,
                    'access' =>  $Access,
                ], 200)->header('Authorization', $token);
            } else {
                return response()->json([
                    'success' =>  false ,
                    'message' =>  'Usuario o contraseña incorrectos',
                ], 200)->header('Authorization', $token);
            }
        } else {
            return response()->json([
                'success' =>  false ,
                'message' =>  'Usuario no autorizado',
            ], 200)->header('Authorization', $token);
        }
    }
    public function logout(Request $request)
    {
        $token = JWTAuth::getToken();
        try {
            JWTAuth::invalidate($token);
            // CErpUser::where('id', $user_id)
            // ->update([ 'is_login' => 0 ]);
            return response()->json([
                'success'  => true,
            ], 200 );
        } catch ( JWTException $ex) {
            return response()->json([
                'success'  => false ,
            ], 422);
        }
    }
    public function verifyToken(Request $request) {
        try {
            // buscamos que tengan accesso al sistema
            $valid = self::ValidModulesAccess($request->name,$request->uid);
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'success' => false,
                ], 200);
            } else {
                $tokenLoad =  JWTAuth::payload()->toArray();
                // obtenemos el detalle del usuario
                $user = JWTAuth::user();
                return response()->json([
                    'success' => true,
                    'uid' => $user->id,
                    'accessModules' => $valid,
                ], 200);
            }
        } catch (TokenExpiredException $ex) {
           // token expirado, logearce de nuevo
            return response()->json([
                'success'  => false,
                'message'  => 'Token expirado',
                'error'  => 'expired',
            ], 422);
        } catch (TokenBlacklistedException $ex) {
           // token enlista negra, logearce de nuevo
            return response()->json([
                'success'  => false,
                'message'  => 'Token en lista negra',
                'error'  => 'blacklist',
            ], 422);
        }
    }

    public function verifyTokenAll(Request $request) {
        try {
            // buscamos que tengan accesso al sistema
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'success' => false,
                ], 200);
            } else {
                // obtenemos el detalle del usuario
                $user = JWTAuth::user();
                return response()->json([
                    'success' => true,
                    'uid' => $user->id,
                ], 200);
            }
        } catch (TokenExpiredException $ex) {
           // token expirado, logearce de nuevo
            return response()->json([
                'success'  => false,
                'message'  => 'Token expirado',
                'error'  => 'expired',
            ], 422);
        } catch (TokenBlacklistedException $ex) {
           // token enlista negra, logearce de nuevo
            return response()->json([
                'success'  => false,
                'message'  => 'Token en lista negra',
                'error'  => 'blacklist',
            ], 422);
        }
    }

    public function updatePassBD(Request $request) {
        try {
            $uid = CErpUser::where('id','=',$request->update_user_id)->first();
            CErpUser::where('uid', $uid->uid)
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

    public function moduleAccess(Request $request) {
        try {
            // buscamos que tengan accesso al sistema hijo
            $valid = self::ValidModulesAccess($request->name,$request->uid);
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json([
                    'success' => false,
                ], 200);
            } else {
                // obtenemos el detalle del usuario
                $user = JWTAuth::user();
                return response()->json([
                    'success' => true,
                    'uid' => $user->id,
                    'accessModules' => $valid,
                ], 200);
            }
        } catch (TokenExpiredException $ex) {
           // token expirado, logearce de nuevo
            return response()->json([
                'success'  => false,
                'message'  => 'Token expirado',
                'error'  => 'expired',
            ], 422);
        } catch (TokenBlacklistedException $ex) {
           // token enlista negra, logearce de nuevo
            return response()->json([
                'success'  => false,
                'message'  => 'Token en lista negra',
                'error'  => 'blacklist',
            ], 422);
        }
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
        $module = DB::table('c_erp_modules')->where('name',$nameValid)->first();
        if(!is_null($module)) {
            $validModule = DB::table('d_erp_access_users')->where('module_id',$module->id)->where('submodule_id',0)->where('user_id',$userID)->first();
        } else {
            $subModule = DB::table('c_erp_submodules')->where('name',$nameValid)->first();
            if(!is_null($subModule)) {
                $validModule = DB::table('d_erp_access_users')->where('submodule_id',$subModule->id)->where('user_id',$userID)->first();
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
            $validModule = DB::table('d_erp_access_users')->where('submodule_son_id',$submoduleChild->id)->where('user_id',$userID)->where('is_active',1)->first();
        }
        if(!is_null($validModule)){
            $valueValid = true;
        }
        return $valueValid;
    }
    private function createTokenApi() {
        return;
    }
}
