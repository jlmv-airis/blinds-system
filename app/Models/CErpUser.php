<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\DErpAccessUser;
use App\Models\CErpUserInfo;

class CErpUser extends Authenticatable implements JWTSubject
{
    use notifiable;
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function userDetail() {
        return $this->hasOne(CErpInfoUser::class, 'user_id', 'id')
                    ->join('c_departments','c_departments.id','=','c_erp_info_users.department_id')
                    ->join('c_companies','c_companies.id','=','c_erp_info_users.company_id');
    }
    public function accessModules() {
        return $this->hasMany(DErpAccessUser::class, 'user_id', 'id')
                    ->join('c_erp_modules','d_erp_access_users.module_id','=','c_erp_modules.id')
                    ->select('c_erp_modules.id as module_id','c_erp_modules.icon as icon','c_erp_modules.if_sub_menu as if_submodulo','c_erp_modules.route as route','c_erp_modules.module as module')
                    ->orderBy('is_order','asc')
                    ->groupBy('c_erp_modules.id');
    }
    public function accessSubModules() {
        return $this->hasMany(DErpAccessUser::class, 'user_id', 'id')
                    ->join('c_erp_submodules', function ($join) {
                        $join->on('d_erp_access_users.submodule_id','=','c_erp_submodules.id')
                        ->where('d_erp_access_users.submodule_son_id',0);
                    })
                    ->where('c_erp_submodules.sub_module', '!=', 'General')
                    ->select('c_erp_submodules.id as submodule_id','c_erp_submodules.module_id as module_id','c_erp_submodules.route as route','c_erp_submodules.icon as subicon','c_erp_submodules.sub_module as submodule')
                    ->orderBy('c_erp_submodules.id','asc');
    }
}
