<?php

namespace App\Http\Controllers;

use App\Models\CErpInfoUser;
use Illuminate\Http\Request;

class CErpInfoUserController extends Controller
{
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
     * @param  \App\Models\CErpInfoUser  $cErpInfoUser
     * @return \Illuminate\Http\Response
     */
    public function show(CErpInfoUser $cErpInfoUser,Request $request)
    {
        //
        try {
            $user = CErpInfoUser::select('c_erp_info_users.id','c_erp_info_users.user_id','c_erp_info_users.short_name','c_erp_info_users.full_name','c_erp_info_users.email','c_erp_info_users.phone','c_erp_info_users.user_img','c_erp_info_users.birthday_date','c_erp_info_users.fda_date','c_erp_info_users.leader_id','c_departments.department')
            ->join('c_departments','c_departments.id','c_erp_info_users.department_id')
            ->where('user_id',$request->user_id)
            ->first();
            $leader = CErpInfoUser::select('c_erp_info_users.user_id','c_erp_info_users.short_name','c_erp_info_users.user_img','c_erp_info_users.email','c_erp_info_users.phone','c_departments.department')
            ->join('c_departments','c_departments.id','c_erp_info_users.department_id')
            ->where('user_id',$user->leader_id)
            ->first();
            $userInfo = [
                'user' => $user,
                'leader' => $leader,
            ];
            return response()->json([
                'success' =>  true ,
                'userInfo' =>  $userInfo ,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'message' =>  'Error en sistema CDG-001-236',
                'error'   => $th
            ], 403);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CErpInfoUser  $cErpInfoUser
     * @return \Illuminate\Http\Response
     */
    public function edit(CErpInfoUser $cErpInfoUser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CErpInfoUser  $cErpInfoUser
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CErpInfoUser $cErpInfoUser)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CErpInfoUser  $cErpInfoUser
     * @return \Illuminate\Http\Response
     */
    public function destroy(CErpInfoUser $cErpInfoUser)
    {
        //
    }
}
