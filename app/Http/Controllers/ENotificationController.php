<?php

namespace App\Http\Controllers;

use App\Models\DSocketConnection;
use App\Models\DViewNotification;
use App\Models\ENotification;
use App\Models\EOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ENotificationController extends Controller
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
     * @param  \App\Models\ENotification  $eNotification
     * @return \Illuminate\Http\Response
     */
    public function show(ENotification $eNotification, $user_id)
    {
        //
        try {
            $notifications = $eNotification::select('e_notifications.id','d_view_notifications.id as view_id','e_notifications.title','e_notifications.description','e_notifications.icon','e_notifications.icon_color','e_notifications.type_notifications_id','e_notifications.created_at','d_view_notifications.is_view','e_notifications.to','c_erp_info_users.short_name','c_erp_info_users.user_img','c_erp_info_users.color as user_color')
            ->join('d_view_notifications', function($join) use ($user_id) {
                $join->on('d_view_notifications.notifications_id','=','e_notifications.id');
                $join->on('d_view_notifications.user_id','=', DB::raw("$user_id"));
                $join->on('d_view_notifications.is_view','=', DB::raw("0"));
            })
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_notifications.assign_user_id')
            ->where('e_notifications.type_notifications_id','=','1')
            ->orderBy('e_notifications.created_at','DESC')
            ->get();
            $tickets = $eNotification::select('e_notifications.id','d_view_notifications.id as view_id','e_notifications.title','e_notifications.description','e_notifications.icon','e_notifications.icon_color','e_notifications.type_notifications_id','e_notifications.created_at','d_view_notifications.is_view','e_notifications.to','c_erp_info_users.short_name','c_erp_info_users.user_img','c_erp_info_users.color as user_color')
            ->join('d_view_notifications', function($join) use ($user_id) {
                $join->on('d_view_notifications.notifications_id','=','e_notifications.id');
                $join->on('d_view_notifications.user_id','=', DB::raw("$user_id"));
                $join->on('d_view_notifications.is_view','=', DB::raw("0"));
            })
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_notifications.assign_user_id')
            ->where('e_notifications.type_notifications_id','=','2')
            ->orderBy('e_notifications.created_at','DESC')
            ->get();
            // Material Request
            $material_requests = EOrder::select('id')->where('status_id',2)->get(); // STATUS nuevo
            return response()->json([
                'success'    =>  true ,
                'notifications'    =>  $notifications ,
                'tickets'    =>  $tickets ,
                'materialRequests'    =>  $material_requests ,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success'       =>  false,
                'error'       =>  $th,
            ], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ENotification  $eNotification
     * @return \Illuminate\Http\Response
     */
    public function edit(ENotification $eNotification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ENotification  $eNotification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try {
            DViewNotification::where('id','=',$request->notification_view_id)->update([ 'is_view' => 1]);
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->where('user_id',$request->user_id)->where('user_type','erp')->get();
            return response()->json([
                'success'    =>  true ,
                'notification_view_id'    =>  $request->notification_view_id ,
                'type_notifications_id'    =>  $request->type_notifications_id ,
                'users_socket'     =>  $users_socket,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success'       =>  false,
                'error'       =>  $th,
            ], 400);
        }
    }

    public function viewAllNotification(Request $request)
    {
        try {
            DViewNotification::where('user_id','=',$request->user_id)->update([ 'is_view' => 1]);
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->where('user_id',$request->user_id)->where('user_type','erp')->get();
            return response()->json([
                'success'    =>  true ,
                'users_socket'          =>  $users_socket ,
                'type_notifications_id' =>  $request->type_notifications_id ,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success'       =>  false,
                'error'       =>  $th,
            ], 400);
        }
    }

    public function viewTicketNotification(Request $request)
    {
        try {
            $user_id = $request->user_id;
            // buscamos el id_view de la notificacion creada por el ticket
            $eNotification = ENotification::select('d_view_notifications.id as view_id','d_view_notifications.is_view')
            ->join('d_view_notifications', function($join) use ($user_id) {
                $join->on('d_view_notifications.notifications_id', '=','e_notifications.id');
                $join->on('d_view_notifications.user_id','=',DB::raw($user_id));
            })
            ->where('e_notifications.identifier',$request->ticket_id)
            ->where('e_notifications.type_notifications_id',$request->type_notifications_id)
            ->first();
            if($eNotification['is_view'] == 0) {
                DViewNotification::where('id','=',$eNotification['view_id'])->update(['is_view' => 1]);
                $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->where('user_id',$request->user_id)->where('user_type','erp')->get();
                return response()->json([
                    'success'               =>  true ,
                    'notification_view_id'  =>  $eNotification['view_id'] ,
                    'type_notifications_id' =>  $request->type_notifications_id ,
                    'users_socket'          =>  $users_socket,
                    'is_socket'             =>  true,
                ], 200);
            } else {
                return response()->json([
                    'success'   =>  true ,
                    'is_socket' =>  false,
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success'       =>  false,
                'error'       =>  $th,
            ], 400);
        }
    }
    public function viewInboxOrderNotification(Request $request)
    {
        try {
            $user_id = $request->user_id;
            // buscamos el id_view de la notificacion creada por el ticket
            $DViewNotification = DViewNotification::select('id as view_id','is_view')
            ->where('id',$request->view_id)
            ->first();
            if($DViewNotification['is_view'] == 0) {
                DViewNotification::where('id','=',$request->view_id)->update(['is_view' => 1]);
                $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->where('user_id',$request->user_id)->where('user_type','erp')->get();
                return response()->json([
                    'success'               =>  true ,
                    'notification_view_id'  =>  $DViewNotification['view_id'] ,
                    'type_notifications_id' =>  1 ,
                    'order_id'              =>  $request->order_id,
                    'users_socket'          =>  $users_socket,
                    'is_socket'             =>  true,
                ], 200);
            } else {
                return response()->json([
                    'success'   =>  true ,
                    'is_socket' =>  false,
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success'       =>  false,
                'error'       =>  $th,
            ], 400);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ENotification  $eNotification
     * @return \Illuminate\Http\Response
     */
    public function destroy(ENotification $eNotification)
    {
        //
    }
}
