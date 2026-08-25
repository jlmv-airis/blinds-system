<?php

namespace App\Http\Controllers;

require_once app_path() . "/fpdf/fpdf.php";
require_once app_path() . "/fpdf/PDF_Code128.php";

use App\Models\DMaterialRequest;
use App\Models\DOrder;
use App\Models\EMaterialRequest;
use App\Models\EOrder;
use App\Models\CLocalInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\classes\FPDF;
use App\classes\Logs;
use App\classes\Modulation;
use App\classes\Notifications;
use App\Models\COrganizerCart;
use App\Models\CProvider;
use App\Models\DAddMaterialRequest;
use App\Models\DErpAccessUser;
use App\Models\DOrganizerCart;
use App\Models\DProductionLine;
use App\Models\DSocketConnection;
use App\Models\EModulation;
use App\Models\EOrganizerCart;
use App\Models\EProductionLine;
use Carbon\Carbon;
use App\classes\WebService;
use App\Models\DGuaranty;
use App\Models\DSection;
use App\Models\EGuaranty;
use App\Models\ESection;
use Illuminate\Support\Str;
use PDF_Code128;

class EMaterialRequestController extends Controller
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
     * @param  \App\Models\EMaterialRequest  $eMaterialRequest
     * @return \Illuminate\Http\Response
     */
    public function show(EMaterialRequest $eMaterialRequest, $request_id)
    {
        // try {
            // obtenemos el inventario
            $rowData = $this->webService->getInventoryRT();
            // Obtenemos los lotes
            $rowDataLotes = $this->webService->getLotesRT();
            $inventory = [];
            $lots = [];
            if(!$rowData) {
                $inventory = [ "lots" => [] ];
            } else {
                foreach ($rowData->items as $key => $item) {
                    $inventory[] = [
                        'sku'     => $item->CVE_ART,
                        'article' => $item->DESCR,
                        'unit'    => $item->UNI_MED,
                        'stock'   => $item->EXIST,
                        'lots'    => [],
                    ];
                    foreach ($rowDataLotes->items as $key2 => $lots) {
                        if($item->CVE_ART == $lots->CVE_ART) {
                            if($lots->STATUS == 'A') {
                                $inventory[$key]['lots'][] = [
                                    'lot' => $lots->LOTE,
                                    'stock' =>$lots->CANTIDAD,
                                ];
                            }
                        }
                    }
                }
            }
            // DATA
            $EMaterialRequest = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
            ->where('e_material_requests.id',$request_id)
            ->first();
            $DMaterialRequest = DMaterialRequest::select('d_material_requests.detail_order_id','d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit','d_material_requests.type_reg', 'd_material_requests.cost',DB::raw('SUM(d_material_requests.quantity) AS quantity, (SUM(d_material_requests.quantity) * d_material_requests.cost ) AS total') )
            ->join('c_articles','c_articles.id','d_material_requests.article_id')
            ->join('c_units','c_units.id','d_material_requests.unit_id')
            ->where('d_material_requests.material_request_id',$request_id)
            ->groupBy('d_material_requests.detail_order_id','d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','d_material_requests.type_reg','c_units.unit')
            ->get();
            $DMaterialGeneralRequest = DMaterialRequest::select('d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit', 'd_material_requests.cost',DB::raw('SUM(d_material_requests.quantity) AS quantity, (SUM(d_material_requests.quantity) * d_material_requests.cost ) AS total') )
            ->join('c_articles','c_articles.id','d_material_requests.article_id')
            ->join('c_units','c_units.id','d_material_requests.unit_id')
            ->where('d_material_requests.material_request_id',$request_id)
            ->groupBy('d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit','d_material_requests.cost')
            ->get();


            // ORDERS
            $getOrders = DMaterialRequest::select('d_orders.order_id')
            ->join('d_orders','d_orders.id','d_material_requests.detail_order_id')
            ->where('d_material_requests.material_request_id',$request_id)
            ->where('d_material_requests.type_reg',DB::raw(1))  //  Type Orders
            ->groupBy('d_orders.order_id')
            ->get();
            $ordersID = [];
            foreach ($getOrders as $order) { $ordersID[] = $order['order_id']; }
            $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen, 1 as type_reg"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id','c_users.short_name as client_name','c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
            ->join('c_users','c_users.id','e_orders.client_id')
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
            ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
            ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
            ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
            ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
            ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id')
            ->whereIn('e_orders.id',$ordersID)
            ->get();
            $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.sku ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN cb.sku ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN fj.sku ELSE c_articles.sku END END END AS sku'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro')
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
            ->whereIn('d_orders.order_id',$ordersID)
            // ->whereIn('d_orders.product_id',[1,2])
            ->get();
            // $getOrders =

            // GUARANTEE
            $EGuaranty = EGuaranty::select('e_guarantee.id','e_guarantee.folio','e_guarantee.nomen',DB::raw("2 as type_reg"),'e_guarantee.user_id','c_erp_info_users.short_name as agent_name','e_guarantee.order_id','e_orders.client_id',DB::raw('CASE WHEN c_users.short_name IS NOT NULL THEN c_users.short_name ELSE c_users.full_name END AS client_name'),'e_guarantee.warranty_type_id','c_type_guarantee.type_warranty','e_guarantee.guarantee_error_id','c_guarantee_errors.guarantee_error','e_guarantee.guarantee_type_error_id','c_guarantee_type_errors.guarantee_type_error','e_guarantee.description','e_guarantee.status_id','c_status_guarantee.status','c_status_guarantee.color_status','e_guarantee.delivery_type_id','c_delivery_types.delivery',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_guarantee.if_installer_required','e_guarantee.created_at')
            ->join('e_orders','e_orders.id','e_guarantee.order_id')
            ->join('c_users','c_users.id','e_orders.client_id')
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_guarantee.user_id')
            ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
            ->join('c_type_guarantee','c_type_guarantee.id','e_guarantee.warranty_type_id')
            ->join('c_guarantee_errors','c_guarantee_errors.id','e_guarantee.guarantee_error_id')
            ->join('c_guarantee_type_errors','c_guarantee_type_errors.id','e_guarantee.guarantee_type_error_id')
            ->leftJoin('c_delivery_types','c_delivery_types.id','e_guarantee.delivery_type_id')
            ->leftJoin('c_user_addresses','c_user_addresses.id','e_guarantee.client_address_id')
            ->where('e_guarantee.material_request_id',$request_id)
            ->get();
            $guaranteeID = [];
            foreach ($EGuaranty as $warranty) { $guaranteeID[] = $warranty['id']; }
            $DGuaranty = DGuaranty::select('d_guarantee.id','d_guarantee.guarantee_id','d_guarantee.item_id','d_guarantee.article_id',DB::raw('CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN la.lambrequin_price ELSE d_guarantee.price END AS price, c_articles.model_id, CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN la.sku ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN cb.sku ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN fj.sku ELSE c_articles.sku END END END AS sku'),'d_guarantee.discount1','d_guarantee.discount2','d_guarantee.discount3','d_guarantee.quantity','d_guarantee.width','d_guarantee.height','d_guarantee.product_id','c_products.product','d_guarantee.operation_id','c_operations.operation','d_guarantee.fall','d_guarantee.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_guarantee.chain_id','c_chains.chain','d_guarantee.height_chain','d_guarantee.side_id','d_guarantee.mechanism_side_id','c_mechanism_sides.mechanism_side','d_guarantee.unit_id','c_units.unit','d_guarantee.component_color_id','c_colors.color as color_component','d_guarantee.commit','d_guarantee.commit_client','d_guarantee.awning_type_id','d_guarantee.area_description','d_guarantee.relation_id','d_guarantee.relation_bracket','d_guarantee.is_inverted','d_guarantee.is_heat_seal','d_guarantee.relation_cassette','d_guarantee.relation_lambrequin','d_guarantee.cassette_id','d_guarantee.relation_motor','d_guarantee.motor_id','d_guarantee.relation_accesories','d_guarantee.relation_heat_seal','d_guarantee.relation_bracket_dn','d_guarantee.relation_control','d_guarantee.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_guarantee.is_tie_stripe','d_guarantee.tube_id','c_tubes.tube','d_guarantee.divisions','d_guarantee.mechanism_id','c_mechanisms.mechanism','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_guarantee.production_location_id','d_guarantee.lambrequin_id','d_guarantee.fijo_id','d_guarantee.corbatin_id','d_guarantee.is_velcro')
            ->join('c_articles','c_articles.id','d_guarantee.article_id')
            ->join('c_products','c_products.id','d_guarantee.product_id')
            ->leftJoin('c_operations','c_operations.id','d_guarantee.operation_id')
            ->join('c_units','c_units.id','d_guarantee.unit_id')
            ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_guarantee.mechanism_side_id')
            ->leftJoin('c_mechanisms','c_mechanisms.id','d_guarantee.mechanism_id')
            ->leftJoin('c_chains','c_chains.id','d_guarantee.chain_id')
            ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_guarantee.counterweight_bar_id')
            ->leftJoin('c_colors','c_colors.id','d_guarantee.component_color_id')
            ->leftJoin('c_config_motors', function($join) {
                $join->on('c_config_motors.article_id', '=', 'd_guarantee.motor_id');
                $join->on('c_config_motors.num_divisions','=','d_guarantee.divisions');
            })
            ->leftJoin('c_articles as c_article_motor','c_article_motor.id','d_guarantee.motor_id')
            ->leftJoin('c_tubes','c_tubes.id','d_guarantee.tube_id')
            ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
            ->leftJoin('c_articles AS la','la.id','d_guarantee.lambrequin_id')
            ->leftJoin('c_articles AS cb','cb.id','d_guarantee.corbatin_id')
            ->leftJoin('c_articles AS fj','fj.id','d_guarantee.fijo_id')
            ->whereIn('d_guarantee.guarantee_id',$guaranteeID)
            ->get();

            $materialRequestOrders = self::setMaterialRequestsOrder($EMaterialRequest->toArray(),$DMaterialRequest->toArray(),$EOrder->toArray(),$DOrder->toArray(),$EGuaranty->toArray(),$DGuaranty->toArray(),$inventory);

            return response()->json([
                'success' =>  true ,
                'materialRequest' => $materialRequestOrders ,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EMaterialRequest  $eMaterialRequest
     * @return \Illuminate\Http\Response
     */
    public function edit(EMaterialRequest $eMaterialRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EMaterialRequest  $eMaterialRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EMaterialRequest $eMaterialRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EMaterialRequest  $eMaterialRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(EMaterialRequest $eMaterialRequest)
    {
        //
    }

    public function getModulationRequest($request_id) {
        // try {
            // obtenemos el inventario
            $rowData = [];
            // $rowData = $this->webService->getInventoryRT();
            // Obtenemos los lotes
            $rowDataLotes = [];
            // $rowDataLotes = $this->webService->getLotesRT();
            //  obtenemos las telas seleccionadas
            $cloths =  DMaterialRequest::select('d_material_requests.article_id','c_articles.article','c_articles.sku','c_articles.width_max',DB::raw('SUM(d_material_requests.quantity) AS quantity'))
            ->join('c_articles','c_articles.id','d_material_requests.article_id')
            ->join('c_models','c_models.id','c_articles.model_id')
            ->where('d_material_requests.material_request_id',$request_id)
            ->whereIn('c_models.product_id',[1,2])
            ->groupBy('d_material_requests.article_id')
            ->orderBy('c_models.product_id')
            ->get();
            // ORDERS
            $detailsOrders = DMaterialRequest::select('d_material_requests.id','d_material_requests.article_id','c_articles.article','d_material_requests.detail_order_id','d_orders.order_id',DB::raw("'LS' AS nomen"),'d_orders.item_id','c_models.product_id','c_products.product','d_orders.width','d_orders.height', DB::raw(' CASE WHEN d_orders.is_inverted = 1 THEN ROUND(( d_orders.height + d_modulations.height_add ),3) ELSE ROUND(( d_orders.width - d_modulations.width_discount ),3) END AS width_modulation, CASE WHEN d_orders.is_inverted = 1 THEN ROUND(( d_orders.width - d_modulations.width_discount ),3) ELSE ROUND(( d_orders.height + d_modulations.height_add ),3) END AS height_modulation'),'d_material_requests.quantity','d_modulations.join_id','d_modulations.width_discount','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_lambrequin','d_orders.lambrequin_id','d_orders.fijo_id','d_material_requests.width_lot','d_material_requests.lot','d_material_requests.relation_item','d_material_requests.type_reg','d_orders.area_description')
            ->join('c_articles','c_articles.id','d_material_requests.article_id')
            ->join('c_models','c_models.id','c_articles.model_id')
            ->join('d_modulations','d_modulations.detail_order_id','d_material_requests.detail_order_id')
            ->join('d_orders','d_orders.id','d_material_requests.detail_order_id')
            ->join('c_products','c_products.id','d_orders.product_id')
            ->where('d_material_requests.material_request_id',$request_id)
            ->where('d_material_requests.type_reg',1)
            ->whereIn('c_models.product_id',[1,2])
            ->orderBy('d_material_requests.relation_item')
            ->orderBy('c_models.product_id')
            ->orderBy('d_orders.width','DESC')
            ->get();
            // GUARANTEE
            $detailsGuarantee = DMaterialRequest::select('d_material_requests.id','d_material_requests.article_id','c_articles.article','d_material_requests.detail_order_id','d_guarantee.guarantee_id AS order_id','e_guarantee.folio','e_guarantee.nomen','d_guarantee.item_id','c_models.product_id','c_products.product','d_guarantee.width','d_guarantee.height', DB::raw(' CASE WHEN d_guarantee.is_inverted = 1 THEN ROUND(( d_guarantee.height + d_modulations.height_add ),3) ELSE ROUND(( d_guarantee.width - d_modulations.width_discount ),3) END AS width_modulation, CASE WHEN d_guarantee.is_inverted = 1 THEN ROUND(( d_guarantee.width - d_modulations.width_discount ),3) ELSE ROUND(( d_guarantee.height + d_modulations.height_add ),3) END AS height_modulation'),'d_material_requests.quantity','d_modulations.join_id','d_modulations.width_discount','d_guarantee.is_inverted','d_guarantee.is_heat_seal','d_guarantee.relation_lambrequin','d_guarantee.lambrequin_id','d_guarantee.fijo_id','d_material_requests.width_lot','d_material_requests.lot','d_material_requests.relation_item','d_material_requests.type_reg','d_guarantee.area_description')
            ->join('c_articles','c_articles.id','d_material_requests.article_id')
            ->join('c_models','c_models.id','c_articles.model_id')
            ->leftJoin('d_modulations', function ($join) {
                $join->on('d_modulations.detail_order_id', '=', 'd_material_requests.detail_order_id');
                $join->on('d_modulations.type_reg',DB::raw('2'));
            })
            ->leftJoin('d_guarantee', function ($join) {
                $join->on('d_guarantee.id', '=', 'd_material_requests.detail_order_id');
                $join->on('d_material_requests.type_reg',DB::raw('2'));
            })
            ->join('e_guarantee','e_guarantee.id','d_guarantee.guarantee_id')
            ->join('c_products','c_products.id','d_guarantee.product_id')
            ->where('d_material_requests.material_request_id',$request_id)
            ->where('d_material_requests.type_reg',2)
            ->whereIn('c_models.product_id',[1,2])
            ->orderBy('d_material_requests.relation_item')
            ->orderBy('c_models.product_id')
            ->orderBy('d_guarantee.width','DESC')
            ->get();
            // $getOrders =
            // $modulationRequest = self::setModulationRequests($cloths->toArray(),$detailsOrders->toArray(),$detailsGuarantee->toArray(),!$rowData ? [] : $rowData->items,!$rowDataLotes ? [] : $rowDataLotes->items);
            $modulationRequest = self::setModulationRequestsNew($cloths->toArray(),$detailsOrders->toArray(),$detailsGuarantee->toArray(),!$rowData ? [] : $rowData->items,!$rowDataLotes ? [] : $rowDataLotes->items);

            return response()->json([
                'success' =>  true ,
                'modulationRequest' => $modulationRequest ,
                'material_request_id' => $request_id ,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    public function getSetRequest($request_id) {
        // try {
            $DMaterialRequest = [];
            $EMaterialRequest = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
            ->where('e_material_requests.id',$request_id)
            ->first()
            ->toArray();
            // CLOTHS
            $statementCloth = DB::getPdo()->prepare("CALL sp_modulation(7,".$request_id.",0,0,0,0.0,0.0,0.0,'','','','','')");
            $statementCloth->execute();
            do {  $resultsCloth[] = $statementCloth->fetchAll(\PDO::FETCH_OBJ); } while ($statementCloth->nextRowSet());
            foreach (json_decode(json_encode($resultsCloth[0]), true) as $value) { $DMaterialRequest[] = $value; }
            // ACC
            $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(8,".$request_id.",0,0,0,0.0,0.0,0.0,'','','','','')");
            $statementAcc->execute();
            do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
            foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $DMaterialRequest[] = $value; }

            $EMaterialRequest['details'] = $DMaterialRequest;
            // PROVIDER
            $providers = CProvider::select('id','provider','company')->where('is_active',1)->get();
            return response()->json([
                'success' =>  true ,
                'setRequest' => $EMaterialRequest ,
                'providers' => $providers ,
            ], 200);
        // } catch (\Throwable $th) {
            //     return response()->json([
            //         'success' => false ,
            //         'error'   => $th
            //     ], 200);
        // }
    }

    public function downloadRequestDetail(Request $request) {

        $DMaterialRequest = [];
        $EMaterialRequest = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
        ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
        ->where('e_material_requests.id',$request->request_id)
        ->first();
        // CLOTHS
        $statementCloth = DB::getPdo()->prepare("CALL sp_modulation(5,".$request->request_id.",0,0,0,0.0,0.0,0.0,'','','','','')");
        $statementCloth->execute();
        do {  $resultsCloth[] = $statementCloth->fetchAll(\PDO::FETCH_OBJ); } while ($statementCloth->nextRowSet());
        foreach (json_decode(json_encode($resultsCloth[0]), true) as $value) { $DMaterialRequest[] = $value; }
        // ACC
        $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(6,".$request->request_id.",0,0,0,0.0,0.0,0.0,'','','','','')");
        $statementAcc->execute();
        do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
        foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $DMaterialRequest[] = $value; }
        return app(FPDF::class)->createRequestDetail($EMaterialRequest,$DMaterialRequest,1); // 1 - Geenral , 2 - proveedor
    }

    public function downloadRequestDetailAssortment(Request $request) {
        $DMaterialRequest = [];
        $EMaterialRequest = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
        ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
        ->where('e_material_requests.id',$request->request_id)
        ->first();
        // CLOTHS
        $statementCloth = DB::getPdo()->prepare("CALL sp_modulation(9,".$request->request_id.",1,0,0,0.0,0.0,0.0,'','','','','')"); // 1 - Rollertex
        $statementCloth->execute();
        do {  $resultsCloth[] = $statementCloth->fetchAll(\PDO::FETCH_OBJ); } while ($statementCloth->nextRowSet());
        foreach (json_decode(json_encode($resultsCloth[0]), true) as $value) { $DMaterialRequest[] = $value; }
        // ACC
        $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(10,".$request->request_id.",1,0,0,0.0,0.0,0.0,'','','','','')"); // 1 - Rollertex
        $statementAcc->execute();
        do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
        foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $DMaterialRequest[] = $value; }
        return app(FPDF::class)->createRequestDetail($EMaterialRequest,$DMaterialRequest,2); // 1 - Geenral , 2 - proveedor
    }

    public function saveSetRequestChanges(Request $request) {
        // try {
            $insertData = [];
            foreach ($request->details as $key => $article) {
                if($article['add_quantity'] != $article['original_add_quantity'] ) {
                    if(is_null($article['original_add_quantity'])) {
                        $insertData[] = [
                            'material_request_id' => $article['material_request_id'],
                            'lot'                 => $article['lot'],
                            'width_lot'           => $article['width_lot'],
                            'article_id'          => $article['article_id'],
                            'unit_id'             => $article['unit_id'],
                            'quantity'            => $article['add_quantity'],
                            'cost'                => $article['cost']
                        ];
                    }
                    if(empty($article['add_quantity'])) {
                        DAddMaterialRequest::where('material_request_id',$article['material_request_id'])
                        ->where('article_id',$article['article_id'])
                        ->where('lot',$article['lot'])
                        ->where('width_lot',$article['width_lot'])
                        ->where('unit_id',$article['unit_id'])
                        ->delete();
                    } else {
                        DAddMaterialRequest::where('material_request_id',$article['material_request_id'])
                        ->where('article_id',$article['article_id'])
                        ->where('lot',$article['lot'])
                        ->where('width_lot',$article['width_lot'])
                        ->where('unit_id',$article['unit_id'])
                        ->update(['quantity'=>$article['add_quantity']]);
                    }
                }
                if($article['provider_id'] != $article['original_provider_id'] ) {
                    DMaterialRequest::where('material_request_id',$article['material_request_id'])
                    ->where('article_id',$article['article_id'])
                    ->where('lot',$article['lot'])
                    ->where('width_lot',$article['width_lot'])
                    ->where('unit_id',$article['unit_id'])
                    ->update(['provider_id'=>$article['provider_id']]);
                }
            }
            DAddMaterialRequest::insert($insertData);

            $DMaterialRequest = [];
            $EMaterialRequest = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
            ->where('e_material_requests.id',$article['material_request_id'])
            ->first()
            ->toArray();
            // CLOTHS
            $statementCloth = DB::getPdo()->prepare("CALL sp_modulation(7,".$article['material_request_id'].",0,0,0,0.0,0.0,0.0,'','','','','')");
            $statementCloth->execute();
            do {  $resultsCloth[] = $statementCloth->fetchAll(\PDO::FETCH_OBJ); } while ($statementCloth->nextRowSet());
            foreach (json_decode(json_encode($resultsCloth[0]), true) as $value) { $DMaterialRequest[] = $value; }
            // ACC
            $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(8,".$article['material_request_id'].",0,0,0,0.0,0.0,0.0,'','','','','')");
            $statementAcc->execute();
            do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
            foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $DMaterialRequest[] = $value; }
            $EMaterialRequest['details'] = $DMaterialRequest;
            return response()->json([
                'success' =>  true ,
                'setRequest' => $EMaterialRequest ,
            ], 200);
    // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
    // }
    }

    public function sendRequest(Request $request) {
        try {
            $date_now = Carbon::now();
            // obtenemos todos los pedidos de la solicitud para actualizarlos
            $orders = DMaterialRequest::select('d_orders.order_id')
            ->join('d_orders','d_orders.id','d_material_requests.detail_order_id')
            ->where('d_material_requests.material_request_id',$request->request_id)
            ->where('d_material_requests.type_reg',1)
            ->groupBy('d_orders.order_id')
            ->get()
            ->toArray();
            foreach ($orders as $order) {
                EOrder::where('id',$order['order_id'])
                ->update([
                    'status_id'             => 7,
                    'material_request_date' => $date_now,
                    'production_date'       => $request->production_date,
                    'deadline_date'         => $this->dateDeadlineAssigned($request->production_date),
                ]);
                // Guardamos en Logs
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->user_id,'Actualizó status de pedido',1,6,14,3,'order_id',$order['order_id'],'Se actualizo status de pedido a Material solicitado');
            }
            // obtenemos las garantias de la solicitud para actualizarlos
            $guarantee = EGuaranty::select('id')
            ->where('material_request_id',$request->request_id)
            ->get()
            ->toArray();
            foreach ($guarantee as $guaranty) {
                EGuaranty::where('id',$guaranty['id'])
                ->update([
                    'status_id'             => 3,
                    'material_request_date' => $date_now,
                    'production_date'       => $request->production_date,
                    'deadline_date'         => $this->dateDeadlineAssigned($request->production_date),
                ]);
                // Guardamos en Logs
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->user_id,'Actualizó status de pedido',1,6,14,3,'guarantee_id',$guaranty['id'],'Se actualizo status de garantia a Material solicitado');
            }
            // cambiamos el status de la solicitud
            EMaterialRequest::where('id',$request->request_id)
            ->update([
                'status_id'       => 2,
                'request_user_id' => $request->user_id,
                'request_date'    => $date_now,
                'production_date' => $request->production_date,
            ]);
            // buscamos los usuarios del cambio
            $users_ids = DErpAccessUser::select('user_id as id')
            ->where('module_id', 6)
            ->where('submodule_id', 14)
            ->get();
            $users_not_ids = DErpAccessUser::select('user_id as id')
            ->where('module_id', 6)
            ->where('submodule_id', 16)
            ->get();
            $to = '/warehouse/material-assortment';
            $message = [
                "title"       => 'Solicitud de material',
                "description" => 'Te solicitaron material para producción',
                "icon"        => 'mdi-file-clock',
                "icon_color"  => '#00DDCC',
            ];
            // Guardamos usuarios para el socket
            $users_socket_ids = [];
            foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
            // cremos notificacion
            $users_socket_notifications_ids = [];
            $notifications = new Notifications;
            $notification = $notifications->createNewNotification($request->request_id,1,0,$users_not_ids,$message,$to);
            foreach ($users_not_ids as $value_not) { $users_socket_notifications_ids[] = $value_not['id']; }
            $users_socket_notification = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_notifications_ids)->where('user_type','ERP')->get();
            //MATERIAL ASSORTMENT
            $material_assortment = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.request_user_id','ur.short_name AS request_short_name','e_material_requests.request_date','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
            ->join('c_erp_info_users AS ur','ur.user_id','e_material_requests.request_user_id')
            ->where('e_material_requests.id',$request->request_id)
            ->first();
            return response()->json([
                'success'                   =>  true ,
                'request_id'                => $request->request_id,
                'material_assortment'       => $material_assortment,
                'users_socket'              => $users_socket,
                'users_socket_notification' => $users_socket_notification,
                'notification'              => $notification,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }

    public function checkMaterialAssortment(Request $request) {
        try {
            $date_now = Carbon::now();
            // cambiamos el status de la solicitud
            EMaterialRequest::where('id',$request->request_id)
            ->update([
                'check1'             => 1,
                'invoice_id'         => $request->invoice_id,
                'invoice_check_date' => $date_now,
            ]);
            // buscamos los usuarios del cambio
            $users_ids = DErpAccessUser::select('user_id as id')
            ->where('module_id', 6)
            ->where('submodule_id', 16)
            ->get();
            $users_changes_ids = DErpAccessUser::select('user_id as id')
            ->where('module_id', 5)
            ->where('submodule_id', 28)
            ->get();
            // Guardamos usuarios para el socket
            $users_socket_ids = [];
            foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
            // Guardamos usuarios para el socket
            $users_socket_changes_ids = [];
            foreach ($users_changes_ids as $value) { $users_socket_changes_ids[] = $value['id']; }
            $users_socket_change = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_changes_ids)->where('user_type','ERP')->get();
            //MATERIAL ASSORTMENT
            $validate_material = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.provider_user_id','e_material_requests.request_date','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
            ->where('e_material_requests.id',$request->request_id)
            ->first();
            return response()->json([
                'success'               =>  true ,
                'request_id'            => $request->request_id,
                'validate_material'     => $validate_material,
                'users_socket'          => $users_socket,
                'users_socket_change'   => $users_socket_change,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }

    public function sendMaterialAssortment(Request $request) {
        // try {
            if( (INT)$request->company_id === 1 ) {
                $date_now = Carbon::now();
                // obtenemos todos los pedidos de la solicitud para actualizarlos
                $orders = DMaterialRequest::select('d_orders.order_id')
                ->join('d_orders','d_orders.id','d_material_requests.detail_order_id')
                ->where('d_material_requests.material_request_id',$request->request_id)
                ->groupBy('d_orders.order_id')
                ->get()
                ->toArray();
                foreach ($orders as $order) {
                    EOrder::where('id',$order['order_id'])
                    ->update([
                        // 'status_id'=>4,
                        'material_assortment_date' => $date_now,
                    ]);
                    // Guardamos en LOGS
                    $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
                    $logs->createMovementLog($request->user_id,'Actualizó status de pedido',1,6,16,3,'order_id',$order['order_id'],'Se actualizo status de pedido a Material entregado');
                }
                // cambiamos el status de la solicitud
                EMaterialRequest::where('id',$request->request_id)
                ->update([
                    'status_id'       => 3,
                    'provider_user_id' => $request->user_id,
                    'material_assortment_date'    => $date_now,
                ]);
                // buscamos los usuarios del cambio
                $users_ids = DErpAccessUser::select('user_id as id')
                ->where('module_id', 6)
                ->where('submodule_id', 16)
                ->get();
                $users_not_ids = DErpAccessUser::select('user_id as id')
                ->where('module_id', 7)
                ->where('submodule_id', 18)
                ->get();
                $to = '/production/validate-material';
                $message = [
                    "title"       => 'Validación de material',
                    "description" => 'Tu material está surtido y a punto de entrega',
                    "icon"        => 'mdi-tooltip-check',
                    "icon_color"  => '#7F00DD',
                ];
                // Guardamos usuarios para el socket
                $users_socket_ids = [];
                foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
                $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
                // cremos notificacion
                $users_socket_notifications_ids = [];
                $notifications = new Notifications;
                $notification = $notifications->createNewNotification($request->request_id,1,0,$users_not_ids,$message,$to);
                foreach ($users_not_ids as $value_not) { $users_socket_notifications_ids[] = $value_not['id']; }
                $users_socket_notification = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_notifications_ids)->where('user_type','ERP')->get();
                //MATERIAL ASSORTMENT
                $validate_material = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.provider_user_id','ur.short_name AS provider_short_name','e_material_requests.request_date','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
                ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
                ->join('c_erp_info_users AS ur','ur.user_id','e_material_requests.provider_user_id')
                ->where('e_material_requests.id',$request->request_id)
                ->first();
                return response()->json([
                    'success'                   =>  true ,
                    'request_id'                => $request->request_id,
                    'validate_material'         => $validate_material,
                    'users_socket'              => $users_socket,
                    'users_socket_notification' => $users_socket_notification,
                    'notification'              => $notification,
                    'company_id'                => $request->company_id,
                ], 200);
            } else {
                $date_now = Carbon::now();
                ESection::where('id',$request->request_id)
                ->update([
                    'status_id'                 => 5,
                    'provider_user_id'          => $request->user_id,
                    'material_assortment_date'  => $date_now,
                ]);
                return response()->json([
                    'success'       =>  true ,
                    'request_id'    => $request->request_id,
                    'company_id'    => $request->company_id,
                ], 200);
            }
        // } catch (\Throwable $th) {
        //         return response()->json([
        //             'success' => false ,
        //             'error'   => $th
        //         ], 200);
        // }
    }

    public function sendValidMaterial(Request $request) {
        // try {
            if( (INT)$request->company_id === 1 ) {
                $date_now = Carbon::now();
                $orders_id = [];
                // obtenemos todos los pedidos de la solicitud para actualizarlos
                $orders = DMaterialRequest::select('d_orders.order_id')
                ->join('d_orders','d_orders.id','d_material_requests.detail_order_id')
                ->where('d_material_requests.material_request_id',$request->request_id)
                ->groupBy('d_orders.order_id')
                ->get()
                ->toArray();
                foreach ($orders as $order) {
                    $orders_id[] = $order['order_id'];
                    EOrder::where('id',$order['order_id'])
                    ->update([
                    // 'status_id'              => 12,
                        'validate_material_date' => $date_now,
                    ]);
                    // Guardamos en LOGS
                    $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
                    $logs->createMovementLog($request->user_id,'Actualizó status de pedido',1,6,16,3,'order_id',$order['order_id'],'Se actualizo status de pedido a En Producción');
                }
                $orders = self::allFilterOrders($orders_id);
                // cambiamos el status de la solicitud
                EMaterialRequest::where('id',$request->request_id)
                ->update([
                    'status_id'       => 4,
                    'complete_date'    => $date_now,
                ]);
                // buscamos los usuarios del cambio
                $users_ids = DErpAccessUser::select('user_id as id')
                ->where('module_id', 7)
                ->where('submodule_id', 18)
                ->get();
                $users_not_ids = DErpAccessUser::select('user_id as id')
                ->where('module_id', 7)
                ->where('submodule_id', 17)
                ->get();
                $to = '/production/orders';
                $message = [
                    "title"       => 'Órdenes en producción',
                    "description" => 'Ya cuentas con órdenes en producción.',
                    "icon"        => 'mdi-tooltip-check',
                    "icon_color"  => '#7F00DD',
                ];
                // Guardamos usuarios para el socket
                $users_socket_ids = [];
                foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
                $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
                // cremos notificacion
                $users_socket_notifications_ids = [];
                $notifications = new Notifications;
                $notification = $notifications->createNewNotification($request->request_id,1,0,$users_not_ids,$message,$to);
                foreach ($users_not_ids as $value_not) { $users_socket_notifications_ids[] = $value_not['id']; }
                $users_socket_notification = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_notifications_ids)->where('user_type','ERP')->get();


                return response()->json([
                    'success'                   =>  true ,
                    'request_id'                => $request->request_id,
                    'orders'                    => $orders,
                    'users_socket'              => $users_socket,
                    'users_socket_notification' => $users_socket_notification,
                    'notification'              => $notification,
                ], 200);
            } else {
                $date_now = Carbon::now();
                ESection::where('id',$request->request_id)
                ->update([
                    'status_id'     => 6,
                    'finalize_date' => $date_now,
                ]);
                return response()->json([
                    'success'       =>  true ,
                    'request_id'    => $request->request_id,
                    'company_id'    => $request->company_id,
                ], 200);
            }
        // } catch (\Throwable $th) {
        //         return response()->json([
        //             'success' => false ,
        //             'error'   => $th
        //         ], 200);
        // }
    }

    public function getRequestInventoryDetail(Request $request) {
        // try {
            $company_id = (INT)$request->company_id;
            if ($company_id == 0) {
                $company_id = 1;
            }
            if( $company_id === 1 ) {
                // obtenemos el inventario
                $rowData = $this->webService->getInventoryRT();
                // Obtenemos los lotes
                $rowDataLotes = $this->webService->getLotesRT();
                $inventoryAvailable = !($this->webService->httpFailed());
                // FALLBACK: si el ERP no responde, usamos inventario local
                if (!$inventoryAvailable) {
                    $localInventory = CLocalInventory::where('companie_id', 1)->where('is_active', 1)->with('lots')->get();
                    if ($localInventory->count() > 0) {
                        $localItems = [];
                        foreach ($localInventory as $li) {
                            $obj = new \stdClass();
                            $obj->CVE_ART = $li->sku;
                            $obj->DESCR   = $li->product;
                            $obj->UNI_MED = $li->unit;
                            $obj->EXIST   = $li->stock;
                            $localItems[] = $obj;
                        }
                        $localLotes = [];
                        foreach ($localInventory as $li) {
                            foreach ($li->lots as $lot) {
                                $lo = new \stdClass();
                                $lo->CVE_ART  = $li->sku;
                                $lo->LOTE     = $lot->lot;
                                $lo->CANTIDAD = $lot->stock;
                                $lo->STATUS   = ($lot->status ?: 'A');
                                $localLotes[] = $lo;
                            }
                        }
                        $rowData = (object)['items' => $localItems];
                        $rowDataLotes = (object)['items' => $localLotes];
                        $inventoryAvailable = true;
                    }
                }
                $inventory = [];
                $lots = [];
                foreach ($rowData->items as $key => $item) {
                    $inventory[] = [
                        'sku'     => $item->CVE_ART,
                        'article' => $item->DESCR,
                        'unit'    => $item->UNI_MED,
                        'stock'   => $item->EXIST,
                        'lots'    => [],
                    ];
                    foreach ($rowDataLotes->items as $key2 => $lots) {
                        if($item->CVE_ART == $lots->CVE_ART) {
                            if($lots->STATUS == 'A') {
                                $inventory[$key]['lots'][] = [
                                    'lot' => $lots->LOTE,
                                    'stock' =>$lots->CANTIDAD,
                                ];
                            }
                        }
                    }
                }
                // REQUEST
                $DMaterialRequest = [];
                $EMaterialRequest = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
                ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
                ->where('e_material_requests.id',$request->request_id)
                ->first()
                ->toArray();
                // CLOTHS
                $statementCloth = DB::getPdo()->prepare("CALL sp_modulation(5,".$request->request_id.",0,0,0,0.0,0.0,0.0,'','','','','')");
                $statementCloth->execute();
                do {  $resultsCloth[] = $statementCloth->fetchAll(\PDO::FETCH_OBJ); } while ($statementCloth->nextRowSet());
                foreach (json_decode(json_encode($resultsCloth[0]), true) as $value) { $DMaterialRequest[] = $value; }
                // // ACC
                // $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(6,".$request->request_id.",0,0,0,0.0,0.0,0.0,'','','','','')");
                // $statementAcc->execute();
                // do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
                // foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $DMaterialRequest[] = $value; }
                $EMaterialRequest['items'] = $DMaterialRequest;

                foreach ($EMaterialRequest['items'] as $key => $item) {
                    $EMaterialRequest['items'][$key]['inventory'] = [];
                    foreach ($inventory as $key2 => $iv) {
                        if($item['sku'] == $iv['sku']) {
                            $EMaterialRequest['items'][$key]['inventory'] = $iv;
                        }
                    }
                }
            } else {
                $response = [];
                $company_id = $request->company_id;
                if( (INT)$company_id === 2) { // INDIGOFF
                    $response = $this->webService->getInventoryRT();
                }
                if( (INT)$company_id === 4) { // INDIGOFF
                    $response = $this->webService->getInventoryINDF();
                }
                if( (INT)$company_id === 5) { // WRKS
                    $response = $this->webService->getInventoryWRKS();
                }
                $inventory = [];
                $lots = [];
                $inventoryAvailable = !($this->webService->httpFailed());
                // FALLBACK: si el ERP no responde, usamos inventario local
                if (!$inventoryAvailable && (INT)$company_id > 0) {
                    $localInventory = CLocalInventory::where('companie_id', (INT)$company_id)->where('is_active', 1)->with('lots')->get();
                    if ($localInventory->count() > 0) {
                        $localItems = [];
                        foreach ($localInventory as $li) {
                            $obj = new \stdClass();
                            $obj->CVE_ART = $li->sku;
                            $obj->DESCR   = $li->product;
                            $obj->UNI_MED = $li->unit;
                            $obj->EXIST   = $li->stock;
                            $localItems[] = $obj;
                        }
                        $response = (object)['items' => $localItems];
                        $inventoryAvailable = true;
                    }
                }
                foreach ($response->items as $key => $item) {
                    $inventory[] = [
                        'sku'     => $item->CVE_ART,
                        'article' => $item->DESCR,
                        'unit'    => $item->UNI_MED,
                        'stock'   => $item->EXIST,
                        'lots'    => [],
                    ];
                }
                $EMaterialRequest = ESection::select('e_sections.id','e_sections.user_id','e_sections.project','e_sections.company_id','c_companies.company','c_erp_info_users.short_name as user_name','e_sections.status_id','c_status_sections.status','c_status_sections.color_status','e_sections.detail','e_sections.quotation_id','e_sections.request_date','e_sections.material_request_date','e_sections.created_at')
                ->join('c_erp_info_users','c_erp_info_users.user_id','e_sections.user_id')
                ->join('c_status_sections','c_status_sections.id','e_sections.status_id')
                ->join('c_companies','c_companies.id','e_sections.company_id')
                ->where('e_sections.id',$request->request_id)
                ->first()
                ->toArray();
                $DSection = DSection::select('d_sections.id','d_sections.sku','c_inventory_products.product AS article','d_sections.projected',DB::raw('CASE WHEN d_add_section_requests.quantity != "" OR d_add_section_requests.quantity IS NOT NULL THEN (d_add_section_requests.quantity + d_sections.section) ELSE d_sections.section END AS quantity') )
                ->leftJoin('c_inventory_products', function($join) use($company_id){
                    $join->on('c_inventory_products.sku', '=', 'd_sections.sku')
                    ->where('c_inventory_products.company_id', '=', $company_id);
                })
                ->leftJoin('d_add_section_requests', function($join){
                    $join->on('d_add_section_requests.detail_section_id', '=', 'd_sections.id')
                    ->where('d_add_section_requests.is_complete', '=','0');
                })
                ->where('d_sections.section_id',$request->request_id)
                ->get()
                ->toArray();
                $EMaterialRequest['items'] = $DSection;

                foreach ($EMaterialRequest['items'] as $key => $item) {
                    $EMaterialRequest['items'][$key]['inventory'] = [];
                    foreach ($inventory as $key2 => $iv) {
                        if($item['sku'] == $iv['sku']) {
                            $EMaterialRequest['items'][$key]['inventory'] = $iv;
                        }
                    }
                }
            }

            $EMaterialRequest['inventory_available'] = $inventoryAvailable;

            return response()->json([
                'success'         =>  true ,
                'materialRequest' => $EMaterialRequest,
                'inventory_available' => $inventoryAvailable,
            ], 200);

        // } catch (\Throwable $th) {
        //         return response()->json([
        //             'success' => false ,
        //             'error'   => $th
        //         ], 200);
        // }
    }

    // public function saveAssignOrders(Request $request) {
    //     // try {
    //         // $numberProductionLine = 1;
    //         // // verificamos si ya se asigno pedidos en esa fecha y en esa linea
    //         // $check = EProductionLine::select('id')->where('production_line_id',$request->line_production_id)->where('production_date',$request->production_date)->first();
    //         // if(!is_null($check)) {
    //         //     $numberProductionLine = 1
    //         // }
    //         $orderDetails = [];
    //         $ordersID = '';
    //         $ordersArrID = [];
    //         foreach ($request->orders as $order) {
    //             $ordersID .= ','.$order['id'];
    //             $ordersArrID[] = $order['id'];
    //             foreach ($order['details'] as $orderDetail) {
    //                 $orderDetails[] = $orderDetail;
    //             }
    //         }
    //         $modulation = new Modulation();
    //         $modulations = $modulation->modulationAlls($orderDetails,$ordersID);
    //         // cremos la linea y el dia
    //         $EProductionLine                     = new EProductionLine();
    //         $EProductionLine->user_id            = $request->user_id;
    //         $EProductionLine->production_line_id = $request->line_production_id;
    //         $EProductionLine->production_date    = $request->production_date;
    //         $EProductionLine->save();
    //         // guardamos detalle de las lineas
    //         $dataProductionLineInsert = [];
    //         foreach ($orderDetails as $orderDetail) {
    //             $tube_id = null;
    //             $set_tube_id = null;
    //             $perfil_color_id = null;
    //             $set_perfil_id = null;
    //             $join_perfil = null;
    //             $counterweight_bar_id = null;
    //             $counterweight_color_id = null;
    //             $set_counterweight_id = null;
    //             $twistbar_id = null;
    //             $twistbar_color_id = null;
    //             $set_twistbar_id = null;
    //             // TUBES
    //             foreach ($modulations['tubes'] as $tube) {
    //                 foreach ($tube['items'] as $item) {
    //                     foreach ($item['moduled_items'] as $modulateItem) {
    //                         if((INT)$modulateItem['id'] === (INT)$orderDetail['id']) {
    //                             $tube_id = $tube['tube_id'];
    //                             $set_tube_id = $item['set_id'];
    //                         }
    //                     }
    //                 }
    //             }
    //             // PERFILES
    //             foreach ($modulations['perfiles'] as $perfil) {
    //                 foreach ($perfil['items'] as $item) {
    //                     foreach ($item['moduled_items'] as $modulateItem) {
    //                         if((INT)$modulateItem['detail_order_id_group'] === 0) {
    //                             if((INT)$modulateItem['id'] === (INT)$orderDetail['id']) {
    //                                 $perfil_color_id = $perfil['color_id'];
    //                                 $set_perfil_id = $item['set_id'];
    //                             }
    //                         } else {
    //                             $explDO = explode(',',$modulateItem['detail_order_id_group'] );
    //                             foreach ($explDO as $do) {
    //                                 if((INT)$do === (INT)$orderDetail['id']) {
    //                                     $perfil_color_id = $perfil['color_id'];
    //                                     $set_perfil_id = $item['set_id'];
    //                                     $join_perfil = $orderDetail['relation_id'];
    //                                 }
    //                             };
    //                         }

    //                     }
    //                 }
    //             }
    //             // COUNTERWEIGHT
    //             foreach ($modulations['counterweight'] as $counterweight) {
    //                 foreach ($counterweight['colors'] as $colors) {
    //                     foreach ($colors['items'] as $item) {
    //                         foreach ($item['moduled_items'] as $modulateItem) {
    //                             if((INT)$modulateItem['id'] === (INT)$orderDetail['id']) {
    //                                 $counterweight_bar_id = $counterweight['counterweight_bar_id'];
    //                                 $counterweight_color_id = $colors['color_id'];
    //                                 $set_counterweight_id = $item['set_id'];
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //             // TWISTBAR
    //             foreach ($modulations['twistbar'] as $twistbar) {
    //                 foreach ($twistbar['colors'] as $colors) {
    //                     foreach ($colors['items'] as $item) {
    //                         foreach ($item['moduled_items'] as $modulateItem) {
    //                             if((INT)$modulateItem['id'] === (INT)$orderDetail['id']) {
    //                                 $twistbar_id = $twistbar['twistbar_id'];
    //                                 $twistbar_color_id = $colors['color_id'];
    //                                 $set_twistbar_id = $item['set_id'];
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //             $dataProductionLineInsert[] = [
    //                 'detail_order_id'            => $orderDetail['id'],
    //                 'production_line_id'         => $EProductionLine->id,
    //                 'tube_id'                    => $tube_id,
    //                 'set_tube_id'                => $set_tube_id,
    //                 'perfil_color_id'            => $perfil_color_id,
    //                 'set_perfil_id'              => $set_perfil_id,
    //                 'join_perfil'                => $join_perfil,
    //                 'counterweight_bar_id'       => $counterweight_bar_id,
    //                 'counterweight_color_id'     => $counterweight_color_id,
    //                 'set_counterweight_id'       => $set_counterweight_id,
    //                 'twistbar_id'                => $twistbar_id,
    //                 'twistbar_color_id'          => $twistbar_color_id,
    //                 'set_twistbar_id'            => $set_twistbar_id,
    //             ];
    //         }
    //         DProductionLine::insert($dataProductionLineInsert);
    //         // CAMBIAMOS STATUS ORDENES
    //         $date_now = Carbon::now()->toDateTimeString();
    //         foreach ($request->orders as $order) {
    //             EOrder::where('id',$order['id'])
    //             ->update([
    //                 'status_id'              => 7,
    //                 'production_date' => $date_now,
    //                 'deadline_date' => $this->dateDeadlineAssigned($date_now),
    //                 'line_production_id' => $request->line_production_id
    //             ]);
    //             // Guardamos en LOGS
    //             $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
    //             $logs->createMovementLog($request->user_id,'Actualizó status de pedido',1,6,16,3,'order_id',$order['id'],'Se actualizo status de pedido a En Producción');
    //         }
    //         $this->assignOrganizerCart($EProductionLine->id,$date_now);
    //         $modulation = EProductionLine::select('e_production_lines.id','e_production_lines.user_id','e_production_lines.production_line_id','c_production_lines.line','e_production_lines.production_date')
    //         ->join('c_production_lines','c_production_lines.id','e_production_lines.production_line_id')
    //         ->where('e_production_lines.id',$EProductionLine->id)
    //         ->first();
    //         $orders = $this->allFilterOrders($ordersArrID);
    //         // SOCKET
    //         // buscamos los usuarios del cambio
    //         $users_ids = DErpAccessUser::select('user_id as id')
    //         ->where('module_id', 7)
    //         ->where('submodule_id', 17)
    //         ->get();
    //         $users_socket_ids = [];
    //         foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
    //         $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
    //         return response()->json([
    //             'success'            =>  true ,
    //             'line_production_id' => $request->line_production_id,
    //             'orders'             => $orders,
    //             'modulation'         => $modulation,
    //             'users_socket'       => $users_socket,
    //         ], 200);

    //     // } catch (\Throwable $th) {
    //     //         return response()->json([
    //     //             'success' => false ,
    //     //             'error'   => $th
    //     //         ], 200);
    //     // }
    // }

    private function setMaterialRequestsOrder($EMaterialRequest,$DMaterialRequest,$EOrder,$DOrder,$EGuaranty,$DGuaranty,$inventory) {

        // ORDERS
        $EMaterialRequest['orders'] = [];
        foreach ($EOrder as $key => $preOrder) { $EMaterialRequest['orders'][] = $preOrder; }
        foreach ($EGuaranty as $key => $preGuaranty) { $EMaterialRequest['orders'][] = $preGuaranty; }
        // var_dump($EMaterialRequest['orders']);
        // dd();
        // ORDERS
        foreach ($EMaterialRequest['orders'] as $key => $order) {
            $EMaterialRequest['orders'][$key]['details'] = [];
            foreach ($DOrder as $key2 => $dOrder) {
                if($order['id'] == $dOrder['order_id'] AND $order['type_reg'] == 1) {
                    $EMaterialRequest['orders'][$key]['details'][] = $dOrder;
                }
            }
            // GUARANTEE
            foreach ($DGuaranty as $key2 => $dw) {
                if($order['id'] == $dw['guarantee_id'] AND $order['type_reg'] == 2) {
                    $EMaterialRequest['orders'][$key]['details'][] = $dw;
                }
            }
            foreach ($EMaterialRequest['orders'][$key]['details'] as $key3 => $details) {
                $EMaterialRequest['orders'][$key]['details'][$key3]['requests'] = [];
                // ORDERS
                foreach ($DMaterialRequest as $key4 => $dMaterialRequest) {
                    if($details['id'] == $dMaterialRequest['detail_order_id'] AND $dMaterialRequest['type_reg'] == 1 ) {
                        $EMaterialRequest['orders'][$key]['details'][$key3]['requests'][] = $dMaterialRequest;
                    }
                    if($details['id'] == $dMaterialRequest['detail_order_id'] AND $dMaterialRequest['type_reg'] == 2 ) {
                        $EMaterialRequest['orders'][$key]['details'][$key3]['requests'][] = $dMaterialRequest;
                    }
                }
                if((INT)COUNT($inventory) > 2 ) {
                    $EMaterialRequest['orders'][$key]['details'][$key3]['inventory'] = [];
                    foreach ($inventory as $key5 => $iv) {
                        if($details['sku'] == $iv['sku']) {
                            $EMaterialRequest['orders'][$key]['details'][$key3]['inventory'] = $iv;
                        }
                    }
                } else {
                    $EMaterialRequest['orders'][$key]['details'][$key3]['inventory'] = [ "lots" => []];
                }
            }
        }
        return $EMaterialRequest;
    }

    private function setMaterialRequestsWarranty($EMaterialRequest,$DMaterialRequest,$EGuaranty,$DGuaranty,$inventory) {

        $EMaterialRequest['orders'] = [];
        $EMaterialRequest['orders'] = $EGuaranty;
        foreach ($EMaterialRequest['orders'] as $key => $order) {
            $EMaterialRequest['orders'][$key]['details'] = [];
            foreach ($DGuaranty as $key2 => $dw) {
                if($order['id'] == $dw['guarantee_id']) {
                    $EMaterialRequest['orders'][$key]['details'][] = $dw;
                }
            }
            foreach ($EMaterialRequest['orders'][$key]['details'] as $key3 => $details) {
                $EMaterialRequest['orders'][$key]['details'][$key3]['requests'] = [];
                foreach ($DMaterialRequest as $key4 => $dMaterialRequest) {
                    if($details['id'] == $dMaterialRequest['detail_order_id']) {
                        $EMaterialRequest['orders'][$key]['details'][$key3]['requests'][] = $dMaterialRequest;
                    }
                }
                $EMaterialRequest['orders'][$key]['details'][$key3]['inventory'] = [];
                foreach ($inventory as $key5 => $iv) {
                    if($details['sku'] == $iv['sku']) {
                        $EMaterialRequest['orders'][$key]['details'][$key3]['inventory'] = $iv;
                    }
                }
            }
        }

        return $EMaterialRequest;
    }

    private function setModulationRequests($cloths,$details,$detailsGuarantee,$inventory) {
        foreach ($cloths as $key => $cloth) {
            $cloths[$key]['details'] = [];
            $cloths[$key]['details'][0]['items'] = [];
            $cloths[$key]['details'][0]['widthMax'] = 2.95;
            $cloths[$key]['details'][0]['widthLot'] = 3;
            $cloths[$key]['details'][0]['inventory'] = [];

            $cloths[$key]['details'][1]['items'] = [];
            $cloths[$key]['details'][1]['widthMax'] = 2.45;
            $cloths[$key]['details'][1]['widthLot'] = 2.5;
            $cloths[$key]['details'][1]['inventory'] = [];

            $cloths[$key]['details'][2]['items'] = [];
            $cloths[$key]['details'][2]['widthMax'] = 1.95;
            $cloths[$key]['details'][2]['widthLot'] = 2;
            $cloths[$key]['details'][2]['inventory'] = [];

            $cloths[$key]['details'][3]['items'] = [];
            $cloths[$key]['details'][3]['widthMax'] = 2.85;
            $cloths[$key]['details'][3]['widthLot'] = 2.9;
            $cloths[$key]['details'][3]['inventory'] = [];

            $cloths[$key]['details'][4]['items'] = [];
            $cloths[$key]['details'][4]['widthMax'] = 2.78;
            $cloths[$key]['details'][4]['widthLot'] = 2.8;
            $cloths[$key]['details'][4]['inventory'] = [];

            $cloths[$key]['details'][5]['items'] = [];
            $cloths[$key]['details'][5]['widthMax'] = 2.99;
            $cloths[$key]['details'][5]['widthLot'] = 2.99;
            $cloths[$key]['details'][5]['inventory'] = [];

            foreach ($detailsGuarantee as $item) {
                if($cloth['article_id'] == $item['article_id']) {
                    switch ((DOUBLE)$item['width_lot']) {
                        case 3:
                            $cloths[$key]['details'][0]['items'][] = $item;
                        break;
                        case 2.5:
                            $cloths[$key]['details'][1]['items'][] = $item;
                        break;
                        case 2:
                            $cloths[$key]['details'][2]['items'][] = $item;
                        break;
                        case 2.9:
                            $cloths[$key]['details'][3]['items'][] = $item;
                        break;
                        case 2.8:
                            $cloths[$key]['details'][4]['items'][] = $item;
                        break;
                        case 2.99:
                            $cloths[$key]['details'][5]['items'][] = $item;
                        break;
                    }
                }
            }

            foreach ($details as $item) {
                if($cloth['article_id'] == $item['article_id']) {
                    switch ((DOUBLE)$item['width_lot']) {
                        case 3:
                            $cloths[$key]['details'][0]['items'][] = $item;
                        break;
                        case 2.5:
                            $cloths[$key]['details'][1]['items'][] = $item;
                        break;
                        case 2:
                            $cloths[$key]['details'][2]['items'][] = $item;
                        break;
                        case 2.9:
                            $cloths[$key]['details'][3]['items'][] = $item;
                        break;
                        case 2.8:
                            $cloths[$key]['details'][4]['items'][] = $item;
                        break;
                        case 2.99:
                            $cloths[$key]['details'][5]['items'][] = $item;
                        break;
                    }
                }
            }
            // inventory
            (INT)COUNT($inventory) > 0 ? $cloths[$key]['details'][0]['inventory'] = $this->foundInventoryArticle($cloth['sku'],$inventory,'3.00M',3) : [];
            (INT)COUNT($inventory) > 0 ? $cloths[$key]['details'][1]['inventory'] = $this->foundInventoryArticle($cloth['sku'],$inventory,'2.50M',2.5) : [];
            (INT)COUNT($inventory) > 0 ? $cloths[$key]['details'][2]['inventory'] = $this->foundInventoryArticle($cloth['sku'],$inventory,'2.00M',2) : [];
            (INT)COUNT($inventory) > 0 ? $cloths[$key]['details'][3]['inventory'] = $this->foundInventoryArticle($cloth['sku'],$inventory,'2.90M',2.9) : [];
            (INT)COUNT($inventory) > 0 ? $cloths[$key]['details'][4]['inventory'] = $this->foundInventoryArticle($cloth['sku'],$inventory,'2.80',2.8) : [];
        }

        return $cloths;
    }

    private function setModulationRequestsNew($cloths,$details,$detailsGuarantee,$inventory,$lots) {
        foreach ($cloths as $key => $cloth) {

            $cloths[$key]['details'] = [];
            $cloths[$key]['details'][0]['id'] = 1;
            $cloths[$key]['details'][0]['items'] = [];
            $cloths[$key]['details'][0]['widthMax'] = 2.95;
            $cloths[$key]['details'][0]['widthLot'] = 3;
            $cloths[$key]['details'][0]['lot_selected'] = '';
            $cloths[$key]['details'][0]['detail_lot_selected'] = '';
            $cloths[$key]['details'][0]['order'] = 1;
            $cloths[$key]['details'][0]['is_original'] = 1;
            $cloths[$key]['details'][0]['inventory'] = [];
            $cloths[$key]['details'][0]['lots'] = [];
            $cloths[$key]['details'][0]['sumHeights'] = 0;
            $cloths[$key]['details'][0]['totalScrap'] = 0;

            $cloths[$key]['details'][1]['id'] = 2;
            $cloths[$key]['details'][1]['items'] = [];
            $cloths[$key]['details'][1]['widthMax'] = 2.45;
            $cloths[$key]['details'][1]['widthLot'] = 2.5;
            $cloths[$key]['details'][1]['lot_selected'] = '';
            $cloths[$key]['details'][1]['detail_lot_selected'] = '';
            $cloths[$key]['details'][1]['order'] = 2;
            $cloths[$key]['details'][1]['is_original'] = 1;
            $cloths[$key]['details'][1]['inventory'] = [];
            $cloths[$key]['details'][1]['lots'] = [];
            $cloths[$key]['details'][1]['sumHeights'] = 0;
            $cloths[$key]['details'][1]['totalScrap'] = 0;

            $cloths[$key]['details'][2]['id'] = 3;
            $cloths[$key]['details'][2]['items'] = [];
            $cloths[$key]['details'][2]['widthMax'] = 1.95;
            $cloths[$key]['details'][2]['widthLot'] = 2;
            $cloths[$key]['details'][2]['lot_selected'] = '';
            $cloths[$key]['details'][2]['detail_lot_selected'] = '';
            $cloths[$key]['details'][2]['order'] = 3;
            $cloths[$key]['details'][2]['is_original'] = 1;
            $cloths[$key]['details'][2]['inventory'] = [];
            $cloths[$key]['details'][2]['lots'] = [];
            $cloths[$key]['details'][2]['sumHeights'] = 0;
            $cloths[$key]['details'][2]['totalScrap'] = 0;

            $cloths[$key]['details'][3]['id'] = 4;
            $cloths[$key]['details'][3]['items'] = [];
            $cloths[$key]['details'][3]['widthMax'] = 2.85;
            $cloths[$key]['details'][3]['widthLot'] = 2.9;
            $cloths[$key]['details'][3]['lot_selected'] = '';
            $cloths[$key]['details'][3]['detail_lot_selected'] = '';
            $cloths[$key]['details'][3]['order'] = 4;
            $cloths[$key]['details'][3]['is_original'] = 1;
            $cloths[$key]['details'][3]['inventory'] = [];
            $cloths[$key]['details'][3]['lots'] = [];
            $cloths[$key]['details'][3]['sumHeights'] = 0;
            $cloths[$key]['details'][3]['totalScrap'] = 0;

            $cloths[$key]['details'][4]['id'] = 5;
            $cloths[$key]['details'][4]['items'] = [];
            $cloths[$key]['details'][4]['widthMax'] = 2.78;
            $cloths[$key]['details'][4]['widthLot'] = 2.8;
            $cloths[$key]['details'][4]['lot_selected'] = '';
            $cloths[$key]['details'][4]['detail_lot_selected'] = '';
            $cloths[$key]['details'][4]['order'] = 5;
            $cloths[$key]['details'][4]['is_original'] = 1;
            $cloths[$key]['details'][4]['inventory'] = [];
            $cloths[$key]['details'][4]['lots'] = [];
            $cloths[$key]['details'][4]['sumHeights'] = 0;
            $cloths[$key]['details'][4]['totalScrap'] = 0;

            $cloths[$key]['details'][5]['id'] = 6;
            $cloths[$key]['details'][5]['items'] = [];
            $cloths[$key]['details'][5]['widthMax'] = 2.99;
            $cloths[$key]['details'][5]['widthLot'] = 2.99;
            $cloths[$key]['details'][5]['lot_selected'] = '';
            $cloths[$key]['details'][5]['order'] = 6;
            $cloths[$key]['details'][5]['inventory'] = [];
            $cloths[$key]['details'][5]['lots'] = [];
            $cloths[$key]['details'][5]['sumHeights'] = 0;
            $cloths[$key]['details'][5]['is_original'] = 1;
            $cloths[$key]['details'][5]['totalScrap'] = 0;

            $keyDetail = 6;
            $pointKeyDetail = 6;
            $saveKeys = [];
            $relation_end3 = null;
            $relation_end25 = null;
            $relation_end2 = null;
            $relation_end29 = null;
            $relation_end28 = null;
            foreach ($details as $item) {
                if($cloth['article_id'] == $item['article_id']) {
                    switch ((DOUBLE)$item['width_lot']) {
                        case 3:
                            // Relation items
                            $relation_init3 = $item['relation_item'];
                            if( (INT)$item['relation_item'] !== 1 AND $relation_init3 !== $relation_end3){
                                $cloths[$key]['details'][$keyDetail]['id'] = Str::random(15);
                                $cloths[$key]['details'][$keyDetail]['items'] = [];
                                $cloths[$key]['details'][$keyDetail]['widthMax'] = 2.95;
                                $cloths[$key]['details'][$keyDetail]['widthLot'] = 3;
                                $cloths[$key]['details'][$keyDetail]['widthLotText'] = '3.00M';
                                $cloths[$key]['details'][$keyDetail]['lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['detail_lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['order'] = 1;
                                $cloths[$key]['details'][$keyDetail]['is_original'] = 0;
                                $cloths[$key]['details'][$keyDetail]['inventory'] = [];
                                $cloths[$key]['details'][$keyDetail]['lots'] = [];
                                $cloths[$key]['details'][$keyDetail]['sumHeights'] = 0;
                                $cloths[$key]['details'][$keyDetail]['totalScrap'] = 0;
                                // guardamos el item nuevo
                                $saveKeys[] = $keyDetail;
                                $pointKeyDetail = $keyDetail;
                                $keyDetail++;
                            }
                            if( (INT)$item['relation_item'] === 1 OR is_null($item['relation_item']) ) {
                                $cloths[$key]['details'][0]['items'][] = $item;
                                $cloths[$key]['details'][0]['lot_selected'] = $item['lot'];
                            } else {
                                $cloths[$key]['details'][$pointKeyDetail]['items'][] = $item;
                                $cloths[$key]['details'][$pointKeyDetail]['lot_selected'] = $item['lot'];
                            }
                            $relation_end3 = $item['relation_item'];
                        break;
                        case 2.5:
                            // Relation items
                            $relation_init25 = $item['relation_item'];
                            if( (INT)$item['relation_item'] !== 1 AND $relation_init25 !== $relation_end25){
                                $cloths[$key]['details'][$keyDetail]['id'] = Str::random(15);
                                $cloths[$key]['details'][$keyDetail]['items'] = [];
                                $cloths[$key]['details'][$keyDetail]['widthMax'] = 2.45;
                                $cloths[$key]['details'][$keyDetail]['widthLot'] = 2.5;
                                $cloths[$key]['details'][$keyDetail]['widthLotText'] = '2.50M';
                                $cloths[$key]['details'][$keyDetail]['lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['detail_lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['order'] = 2;
                                $cloths[$key]['details'][$keyDetail]['is_original'] = 0;
                                $cloths[$key]['details'][$keyDetail]['inventory'] = [];
                                $cloths[$key]['details'][$keyDetail]['lots'] = [];
                                $cloths[$key]['details'][$keyDetail]['sumHeights'] = 0;
                                $cloths[$key]['details'][$keyDetail]['totalScrap'] = 0;
                                $saveKeys[] = $keyDetail;
                                $pointKeyDetail = $keyDetail;
                                $keyDetail++;
                            }

                            if( (INT)$item['relation_item'] === 1 OR is_null($item['relation_item']) ) {
                                $cloths[$key]['details'][1]['items'][] = $item;
                                $cloths[$key]['details'][1]['lot_selected'] = $item['lot'];
                            } else {
                                $cloths[$key]['details'][$pointKeyDetail]['items'][] = $item;
                                $cloths[$key]['details'][$pointKeyDetail]['lot_selected'] = $item['lot'];
                            }
                            $relation_end25 = $item['relation_item'];
                        break;
                        case 2:
                            $relation_init2 = $item['relation_item'];
                            if( (INT)$item['relation_item'] !== 1 AND $relation_init2 !== $relation_end2){
                                $cloths[$key]['details'][$keyDetail]['id'] = Str::random(15);
                                $cloths[$key]['details'][$keyDetail]['items'] = [];
                                $cloths[$key]['details'][$keyDetail]['widthMax'] = 2.95;
                                $cloths[$key]['details'][$keyDetail]['widthLot'] = 3;
                                $cloths[$key]['details'][$keyDetail]['widthLotText'] = '3.00M';
                                $cloths[$key]['details'][$keyDetail]['lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['detail_lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['order'] = 1;
                                $cloths[$key]['details'][$keyDetail]['is_original'] = 0;
                                $cloths[$key]['details'][$keyDetail]['inventory'] = [];
                                $cloths[$key]['details'][$keyDetail]['lots'] = [];
                                $cloths[$key]['details'][$keyDetail]['sumHeights'] = 0;
                                $cloths[$key]['details'][$keyDetail]['totalScrap'] = 0;
                                // guardamos el item nuevo
                                $saveKeys[] = $keyDetail;
                                $pointKeyDetail = $keyDetail;
                                $keyDetail++;
                            }
                            if( (INT)$item['relation_item'] === 1 OR is_null($item['relation_item']) ) {
                                $cloths[$key]['details'][2]['items'][] = $item;
                                $cloths[$key]['details'][2]['lot_selected'] = $item['lot'];
                            } else {
                                $cloths[$key]['details'][$pointKeyDetail]['items'][] = $item;
                                $cloths[$key]['details'][$pointKeyDetail]['lot_selected'] = $item['lot'];
                            }
                            $relation_end2 = $item['relation_item'];

                        break;
                        case 2.9:
                            $relation_init29 = $item['relation_item'];
                            if( (INT)$item['relation_item'] !== 1 AND $relation_init29 !== $relation_end29){
                                $cloths[$key]['details'][$keyDetail]['id'] = Str::random(15);
                                $cloths[$key]['details'][$keyDetail]['items'] = [];
                                $cloths[$key]['details'][$keyDetail]['widthMax'] = 2.95;
                                $cloths[$key]['details'][$keyDetail]['widthLot'] = 3;
                                $cloths[$key]['details'][$keyDetail]['widthLotText'] = '3.00M';
                                $cloths[$key]['details'][$keyDetail]['lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['detail_lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['order'] = 1;
                                $cloths[$key]['details'][$keyDetail]['is_original'] = 0;
                                $cloths[$key]['details'][$keyDetail]['inventory'] = [];
                                $cloths[$key]['details'][$keyDetail]['lots'] = [];
                                $cloths[$key]['details'][$keyDetail]['sumHeights'] = 0;
                                $cloths[$key]['details'][$keyDetail]['totalScrap'] = 0;
                                // guardamos el item nuevo
                                $saveKeys[] = $keyDetail;
                                $pointKeyDetail = $keyDetail;
                                $keyDetail++;
                            }
                            if( (INT)$item['relation_item'] === 1 OR is_null($item['relation_item']) ) {
                                $cloths[$key]['details'][3]['items'][] = $item;
                                $cloths[$key]['details'][3]['lot_selected'] = $item['lot'];
                            } else {
                                $cloths[$key]['details'][$pointKeyDetail]['items'][] = $item;
                                $cloths[$key]['details'][$pointKeyDetail]['lot_selected'] = $item['lot'];
                            }
                            $relation_end29 = $item['relation_item'];
                        break;
                        case 2.8:
                            $relation_init28 = $item['relation_item'];
                            if( (INT)$item['relation_item'] !== 1 AND $relation_init28 !== $relation_end28 ){
                                $cloths[$key]['details'][$keyDetail]['id'] = Str::random(15);
                                $cloths[$key]['details'][$keyDetail]['items'] = [];
                                $cloths[$key]['details'][$keyDetail]['widthMax'] = 2.95;
                                $cloths[$key]['details'][$keyDetail]['widthLot'] = 3;
                                $cloths[$key]['details'][$keyDetail]['widthLotText'] = '3.00M';
                                $cloths[$key]['details'][$keyDetail]['lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['detail_lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['order'] = 1;
                                $cloths[$key]['details'][$keyDetail]['is_original'] = 0;
                                $cloths[$key]['details'][$keyDetail]['inventory'] = [];
                                $cloths[$key]['details'][$keyDetail]['lots'] = [];
                                $cloths[$key]['details'][$keyDetail]['sumHeights'] = 0;
                                $cloths[$key]['details'][$keyDetail]['totalScrap'] = 0;
                                // guardamos el item nuevo
                                $saveKeys[] = $keyDetail;
                                $pointKeyDetail = $keyDetail;
                                $keyDetail++;
                            }
                            if( (INT)$item['relation_item'] === 1 OR is_null($item['relation_item']) ) {
                                $cloths[$key]['details'][4]['items'][] = $item;
                                $cloths[$key]['details'][4]['lot_selected'] = $item['lot'];
                            } else {
                                $cloths[$key]['details'][$pointKeyDetail]['items'][] = $item;
                                $cloths[$key]['details'][$pointKeyDetail]['lot_selected'] = $item['lot'];
                            }
                            $relation_end28 = $item['relation_item'];
                        break;
                        case 2.99:
                            $cloths[$key]['details'][5]['items'][] = $item;
                        break;
                    }
                }
            }

            foreach ($detailsGuarantee as $item) {
                if($cloth['article_id'] == $item['article_id']) {
                    switch ((DOUBLE)$item['width_lot']) {
                        case 3:
                            // Relation items
                            $relation_init3 = $item['relation_item'];
                            if( (INT)$item['relation_item'] !== 1 AND $relation_init3 !== $relation_end3){
                                $cloths[$key]['details'][$keyDetail]['id'] = Str::random(15);
                                $cloths[$key]['details'][$keyDetail]['items'] = [];
                                $cloths[$key]['details'][$keyDetail]['widthMax'] = 2.95;
                                $cloths[$key]['details'][$keyDetail]['widthLot'] = 3;
                                $cloths[$key]['details'][$keyDetail]['widthLotText'] = '3.00M';
                                $cloths[$key]['details'][$keyDetail]['lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['detail_lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['order'] = 1;
                                $cloths[$key]['details'][$keyDetail]['is_original'] = 0;
                                $cloths[$key]['details'][$keyDetail]['inventory'] = [];
                                $cloths[$key]['details'][$keyDetail]['lots'] = [];
                                $cloths[$key]['details'][$keyDetail]['sumHeights'] = 0;
                                $cloths[$key]['details'][$keyDetail]['totalScrap'] = 0;
                                // guardamos el item nuevo
                                $saveKeys[] = $keyDetail;
                                $pointKeyDetail = $keyDetail;
                                $keyDetail++;
                            }
                            if( (INT)$item['relation_item'] === 1 OR is_null($item['relation_item']) ) {
                                $cloths[$key]['details'][0]['items'][] = $item;
                                $cloths[$key]['details'][0]['lot_selected'] = $item['lot'];
                            } else {
                                $cloths[$key]['details'][$pointKeyDetail]['items'][] = $item;
                                $cloths[$key]['details'][$pointKeyDetail]['lot_selected'] = $item['lot'];
                            }
                            $relation_end3 = $item['relation_item'];

                        break;
                        case 2.5:
                            // Relation items
                            $relation_init25 = $item['relation_item'];
                            if( (INT)$item['relation_item'] !== 1 AND $relation_init25 !== $relation_end25){
                                $cloths[$key]['details'][$keyDetail]['id'] = Str::random(15);
                                $cloths[$key]['details'][$keyDetail]['items'] = [];
                                $cloths[$key]['details'][$keyDetail]['widthMax'] = 2.45;
                                $cloths[$key]['details'][$keyDetail]['widthLot'] = 2.5;
                                $cloths[$key]['details'][$keyDetail]['widthLotText'] = '2.50M';
                                $cloths[$key]['details'][$keyDetail]['lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['detail_lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['order'] = 2;
                                $cloths[$key]['details'][$keyDetail]['is_original'] = 0;
                                $cloths[$key]['details'][$keyDetail]['inventory'] = [];
                                $cloths[$key]['details'][$keyDetail]['lots'] = [];
                                $cloths[$key]['details'][$keyDetail]['sumHeights'] = 0;
                                $cloths[$key]['details'][$keyDetail]['totalScrap'] = 0;
                                $saveKeys[] = $keyDetail;
                                $pointKeyDetail = $keyDetail;
                                $keyDetail++;
                            }

                            if( (INT)$item['relation_item'] === 1 OR is_null($item['relation_item']) ) {
                                $cloths[$key]['details'][1]['items'][] = $item;
                                $cloths[$key]['details'][1]['lot_selected'] = $item['lot'];
                            } else {
                                $cloths[$key]['details'][$pointKeyDetail]['items'][] = $item;
                                $cloths[$key]['details'][$pointKeyDetail]['lot_selected'] = $item['lot'];
                            }
                            $relation_end25 = $item['relation_item'];
                        break;
                        case 2:
                            $relation_init2 = $item['relation_item'];
                            if( (INT)$item['relation_item'] !== 1 AND $relation_init2 !== $relation_end2){
                                $cloths[$key]['details'][$keyDetail]['id'] = Str::random(15);
                                $cloths[$key]['details'][$keyDetail]['items'] = [];
                                $cloths[$key]['details'][$keyDetail]['widthMax'] = 2.95;
                                $cloths[$key]['details'][$keyDetail]['widthLot'] = 3;
                                $cloths[$key]['details'][$keyDetail]['widthLotText'] = '3.00M';
                                $cloths[$key]['details'][$keyDetail]['lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['detail_lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['order'] = 1;
                                $cloths[$key]['details'][$keyDetail]['is_original'] = 0;
                                $cloths[$key]['details'][$keyDetail]['inventory'] = [];
                                $cloths[$key]['details'][$keyDetail]['lots'] = [];
                                $cloths[$key]['details'][$keyDetail]['sumHeights'] = 0;
                                $cloths[$key]['details'][$keyDetail]['totalScrap'] = 0;
                                // guardamos el item nuevo
                                $saveKeys[] = $keyDetail;
                                $pointKeyDetail = $keyDetail;
                                $keyDetail++;
                            }
                            if( (INT)$item['relation_item'] === 1 OR is_null($item['relation_item']) ) {
                                $cloths[$key]['details'][2]['items'][] = $item;
                                $cloths[$key]['details'][2]['lot_selected'] = $item['lot'];
                            } else {
                                $cloths[$key]['details'][$pointKeyDetail]['items'][] = $item;
                                $cloths[$key]['details'][$pointKeyDetail]['lot_selected'] = $item['lot'];
                            }
                            $relation_end2 = $item['relation_item'];
                        break;
                        case 2.9:
                            $relation_init29 = $item['relation_item'];
                            if( (INT)$item['relation_item'] !== 1 AND $relation_init29 !== $relation_end29){
                                $cloths[$key]['details'][$keyDetail]['id'] = Str::random(15);
                                $cloths[$key]['details'][$keyDetail]['items'] = [];
                                $cloths[$key]['details'][$keyDetail]['widthMax'] = 2.95;
                                $cloths[$key]['details'][$keyDetail]['widthLot'] = 3;
                                $cloths[$key]['details'][$keyDetail]['widthLotText'] = '3.00M';
                                $cloths[$key]['details'][$keyDetail]['lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['detail_lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['order'] = 1;
                                $cloths[$key]['details'][$keyDetail]['is_original'] = 0;
                                $cloths[$key]['details'][$keyDetail]['inventory'] = [];
                                $cloths[$key]['details'][$keyDetail]['lots'] = [];
                                $cloths[$key]['details'][$keyDetail]['sumHeights'] = 0;
                                $cloths[$key]['details'][$keyDetail]['totalScrap'] = 0;
                                // guardamos el item nuevo
                                $saveKeys[] = $keyDetail;
                                $pointKeyDetail = $keyDetail;
                                $keyDetail++;
                            }
                            if( (INT)$item['relation_item'] === 1 OR is_null($item['relation_item']) ) {
                                $cloths[$key]['details'][3]['items'][] = $item;
                                $cloths[$key]['details'][3]['lot_selected'] = $item['lot'];
                            } else {
                                $cloths[$key]['details'][$pointKeyDetail]['items'][] = $item;
                                $cloths[$key]['details'][$pointKeyDetail]['lot_selected'] = $item['lot'];
                            }
                            $relation_end29 = $item['relation_item'];
                        break;
                        case 2.8:
                            $relation_init28 = $item['relation_item'];
                            if( (INT)$item['relation_item'] !== 1 AND $relation_init28 !== $relation_end28 ){
                                $cloths[$key]['details'][$keyDetail]['id'] = Str::random(15);
                                $cloths[$key]['details'][$keyDetail]['items'] = [];
                                $cloths[$key]['details'][$keyDetail]['widthMax'] = 2.95;
                                $cloths[$key]['details'][$keyDetail]['widthLot'] = 3;
                                $cloths[$key]['details'][$keyDetail]['widthLotText'] = '3.00M';
                                $cloths[$key]['details'][$keyDetail]['lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['detail_lot_selected'] = '';
                                $cloths[$key]['details'][$keyDetail]['order'] = 1;
                                $cloths[$key]['details'][$keyDetail]['is_original'] = 0;
                                $cloths[$key]['details'][$keyDetail]['inventory'] = [];
                                $cloths[$key]['details'][$keyDetail]['lots'] = [];
                                $cloths[$key]['details'][$keyDetail]['sumHeights'] = 0;
                                $cloths[$key]['details'][$keyDetail]['totalScrap'] = 0;
                                // guardamos el item nuevo
                                $saveKeys[] = $keyDetail;
                                $pointKeyDetail = $keyDetail;
                                $keyDetail++;
                            }
                            if( (INT)$item['relation_item'] === 1 OR is_null($item['relation_item']) ) {
                                $cloths[$key]['details'][4]['items'][] = $item;
                                $cloths[$key]['details'][4]['lot_selected'] = $item['lot'];
                            } else {
                                $cloths[$key]['details'][$pointKeyDetail]['items'][] = $item;
                                $cloths[$key]['details'][$pointKeyDetail]['lot_selected'] = $item['lot'];
                            }
                            $relation_end28 = $item['relation_item'];
                        break;
                        case 2.99:
                            $cloths[$key]['details'][5]['items'][] = $item;
                        break;
                    }
                }
            }
            // inventory
            (INT)COUNT($inventory) > 0 ? $cloths[$key]['details'][0]['inventory'] = $this->foundInventoryArticle($cloth['sku'],$inventory,'3.00M',3) : [];
            (INT)COUNT($inventory) > 0 ? $cloths[$key]['details'][1]['inventory'] = $this->foundInventoryArticle($cloth['sku'],$inventory,'2.50M',2.5) : [];
            (INT)COUNT($inventory) > 0 ? $cloths[$key]['details'][2]['inventory'] = $this->foundInventoryArticle($cloth['sku'],$inventory,'2.00M',2) : [];
            (INT)COUNT($inventory) > 0 ? $cloths[$key]['details'][3]['inventory'] = $this->foundInventoryArticle($cloth['sku'],$inventory,'2.90M',2.9) : [];
            (INT)COUNT($inventory) > 0 ? $cloths[$key]['details'][4]['inventory'] = $this->foundInventoryArticle($cloth['sku'],$inventory,'2.80',2.8) : [];
            foreach ($saveKeys as $value) {
                (INT)COUNT($inventory) > 0 ? $cloths[$key]['details'][$value]['inventory'] = $this->foundInventoryArticle($cloth['sku'],$inventory,$cloths[$key]['details'][$value]['widthLotText'],$cloths[$key]['details'][$value]['widthLot'] ) : [];
            }
            // LOTS
            (INT)COUNT($lots) > 0 ? $cloths[$key]['details'][0]['lots'] = $this->foundLotsArticle($cloth['sku'],$lots,'3.00M',3) : [];
            (INT)COUNT($lots) > 0 ? $cloths[$key]['details'][1]['lots'] = $this->foundLotsArticle($cloth['sku'],$lots,'2.50M',2.5) : [];
            (INT)COUNT($lots) > 0 ? $cloths[$key]['details'][2]['lots'] = $this->foundLotsArticle($cloth['sku'],$lots,'2.00M',2) : [];
            (INT)COUNT($lots) > 0 ? $cloths[$key]['details'][3]['lots'] = $this->foundLotsArticle($cloth['sku'],$lots,'2.90M',2.9) : [];
            (INT)COUNT($lots) > 0 ? $cloths[$key]['details'][4]['lots'] = $this->foundLotsArticle($cloth['sku'],$lots,'2.80M',2.8) : [];
            foreach ($saveKeys as $value) {
                (INT)COUNT($lots) > 0 ? $cloths[$key]['details'][$value]['lots'] = $this->foundLotsArticle($cloth['sku'],$lots,$cloths[$key]['details'][$value]['widthLotText'],$cloths[$key]['details'][$value]['widthLot'] ) : [];
            }
        }

        return $cloths;
    }

    private function foundInventoryArticle($sku,$inventory,$mts,$width) {
        $finalInventory = [];
        $skuRepo = explode('-',$sku);
        $sku = $skuRepo[0].'-'.$skuRepo[1];
        foreach ($inventory as $key => $iv) {
            if($iv->UNI_MED != 'kg') {
                $skuFound = explode('-',$iv->CVE_ART);
                if(COUNT($skuFound) > 2) {
                    $skuInv = $skuFound[0].'-'.$skuFound[1];
                    if($skuInv == $sku) {
                        if($skuFound[2] == $mts) {
                            $finalInventory = [
                                'width'   => $width,
                                'sku'     => $iv->CVE_ART,
                                'article' => $iv->DESCR,
                                'unit'    => $iv->UNI_MED,
                                'stock'   => $iv->EXIST,
                            ];
                        }
                    }
                }
            }
        }
        return $finalInventory;
    }

    private function foundLotsArticle($sku,$lots,$mts,$width) {
        $finalLots = [];
        $skuRepo = explode('-',$sku);
        $sku = $skuRepo[0].'-'.$skuRepo[1];
        foreach ($lots as $key => $lot) {
            $skuFound = explode('-',$lot->CVE_ART);
            if(COUNT($skuFound) > 2) {
                $skuInv = $skuFound[0].'-'.$skuFound[1];
                if($skuInv == $sku) {
                    if($skuFound[2] == $mts) {
                        $finalLots[] = [
                            'sku'       => $lot->CVE_ART,
                            'lot'       => $lot->LOTE,
                            'info'      => $lot->LOTE.' | '.round($lot->CANTIDAD,3),
                            'quantity'  => round($lot->CANTIDAD,3),
                            'width'     => $width,
                            'mts'       => $mts,
                        ];
                    }
                }
            }
        }
        return $finalLots;
    }

    private function allFilterOrders($orders_id) {
        $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.packing_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        // ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id')
        ->whereIn('e_orders.id',$orders_id)
        ->get();
        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro')
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
        ->whereIn('d_orders.order_id',$orders_id)
        ->get();
        $orders = self::setAllOrder($EOrder->toArray(),$DOrder->toArray());
        return $orders;
    }

    private function getIndividualOrder($orderID) {
        $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.packing_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        // ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id')
        ->where('e_orders.id',DB::raw($orderID))
        ->first();
        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro')
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
        ->where('d_orders.order_id',DB::raw($orderID))
        ->get();
        $orders = $this->setIndividualOrder($EOrder->toArray(),$DOrder->toArray());
        return $orders;
    }

    private function setAllOrder($Eorder,$DOrder) {

        foreach ($Eorder as $key => $order) {
            $Eorder[$key]['details'] = [];
            foreach ($DOrder as $key2 =>  $dorder) {
                if($dorder['order_id'] == $order['id']) {
                    $Eorder[$key]['details'][] = $DOrder[$key2];
                }
            }
        }
        return $Eorder;
    }

    private function setIndividualOrder($Eorder,$DOrder) {

        $Eorder['details'] = [];
        foreach ($DOrder as $key2 =>  $dorder) {
            $Eorder['details'][] = $DOrder[$key2];
        }
        return $Eorder;
    }

    private function dateDeadlineAssigned() {
        $date_now = Carbon::now();
        $new_date = $date_now->add(1, 'day');
        $day=date('w', strtotime($date_now->toDateTimeString()));
        switch ($day) {
            case 5:
                $new_date = $date_now->add(3, 'day');
                return $new_date->toDateTimeString();
            break;
            default:
                return $new_date->toDateTimeString();
            break;
        }
    }

    private function assignOrganizerCart($productionLineID,$date_now) {
        // OBTENEMOS INFO ACOMODADA POR ARTICULO
        $DProductionLine = DProductionLine::select('d_production_lines.id','d_production_lines.detail_order_id')
        ->join('d_orders','d_orders.id','d_production_lines.detail_order_id')
        ->join('d_modulations','d_modulations.detail_order_id','d_production_lines.detail_order_id')
        ->where('d_production_lines.production_line_id',$productionLineID)
        ->orderBy('d_orders.article_id')
        ->orderBy('d_modulations.join_id')
        ->orderBy('d_orders.order_id')
        ->orderBy('d_orders.item_id')
        ->get()
        ->toArray();
        // BUSCAMOS QUE EXISTAN CARRITOS DISPONIBLES EN LA FECHA
        $busyCartsQuery = COrganizerCart::select('c_organizer_carts.id')
        ->leftJoin('e_organizer_carts','e_organizer_carts.organizer_cart_id','c_organizer_carts.id')
        ->leftJoin('e_production_lines','e_production_lines.id','e_organizer_carts.production_line_id')
        ->where('e_production_lines.production_date',$date_now)
        ->get()
        ->toArray();
        $busyCartsID = [];
        foreach ($busyCartsQuery as $busyCart) { $busyCartsID[] = ['id'];}
        $unoccupiedCarts = COrganizerCart::select('id','name')->whereNotIn('id',$busyCartsID)->get()->toArray();
        // llenamos carritos
        $locationsInsert = [];
        $initReg = 0;
        $itemsPerCart = 20;
        if(count($DProductionLine) < $itemsPerCart ) { $itemStop = count($DProductionLine); } else { $itemStop = $itemsPerCart; }
        foreach ($unoccupiedCarts as $cart) {
            if((INT)$initReg < (INT)count($DProductionLine)) {
                $position=1;
                $EOrganizerCart                    = new EOrganizerCart;
                $EOrganizerCart->production_line_id   = $productionLineID;
                $EOrganizerCart->organizer_cart_id = $cart['id'];
                $EOrganizerCart->save();
                while ( $initReg < $itemStop ) {
                    $locationsInsert[] = [
                        'detail_order_id'   => $DProductionLine[$initReg]['detail_order_id'],
                        'organizer_cart_id' => $cart['id'],
                        'organizer_cart_position_id' => $position
                    ];
                    $initReg++;
                    $position++;
                }
                $itemStop = $itemStop + $itemsPerCart;
                if($itemStop > count($DProductionLine) ) { $itemStop = count($DProductionLine) ; }
            }
        }
        // insertamos los carros
        DOrganizerCart::insert($locationsInsert);
        return;
    }
}
