<?php

namespace App\Http\Controllers;

require_once app_path() . "/fpdf/fpdf.php";
require_once app_path() . "/fpdf/PDF_Code128.php";

use App\Exports\guaranteeExcelExport;
use App\Models\CArticle;
use App\Models\CUserAddress;
use App\Models\DGuaranty;
use App\Models\DImagesArticle;
use App\Models\DOrder;
use App\Models\EGuaranty;
use App\Models\EOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PDF_Code128;
use App\classes\FPDF;

class EGuarantyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $guaranteeInit = self::allGuaranteeInit();
            $guaranteeFinished = self::allGuaranteeFinished();
            return response()->json([
                'success'      =>  true ,
                'guaranteeInit' => $guaranteeInit,
                'guaranteeFinished' => $guaranteeFinished,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
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
        try {
            $nomen = '';
            if( (INT)$request->warranty_type_id === 1 ) { // ABSOLUTA
                $nomen = 'GLS';
                $max_folio = 1;
                $resultMaxFolio = EGuaranty::select(DB::raw('MAX(folio) AS max_folio'))->where('nomen','GLS')->first()->toArray();
                if(!is_null($resultMaxFolio['max_folio'])) {  $max_folio =  (INT)$resultMaxFolio['max_folio'] + 1; }
            } else if( (INT)$request->warranty_type_id === 2 OR (INT)$request->warranty_type_id === 3 ) { // SERVICIO
                $nomen = 'SLS';
                $max_folio = 1;
                $resultMaxFolio = EGuaranty::select(DB::raw('MAX(folio) AS max_folio'))->where('nomen','SLS')->first()->toArray();
                if(!is_null($resultMaxFolio['max_folio'])) {  $max_folio =  (INT)$resultMaxFolio['max_folio'] + 1; }
            }

            $dataInsert = [];
            $itemID = 0;
            // SAVE ESTRUCTURE
            $EGuaranty                          = new EGuaranty();
            $EGuaranty->folio                   = $max_folio;
            $EGuaranty->nomen                   = $nomen;
            $EGuaranty->user_id                 = $request->user_id;
            $EGuaranty->order_id                = $request->order_id;
            $EGuaranty->warranty_type_id        = $request->warranty_type_id;
            $EGuaranty->guarantee_error_id      = $request->guarantee_error_id;
            $EGuaranty->guarantee_type_error_id = $request->guarantee_type_error_id;
            $EGuaranty->description             = $request->description;
            $EGuaranty->capture_id              = $request->capture_id;
            $ifInstallerRequired = 0;
            if($request->if_installer_required == true) { $ifInstallerRequired = 1; }
            $EGuaranty->if_installer_required   = $ifInstallerRequired;
            $EGuaranty->delivery_type_id        = $request->delivery_type_id;
            $EGuaranty->client_address_id       = $request->client_address_id;
            $EGuaranty->charge                  = $request->charge;
            $EGuaranty->save();
            $validItems = 1;
            $guaranteeID = $EGuaranty->id;
            if( (INT)$request->capture_id === 1 ) {
                // SAVE DETAILS
                foreach ( $request->detail_order as $do ) {
                    $itemID++;
                    $dataInsert[] = [
                        'guarantee_id'          => $guaranteeID,
                        'detail_order_id'       => $do['id'],
                        'item_id'               => $itemID,
                        'article_id'            => $do['article_id'],
                        'product_id'            => $do['product_id'],
                        'operation_id'          => $do['operation_id'],
                        'quantity'              => $do['quantity'],
                        'width'                 => $do['width'],
                        'height'                => $do['height'],
                        'fall'                  => $do['fall'],
                        'counterweight_bar_id'  => $do['counterweight_bar_id'],
                        'chain_id'              => $do['chain_id'],
                        'height_chain'          => $do['height_chain'],
                        'mechanism_id'          => $do['mechanism_id'],
                        'side_id'               => $do['side_id'],
                        'mechanism_side_id'     => $do['mechanism_side_id'],
                        'unit_id'               => $do['unit_id'],
                        'component_color_id'    => $do['component_color_id'],
                        'commit'                => $do['commit'],
                        'commit_client'         => $do['commit_client'],
                        'awning_type_id'        => $do['awning_type_id'], // tipo de toldo
                        'area_description'      => $do['area_description'],
                        'relation_id'           => $do['relation_id'],
                        'relation_bracket'      => $do['relation_bracket'],
                        'is_inverted'           => $do['is_inverted'],
                        'is_heat_seal'          => $do['is_heat_seal'],
                        'relation_motor'        => $do['relation_motor'],
                        'motor_id'              => $do['motor_id'],
                        'relation_cassette'     => $do['relation_cassette'],
                        'cassette_id'           => $do['cassette_id'],
                        'relation_lambrequin'   => $do['relation_lambrequin'],
                        'relation_accesories'   => $do['relation_accesories'],
                        'lambrequin_id'         => $do['lambrequin_id'],
                        'is_tie_stripe'         => $do['is_tie_stripe'],
                        'tube_id'               => $do['tube_id'],
                        'divisions'             => $do['divisions'],
                        'item_detail'           => $do['item_detail'],
                        'damage_fabric'         => (INT)$request->capture_id === 2 ? $do['damage_fabric'] : 0,
                        'damage_tube'           => (INT)$request->capture_id === 2 ? $do['damage_tube'] : 0,
                        'damage_mechanism'      => (INT)$request->capture_id === 2 ? $do['damage_mechanism'] : 0,
                        'damage_counterweight'  => (INT)$request->capture_id === 2 ? $do['damage_counterweight'] : 0,
                        'damage_chain'          => (INT)$request->capture_id === 2 ? $do['damage_chain'] : 0,
                        'damage_fascia'         => (INT)$request->capture_id === 2 ? $do['damage_fascia'] : 0,
                        'damage_motor'          => (INT)$request->capture_id === 2 ? $do['damage_motor'] : 0,
                    ];
                }
            } else {
                $validItems = 0;
                // COMPONENTES
                foreach ( $request->detail_order as $do ) {
                    // DAMAGE
                    if(   ( ( (INT)$do['article_id'] !== (INT)$do['cloth_idt'] OR (INT)$do['counterweight_bar_id'] !== (INT)$do['counterweight_idt'] OR (INT)$do['tube_id'] !== (INT)$do['tube_idt'] OR (INT)$do['component_color_id'] !== (INT)$do['component_color_idt'] OR ( (INT)$do['chain_id'] !== (INT)$do['chain_idt'] OR ( is_null($do['chain_id']) AND (INT)$do['operation_id'] === 1  ) ) OR ( (INT)$do['height_chain'] !== (INT)$do['height_chain_idt'] OR ( is_null($do['height_chain']) AND (INT)$do['operation_id'] === 1 ) ) OR ( (INT)$do['mechanism_id'] !== (INT)$do['mechanism_idt'] ) ) OR
                    ( (INT)$do['damage_fabric'] === 1 OR (INT)$do['damage_tube'] === 1 OR (INT)$do['damage_mechanism'] === 1 OR (INT)$do['damage_counterweight'] === 1 OR (INT)$do['damage_chain'] === 1 OR (INT)$do['damage_fascia'] === 1 OR (INT)$do['damage_motor'] === 1 ) ) OR (INT)$do['relation_cassette'] > 0 OR (INT)$do['product_id'] === 2 ) {
                        $validItems++;
                        $itemID++;
                        $dataInsert[] = [
                            'guarantee_id'          => $guaranteeID,
                            'detail_order_id'       => $do['id'],
                            'item_id'               => $itemID,
                            'article_id'            => $do['article_id'],
                            'product_id'            => $do['product_id'],
                            'operation_id'          => $do['operation_id'],
                            'quantity'              => $do['quantity'],
                            'width'                 => $do['width'],
                            'height'                => $do['height'],
                            'fall'                  => $do['fall'],
                            'counterweight_bar_id'  => $do['counterweight_bar_id'],
                            'chain_id'              => $do['chain_id'],
                            'height_chain'          => $do['height_chain'],
                            'mechanism_id'          => $do['mechanism_id'],
                            'side_id'               => $do['side_id'],
                            'mechanism_side_id'     => $do['mechanism_side_id'],
                            'unit_id'               => $do['unit_id'],
                            'component_color_id'    => $do['component_color_id'],
                            'commit'                => $do['commit'],
                            'commit_client'         => $do['commit_client'],
                            'awning_type_id'        => $do['awning_type_id'], // tipo de toldo
                            'area_description'      => $do['area_description'],
                            'relation_id'           => $do['relation_id'],
                            'relation_bracket'      => $do['relation_bracket'],
                            'is_inverted'           => $do['is_inverted'],
                            'is_heat_seal'          => $do['is_heat_seal'],
                            'relation_motor'        => $do['relation_motor'] ,
                            'motor_id'              => $do['motor_id'],
                            'relation_cassette'     => $do['relation_cassette'],
                            'cassette_id'           => $do['cassette_id'],
                            'relation_lambrequin'   => $do['relation_lambrequin'],
                            'relation_accesories'   => $do['relation_accesories'],
                            'lambrequin_id'         => $do['lambrequin_id'],
                            'is_tie_stripe'         => $do['is_tie_stripe'],
                            'tube_id'               => $do['tube_id'],
                            'divisions'             => $do['divisions'],
                            'item_detail'           => $do['item_detail'],
                            'damage_fabric'         => (INT)$request->capture_id === 2 ? $do['damage_fabric'] : 0,
                            'damage_tube'           => (INT)$request->capture_id === 2 ? $do['damage_tube'] : 0,
                            'damage_mechanism'      => (INT)$request->capture_id === 2 ? $do['damage_mechanism'] : 0,
                            'damage_counterweight'  => (INT)$request->capture_id === 2 ? $do['damage_counterweight'] : 0,
                            'damage_chain'          => (INT)$request->capture_id === 2 ? $do['damage_chain'] : 0,
                            'damage_fascia'         => (INT)$request->capture_id === 2 ? $do['damage_fascia'] : 0,
                            'damage_motor'          => (INT)$request->capture_id === 2 ? $do['damage_motor'] : 0,
                        ];
                    }
                }
            }
            if( (INT)$validItems > 0) {
                DGuaranty::insert($dataInsert);
                // GET DATA
                $guarateeDataSuccess = EGuaranty::select('*')->where('id',$guaranteeID)->first();
                return response()->json([
                    "success" => true,
                    "guarateeDataSuccess" => $guarateeDataSuccess,
                ],200);
            } else {
                EGuaranty::where('id',$guaranteeID)->delete();
                DB::update("ALTER TABLE e_guarantee AUTO_INCREMENT = ".$guaranteeID);
                return response()->json([
                    "success" => false,
                    "type_error" => 'no_data_mechanism',
                ],400);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "success" => false,
                "error" => $th->getMessage(),
                "type_error" => 'error_system',
            ],400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EGuaranty  $eGuaranty
     * @return \Illuminate\Http\Response
     */
    public function show(EGuaranty $eGuaranty, $waranty_ids)
    {
        // try {

            $guaranteeSelected = EGuaranty::select('e_guarantee.id','e_guarantee.folio','e_guarantee.nomen','e_guarantee.user_id','c_erp_info_users.short_name as agent_name','e_guarantee.order_id','e_orders.client_id',DB::raw('CASE WHEN c_users.short_name IS NOT NULL THEN c_users.short_name ELSE c_users.full_name END AS client_name'),'e_guarantee.warranty_type_id','c_type_guarantee.type_warranty','e_guarantee.guarantee_error_id','c_guarantee_errors.guarantee_error','e_guarantee.guarantee_type_error_id','c_guarantee_type_errors.guarantee_type_error','e_guarantee.description','e_guarantee.status_id','c_status_guarantee.status','c_status_guarantee.color_status','e_guarantee.delivery_type_id','c_delivery_types.delivery',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_guarantee.if_installer_required','e_guarantee.capture_id',DB::raw('CASE e_guarantee.capture_id WHEN 1 THEN "Persiana Nueva" WHEN 2 THEN "Captura componentes" END AS capture '),'e_guarantee.created_at','e_guarantee.charge')
            ->join('e_orders','e_orders.id','e_guarantee.order_id')
            ->join('c_users','c_users.id','e_orders.client_id')
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_guarantee.user_id')
            ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
            ->join('c_type_guarantee','c_type_guarantee.id','e_guarantee.warranty_type_id')
            ->join('c_guarantee_errors','c_guarantee_errors.id','e_guarantee.guarantee_error_id')
            ->join('c_guarantee_type_errors','c_guarantee_type_errors.id','e_guarantee.guarantee_type_error_id')
            ->leftJoin('c_delivery_types','c_delivery_types.id','e_guarantee.delivery_type_id')
            ->leftJoin('c_user_addresses','c_user_addresses.id','e_guarantee.client_address_id')
            ->where('e_guarantee.id',$waranty_ids)
            ->first()
            ->toArray();

            $detailGuarantee = DGuaranty::select('d_guarantee.id','d_guarantee.guarantee_id','d_guarantee.item_id','d_guarantee.article_id','d_orders.article_id as ch_article_id',DB::raw('CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN la.lambrequin_price ELSE d_guarantee.price END AS price'),'c_articles.model_id','d_guarantee.discount1','d_guarantee.discount2','d_guarantee.discount3','d_guarantee.quantity','d_guarantee.width','d_orders.width as ch_width','d_guarantee.height','d_orders.height as ch_height','d_guarantee.product_id','c_products.product','d_guarantee.operation_id','c_operations.operation','d_guarantee.fall','d_guarantee.counterweight_bar_id','d_orders.counterweight_bar_id as ch_counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_guarantee.chain_id','d_orders.chain_id as ch_chain_id','c_chains.chain','d_guarantee.height_chain','d_orders.height_chain as ch_height_chain','d_guarantee.side_id','d_orders.side_id as ch_side_id','d_guarantee.mechanism_side_id','d_orders.mechanism_side_id as ch_mechanism_side_id','c_mechanism_sides.mechanism_side','d_guarantee.unit_id','c_units.unit','d_guarantee.component_color_id','d_orders.component_color_id as ch_component_color_id','c_colors.color as color_component','d_guarantee.commit','d_guarantee.commit_client','d_guarantee.awning_type_id','d_guarantee.area_description','d_guarantee.relation_id','d_guarantee.relation_bracket','d_guarantee.is_inverted','d_guarantee.is_heat_seal','d_guarantee.relation_cassette','d_guarantee.relation_lambrequin','d_guarantee.cassette_id','d_guarantee.relation_motor','d_guarantee.motor_id','d_orders.motor_id as ch_motor_id','d_guarantee.relation_accesories','d_guarantee.relation_heat_seal','d_guarantee.relation_bracket_dn','d_guarantee.relation_control','d_guarantee.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_guarantee.is_tie_stripe','d_guarantee.tube_id','d_orders.tube_id as ch_tube_id','c_tubes.tube','d_guarantee.divisions','d_guarantee.mechanism_id','d_orders.mechanism_id as ch_mechanism_id','c_mechanisms.mechanism','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_guarantee.production_location_id','d_guarantee.lambrequin_id','d_orders.lambrequin_id as ch_lambrequin_id','d_guarantee.fijo_id','d_orders.fijo_id as ch_fijo_id','d_guarantee.corbatin_id','d_orders.corbatin_id as ch_corbatin_id','d_guarantee.is_velcro','d_guarantee.item_detail','d_guarantee.damage_fabric','d_guarantee.damage_tube','d_guarantee.damage_mechanism','d_guarantee.damage_counterweight','d_guarantee.damage_chain','d_guarantee.damage_fascia','d_guarantee.damage_motor')
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
            ->leftJoin('d_orders','d_orders.id','d_guarantee.detail_order_id')
            ->where('d_guarantee.guarantee_id',$waranty_ids)
            ->get()
            ->toArray();
            $guaranteeSelected['details'] = [];
            foreach ($detailGuarantee as $key => $dg) {
                $guaranteeSelected['details'][] = $dg;
            }
            return response()->json([
                "success" => true,
                "guaranteeSelected" => $guaranteeSelected,
            ],200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         "success" => false,
        //         "error" => $th->getMessage(),
        //     ],400);
            // }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EGuaranty  $eGuaranty
     * @return \Illuminate\Http\Response
     */
    public function edit(EGuaranty $eGuaranty)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EGuaranty  $eGuaranty
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EGuaranty $eGuaranty)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EGuaranty  $eGuaranty
     * @return \Illuminate\Http\Response
     */
    public function destroy(EGuaranty $eGuaranty)
    {
        //
    }

    public function getFindOrder(Request $request)
    {
        // try {
            $order = $this->getIndividualOrderFind($request->client_id,$request->order_id);
            if($order === null) {
                return response()->json([
                    'success'       =>  false ,
                ], 400);
            } else {
                return response()->json([
                    'success'       =>  true ,
                    'order'         =>  $order,
                ], 200);
            }
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function getArticles(Request $request)
    {
        // try {
            $articlesIDs = [];
            //Buscamos los atticulos
            $CArticle = CArticle::select('c_articles.id','c_articles.article','c_articles.sku','c_articles.erp_id','c_articles.model_id','c_models.model','c_models.product_id','c_articles.price','c_articles.cost', 'width_min', 'width_max','c_articles.height_min','c_articles.height_max','c_articles.cloth_discount','c_articles.width_inverted', 'height_inverted','c_articles.stock_lot','c_articles.unit_id','c_units.unit','c_articles.color_id','c_articles.thumbnail','c_articles.is_inverted','c_articles.is_warranty_inverted','c_articles.is_heat_seal','c_articles.lambrequin_price','c_articles.only_counterweight_id','c_articles.is_control','c_articles.channels','c_articles.is_partner', 'c_articles.is_active','c_articles.created_at')
            ->join('c_models','c_models.id','c_articles.model_id')
            ->join('c_units','c_units.id','c_articles.unit_id')
            ->where('is_view',1)
            ->get()
            ->toArray();
            // guardamos el id del articulo en un array
            foreach ($CArticle as $article) { $articlesIDs[] = $article['id']; }
            $DImagesArticle = DImagesArticle::whereIn('article_id',$articlesIDs)->get();
            // $discountData = self::getDiscountData();
            $articles = self::setArticles($CArticle,$DImagesArticle->toArray());
            return response()->json([
                "success" => true,
                "articles" => $articles,
            ],200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         "success" => false,
        //         "error" => $th->getMessage(),
        //     ],400);
        // }
    }

    public function updateChargeGuarantee(Request $request, EGuaranty $eGuaranty, $guarantee_id)
    {
        try {
            $eGuaranty::where('id',$guarantee_id)
            ->update(['charge'=>$request->charge]);
            return response()->json([
                "success" => true,
                "charge" => $request->charge,
            ],200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }

    public function downloadGuaranteeDetail($guarantee_id)
    {
        // try {

            $guarantee = $this->getIndividualGuarantee($guarantee_id);
            $pdf = new FPDF(new PDF_Code128("L", "mm", "A4"));
            return $pdf->createGuaranteeDetail($guarantee);

        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    public function downloadGuaranteeExcel(Request $request)
    {
        try {
            $nameFile = "Detalle_garantia_".$request->guarantee_id."";
            $rowData = DGuaranty::select('d_guarantee.guarantee_id','d_guarantee.item_id','d_guarantee.quantity','d_guarantee.width','d_guarantee.height',DB::raw('CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article'),'c_mechanism_sides.mechanism_side','c_mechanisms.mechanism','d_guarantee.area_description','d_guarantee.commit_client' )
            ->join('c_articles','c_articles.id','d_guarantee.article_id')
            ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_guarantee.mechanism_side_id')
            ->leftJoin('c_mechanisms','c_mechanisms.id','d_guarantee.mechanism_id')
            ->leftJoin('c_articles AS la','la.id','d_guarantee.lambrequin_id')
            ->leftJoin('c_articles AS cb','cb.id','d_guarantee.corbatin_id')
            ->leftJoin('c_articles AS fj','fj.id','d_guarantee.fijo_id')
            ->where('guarantee_id',$request->guarantee_id)
            ->get()
            ->toArray();

            $file = Excel::raw(new guaranteeExcelExport($rowData), \Maatwebsite\Excel\Excel::XLSX);
            return [
                "name" => $nameFile.".xls",
                "file" => base64_encode($file),
            ];
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }


    // PIVATE
    private function getIndividualOrderFind($clientID,$orderID) {
        $EOrder =  EOrder::select('e_orders.id','e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.packing_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        // ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id')
        ->where('e_orders.id',DB::raw($orderID))
        ->where('e_orders.client_id',DB::raw($clientID))
        ->first();
        if(!is_null($EOrder)) {
            $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','d_orders.article_id AS cloth_idt', DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE c_articles.article END AS article, c_articles.model_id, d_orders.price'), 'd_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','d_orders.counterweight_bar_id AS counterweight_idt','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','d_orders.chain_id AS chain_idt','c_chains.chain','d_orders.height_chain','d_orders.height_chain AS height_chain_idt','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','d_orders.component_color_id AS component_color_idt','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','d_orders.tube_id AS tube_idt','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','d_orders.mechanism_id AS mechanism_idt','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id',DB::raw('0 AS damage_fabric, 0 AS damage_tube, 0 AS damage_mechanism, 0 AS damage_counterweight, 0 AS damage_chain, 0 AS damage_fascia, 0 AS damage_motor'))
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
            ->join('e_orders', function($join) use($clientID) {
                $join->on('e_orders.id', '=', 'd_orders.order_id')
                ->where('e_orders.client_id',DB::raw($clientID));
            })
            ->where('d_orders.order_id',DB::raw($orderID))
            ->get();
            $orders = $this->setIndividualOrder($EOrder->toArray(),$DOrder->toArray());
            return $orders;
        } else {
            return null;
        }
    }

    private function setIndividualOrder($Eorder,$DOrder) {
        $Eorder['details'] = [];
        foreach ($DOrder as $key2 =>  $dorder) {
            $Eorder['details'][] = $DOrder[$key2];
        }
        return $Eorder;
    }

    private function setArticles($CArticle,$DImagesArticle) {
        foreach ($CArticle as $key => $article) {
            if(is_null($article['thumbnail'])) { $CArticle[$key]['thumbnail'] = 'not-found.png'; }
            $CArticle[$key]['imagen_articles'] = [];
            // Obtener descuento del articulo mediante $discountData temporalmente le agregamos 0 descuento
            $CArticle[$key]['discount'] = 0;
            foreach ($DImagesArticle as $key => $imgArticle) {
                if($article['id'] == $imgArticle['article_id']) {
                    // Obtener descuento de la partida mediante $discountData temporalmente le agregamos 0 descuento
                    $CArticle[$key]['imagen_articles'][] = $imgArticle;
                }
            }
        }
        return $CArticle;
    }

    private function allGuaranteeInit() {
        return EGuaranty::select('e_guarantee.id','e_guarantee.folio','e_guarantee.nomen','e_guarantee.user_id','c_erp_info_users.short_name as agent_name','e_guarantee.order_id','e_orders.client_id',DB::raw('CASE WHEN c_users.short_name IS NOT NULL THEN c_users.short_name ELSE c_users.full_name END AS client_name'),'e_guarantee.warranty_type_id','e_guarantee.guarantee_error_id','e_guarantee.guarantee_type_error_id','e_guarantee.description','e_guarantee.status_id','c_status_guarantee.status','c_status_guarantee.color_status','c_delivery_types.delivery','e_guarantee.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_guarantee.if_installer_required','e_guarantee.created_at','e_guarantee.charge')
        ->join('e_orders','e_orders.id','e_guarantee.order_id')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_guarantee.user_id')
        ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
        ->leftJoin('c_delivery_types','c_delivery_types.id','e_guarantee.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_guarantee.client_address_id')
        ->whereNotIn('e_guarantee.status_id',[8,9])
        ->orderBy('id','DESC')
        ->get();
    }
    private function allGuaranteeFinished() {
        return EGuaranty::select('e_guarantee.id','e_guarantee.folio','e_guarantee.nomen','e_guarantee.user_id','c_erp_info_users.short_name as agent_name','e_guarantee.order_id','e_orders.client_id',DB::raw('CASE WHEN c_users.short_name IS NOT NULL THEN c_users.short_name ELSE c_users.full_name END AS client_name'),'e_guarantee.warranty_type_id','e_guarantee.guarantee_error_id','e_guarantee.guarantee_type_error_id','e_guarantee.description','c_delivery_types.delivery','e_guarantee.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_guarantee.if_installer_required','e_guarantee.created_at','e_guarantee.charge')
        ->join('e_orders','e_orders.id','e_guarantee.order_id')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_guarantee.user_id')
        ->leftJoin('c_delivery_types','c_delivery_types.id','e_guarantee.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_guarantee.client_address_id')
        ->whereIN('e_guarantee.status_id',[8,9])
        ->get();
    }

    public function getIndividualGuarantee($guarenteeID) {
        $EGuaranty = EGuaranty::select('e_guarantee.id','e_guarantee.folio','e_guarantee.nomen',DB::raw("CASE WHEN e_guarantee.capture_id = 1 THEN 'Persiana Nueva' ELSE 'Captura componentes' END AS type_capture"),'e_guarantee.user_id','c_erp_info_users.short_name as agent_name','e_guarantee.order_id','e_orders.client_id',DB::raw('null AS proyect_name,null AS payment_method,null AS payment_option, null AS account_number,CASE WHEN c_users.short_name IS NOT NULL THEN c_users.short_name ELSE c_users.full_name END AS client_name'),'e_guarantee.warranty_type_id','c_type_guarantee.type_warranty','e_guarantee.guarantee_error_id','c_guarantee_errors.guarantee_error','e_guarantee.guarantee_type_error_id','c_guarantee_type_errors.guarantee_type_error','e_guarantee.description','e_guarantee.status_id','c_status_guarantee.status','c_status_guarantee.color_status','e_guarantee.delivery_type_id','c_delivery_types.delivery',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_guarantee.if_installer_required','e_guarantee.created_at','e_guarantee.charge')
        ->join('e_orders','e_orders.id','e_guarantee.order_id')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_guarantee.user_id')
        ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
        ->join('c_type_guarantee','c_type_guarantee.id','e_guarantee.warranty_type_id')
        ->join('c_guarantee_errors','c_guarantee_errors.id','e_guarantee.guarantee_error_id')
        ->join('c_guarantee_type_errors','c_guarantee_type_errors.id','e_guarantee.guarantee_type_error_id')
        ->leftJoin('c_delivery_types','c_delivery_types.id','e_guarantee.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_guarantee.client_address_id')
        ->where('e_guarantee.id',$guarenteeID)
        ->first();
        $DGuaranty = DGuaranty::select('d_guarantee.id',DB::raw('d_guarantee.guarantee_id AS order_id'),'d_guarantee.guarantee_id','d_guarantee.item_id','d_guarantee.article_id',DB::raw('CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN la.lambrequin_price ELSE d_guarantee.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_guarantee.discount1','d_guarantee.discount2','d_guarantee.discount3','d_guarantee.quantity','d_guarantee.width','d_guarantee.height','d_guarantee.product_id','c_products.product','d_guarantee.operation_id','c_operations.operation','d_guarantee.fall','d_guarantee.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_guarantee.chain_id','c_chains.chain','d_guarantee.height_chain','d_guarantee.side_id','d_guarantee.mechanism_side_id','c_mechanism_sides.mechanism_side','d_guarantee.unit_id','c_units.unit','d_guarantee.component_color_id','c_colors.color as color_component','d_guarantee.commit','d_guarantee.commit_client','d_guarantee.awning_type_id','d_guarantee.area_description','d_guarantee.relation_id','d_guarantee.relation_bracket','d_guarantee.is_inverted','d_guarantee.is_heat_seal','d_guarantee.relation_cassette','d_guarantee.relation_lambrequin','d_guarantee.cassette_id','d_guarantee.relation_motor','d_guarantee.motor_id','d_guarantee.relation_accesories','d_guarantee.relation_heat_seal','d_guarantee.relation_bracket_dn','d_guarantee.relation_control','d_guarantee.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_guarantee.is_tie_stripe','d_guarantee.tube_id','c_tubes.tube','d_guarantee.divisions','d_guarantee.mechanism_id','c_mechanisms.mechanism','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_guarantee.production_location_id','d_production_locations.location','d_guarantee.lambrequin_id','d_guarantee.fijo_id','d_guarantee.corbatin_id','d_guarantee.is_velcro','d_guarantee.item_detail','d_guarantee.damage_fabric','d_guarantee.damage_tube','d_guarantee.damage_mechanism','d_guarantee.damage_counterweight','d_guarantee.damage_chain','d_guarantee.damage_fascia','d_guarantee.damage_motor')
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
        ->leftJoin('d_production_locations','d_production_locations.id','d_guarantee.production_location_id')
        ->where('d_guarantee.guarantee_id',$guarenteeID)
        ->get();
        $guarantee = $this->setIndividualGuarantee($EGuaranty->toArray(),$DGuaranty->toArray());
        return $guarantee;
    }

    private function setIndividualGuarantee($EGuarntee,$DGuarantee) {

        $EGuarntee['details'] = [];
        foreach ($DGuarantee as $key2 =>  $dGuarnty) {
            $EGuarntee['details'][] = $DGuarantee[$key2];
        }
        return $EGuarntee;
    }
}




