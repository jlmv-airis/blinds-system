<?php

namespace App\Http\Controllers;

use App\Models\CDashboardPermission;
use App\Models\CDepartment;
use App\Models\CErpInfoUser;
use App\Models\CErpUser;
use App\Models\EDashboard;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;

class EDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        // try {
            $dashboards = EDashboard::get()->toArray();
            $CErpDashboardPermission = CDashboardPermission::select('id','dashboard_id','erp_type_dashboard_permission_id','identifier')->where('is_active','1')->get();
            foreach ($dashboards as $key => $itemDash) {
                $dashboards[$key]['dasboard_permissions'] = array();
                foreach ($CErpDashboardPermission as $permis) {
                    if($itemDash['id'] == $permis['dashboard_id']) {
                        array_push($dashboards[$key]['dasboard_permissions'], $permis);
                    }
                }
            }
            $users = CErpUser::select('c_erp_users.id','c_erp_info_users.short_name','c_erp_info_users.user_img')
            ->join('c_erp_info_users','c_erp_info_users.user_id','c_erp_users.id')
            ->where('is_active','1')->get();
            $departments = CDepartment::where('is_active','1')->get();
            $dashboardPermissions = [];
            foreach ($users as $user) {
                $dashboardPermissions[] = [
                    'identifier'               => $user['id'],
                    'name'                     => $user['short_name'],
                    'type_dashboard_permit_id' => 1,
                    'icon'                     => 'mdi-account-outline',
                    'user_img'                 => $user['user_img'],
                ];
            }
            foreach ($departments as $department) {
                $dashboardPermissions[] = [
                    'identifier'               => $department['id'],
                    'name'                     => $department['department'],
                    'type_dashboard_permit_id' => 2,
                    'icon'                     => 'mdi-office-building',
                    'user_img'                 => null
                ];
            }

            return response()->json([
                'success'   =>  true ,
                'dashboards' =>  $dashboards ,
                'dashboardPermissions' =>  $dashboardPermissions ,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
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
        //
//         // try {
            $EDashboard = new EDashboard;
            $EDashboard->dashboard   = $request->dashboard;
            $EDashboard->description = $request->description;
            $EDashboard->save();
            // Guardamos los permisos
            $insertPermissions = [];
            foreach ($request->generalPermissions as $permission) {
                $insertPermissions [] = [
                    'dashboard_id' => $EDashboard->id,
                    'erp_type_dashboard_permission_id' => $permission['type_dashboard_permit_id'],
                    'identifier' => $permission['identifier'],
                ];
            }
            CDashboardPermission::insert($insertPermissions);
            $path = dirname(getcwd()) . "/resources/js/components/businessIntelligence/dashboards";
            $file_name = "Dashboard_".$EDashboard->id.".vue";
            $component_name = "Dashboard_" . $EDashboard->id;
            $fs = new Filesystem();
            if (!$fs->isDirectory($path)) {
                $fs->makeDirectory($path);
            }
            $file_path = $path . "/" . $file_name;
            $file_template = "<template>
    <div>
    </div>
</template>
<script>
    import { mapGetters } from 'vuex'
    import SToasts from '../../../services/sweetToast'
    export default {
        name: '{$component_name}',
        data: () => ({
        }),
        computed: {
            ...mapGetters({
                accessData: 'auth/getAccessData',
            }),
        },
        methods: {
        },
        created() {
        },
    }
</script>
<style scoped>
</style>";
            $fs->put($file_path, $file_template);
            $dashboard = EDashboard::where('id',$EDashboard->id)->first()->toArray();
            $CErpDashboardPermission = CDashboardPermission::select('id','dashboard_id','erp_type_dashboard_permission_id','identifier')->where('is_active','1')->where('dashboard_id',$EDashboard->id)->get();
            $dashboard['dasboard_permissions'] = array();
            foreach ($CErpDashboardPermission as $permis) {
                array_push($dashboard['dasboard_permissions'], $permis);
            }
            return response()->json([
                'success'   =>  true ,
                'dashboard' =>  $dashboard ,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EDashboard  $eDashboard
     * @return \Illuminate\Http\Response
     */
    public function show(EDashboard $eDashboard, $user_id)
    {
        //
        try {
            //code...
            $userAccount = CErpInfoUser::where('user_id',$user_id)->first();
            // obtenemos los reportes por departamento
            $dashboardDepartment = CDashboardPermission::select('e_dashboards.id','e_dashboards.dashboard','e_dashboards.description')
            ->where('erp_type_dashboard_permission_id',2)
            ->where('identifier',$userAccount['department_id'])
            ->join('e_dashboards','e_dashboards.id','c_dashboard_permissions.dashboard_id')
            ->get()->toArray();
            // Obtenemos los reportes por usuario
            $dashboardUser = CDashboardPermission::select('e_dashboards.id','e_dashboards.dashboard','e_dashboards.description')
            ->where('erp_type_dashboard_permission_id',1)
            ->where('identifier',$user_id)
            ->join('e_dashboards','e_dashboards.id','c_dashboard_permissions.dashboard_id')
            ->get()->toArray();
            // organizamos los dashboards
            $myDashboards = [];
            if($dashboardDepartment) {
                $myDashboards = $dashboardDepartment;
                if($dashboardUser) {
                    foreach ($dashboardUser as $userDashboards) {
                        $isDash = 0;
                        foreach ($myDashboards as  $dashboard) {
                            if($userDashboards['id'] == $dashboard['id']) { $isDash = 1; }
                        }
                        if($isDash == 0) { array_push($myDashboards,$userDashboards); }
                    }
                }
            } else {
                $myDashboards = $dashboardUser;
            }
            // dd($myDashboards);
            usort($myDashboards, function( array $elem1, $elem2 ) {
                return $elem1['dashboard'] <=> $elem2['dashboard'];
            });
            return response()->json([
                'success'   =>  true ,
                'myDashboards' =>  $myDashboards ,
            ], 200);
        } catch (\Throwable $th) {
                return response()->json([
                    'success' => false ,
                    'error'   => $th
                ], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EDashboard  $eDashboard
     * @return \Illuminate\Http\Response
     */
    public function edit(EDashboard $eDashboard)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EDashboard  $eDashboard
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EDashboard $eDashboard)
    {
        //
        // try {
            $dashboardUpdate = $request->dashboardUpdate;
            EDashboard::where('id',$dashboardUpdate['id'])
            ->update([
                'dashboard' => $dashboardUpdate['dashboard'],
                'description' => $dashboardUpdate['description'],
            ]);
            // Eliminamos los permisos
            CDashboardPermission::where('dashboard_id',$dashboardUpdate['id'])->delete();
            // Guardamos los permisos
            $insertPermissions = [];
            foreach ($dashboardUpdate['dasboard_permissions'] as $permission) {
                $insertPermissions [] = [
                    'dashboard_id' => $dashboardUpdate['id'],
                    'erp_type_dashboard_permission_id' => $permission['type_dashboard_permit_id'],
                    'identifier' => $permission['identifier'],
                ];
            }
            CDashboardPermission::insert($insertPermissions);
            return response()->json([
                'success'   =>  true ,
                'dashboardUpdate' =>  $dashboardUpdate ,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EDashboard  $eDashboard
     * @return \Illuminate\Http\Response
     */
    public function destroy(EDashboard $eDashboard)
    {
        //
    }
}
