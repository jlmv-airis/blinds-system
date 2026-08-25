<?php

namespace App\Http\Controllers;

use App\classes\Notifications;
use App\Models\CErpInfoUser;
use App\Models\CErpUser;
use App\Models\DErpAccessUser;
use App\Models\DSocketConnection;
use App\Models\EQuotationDiscountRequest;
use Illuminate\Http\Request;

class EQuotationDiscountRequestController extends Controller
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

        // try {
            // verificamos si no tenemos un request activo
            $DROld = EQuotationDiscountRequest::select('id')
            ->where('quotation_id',$request->quotation_id)
            ->first();
            if(!is_null($DROld)) {
                EQuotationDiscountRequest::where('id', $DROld->id)
                ->update([ 'is_active' => 0 ]);
            }

            $EQuotationDiscountRequest               = new EQuotationDiscountRequest();
            $EQuotationDiscountRequest->quotation_id = $request->quotation_id;
            $EQuotationDiscountRequest->user_id      = $request->user_id;
            $EQuotationDiscountRequest->discount     = $request->discount;
            $EQuotationDiscountRequest->reason       = $request->reason;
            $EQuotationDiscountRequest->save();

            $discountRequest = EQuotationDiscountRequest::select('e_quotation_discount_requests.id','e_quotation_discount_requests.user_id','c_erp_info_users.short_name','e_quotation_discount_requests.quotation_id','e_quotation_discount_requests.discount','e_quotation_discount_requests.reason')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_quotation_discount_requests.user_id')
            ->where('e_quotation_discount_requests.id',$EQuotationDiscountRequest->id)
            ->first();
            // Guardamos usuarios para el socket
            // buscamos a los lideres que recibiran la solicitud
            $users_ids = CErpUser::select('c_erp_users.id')
            ->join('c_erp_info_users','c_erp_info_users.user_id','c_erp_users.id')
            ->where('c_erp_info_users.is_leader', 1)
            ->where('c_erp_info_users.department_id', 2)
            ->where('c_erp_users.is_active', 1)
            ->get();
            foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
            // cremos notificacion
            $message = [
                "title"       => 'Solicitud de descuento',
                "description" => 'Solicito un descuento especial para un cliente en su cotización No. '.$request->quotation_id,
                "icon"        => 'mdi-percent-box',
                "icon_color"  => '#E72658',
            ];
            $notifications = new Notifications;
            $notification = $notifications->createNewNotification($request->quotation_id,1,$request->user_id,$users_ids,$message,'/quotations/overview');

            return response()->json([
                'success'                   =>  true ,
                'quotation_id'              =>  $request->quotation_id,
                'discountRequest'           =>  $discountRequest,
                'users_socket'              => $users_socket,
                'users_socket_notification' => $users_socket,
                'notification'              => $notification,
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
     * @param  \App\Models\EQuotationDiscountRequest  $eQuotationDiscountRequest
     * @return \Illuminate\Http\Response
     */
    public function show(EQuotationDiscountRequest $eQuotationDiscountRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EQuotationDiscountRequest  $eQuotationDiscountRequest
     * @return \Illuminate\Http\Response
     */
    public function edit(EQuotationDiscountRequest $eQuotationDiscountRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EQuotationDiscountRequest  $eQuotationDiscountRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EQuotationDiscountRequest $eQuotationDiscountRequest)
    {
        // try {
            // si ya contamos con un descuento previamente aprovado lo tenemos que quitar del panel
            EQuotationDiscountRequest::where('quotation_id', $request->quotation_id)
            ->update([
                'is_approved' => 0,
                'is_active' => 0,
            ]);
            // actualizamos el nuevo
            EQuotationDiscountRequest::where('id', $request->id)
            ->update([
                'is_approved' => 1,
                'is_active' => 1,
            ]);
            // DATA
            $discountRequest = EQuotationDiscountRequest::select('e_quotation_discount_requests.id','e_quotation_discount_requests.user_id','c_erp_info_users.short_name','e_quotation_discount_requests.quotation_id','e_quotation_discount_requests.discount','e_quotation_discount_requests.reason')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_quotation_discount_requests.user_id')
            ->where('e_quotation_discount_requests.id',$request->id)
            ->first();
            // Guardamos usuarios para el socket
            // buscamos a los lideres que recibiran la solicitud
            $users_ids = DErpAccessUser::select('user_id as id')
            ->where('module_id', 3)
            ->where('submodule_id', 2)
            ->get();
            foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
            // cremos notificacion
            $message = [
                "title"       => 'Acepto tu solicitud de descuento',
                "description" => 'Tu solicitud de descuento fue aceptada. Si no ves el descuento, recarga la página.',
                "icon"        => 'mdi-check-circle',
                "icon_color"  => '#8BE432',
            ];
            $notifications = new Notifications;
            $notification = $notifications->createNewNotification($request->quotation_id,1,$request->user_id,[['id' => $discountRequest->user_id]],$message,'/quotations/overview/panel/'.$request->quotation_id);

            return response()->json([
                'success'                   => true,
                'discountRequest'           => $discountRequest,
                'quotation_id'              => $request->quotation_id,
                'users_socket'              => $users_socket,
                'users_socket_notification' => $users_socket,
                'notification'              => $notification,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function sendDenyRequestDiscount(Request $request, EQuotationDiscountRequest $eQuotationDiscountRequest)
    {
        // try {
            EQuotationDiscountRequest::where('id', $request->id)
            ->update([
                'is_approved' => 0,
                'is_active' => 0,
                'reason_denial' => $request->reason_denial,
            ]);

            $discountRequest = EQuotationDiscountRequest::select('e_quotation_discount_requests.id','e_quotation_discount_requests.user_id','c_erp_info_users.short_name','e_quotation_discount_requests.quotation_id','e_quotation_discount_requests.discount','e_quotation_discount_requests.reason')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_quotation_discount_requests.user_id')
            ->where('e_quotation_discount_requests.id',$request->id)
            ->first();
            // Guardamos usuarios para el socket
            // buscamos a los lideres que recibiran la solicitud
            $users_ids = CErpInfoUser::select('user_id as id')
            ->where('user_id', $discountRequest->user_id)
            ->get();
            foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
            // cremos notificacion
            $message = [
                "title"       => 'Acepto tu solicitud de descuento',
                "description" => 'Tu solicitud de descuento fue denegada. Revisa el motivo en tu panel.',
                "icon"        => 'mdi-cancel',
                "icon_color"  => '#E0241B',
            ];
            $notifications = new Notifications;
            $notification = $notifications->createNewNotification($request->quotation_id,1,$request->user_id,[['id' => $discountRequest->user_id]],$message,'/quotations/overview/panel/'.$request->quotation_id);

            return response()->json([
                'success'                   => true,
                'discountRequest'           => $discountRequest,
                'quotation_id'              => $request->quotation_id,
                'users_socket'              => $users_socket,
                'users_socket_notification' => $users_socket,
                'notification'              => $notification,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   => $th
        //     ], 400);
        // }

    }

    public function deleteRequestDiscount(Request $request, EQuotationDiscountRequest $eQuotationDiscountRequest)
    {
        // try {
            EQuotationDiscountRequest::where('id', $request->id)
            ->update([
                'is_approved' => 0,
                'is_active' => 0,
                'reason_denial' => $request->reason_denial,
            ]);

            $discountRequest = EQuotationDiscountRequest::select('e_quotation_discount_requests.id','e_quotation_discount_requests.user_id','c_erp_info_users.short_name','e_quotation_discount_requests.quotation_id','e_quotation_discount_requests.discount','e_quotation_discount_requests.reason')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_quotation_discount_requests.user_id')
            ->where('e_quotation_discount_requests.id',$request->id)
            ->first();
            // Guardamos usuarios para el socket
            // buscamos a los lideres que recibiran la solicitud
            $users_ids = CErpInfoUser::select('user_id as id')
            ->where('user_id', $discountRequest->user_id)
            ->get();
            foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();

            return response()->json([
                'success'                   => true,
                'discountRequest'           => $discountRequest,
                'quotation_id'              => $request->quotation_id,
                'users_socket'              => $users_socket,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   => $th
        //     ], 400);
        // }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EQuotationDiscountRequest  $eQuotationDiscountRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(EQuotationDiscountRequest $eQuotationDiscountRequest)
    {
        //
    }
}
