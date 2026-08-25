<?php

namespace App\Http\Controllers;

require_once app_path() . "/fpdf/fpdf.php";
require_once app_path() . "/fpdf/PDF_Code128.php";

use App\classes\Notifications;
use App\Models\CErpUser;
use App\Models\DErpAccessUser;
use App\Models\DOrder;
use App\Models\DQuotation;
use App\Models\DQuotationDiscount;
use App\Models\DSocketConnection;
use App\Models\EOrder;
use App\Models\EQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\classes\FPDF;
use App\classes\SendMail;
use App\Models\CErpInfoUser;
use App\Models\EQuotationDiscountRequest;
use PDF_Code128;
use App\classes\Logs;
use App\Models\CUser;

class EQuotationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // try {
            // verificamos si es  lider
            $lider = CErpInfoUser::select('is_leader')->where('user_id',$request->user_id)->first();
            // DATA
            $quotations =  self::allQuotations($request->user_id,$lider['is_leader'],$request->page,$request->limit,'',$request->isSearch);
            $quotationsCancel =  self::allQuotationsCancel($request->user_id,$lider['is_leader'],$request->page,$request->limit,'',$request->isSearch);
            $quotationsInOrder =  self::allQuotationsInOrder($request->user_id,$lider['is_leader'],$request->page,$request->limit,'',$request->isSearch);
            //TOTALS REGS
            $pageCountData = EQuotation::select( DB::raw('COUNT(*) as num') );
            if( (INT)$lider['is_leader'] === 0 ) { $pageCountData->where('e_quotations.user_id',$request->user_id); }
            $pageCountData = $pageCountData->whereIn('e_quotations.status_id',[1,4])
            ->first();

            $pageCountCancelData = EQuotation::select( DB::raw('COUNT(*) as num') );
            if( (INT)$lider['is_leader'] === 0 ) { $pageCountCancelData->where('e_quotations.user_id',$request->user_id); }
            $pageCountCancelData = $pageCountCancelData->where('e_quotations.status_id',2)
            ->first();

            $pageCountInOrderData = EQuotation::select( DB::raw('COUNT(*) as num') );
            if( (INT)$lider['is_leader'] === 0 ) { $pageCountInOrderData->where('e_quotations.user_id',$request->user_id); }
            $pageCountInOrderData = $pageCountInOrderData->where('e_quotations.status_id',3)
            ->first();

            return response()->json([
                'success'       =>  true ,
                'pageCount' =>  ceil($pageCountData->num/$request->limit),
                'pageCountCancel' =>  ceil($pageCountCancelData->num/$request->limit),
                'pageCountInOrder' =>  ceil($pageCountInOrderData->num/$request->limit),
                'quotations' =>  $quotations,
                'quotationsCancel' =>  $quotationsCancel,
                'quotationsInOrder' =>  $quotationsInOrder,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }


    public function viewQuotationLead(EQuotation $eQuotation, $lead)
    {
        // try {
            $quotationsLead = EQuotation::select('e_quotations.id','e_quotations.user_id','ui.short_name as create_name','e_quotations.proyect_name','e_quotations.created_at')
            ->leftJoin('c_erp_info_users as ui','ui.user_id','e_quotations.user_id')
            ->where('e_quotations.is_for_lead',1)
            ->where('e_quotations.lead_id',$lead)
            ->get();

            return response()->json([
                'success'       =>  true ,
                'quotationsLead' =>  $quotationsLead,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
    }

    public function getQuotationsInitPag(Request $request)
    {
        // try {
            // verificamos si es  lider
            $lider = CErpInfoUser::select('is_leader')->where('user_id',$request->user_id)->first();
            // DATA
            $search = $request->search;
            switch ( (INT)$request->opt ) {
                case 1: // INIT
                    $quotations =  self::allQuotations($request->user_id,$lider['is_leader'],$request->page,$request->limit,$search,$request->isSearch);
                    //TOTALS REGS
                    $pageCountData = EQuotation::select( DB::raw('COUNT(*) as num') )
                    ->join('c_users','c_users.id','e_quotations.client_id')
                    ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id');
                    if( (INT)$lider['is_leader'] === 0 ) { $pageCountData->where('e_quotations.user_id',$request->user_id); }
                    if( (INT)$request->isSearch) {
                        $pageCountData->where( function($query) use ($search){
                            return $query
                            ->orWhere('e_quotations.id','like','%'.$search.'%')
                            ->orWhere('e_quotations.user_id','like','%'.$search.'%')
                            ->orWhere('c_erp_info_users.short_name','like','%'.$search.'%')
                            ->orWhere('e_quotations.client_id','like','%'.$search.'%')
                            ->orWhere('c_users.full_name','like','%'.$search.'%')
                            ->orWhere('e_quotations.proyect_name','like','%'.$search.'%');
                        });
                    }
                    $pageCountData = $pageCountData->whereIn('e_quotations.status_id',[1,4])
                    ->first();
                break;
                case 2: // CANCEL
                    $quotations =  self::allQuotationsCancel($request->user_id,$lider['is_leader'],$request->page,$request->limit,$search,$request->isSearch);
                    $pageCountData = EQuotation::select( DB::raw('COUNT(*) as num') )
                    ->join('c_users','c_users.id','e_quotations.client_id')
                    ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id');
                    if( (INT)$lider['is_leader'] === 0 ) { $pageCountData->where('e_quotations.user_id',$request->user_id); }
                    if( (INT)$request->isSearch) {
                        $pageCountData->where( function($query) use ($search){
                            return $query
                            ->orWhere('e_quotations.id','like','%'.$search.'%')
                            ->orWhere('e_quotations.user_id','like','%'.$search.'%')
                            ->orWhere('c_erp_info_users.short_name','like','%'.$search.'%')
                            ->orWhere('e_quotations.client_id','like','%'.$search.'%')
                            ->orWhere('c_users.full_name','like','%'.$search.'%')
                            ->orWhere('e_quotations.proyect_name','like','%'.$search.'%');
                        });
                    }
                    $pageCountData = $pageCountData->where('e_quotations.status_id',2)
                    ->first();
                break;
                case 3: // IN ORDER
                    $quotations =  self::allQuotationsInOrder($request->user_id,$lider['is_leader'],$request->page,$request->limit,$search,$request->isSearch);
                    $pageCountData = EQuotation::select( DB::raw('COUNT(*) as num') )
                    ->join('c_users','c_users.id','e_quotations.client_id')
                    ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id');
                    if( (INT)$lider['is_leader'] === 0 ) { $pageCountData->where('e_quotations.user_id',$request->user_id); }
                    if( (INT)$request->isSearch) {
                        $pageCountData->where( function($query) use ($search){
                            return $query
                            ->orWhere('e_quotations.id','like','%'.$search.'%')
                            ->orWhere('e_quotations.user_id','like','%'.$search.'%')
                            ->orWhere('c_erp_info_users.short_name','like','%'.$search.'%')
                            ->orWhere('e_quotations.client_id','like','%'.$search.'%')
                            ->orWhere('c_users.full_name','like','%'.$search.'%')
                            ->orWhere('e_quotations.proyect_name','like','%'.$search.'%');
                        });
                    }
                    $pageCountData = $pageCountData->where('e_quotations.status_id',3)
                    ->first();
                break;
            }

            return response()->json([
                'success'       =>  true ,
                'pageCount' =>  ceil($pageCountData->num/$request->limit),
                'quotations' =>  $quotations,
                'opt' =>  $request->opt,
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
        // try {
            $EQuotation = new EQuotation;
            $EQuotation->user_id           = $request->user_id;
            $EQuotation->client_id         = $request->client_id;
            $EQuotation->proyect_name      = $request->proyect_name;
            $EQuotation->payment_method_id = $request->payment_method_id;
            $EQuotation->payment_option_id = $request->payment_option_id;
            $EQuotation->account_number    = $request->account_number;
            $EQuotation->delivery_type_id  = $request->delivery_type_id;
            $EQuotation->client_address_id = $request->client_address_id;
            $EQuotation->save();
            $quotation =  self::individualQuotation($EQuotation->id);
            // mandar cotizacion a los lideres
            $userLider = CErpInfoUser::select('user_id as id')->where('is_leader',1)->whereNotIn('user_id',[$request->user_id])->get();
            foreach ($userLider as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
            // mandar la cotizacion al cliente
            $client_socket = DSocketConnection::select('socket_id','user_id','user_type')->where('user_id',$request->client_id)->where('user_type','CLIE')->get();
            return response()->json([
                'success'       =>  true ,
                'quotation'     =>  $quotation,
                'quotation_id'  =>  $EQuotation->id,
                'users_socket'  =>  $users_socket,
                'client_socket' =>  $client_socket,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function storeQLead(Request $request)
    {
        // try {
            $EQuotation = new EQuotation;
            $EQuotation->user_id            = $request->user_id;
            $EQuotation->client_id          = $request->client_id;
            $EQuotation->proyect_name       = $request->proyect_name;
            $EQuotation->payment_method_id  = $request->payment_method_id;
            $EQuotation->payment_option_id  = $request->payment_option_id;
            $EQuotation->account_number     = $request->account_number;
            $EQuotation->delivery_type_id   = $request->delivery_type_id;
            $EQuotation->client_address_id  = $request->client_address_id;
            $EQuotation->lead_id            = $request->lead_id;
            $EQuotation->is_for_lead        = 1;
            $EQuotation->save();
            $quotation =  self::individualQuotation($EQuotation->id);
            // mandar cotizacion a los lideres
            $userLider = CErpInfoUser::select('user_id as id')->where('is_leader',1)->whereNotIn('user_id',[$request->user_id])->get();
            foreach ($userLider as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
            // mandar la cotizacion al cliente
            $client_socket = DSocketConnection::select('socket_id','user_id','user_type')->where('user_id',$request->client_id)->where('user_type','CLIE')->get();
            return response()->json([
                'success'       =>  true ,
                'quotation'     =>  $quotation,
                'quotation_id'  =>  $EQuotation->id,
                'users_socket'  =>  $users_socket,
                'client_socket' =>  $client_socket,
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
     * @param  \App\Models\EQuotation  $eQuotation
     * @return \Illuminate\Http\Response
     */
    public function show(EQuotation $eQuotation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EQuotation  $eQuotation
     * @return \Illuminate\Http\Response
     */
    public function edit(EQuotation $eQuotation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EQuotation  $eQuotation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EQuotation $eQuotation)
    {
        try {
            EQuotation::where('id',$request->quotation_id)
            ->update([
                'client_id'         => $request->client_id,
                'proyect_name'      => $request->proyect_name,
                'payment_method_id' => $request->payment_method_id,
                'payment_option_id' => $request->payment_option_id,
                'account_number'    => $request->account_number,
                'delivery_type_id'  => $request->delivery_type_id,
                'client_address_id' => $request->client_address_id,
            ]);

            $quotation =  self::individualQuotation($request->quotation_id);
            return response()->json([
                'success'       =>  true ,
                'quotation' =>  $quotation,
                'quotation_id' =>  $request->quotation_id,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EQuotation  $eQuotation
     * @return \Illuminate\Http\Response
     */
    public function destroy(EQuotation $eQuotation)
    {
        //
    }

    public function setQuotationCancel(Request $request)
    {
        // try {
            EQuotation::where('id',$request->id)
            ->update(['status_id'=>2]);
            $quotation =  self::individualQuotation($request->id);
            return response()->json([
                'success'      =>  true ,
                'id' =>  $request->id,
                'quotation'    =>  $quotation,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    public function setQuotationReturn(Request $request)
    {
        // try {
            EQuotation::where('id',$request->id)
            ->update(['status_id'=>1]);
            $quotation =  self::individualQuotation($request->id);
            return response()->json([
                'success'      =>  true ,
                'id' =>  $request->id,
                'quotation'    =>  $quotation,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    public function startOrder(Request $request)
    {
        // try {
            // verificamos que la cotizacion no tenga un pedido asignado
            $orderInQuotation = EQuotation::select('order_id')->where('id',$request->quotation['id'])->first();
            if(is_null($orderInQuotation->order_id) || $request->is_edit) {
                $orderID = 0;
                if( !$request->is_edit ) {
                    // Obtenemos el agente que acutalmente maneja el cliente
                    $agent = CUser::where('id',$request->quotation['client_id'])->first();
                    // Creamos la estructura
                    $EOrder = new EOrder();
                    $EOrder->user_id = $request->quotation['user_id'];
                    $EOrder->client_id = $request->quotation['client_id'];
                    if( !is_null($agent['agent_id']) ) { $EOrder->agent_id = $agent['agent_id']; }
                    $EOrder->proyect_name = $request->quotation['proyect_name'];
                    $EOrder->payment_method_id = $request->quotation['payment_method_id'];
                    $EOrder->payment_option_id = $request->quotation['payment_option_id'];
                    $EOrder->account_number = $request->quotation['account_number'];
                    $EOrder->delivery_type_id = $request->quotation['delivery_type_id'];
                    $EOrder->client_address_id = $request->quotation['client_address_id'];
                    $EOrder->quotation_id = $request->quotation['id'];
                    $EOrder->type_system_id = 1;
                    $EOrder->save();
                    // cambiamos el status  de la cotizacion
                    EQuotation::where('id',$request->quotation['id'])
                    ->update([
                        'status_id'=>3,
                        'order_id'=>$EOrder->id
                    ]);
                    $orderID = $EOrder->id;
                } else {
                    $orderID = $request->order_id;
                    // LOG
                    $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
                    $logs->createMovementLog($request->quotation['user_id'],'Edición de pedido.',1,3,2,3,'order_id',$orderID,'Se realizaron cambios al pedido.');
                }
                // Creamos los detalles
                $dataRecord = [];
                $item_id = 1;
                foreach ($request->quotation['details'] as $items) {
                    /* if($items['quotation_product_id'] == 1 OR  $items['quotation_product_id'] == 2 AND $items['divisions'] == 1) {
                        for( $i=1 ;  $i <= $items['quantity'] ; $i++ ) {
                            $dataRecord[] = [
                                'order_id'             => $orderID,
                                'item_id'              => $item_id,
                                'article_id'           => $items['article_id'],
                                'product_id'           => $items['quotation_product_id'],
                                'operation_id'         => $items['operation_id'],
                                'quantity'             => 1,
                                'width'                => $items['width'],
                                'height'               => $items['height'],
                                'price'                => $items['price'],
                                'discount1'            => $request->quotation['client_discount'],
                                'discount2'            => $items['article_discount'],
                                'discount3'            => $items['request_discount'],
                                'fall'                 => $items['fall'],
                                'counterweight_bar_id' => $items['counterweight_bar_id'],
                                'chain_id'             => $items['chain_id'],
                                'height_chain'         => $items['height_chain'],
                                'mechanism_id'         => $items['mechanism_id'],
                                'side_id'              => $items['side_id'],
                                'mechanism_side_id'    => $items['mechanism_side_id'],
                                'unit_id'              => $items['unit_id'],
                                'component_color_id'   => $items['component_color_id'],
                                'commit'               => $items['commit'],
                                'commit_client'        => $items['commit_client'],
                                'awning_type_id'       => $items['awning_type_id'],
                                'area_description'     => $items['area_description'],
                                'is_inverted'          => $items['is_inverted'],
                                'is_heat_seal'         => $items['is_heat_seal'],
                                'relation_id'          => $items['relation_id'],
                                'relation_cassette'    => $items['relation_cassette'],
                                'cassette_id'          => $items['cassette_id'],
                                'relation_bracket'     => $items['relation_bracket'],
                                'relation_lambrequin'  => $items['relation_lambrequin'],
                                'relation_motor'       => $items['relation_motor'],
                                'motor_id'             => $items['motor_id'],
                                'relation_accesories'  => $items['relation_accesories'],
                                'tube_id'              => $items['tube_id'],
                                'lambrequin_id'        => $items['lambrequin_id'],
                                'lambrequin_id'        => $items['lambrequin_id'],
                                'is_tie_stripe'        => $items['is_tie_stripe'],
                                'divisions'            => $items['divisions'],
                                'quotation_item_id'    => $items['item_id'],
                            ];
                            $item_id++;
                        }
                    }  else { */
                        $dataRecord[] = [
                            'order_id'             => $orderID,
                            'item_id'              => $item_id,
                            'article_id'           => $items['article_id'],
                            'product_id'           => $items['quotation_product_id'],
                            'operation_id'         => $items['operation_id'],
                            'quantity'             => $items['quantity'],
                            'width'                => $items['width'],
                            'height'               => $items['height'],
                            'price'                => $items['price'],
                            'discount1'            => $request->quotation['client_discount'],
                            'discount2'            => $items['article_discount'],
                            'discount3'            => $items['request_discount'],
                            'fall'                 => $items['fall'],
                            'counterweight_bar_id' => $items['counterweight_bar_id'],
                            'chain_id'             => $items['chain_id'],
                            'height_chain'         => $items['height_chain'],
                            'mechanism_id'         => $items['mechanism_id'],
                            'side_id'              => $items['side_id'],
                            'mechanism_side_id'    => $items['mechanism_side_id'],
                            'unit_id'              => $items['unit_id'],
                            'component_color_id'   => $items['component_color_id'],
                            'commit'               => $items['commit'],
                            'commit_client'        => $items['commit_client'],
                            'awning_type_id'       => $items['awning_type_id'],
                            'area_description'     => $items['area_description'],
                            'is_inverted'          => $items['is_inverted'],
                            'is_heat_seal'         => $items['is_heat_seal'],
                            'relation_id'          => $items['relation_id'],
                            'relation_cassette'    => $items['relation_cassette'],
                            'cassette_id'          => $items['cassette_id'],
                            'relation_bracket'     => $items['relation_bracket'],
                            'relation_lambrequin'  => $items['relation_lambrequin'],
                            'relation_motor'       => $items['relation_motor'],
                            'motor_id'             => $items['motor_id'],
                            'relation_accesories'  => $items['relation_accesories'],
                            'relation_heat_seal'   => $items['relation_heat_seal'],
                            'relation_bracket_dn'  => $items['relation_bracket_dn'],
                            'relation_control'     => $items['relation_control'],
                            'channel'              => $items['channel'],
                            'tube_id'              => $items['tube_id'],
                            'lambrequin_id'        => $items['lambrequin_id'],
                            'corbatin_id'          => $items['corbatin_id'],
                            'fijo_id'              => $items['fijo_id'],
                            'is_tie_stripe'        => $items['is_tie_stripe'],
                            'divisions'            => $items['divisions'],
                            'quotation_item_id'    => $items['item_id'],
                            'is_velcro'            => $items['is_velcro'],
                            'relation_perfil_priv' => $items['relation_perfil_priv'],
                            'relation_tensor'      => $items['relation_tensor'],
                            'if_chain_height'      => $items['if_chain_height'],
                        ];
                        $item_id++;
                    // }
                }
                if($request->is_edit) { // si se esta editando
                    if(!is_null($request->order_id)) {
                        DOrder::where('order_id',$request->order_id)->delete();
                    }
                }
                DOrder::insert($dataRecord);
                // LOG
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->quotation['user_id'],'Creo un nuevo pedido',1,3,2,1,'order_id',$orderID,'Se creó un nuevo pedido a partir de la cotización No. '.$request->quotation['id']);
                // data order
                $EOrderSend =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id','c_users.full_name as client_name','c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
                ->join('c_users','c_users.id','e_orders.client_id')
                ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
                ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
                ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
                ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
                ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
                ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id')
                ->where('e_orders.id',$orderID)
                ->first();
                $DOrderSend = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro','d_orders.relation_perfil_priv','d_orders.relation_tensor','d_orders.if_chain_height')
                ->join('c_articles','c_articles.id','d_orders.article_id')
                ->join('c_products','c_products.id','d_orders.product_id')
                ->leftJoin('c_operations','c_operations.id','d_orders.operation_id')
                ->join('c_units','c_units.id','d_orders.unit_id')
                ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_orders.mechanism_side_id')
                ->leftJoin('c_mechanisms','c_mechanisms.id','d_orders.mechanism_id')
                ->leftJoin('c_chains','c_chains.id','d_orders.chain_id')
                ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_orders.counterweight_bar_id')
                ->leftJoin('c_colors','c_colors.id','d_orders.component_color_id')
                ->leftJoin('c_config_motors', function($join) {
                    $join->on('c_config_motors.article_id', '=', 'd_orders.motor_id');
                    $join->on('c_config_motors.num_divisions','=','d_orders.divisions');
                })
                ->leftJoin('c_articles as c_article_motor','c_article_motor.id','d_orders.motor_id')
                ->leftJoin('c_tubes','c_tubes.id','d_orders.tube_id')
                ->join('c_status_productions','c_status_productions.id','d_orders.status_production_id')
                ->leftJoin('c_articles AS la','la.id','d_orders.lambrequin_id')
                ->leftJoin('c_articles AS cb','cb.id','d_orders.corbatin_id')
                ->leftJoin('c_articles AS fj','fj.id','d_orders.fijo_id')
                ->where('order_id',$orderID)
                ->get();
                $order = self::setIndividualOrder($EOrderSend->toArray(),$DOrderSend->toArray());

                if(!$request->is_edit) { // SI NO SE ESTA EDITANDO
                    // Creamos las notificaciones
                    $users_ids = DErpAccessUser::select('user_id as id')
                    ->where('module_id', 4)
                    ->where('submodule_id', 10)
                    ->get();
                    $to = '/orders/pre';
                    $message = [
                        "title"       => 'Pedido por aprobar',
                        "description" => 'Tienes un nuevo pedido ('.$orderID.') para revisar y aprobar.',
                        "icon"        => 'mdi-clipboard-plus-outline',
                        "icon_color"  => '#ACE726',
                    ];
                    $notifications = new Notifications;
                    $notification = $notifications->createNewNotification($orderID,1,0,$users_ids,$message,$to);
                    $users_socket_ids = [];
                    foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
                    $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
                    return response()->json([
                        'success'      =>  true ,
                        'users_socket' => $users_socket,
                        'notification' => $notification,
                        'order'        => $order,
                        'quotation_id' => $request->quotation['id'],
                        'order_id'     => $orderID,
                        'is_edit'      => $request->is_edit,
                    ], 200);
                } else {
                    $users_socket = [];
                    $notification = [];
                    $users_socket = [];
                    return response()->json([
                        'success'      =>  true ,
                        'users_socket' => $users_socket,
                        'notification' => $notification,
                        'order'        => $order,
                        'quotation_id' => $request->quotation_id,
                        'order_id'     => $request->order_id,
                        'is_edit'      => $request->is_edit,
                    ], 200);
                }

            } else {
                return response()->json([
                    'success' => false ,
                    'error'   => 'Error al crear el pedido a partir de la cotización.'
                ], 400);
            }
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function downloadQuotationDetail($quotation_id)
    {
        // try {

            $quotation = $this->individualQuotation($quotation_id);
            $pdf = new FPDF(new PDF_Code128("L", "mm", "A4"));
            return $pdf->createQuotationDetail($quotation);

        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    public function sendEmailQuotation(Request $request)
    {
        // try {

            $quotation = $this->individualQuotation($request->quotation_id);
            $sendMail = new SendMail;
            $sendMail->sendQuotation($quotation,$request->all());

        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    private function allQuotations($user_id,$isLeader,$page,$limit,$search,$isSearch) {
        $EQuotation = EQuotation::select('e_quotations.id','e_quotations.user_id','ui.short_name as create_name','c_erp_info_users.short_name as agent_name','e_quotations.client_id',DB::raw("CASE WHEN e_quotations.is_for_lead = 1 THEN CONCAT('L-',e_leads.company) ELSE c_users.full_name END AS client_name"),'c_users.discount as client_discount','e_quotations.status_id','c_status_quotations.status','e_quotations.proyect_name','e_quotations.payment_method_id','c_payment_methods.payment_method','e_quotations.payment_option_id','c_payment_options.payment_option','e_quotations.account_number','e_quotations.delivery_type_id','c_delivery_types.delivery','e_quotations.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_quotations.created_at')
        ->join('c_users','c_users.id','e_quotations.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->leftJoin('c_erp_info_users as ui','ui.user_id','e_quotations.user_id')
        ->join('c_status_quotations','c_status_quotations.id','e_quotations.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_quotations.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_quotations.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_quotations.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_quotations.client_address_id')
        ->leftJoin('e_leads','e_leads.id','e_quotations.lead_id');
        if( (INT)$isLeader === 0 ) { $EQuotation->where('e_quotations.user_id',$user_id); }
        if( (INT)$isSearch) {
            $EQuotation->where( function($query) use ($search){
                return $query
                ->orWhere('e_quotations.id','like','%'.$search.'%')
                ->orWhere('e_quotations.user_id','like','%'.$search.'%')
                ->orWhere('c_erp_info_users.short_name','like','%'.$search.'%')
                ->orWhere('e_quotations.client_id','like','%'.$search.'%')
                ->orWhere('c_users.full_name','like','%'.$search.'%')
                ->orWhere('e_quotations.proyect_name','like','%'.$search.'%');
            });
        }
        $EQuotation = $EQuotation->whereIn('e_quotations.status_id',[1,4])
        ->orderBy('e_quotations.id','DESC')
        ->offset(($page - 1) * $limit)
        ->take($limit)
        ->get();
        $quotationIDs = [];
        foreach ($EQuotation as $equo) { $quotationIDs[] = $equo['id']; }
        $DQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_quotations.corbatin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_quotations.fijo_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE  CASE c_users.price_list_id WHEN 1 THEN c_articles.price WHEN 2 THEN c_articles.price_list_2 END END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','c_mechanisms.mechanism','d_quotations.relation_lambrequin','d_quotations.lambrequin_id','d_quotations.corbatin_id','d_quotations.fijo_id','d_quotations.is_velcro','d_quotations.relation_perfil_priv','d_quotations.relation_tensor','d_quotations.if_chain_height')
        ->join('e_quotations', function($join) use ($user_id,$isLeader) {
            $join->on('e_quotations.id', '=', 'd_quotations.quotation_id')
            ->whereIn('e_quotations.status_id',[1,4]);
            if( (INT)$isLeader === 0 ) {  $join->on('e_quotations.user_id','=',DB::raw($user_id)); }
        })
        ->join('c_articles','c_articles.id','d_quotations.article_id')
        ->join('c_products','c_products.id','d_quotations.quotation_product_id')
        ->leftJoin('c_operations','c_operations.id','d_quotations.operation_id')
        ->join('c_units','c_units.id','d_quotations.unit_id')
        ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_quotations.mechanism_side_id')
        ->leftJoin('c_chains','c_chains.id','d_quotations.chain_id')
        ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_quotations.counterweight_bar_id')
        ->leftJoin('c_colors','c_colors.id','d_quotations.component_color_id')
        ->leftJoin('c_tubes','c_tubes.id','d_quotations.tube_id')
        ->leftJoin('c_articles AS la','la.id','d_quotations.lambrequin_id')
        ->leftJoin('c_articles AS cb','cb.id','d_quotations.corbatin_id')
        ->leftJoin('c_articles AS fj','fj.id','d_quotations.fijo_id')
        ->leftJoin('c_users','c_users.id','e_quotations.client_id')
        ->leftJoin('c_mechanisms','c_mechanisms.id','d_quotations.mechanism_id')
        ->whereIn('d_quotations.quotation_id',$quotationIDs)
        ->get();
        $discountData = self::getDiscountData();
        $requestdiscounts = EQuotationDiscountRequest::select('e_quotation_discount_requests.id','e_quotation_discount_requests.user_id','c_erp_info_users.short_name','e_quotation_discount_requests.quotation_id','e_quotation_discount_requests.discount','e_quotation_discount_requests.reason','e_quotation_discount_requests.is_approved')
        ->join('c_erp_info_users','c_erp_info_users.user_id','e_quotation_discount_requests.user_id')
        ->where('e_quotation_discount_requests.is_active',1)
        ->get();
        $quotations = self::setAllQuotations($EQuotation->toArray(),$DQuotation->toArray(),$discountData,$requestdiscounts);
        return $quotations;
    }

    private function allQuotationsCancel($user_id,$isLeader,$page,$limit,$search,$isSearch) {
        $EQuotation = EQuotation::select('e_quotations.id','e_quotations.user_id','ui.short_name as create_name','c_erp_info_users.short_name as agent_name','e_quotations.client_id',DB::raw("CASE WHEN e_quotations.is_for_lead = 1 THEN CONCAT('L-',e_leads.company) ELSE c_users.full_name END AS client_name"),'c_users.discount as client_discount','e_quotations.status_id','c_status_quotations.status','e_quotations.proyect_name','e_quotations.payment_method_id','c_payment_methods.payment_method','e_quotations.payment_option_id','c_payment_options.payment_option','e_quotations.account_number','e_quotations.delivery_type_id','c_delivery_types.delivery','e_quotations.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_quotations.created_at')
        ->join('c_users','c_users.id','e_quotations.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->leftJoin('c_erp_info_users as ui','ui.user_id','e_quotations.user_id')
        ->join('c_status_quotations','c_status_quotations.id','e_quotations.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_quotations.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_quotations.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_quotations.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_quotations.client_address_id')
        ->leftJoin('e_leads','e_leads.id','e_quotations.lead_id');
        if( (INT)$isLeader === 0 ) { $EQuotation->where('e_quotations.user_id',$user_id); }
        if( (INT)$isSearch) {
            $EQuotation->where( function($query) use ($search){
                return $query
                ->orWhere('e_quotations.id','like','%'.$search.'%')
                ->orWhere('e_quotations.user_id','like','%'.$search.'%')
                ->orWhere('c_erp_info_users.short_name','like','%'.$search.'%')
                ->orWhere('e_quotations.client_id','like','%'.$search.'%')
                ->orWhere('c_users.full_name','like','%'.$search.'%')
                ->orWhere('e_quotations.proyect_name','like','%'.$search.'%');
            });
        }
        $EQuotation = $EQuotation->where('e_quotations.status_id',2)
        ->orderBy('e_quotations.id','DESC')
        ->offset(($page - 1) * $limit)
        ->take($limit)
        ->get();
        $quotationIDs = [];
        foreach ($EQuotation as $equo) { $quotationIDs[] = $equo['id']; }
        $DQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_quotations.corbatin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_quotations.fijo_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE CASE c_users.price_list_id WHEN 1 THEN c_articles.price WHEN 2 THEN c_articles.price_list_2 END END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','c_mechanisms.mechanism','d_quotations.relation_lambrequin','d_quotations.lambrequin_id','d_quotations.corbatin_id','d_quotations.fijo_id','d_quotations.is_velcro','d_quotations.relation_perfil_priv','d_quotations.relation_tensor','d_quotations.if_chain_height')
        ->join('e_quotations', function($join) use ($user_id,$isLeader) {
            $join->on('e_quotations.id', '=', 'd_quotations.quotation_id')
            ->where('e_quotations.status_id',2);
            if( (INT)$isLeader === 0 ) {  $join->on('e_quotations.user_id','=',DB::raw($user_id)); }
        })
        ->join('c_articles','c_articles.id','d_quotations.article_id')
        ->join('c_products','c_products.id','d_quotations.quotation_product_id')
        ->leftJoin('c_operations','c_operations.id','d_quotations.operation_id')
        ->join('c_units','c_units.id','d_quotations.unit_id')
        ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_quotations.mechanism_side_id')
        ->leftJoin('c_chains','c_chains.id','d_quotations.chain_id')
        ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_quotations.counterweight_bar_id')
        ->leftJoin('c_colors','c_colors.id','d_quotations.component_color_id')
        ->leftJoin('c_tubes','c_tubes.id','d_quotations.tube_id')
        ->leftJoin('c_articles AS la','la.id','d_quotations.lambrequin_id')
        ->leftJoin('c_articles AS cb','cb.id','d_quotations.corbatin_id')
        ->leftJoin('c_articles AS fj','fj.id','d_quotations.fijo_id')
        ->leftJoin('c_users','c_users.id','e_quotations.client_id')
        ->leftJoin('c_mechanisms','c_mechanisms.id','d_quotations.mechanism_id')
        ->whereIn('d_quotations.quotation_id',$quotationIDs)
        ->get();
        $discountData = self::getDiscountData();
        $requestdiscounts = EQuotationDiscountRequest::select('e_quotation_discount_requests.id','e_quotation_discount_requests.user_id','c_erp_info_users.short_name','e_quotation_discount_requests.quotation_id','e_quotation_discount_requests.discount','e_quotation_discount_requests.reason','e_quotation_discount_requests.is_approved')
        ->join('c_erp_info_users','c_erp_info_users.user_id','e_quotation_discount_requests.user_id')
        ->where('e_quotation_discount_requests.is_active',1)
        ->get();
        $quotations = self::setAllQuotations($EQuotation->toArray(),$DQuotation->toArray(),$discountData,$requestdiscounts);
        return $quotations;
    }

    private function allQuotationsInOrder($user_id,$isLeader,$page,$limit,$search,$isSearch) {
        $EQuotation = EQuotation::select('e_quotations.id','e_quotations.user_id','ui.short_name as create_name','c_erp_info_users.short_name as agent_name','e_quotations.client_id','e_quotations.order_id','c_users.full_name as client_name','c_users.discount as client_discount','e_quotations.status_id','c_status_quotations.status','e_quotations.proyect_name','e_quotations.payment_method_id','c_payment_methods.payment_method','e_quotations.payment_option_id','c_payment_options.payment_option','e_quotations.account_number','e_quotations.delivery_type_id','c_delivery_types.delivery','e_quotations.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_quotations.created_at','e_quotations.created_at','c_status_orders.status AS status_order','c_status_orders.color_status AS order_color_status')
        ->join('c_users','c_users.id','e_quotations.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->leftJoin('c_erp_info_users as ui','ui.user_id','e_quotations.user_id')
        ->join('c_status_quotations','c_status_quotations.id','e_quotations.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_quotations.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_quotations.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_quotations.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_quotations.client_address_id')
        ->join('e_orders','e_orders.id','e_quotations.order_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->whereNotIn('e_quotations.user_id',[100000,100001,100005]);
        if( (INT)$isLeader === 0 ) { $EQuotation->where('e_quotations.user_id',$user_id); }
        if( (INT)$isSearch) {
            $EQuotation->where( function($query) use ($search){
                return $query
                ->orWhere('e_quotations.id','like','%'.$search.'%')
                ->orWhere('e_quotations.user_id','like','%'.$search.'%')
                ->orWhere('c_erp_info_users.short_name','like','%'.$search.'%')
                ->orWhere('e_quotations.client_id','like','%'.$search.'%')
                ->orWhere('c_users.full_name','like','%'.$search.'%')
                ->orWhere('e_quotations.proyect_name','like','%'.$search.'%');
            });
        }
        $EQuotation = $EQuotation->where('e_quotations.status_id',3)
        ->orderBy('e_quotations.id','DESC')
        ->offset(($page - 1) * $limit)
        ->take($limit)
        ->get();
        $quotationIDs = [];
        foreach ($EQuotation as $equo) { $quotationIDs[] = $equo['id']; }
        $DQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_quotations.corbatin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_quotations.fijo_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE  CASE c_users.price_list_id WHEN 1 THEN c_articles.price WHEN 2 THEN c_articles.price_list_2 END END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','c_mechanisms.mechanism','d_quotations.relation_lambrequin','d_quotations.lambrequin_id','d_quotations.corbatin_id','d_quotations.fijo_id','d_quotations.is_velcro','d_quotations.relation_perfil_priv','d_quotations.relation_tensor','d_quotations.if_chain_height')
        ->join('e_quotations', function($join) use ($user_id,$isLeader) {
            $join->on('e_quotations.id', '=', 'd_quotations.quotation_id')
            ->where('e_quotations.status_id',3);
            if( (INT)$isLeader === 0 ) {  $join->on('e_quotations.user_id','=',DB::raw($user_id)); }
        })
        ->join('c_articles','c_articles.id','d_quotations.article_id')
        ->join('c_products','c_products.id','d_quotations.quotation_product_id')
        ->leftJoin('c_operations','c_operations.id','d_quotations.operation_id')
        ->join('c_units','c_units.id','d_quotations.unit_id')
        ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_quotations.mechanism_side_id')
        ->leftJoin('c_chains','c_chains.id','d_quotations.chain_id')
        ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_quotations.counterweight_bar_id')
        ->leftJoin('c_colors','c_colors.id','d_quotations.component_color_id')
        ->leftJoin('c_tubes','c_tubes.id','d_quotations.tube_id')
        ->leftJoin('c_articles AS la','la.id','d_quotations.lambrequin_id')
        ->leftJoin('c_articles AS cb','cb.id','d_quotations.corbatin_id')
        ->leftJoin('c_articles AS fj','fj.id','d_quotations.fijo_id')
        ->leftJoin('c_users','c_users.id','e_quotations.client_id')
        ->leftJoin('c_mechanisms','c_mechanisms.id','d_quotations.mechanism_id')
        ->whereIn('d_quotations.quotation_id',$quotationIDs)
        ->get();
        $discountData = self::getDiscountData();
        $requestdiscounts = EQuotationDiscountRequest::select('e_quotation_discount_requests.id','e_quotation_discount_requests.user_id','c_erp_info_users.short_name','e_quotation_discount_requests.quotation_id','e_quotation_discount_requests.discount','e_quotation_discount_requests.reason','e_quotation_discount_requests.is_approved')
        ->join('c_erp_info_users','c_erp_info_users.user_id','e_quotation_discount_requests.user_id')
        ->where('e_quotation_discount_requests.is_active',1)
        ->get();
        $quotations = self::setAllQuotations($EQuotation->toArray(),$DQuotation->toArray(),$discountData,$requestdiscounts);
        return $quotations;
    }

    private function individualQuotation($quotation_id) {
        $EQuotation =  EQuotation::select('e_quotations.id','e_quotations.user_id','c_erp_info_users.short_name as agent_name','e_quotations.client_id','c_users.full_name as client_name','c_users.discount as client_discount','e_quotations.status_id','c_status_quotations.status','e_quotations.proyect_name','e_quotations.payment_method_id','c_payment_methods.payment_method','e_quotations.payment_option_id','c_payment_options.payment_option','e_quotations.account_number','e_quotations.delivery_type_id','c_delivery_types.delivery','e_quotations.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_quotations.created_at')
        ->join('c_users','c_users.id','e_quotations.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_quotations','c_status_quotations.id','e_quotations.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_quotations.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_quotations.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_quotations.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_quotations.client_address_id')
        ->where('e_quotations.id',$quotation_id)
        ->first();
        $DQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_quotations.corbatin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_quotations.fijo_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE CASE c_users.price_list_id WHEN 1 THEN c_articles.price WHEN 2 THEN c_articles.price_list_2 END END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','d_quotations.default_tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','c_mechanisms.mechanism','d_quotations.relation_lambrequin','d_quotations.lambrequin_id','d_quotations.fijo_id','d_quotations.corbatin_id','d_quotations.fijo_id','d_quotations.is_velcro','d_quotations.default_mechanism_id','d_quotations.relation_perfil_priv','d_quotations.relation_tensor','d_quotations.if_chain_height')
        ->join('c_articles','c_articles.id','d_quotations.article_id')
        ->join('c_products','c_products.id','d_quotations.quotation_product_id')
        ->leftJoin('c_operations','c_operations.id','d_quotations.operation_id')
        ->join('c_units','c_units.id','d_quotations.unit_id')
        ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_quotations.mechanism_side_id')
        ->leftJoin('c_chains','c_chains.id','d_quotations.chain_id')
        ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_quotations.counterweight_bar_id')
        ->leftJoin('c_colors','c_colors.id','d_quotations.component_color_id')
        ->leftJoin('c_tubes','c_tubes.id','d_quotations.tube_id')
        ->leftJoin('c_articles AS la','la.id','d_quotations.lambrequin_id')
        ->leftJoin('c_articles AS cb','cb.id','d_quotations.corbatin_id')
        ->leftJoin('c_articles AS fj','fj.id','d_quotations.fijo_id')
        ->leftJoin('e_quotations','e_quotations.id','d_quotations.quotation_id')
        ->leftJoin('c_users','c_users.id','e_quotations.client_id')
        ->leftJoin('c_mechanisms','c_mechanisms.id','d_quotations.mechanism_id')
        ->where('d_quotations.quotation_id',$quotation_id)
        ->get();
        $discountData = self::getDiscountData();
        $requestdiscount = EQuotationDiscountRequest::select('e_quotation_discount_requests.id','e_quotation_discount_requests.user_id','c_erp_info_users.short_name','e_quotation_discount_requests.quotation_id','e_quotation_discount_requests.discount','e_quotation_discount_requests.reason','e_quotation_discount_requests.is_approved')
        ->join('c_erp_info_users','c_erp_info_users.user_id','e_quotation_discount_requests.user_id')
        ->where('e_quotation_discount_requests.is_active',1)
        ->where('e_quotation_discount_requests.quotation_id',$quotation_id)
        ->get();
        $quotation = self::setIndividualQuotations($EQuotation->toArray(),$DQuotation->toArray(),$discountData,$requestdiscount->toArray());
        return $quotation;
    }

    private function setAllQuotations($EQuotation,$DQuotation,$discountData,$requestdiscounts) {
        foreach ($EQuotation as $key => $equo) {
            $EQuotation[$key]['details'] = [];
            $EQuotation[$key]['request_discount'] = [];
            foreach ($DQuotation as $key2 =>  $dquo) {
                if($dquo['quotation_id'] == $equo['id']) {
                    // Obtener descuento de la partida mediante $discountData temporalmente le agregamos 0 descuento
                    $DQuotation[$key2]['article_discount'] = 0;

                    foreach ($requestdiscounts as  $rd) {
                        if($rd['quotation_id'] == $equo['id']) {
                            if( (INT)$rd['is_approved'] === 1 ) {
                                $DQuotation[$key2]['request_discount'] = $rd['discount'];
                            }
                        }
                    }
                    $EQuotation[$key]['details'][] = $DQuotation[$key2];
                }
            }
            foreach ($requestdiscounts as $key3 =>  $rd) {
                if($rd['quotation_id'] == $equo['id']) {
                    if( (INT)$rd['is_approved'] === 0 ) {
                        $EQuotation[$key]['request_discount'] = $requestdiscounts[$key3];
                    }
                }
            }
        }
        return $EQuotation;
    }

    private function setIndividualQuotations($EQuotation,$DQuotation,$discountData,$requestdiscount) {
        $EQuotation['details'] = [];
        $EQuotation['request_discount'] = [];
        foreach ($DQuotation as $key =>  $dquo) {
            // Obtener descuento de la partida mediante $discountData temporalmente le agregamos 0 descuento
            $DQuotation[$key]['article_discount'] = 0;
            // DISCOUTN 3 REQUEST
            foreach ($requestdiscount as $rd) {
                if( (INT)$rd['is_approved'] === 1 ) {
                    $DQuotation[$key]['request_discount'] = $rd['discount'];
                }
            }
            $EQuotation['details'][] = $DQuotation[$key];
        }
        foreach ($requestdiscount as $rd) {
            $EQuotation['request_discount'] = $rd;
        }
        return $EQuotation;
    }

    private function setIndividualOrder($EOrder,$DOrder) {
        $EOrder['details'] = [];
        foreach ($DOrder as $dord) {
            $EOrder['details'][] = $dord;
        }
        return $EOrder;
    }

    private function getDiscountData() {
        $discountData = DQuotationDiscount::select()->get();
        return $discountData;
    }
}
