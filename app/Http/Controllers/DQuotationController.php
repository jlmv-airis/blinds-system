<?php

namespace App\Http\Controllers;

use App\Models\CArticle;
use App\Models\CErpInfoUser;
use App\Models\CTube;
use App\Models\CUser;
use App\Models\DQuotation;
use App\Models\DQuotationDiscount;
use App\Models\DSocketConnection;
use App\Models\EQuotation;
use App\Models\EQuotationDiscountRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mavinoo\Batch\Batch;
use App\classes\SetMechanism;
use App\Models\BTestLog;
use App\Models\CMechanism;
use App\Models\DOrder;

class DQuotationController extends Controller
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
    // public function store(Request $request)
    // {
    //     // try {
    //         $initInsertedId = 0;
    //         // DATA
    //         $dataRecord = [];
    //         $item_id = 1;
    //         $relation_id = 0;
    //         $relation_motor = 0;
    //         $relation_cassette = 0;
    //         $relation_bracket = 0;
    //         // buscaos el maximo del item para su consecutivo
    //         $relationAll = null;
    //         $relationAll = DQuotation::select(DB::raw('MAX(item_id) as max_item_id'))->where('quotation_id',$request->quotation_id)->first();
    //         if(!is_null($relationAll['max_item_id'])) { $item_id = $relationAll['max_item_id'] + 1; }
    //         // maximo relacion
    //         $relationAll = null;
    //         $relationAll = DQuotation::select(DB::raw('MAX(relation_id) as max_relation'))->where('quotation_id',$request->quotation_id)->first();
    //         if(!is_null($relationAll['max_relation'])) {
    //             $relation_id = $relationAll['max_relation'];
    //         }
    //         // maximo relacion Motor
    //         $relationAll = null;
    //         $relationAll = DQuotation::select(DB::raw('MAX(relation_motor) as max_motor_relation'))->where('quotation_id',$request->quotation_id)->first();
    //         if(!is_null($relationAll['max_motor_relation'])) {
    //             $relation_motor = $relationAll['max_motor_relation'];
    //         }
    //         // maximo realacion cassette
    //         $relationAll = null;
    //         $relationAll = DQuotation::select(DB::raw('MAX(relation_cassette) as max_cassette_relation'))->where('quotation_id',$request->quotation_id)->first();
    //         if(!is_null($relationAll['max_cassette_relation'])) {
    //             $relation_cassette = $relationAll['max_cassette_relation'];
    //         }
    //         // maxima relacion bracket
    //         $relationAll = null;
    //         $relationAll = DQuotation::select(DB::raw('MAX(relation_bracket) as max_bracket_relation'))->where('quotation_id',$request->quotation_id)->first();
    //         if(!is_null($relationAll['max_bracket_relation'])) {
    //             $relation_bracket = $relationAll['max_bracket_relation'];
    //         }
    //         // cantidad seleccionada
    //         foreach ($request->recordQuotation as $key => $items) { // cantidad seleccionada
    //             // aumentamos la relacion
    //             $relation_id++;
    //             // si tiene motor la relacionamos
    //             if($items['is_motor']) { $relation_motor++; }
    //             // si tiene cassette la relacionamos
    //             if($items['is_cassette']) { $relation_cassette++; }
    //             // si tienen braquet intermedio la relacionamos
    //             if($items['is_bracket']) { $relation_bracket++; }
    //             // creamos los registros
    //             foreach ($items['records'] as $record) {

    //                 $dataRecord[] = [
    //                     'quotation_id'         => $request->quotation_id,
    //                     'item_id'              => $item_id,
    //                     'article_id'           => $items['article_id'],
    //                     'quotation_product_id' => $items['quotation_product_id'],
    //                     'operation_id'         => $items['operation_id'],
    //                     'quantity'             => $items['quantity'],
    //                     'width'                => $record['width'],
    //                     'height'               => $record['height'],
    //                     'fall'                 => $items['fall'],
    //                     'counterweight_bar_id' => $items['counterweight_bar_id'],
    //                     'chain_id'             => $items['chain_id'],
    //                     'height_chain'         => $record['height_chain'],
    //                     'mechanism_side_id'    => $record['mechanism_side_id'],
    //                     'unit_id'              => $record['unit_id'],
    //                     'component_color_id'   => $items['component_color_id'],
    //                     'commit'               => null,
    //                     'commit_client'        => $items['detail'],
    //                     'awning_type_id'       => null, // tipo de toldo
    //                     'area_description'     => $items['area'],
    //                     'relation_id'          => $relation_id,
    //                     'relation_bracket'     => $items['is_bracket'] ? $relation_bracket : 0,
    //                     'is_inverted'          => $record['inverted'],
    //                     'is_heat_seal'         => 0, // falta definir termosello
    //                     'relation_motor'       => $items['is_motor'] ? $relation_motor : 0,
    //                     'motor_id'             => $items['is_motor'] ? $items['motor_id'] : null,
    //                     'relation_cassette'    => $items['is_cassette'] ? $relation_cassette : 0,
    //                     'cassette_id'          => $items['is_cassette'] ? $items['cassete_id'] : null,
    //                     'is_tie_stripe'       => $items['quotation_product_id'] == 2 ? 1 : 0,
    //                 ];
    //                 $initInsertedId++;
    //                 $item_id++;
    //             }
    //             // creamos los extras
    //             foreach ($items['extra'] as $extraRecord) {
    //                 switch ($extraRecord['item']) {
    //                     case 'cassette':
    //                         $dataRecord[] = [
    //                             'quotation_id'         => $request->quotation_id,
    //                             'item_id'              => $item_id,
    //                             'article_id'           => $extraRecord['article_id'],
    //                             'quotation_product_id' => $extraRecord['product_id'],
    //                             'operation_id'         => null,
    //                             'quantity'             => 1,
    //                             'width'                => $extraRecord['width'],
    //                             'height'               => null,
    //                             'fall'                 => null,
    //                             'counterweight_bar_id' => null,
    //                             'chain_id'             => null,
    //                             'height_chain'         => null,
    //                             'mechanism_side_id'    => null,
    //                             'unit_id'              => $extraRecord['unit_id'],
    //                             'component_color_id'   => $items['component_color_id'],
    //                             'commit'               => null,
    //                             'commit_client'        => $items['detail'],
    //                             'awning_type_id'       => null, // tipo de toldo
    //                             'area_description'     => $items['area'],
    //                             'relation_id'          => $relation_id,
    //                             'relation_bracket'     => null,
    //                             'is_inverted'          => null,
    //                             'is_heat_seal'         => 0, // falta definir termosello
    //                             'relation_motor'       => null,
    //                             'motor_id'             => null,
    //                             'relation_cassette'    => $relation_cassette,
    //                             'cassette_id'          => null,
    //                             'is_tie_stripe'        => null,
    //                         ];
    //                         $initInsertedId++;
    //                         $item_id++;
    //                     break;
    //                     case 'motor':
    //                         $dataRecord[] = [
    //                             'quotation_id'         => $request->quotation_id,
    //                             'item_id'              => $item_id,
    //                             'article_id'           => $extraRecord['article_id'],
    //                             'quotation_product_id' => $extraRecord['product_id'],
    //                             'operation_id'         => null,
    //                             'quantity'             => 1,
    //                             'width'                => null,
    //                             'height'               => null,
    //                             'fall'                 => null,
    //                             'counterweight_bar_id' => null,
    //                             'chain_id'             => null,
    //                             'height_chain'         => null,
    //                             'mechanism_side_id'    => null,
    //                             'unit_id'              => $extraRecord['unit_id'],
    //                             'component_color_id'   => null,
    //                             'commit'               => null,
    //                             'commit_client'        => $items['detail'],
    //                             'awning_type_id'       => null, // tipo de toldo
    //                             'area_description'     => $items['area'],
    //                             'relation_id'          => $relation_id,
    //                             'relation_bracket'     => null,
    //                             'is_inverted'          => null,
    //                             'is_heat_seal'         => 0, // falta definir termosello
    //                             'relation_motor'       => $relation_motor,
    //                             'motor_id'             => null,
    //                             'relation_cassette'    => null,
    //                             'cassette_id'          => null,
    //                             'is_tie_stripe'        => null,
    //                         ];
    //                         $initInsertedId++;
    //                         $item_id++;
    //                     break;
    //                 }

    //             }
    //         }
    //         DQuotation::insert($dataRecord);
    //         $dQuotationAll = DQuotation::all();
    //         $lastInsertedId = $dQuotationAll->last()->id;
    //         $initInsertedId = ($lastInsertedId - $initInsertedId)+1;
    //         $DQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id','c_articles.article','c_articles.price','c_articles.model_id',DB::raw('0 as article_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','d_quotations.operation_id','d_quotations.fall','d_quotations.counterweight_bar_id','d_quotations.chain_id','d_quotations.height_chain','d_quotations.mechanism_side_id','d_quotations.unit_id','d_quotations.component_color_id','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','d_quotations.mechanism_id')
    //         ->join('c_articles','c_articles.id','d_quotations.article_id')
    //         ->whereBetween('d_quotations.id', [$initInsertedId, $lastInsertedId])
    //         ->get();
    //         return response()->json([
    //             'success'      =>  true ,
    //             'quotationDetail' =>  $DQuotation,
    //         ], 200);
    //     // } catch (\Throwable $th) {
    //     //     return response()->json([
    //     //         'success' => false ,
    //     //         'error'   => $th
    //     //     ], 200);
    //     // }
    // }

    public function store(Request $request)
    {
        // try {
            $initInsertedId = 0;
            $modelUpdate = 0;
            $modelDataUpdate = [];
            // DATA
            $item_heat_seal = 0;
            $dataRecord = [];
            $item_id = 1;
            $relation_id = 0;
            // buscaos el maximo del item para su consecutivo
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(item_id) as max_item_id'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_item_id'])) { $item_id = $relationAll['max_item_id'] + 1; }
            // maximo relacion
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(relation_id) as max_relation'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_relation'])) {
                $relation_id = $relationAll['max_relation'];
            }

            if( $request->recordQuotation['quotation_product_id'] == 2 OR $request->recordQuotation['quotation_product_id'] == 1) {
                $relation_motor = 0;
                $relation_cassette = 0;
                $relation_lambrequin = 0;
                $relation_bracket = 0;
                $relation_accesories = 0;
                $relation_perfil_priv = 0;
                $relation_tensor = 0;
                // maximo relacion Motor
                $relationAll = null;
                $relationAll = DQuotation::select(DB::raw('MAX(relation_motor) as max_motor_relation'))->where('quotation_id',$request->quotation_id)->first();
                if(!is_null($relationAll['max_motor_relation'])) {
                    $relation_motor = $relationAll['max_motor_relation'];
                }
                // maximo realacion cassette
                $relationAll = null;
                $relationAll = DQuotation::select(DB::raw('MAX(relation_cassette) as max_cassette_relation'))->where('quotation_id',$request->quotation_id)->first();
                if(!is_null($relationAll['max_cassette_relation'])) {
                    $relation_cassette = $relationAll['max_cassette_relation'];
                }
                // maximo realacion lambrequin
                $relationAll = null;
                $relationAll = DQuotation::select(DB::raw('MAX(relation_lambrequin) as max_relation_lambrequin'))->where('quotation_id',$request->quotation_id)->first();
                if(!is_null($relationAll['max_relation_lambrequin'])) {
                    $relation_lambrequin = $relationAll['max_relation_lambrequin'];
                }
                // maxima relacion bracket
                $relationAll = null;
                $relationAll = DQuotation::select(DB::raw('MAX(relation_bracket) as max_bracket_relation'))->where('quotation_id',$request->quotation_id)->first();
                if(!is_null($relationAll['max_bracket_relation'])) {
                    $relation_bracket = $relationAll['max_bracket_relation'];
                }
                // maximo realacion accesorio
                $relationAll = null;
                $relationAll = DQuotation::select(DB::raw('MAX(relation_accesories) as max_relation_accesories'))->where('quotation_id',$request->quotation_id)->first();
                if(!is_null($relationAll['max_relation_accesories'])) {
                    $relation_accesories = $relationAll['max_relation_accesories'];
                }
                // maximo perfil priv
                $relationAll = null;
                $relationAll = DQuotation::select(DB::raw('MAX(relation_perfil_priv) as max_relation_perfil_priv'))->where('quotation_id',$request->quotation_id)->first();
                if(!is_null($relationAll['max_relation_perfil_priv'])) {
                    $relation_perfil_priv = $relationAll['max_relation_perfil_priv'];
                }
                // maximo realacion tensor
                $relationAll = null;
                $relationAll = DQuotation::select(DB::raw('MAX(relation_tensor) as max_relation_tensor'))->where('quotation_id',$request->quotation_id)->first();
                if(!is_null($relationAll['max_relation_tensor'])) {
                    $relation_tensor = $relationAll['max_relation_tensor'];
                }
                /*if($request->recordQuotation['division'] == 1) {
                    foreach ($request->recordQuotation['records'] as $record) {
                        // aumentamos la relacion
                        $relation_id++;
                        // si tiene motor la relacionamos
                        if($request->recordQuotation['is_motor']) { $relation_motor++; }
                        // si tiene cassette la relacionamos
                        if($request->recordQuotation['is_cassette'] OR $request->recordQuotation['quotation_product_id'] == 2) { $relation_cassette++; }
                        // si tiene cassette la relacionamos
                        if($request->recordQuotation['is_lambrequin']) { $relation_lambrequin++; }
                        // si tienen braquet intermedio la relacionamos
                        if($request->recordQuotation['is_bracket']) { $relation_bracket++; }
                        // si tienen tubo de 50 0 63 relacionamos
                        if( ( $record['tube_id'] == 3 || $record['tube_id'] == 4 || $record['tube_id'] == 5 ) OR (INT)$request->recordQuotation['chain_id'] === 2 OR (INT)$request->recordQuotation['extra_id'] === 1 OR ( (INT)$request->recordQuotation['counterweight_bar_id'] === 2 OR $request->recordQuotation['counterweight_bar_id'] === 4 ) ) { $relation_accesories++; }

                        // creamos los registros
                        $dataRecord[] = [
                            'quotation_id'         => $request->quotation_id,
                            'item_id'              => $item_id,
                            'article_id'           => $request->recordQuotation['article_id'],
                            'quotation_product_id' => $request->recordQuotation['quotation_product_id'],
                            'operation_id'         => $request->recordQuotation['operation_id'],
                            'quantity'             => $request->recordQuotation['quantity'],
                            'width'                => $record['width'],
                            'height'               => $record['height'],
                            'fall'                 => $request->recordQuotation['fall'],
                            'counterweight_bar_id' => $request->recordQuotation['counterweight_bar_id'],
                            'chain_id'             => $request->recordQuotation['chain_id'],
                            'height_chain'         => $record['height_chain'],
                            'mechanism_id'         => $record['mechanism_id'],
                            'default_mechanism_id' => $record['mechanism_id'],
                            'side_id'              => $record['side_id'],
                            'mechanism_side_id'    => $record['mechanism_side_id'],
                            'unit_id'              => $record['unit_id'],
                            'component_color_id'   => $request->recordQuotation['component_color_id'],
                            'commit'               => null,
                            'commit_client'        => $request->recordQuotation['detail'],
                            'awning_type_id'       => null, // tipo de toldo
                            'area_description'     => $request->recordQuotation['area'],
                            'relation_id'          => $relation_id,
                            'relation_bracket'     => $request->recordQuotation['is_bracket'] ? $relation_bracket : 0,
                            'is_inverted'          => $record['inverted'],
                            'is_heat_seal'         => 0, // falta definir termosello
                            'relation_motor'       => $request->recordQuotation['is_motor'] ? $relation_motor : 0,
                            'motor_id'             => $request->recordQuotation['is_motor'] ? $request->recordQuotation['motor_id'] : null,
                            'relation_cassette'    => $request->recordQuotation['is_cassette'] ? $relation_cassette : ( $request->recordQuotation['quotation_product_id'] == 2 ?  $relation_cassette  : 0 ),
                            'cassette_id'          => $request->recordQuotation['is_cassette'] ? $request->recordQuotation['cassete_id'] : null,
                            'relation_lambrequin'  => $request->recordQuotation['is_lambrequin'] ? $relation_lambrequin : 0,
                            'relation_accesories'  => ( $record['tube_id'] == 3 || $record['tube_id'] == 4 || $record['tube_id'] == 5  OR (INT)$request->recordQuotation['chain_id'] === 2 OR (INT)$request->recordQuotation['extra_id'] === 1 OR ( (INT)$request->recordQuotation['counterweight_bar_id'] === 2 OR $request->recordQuotation['counterweight_bar_id'] === 4 ) ) ? $relation_accesories : null,
                            'lambrequin_id'        => null,
                            'is_tie_stripe'        => $request->recordQuotation['quotation_product_id'] == 2 ? 1 : 0,
                            'tube_id'              => $record['tube_id'],
                            'default_tube_id'      => $record['tube_id'],
                            'divisions'            => $request->recordQuotation['division'],
                        ];
                        $initInsertedId++;
                        $item_id++;
                        for ($i=0; $i < $request->recordQuotation['quantity'] ; $i++) { $widthSum = $widthSum + $record['width']; }
                    }
                    // creamos los extras
                    foreach ($request->recordQuotation['extra'] as $extraRecord) {
                        switch ($extraRecord['item']) {
                            case 'cassette':
                                $dataRecord[] = [
                                    'quotation_id'         => $request->quotation_id,
                                    'item_id'              => $item_id,
                                    'article_id'           => $extraRecord['article_id'],
                                    'quotation_product_id' => $extraRecord['product_id'],
                                    'operation_id'         => null,
                                    'quantity'             => $request->recordQuotation['quantity'],
                                    'width'                => $extraRecord['width'],
                                    'height'               => null,
                                    'fall'                 => null,
                                    'counterweight_bar_id' => null,
                                    'chain_id'             => null,
                                    'height_chain'         => null,
                                    'mechanism_id'         => null,
                                    'default_mechanism_id' => null,
                                    'side_id'              => null,
                                    'mechanism_side_id'    => null,
                                    'unit_id'              => $extraRecord['unit_id'],
                                    'component_color_id'   => $request->recordQuotation['component_color_id'],
                                    'commit'               => null,
                                    'commit_client'        => $request->recordQuotation['detail'],
                                    'awning_type_id'       => null, // tipo de toldo
                                    'area_description'     => $request->recordQuotation['area'],
                                    'relation_id'          => $relation_id,
                                    'relation_bracket'     => null,
                                    'is_inverted'          => null,
                                    'is_heat_seal'         => 0, // falta definir termosello
                                    'relation_motor'       => null,
                                    'motor_id'             => null,
                                    'relation_cassette'    => $relation_cassette,
                                    'cassette_id'          => null,
                                    'relation_lambrequin'  => null,
                                    'relation_accesories'  => null,
                                    'lambrequin_id'        => null,
                                    'is_tie_stripe'        => null,
                                    'tube_id'              => null,
                                    'default_tube_id'      => null,
                                    'divisions'            => null,
                                ];
                                $initInsertedId++;
                                $item_id++;
                                // Se agregan dos tapas al cassete
                                $dataRecord[] = [
                                    'quotation_id'         => $request->quotation_id,
                                    'item_id'              => $item_id,
                                    'article_id'           => 245,
                                    'quotation_product_id' => 4,
                                    'operation_id'         => null,
                                    'quantity'             => 2,
                                    'width'                => null,
                                    'height'               => null,
                                    'fall'                 => null,
                                    'counterweight_bar_id' => null,
                                    'chain_id'             => null,
                                    'height_chain'         => null,
                                    'mechanism_id'         => null,
                                    'default_mechanism_id' => null,
                                    'side_id'              => null,
                                    'mechanism_side_id'    => null,
                                    'unit_id'              => 3,
                                    'component_color_id'   => null,
                                    'commit'               => null,
                                    'commit_client'        => null,
                                    'awning_type_id'       => null, // tipo de toldo
                                    'area_description'     => null,
                                    'relation_id'          => $relation_id,
                                    'relation_bracket'     => null,
                                    'is_inverted'          => null,
                                    'is_heat_seal'         => 0, // falta definir termosello
                                    'relation_motor'       => null,
                                    'motor_id'             => null,
                                    'relation_cassette'    => null,
                                    'cassette_id'          => null,
                                    'relation_lambrequin'  => null,
                                    'relation_accesories'  => $relation_accesories,
                                    'lambrequin_id'        => null,
                                    'is_tie_stripe'        => null,
                                    'tube_id'              => null,
                                    'default_tube_id'      => null,
                                    'divisions'            => null,

                                ];
                                $initInsertedId++;
                                $item_id++;
                            break;
                            case 'motor':
                                $dataRecord[] = [
                                    'quotation_id'         => $request->quotation_id,
                                    'item_id'              => $item_id,
                                    'article_id'           => $extraRecord['article_id'],
                                    'quotation_product_id' => $extraRecord['product_id'],
                                    'operation_id'         => null,
                                    'quantity'             => $request->recordQuotation['quantity'],
                                    'width'                => null,
                                    'height'               => null,
                                    'fall'                 => null,
                                    'counterweight_bar_id' => null,
                                    'chain_id'             => null,
                                    'height_chain'         => null,
                                    'mechanism_id'         => null,
                                    'default_mechanism_id' => null,
                                    'side_id'              => null,
                                    'mechanism_side_id'    => null,
                                    'unit_id'              => $extraRecord['unit_id'],
                                    'component_color_id'   => null,
                                    'commit'               => null,
                                    'commit_client'        => $request->recordQuotation['detail'],
                                    'awning_type_id'       => null, // tipo de toldo
                                    'area_description'     => $request->recordQuotation['area'],
                                    'relation_id'          => $relation_id,
                                    'relation_bracket'     => null,
                                    'is_inverted'          => null,
                                    'is_heat_seal'         => 0, // falta definir termosello
                                    'relation_motor'       => $relation_motor,
                                    'motor_id'             => null,
                                    'relation_cassette'    => null,
                                    'cassette_id'          => null,
                                    'relation_lambrequin'  => null,
                                    'relation_accesories'  => null,
                                    'lambrequin_id'        => null,
                                    'is_tie_stripe'        => null,
                                    'tube_id'              => null,
                                    'default_tube_id'      => null,
                                    'divisions'            => null,
                                ];
                                $initInsertedId++;
                                $item_id++;

                                // Como es una division se agrega un set de motor
                                $dataRecord[] = [
                                    'quotation_id'         => $request->quotation_id,
                                    'item_id'              => $item_id,
                                    'article_id'           => 264,
                                    'quotation_product_id' => 4,
                                    'operation_id'         => null,
                                    'quantity'             => 1,
                                    'width'                => null,
                                    'height'               => null,
                                    'fall'                 => null,
                                    'counterweight_bar_id' => null,
                                    'chain_id'             => null,
                                    'height_chain'         => null,
                                    'mechanism_id'         => null,
                                    'default_mechanism_id' => null,
                                    'side_id'              => null,
                                    'mechanism_side_id'    => null,
                                    'unit_id'              => 3,
                                    'component_color_id'   => null,
                                    'commit'               => null,
                                    'commit_client'        => null,
                                    'awning_type_id'       => null, // tipo de toldo
                                    'area_description'     => null,
                                    'relation_id'          => $relation_id,
                                    'relation_bracket'     => null,
                                    'is_inverted'          => null,
                                    'is_heat_seal'         => 0, // falta definir termosello
                                    'relation_motor'       => null,
                                    'motor_id'             => $relation_motor,
                                    'relation_cassette'    => null,
                                    'cassette_id'          => null,
                                    'relation_lambrequin'  => null,
                                    'relation_accesories'  => null,
                                    'lambrequin_id'        => null,
                                    'is_tie_stripe'        => null,
                                    'tube_id'              => null,
                                    'default_tube_id'      => null,
                                    'divisions'            => null,

                                ];
                                $initInsertedId++;
                                $item_id++;
                            break;
                            case 'lambrequin':
                                $dataRecord[] = [
                                    'quotation_id'         => $request->quotation_id,
                                    'item_id'              => $item_id,
                                    'article_id'           => $extraRecord['article_id'],
                                    'quotation_product_id' => $extraRecord['product_id'],
                                    'operation_id'         => null,
                                    'quantity'             => $request->recordQuotation['quantity'],
                                    'width'                => $extraRecord['width'],
                                    'height'               => $extraRecord['height'],
                                    'fall'                 => null,
                                    'counterweight_bar_id' => null,
                                    'chain_id'             => null,
                                    'height_chain'         => null,
                                    'mechanism_id'         => null,
                                    'default_mechanism_id' => null,
                                    'side_id'              => null,
                                    'mechanism_side_id'    => null,
                                    'unit_id'              => $extraRecord['unit_id'],
                                    'component_color_id'   => $request->recordQuotation['component_color_id'],
                                    'commit'               => null,
                                    'commit_client'        => $request->recordQuotation['detail'],
                                    'awning_type_id'       => null, // tipo de toldo
                                    'area_description'     => $request->recordQuotation['area'],
                                    'relation_id'          => $relation_id,
                                    'relation_bracket'     => null,
                                    'is_inverted'          => null,
                                    'is_heat_seal'         => 0, // falta definir termosello
                                    'relation_motor'       => null,
                                    'motor_id'             => null,
                                    'relation_cassette'    => null,
                                    'cassette_id'          => null,
                                    'relation_lambrequin'  => $relation_lambrequin,
                                    'relation_accesories'  => null,
                                    'lambrequin_id'        => $request->recordQuotation['article_id'],
                                    'is_tie_stripe'        => null,
                                    'tube_id'              => null,
                                    'default_tube_id'      => null,
                                    'divisions'            => null,
                                ];
                                $initInsertedId++;
                                $item_id++;
                            break;
                        }
                    }
                    // SI hay un tubo de 50 o 63 agregamos el extra
                    if($record['tube_id'] == 3 || $record['tube_id'] == 4 || $record['tube_id'] == 5) {

                        $tube_article_id = 0;
                        $tube_unit_id = 0;
                        $adapter_article_id = 0;
                        $adapter_unit_id = 0;

                        switch ($record['tube_id']) {
                            case 3: // 50 astriado
                                $tube_article_id = 42;
                                $tube_unit_id = 2;
                                $adapter_article_id = 46;
                                $adapter_unit_id = 5;
                                $total_width = $widthSum;
                            break;
                            case 4: // 50 LISO
                                $tube_article_id = 43;
                                $tube_unit_id = 3;
                                $adapter_article_id = 46;
                                $adapter_unit_id = 5;
                                $total_width = NULL;
                            break;
                            case 5: // 63 mm
                                $tube_article_id = 44;
                                $tube_unit_id = 3;
                                $adapter_article_id = 47;
                                $adapter_unit_id = 5;
                                $total_width = null;
                            break;
                        }
                        // agregamos tubo
                        $dataRecord[] = [
                            'quotation_id'         => $request->quotation_id,
                            'item_id'              => $item_id,
                            'article_id'           => $tube_article_id,
                            'quotation_product_id' => 4,
                            'operation_id'         => null,
                            'quantity'             => 1,
                            'width'                => $total_width,
                            'height'               => null,
                            'fall'                 => null,
                            'counterweight_bar_id' => null,
                            'chain_id'             => null,
                            'height_chain'         => null,
                            'mechanism_id'         => null,
                            'default_mechanism_id' => null,
                            'side_id'              => null,
                            'mechanism_side_id'    => null,
                            'unit_id'              => $tube_unit_id,
                            'component_color_id'   => null,
                            'commit'               => null,
                            'commit_client'        => null,
                            'awning_type_id'       => null, // tipo de toldo
                            'area_description'     => null,
                            'relation_id'          => $relation_id,
                            'relation_bracket'     => null,
                            'is_inverted'          => null,
                            'is_heat_seal'         => 0, // falta definir termosello
                            'relation_motor'       => null,
                            'motor_id'             => null,
                            'relation_cassette'    => null,
                            'cassette_id'          => null,
                            'relation_lambrequin'  => null,
                            'relation_accesories'  => $relation_accesories,
                            'lambrequin_id'        => null,
                            'is_tie_stripe'        => null,
                            'tube_id'              => null,
                            'default_tube_id'      => null,
                            'divisions'            => null,

                        ];
                        $initInsertedId++;
                        $item_id++;
                        // agregamos el reductor
                        $dataRecord[] = [
                            'quotation_id'         => $request->quotation_id,
                            'item_id'              => $item_id,
                            'article_id'           => $adapter_article_id,
                            'quotation_product_id' => 4,
                            'operation_id'         => null,
                            'quantity'             => $request->recordQuotation['quantity'],
                            'width'                => null,
                            'height'               => null,
                            'fall'                 => null,
                            'counterweight_bar_id' => null,
                            'chain_id'             => null,
                            'height_chain'         => null,
                            'mechanism_id'         => null,
                            'side_id'              => null,
                            'mechanism_side_id'    => null,
                            'unit_id'              => $adapter_unit_id,
                            'component_color_id'   => null,
                            'commit'               => null,
                            'commit_client'        => null,
                            'awning_type_id'       => null, // tipo de toldo
                            'area_description'     => null,
                            'relation_id'          => $relation_id,
                            'relation_bracket'     => null,
                            'is_inverted'          => null,
                            'is_heat_seal'         => 0, // falta definir termosello
                            'relation_motor'       => null,
                            'motor_id'             => null,
                            'relation_cassette'    => null,
                            'cassette_id'          => null,
                            'relation_lambrequin'  => null,
                            'relation_accesories'  => $relation_accesories,
                            'lambrequin_id'        => null,
                            'is_tie_stripe'        => null,
                            'tube_id'              => null,
                            'default_tube_id'      => null,
                            'divisions'            => null,

                        ];
                        $initInsertedId++;
                        $item_id++;
                    }
                    // SI hay na cadena metalica la agregamos
                    if( (INT)$request->recordQuotation['chain_id'] === 2 ) {
                        foreach ($request->recordQuotation['records'] as $record) {
                            // agregamos el cadena metalica
                            $dataRecord[] = [
                                'quotation_id'         => $request->quotation_id,
                                'item_id'              => $item_id,
                                'article_id'           => 70,
                                'quotation_product_id' => 4,
                                'operation_id'         => null,
                                'quantity'             => $request->recordQuotation['quantity'],
                                'width'                => $record['height_chain'],
                                'height'               => null,
                                'fall'                 => null,
                                'counterweight_bar_id' => null,
                                'chain_id'             => null,
                                'height_chain'         => null,
                                'mechanism_id'         => null,
                                'default_mechanism_id' => null,
                                'side_id'              => null,
                                'mechanism_side_id'    => null,
                                'unit_id'              => 2,
                                'component_color_id'   => null,
                                'commit'               => null,
                                'commit_client'        => null,
                                'awning_type_id'       => null, // tipo de toldo
                                'area_description'     => null,
                                'relation_id'          => $relation_id,
                                'relation_bracket'     => null,
                                'is_inverted'          => null,
                                'is_heat_seal'         => 0, // falta definir termosello
                                'relation_motor'       => null,
                                'motor_id'             => null,
                                'relation_cassette'    => null,
                                'cassette_id'          => null,
                                'relation_lambrequin'  => null,
                                'relation_accesories'  => $relation_accesories,
                                'lambrequin_id'        => null,
                                'is_tie_stripe'        => null,
                                'tube_id'              => null,
                                'default_tube_id'      => null,
                                'divisions'            => null,

                            ];
                            $initInsertedId++;
                            $item_id++;
                        }
                    }

                    // Si es base ovalada se agrega
                    if( (INT)$request->recordQuotation['counterweight_bar_id'] === 2 OR $request->recordQuotation['counterweight_bar_id'] === 4 ) {
                        $counterweightBarSelect = 0;
                        switch ((INT)$request->recordQuotation['counterweight_bar_id']) {
                            case 2:
                                $counterweightBarSelect = 255;
                            break;
                            case 4:
                                $counterweightBarSelect = 268;
                            break;
                        }
                        foreach ($request->recordQuotation['records'] as $record) {
                            // agregamos la base ovalada
                            $dataRecord[] = [
                                'quotation_id'         => $request->quotation_id,
                                'item_id'              => $item_id,
                                'article_id'           => $counterweightBarSelect,
                                'quotation_product_id' => 4,
                                'operation_id'         => null,
                                'quantity'             => $request->recordQuotation['quantity'],
                                'width'                => $record['width'],
                                'height'               => null,
                                'fall'                 => null,
                                'counterweight_bar_id' => null,
                                'chain_id'             => null,
                                'height_chain'         => null,
                                'mechanism_id'         => null,
                                'default_mechanism_id' => null,
                                'side_id'              => null,
                                'mechanism_side_id'    => null,
                                'unit_id'              => 2,
                                'component_color_id'   => null,
                                'commit'               => null,
                                'commit_client'        => null,
                                'awning_type_id'       => null, // tipo de toldo
                                'area_description'     => null,
                                'relation_id'          => $relation_id,
                                'relation_bracket'     => null,
                                'is_inverted'          => null,
                                'is_heat_seal'         => 0, // falta definir termosello
                                'relation_motor'       => null,
                                'motor_id'             => null,
                                'relation_cassette'    => null,
                                'cassette_id'          => null,
                                'relation_lambrequin'  => null,
                                'relation_accesories'  => $relation_accesories,
                                'lambrequin_id'        => null,
                                'is_tie_stripe'        => null,
                                'tube_id'              => null,
                                'default_tube_id'      => null,
                                'divisions'            => null,
                            ];
                            $initInsertedId++;
                            $item_id++;
                        }
                    }
                } else {
                */
                for ($i=0; $i < $request->recordQuotation['quantity'] ; $i++) {
                    $widthSum = 0;
                    // aumentamos la relacion
                    $relation_id++;
                    // si tiene motor la relacionamos
                    if($request->recordQuotation['is_motor']) { $relation_motor++; }
                    // si tiene cassette la relacionamos
                    if($request->recordQuotation['is_cassette'] OR $request->recordQuotation['quotation_product_id'] == 2 ) { $relation_cassette++; }
                    // si tiene lambrequin la relacionamos
                    if($request->recordQuotation['is_lambrequin']) { $relation_lambrequin++; }
                    // si tienen braquet intermedio la relacionamos
                    if($request->recordQuotation['is_bracket']) { $relation_bracket++; }
                    // si tienen tubo de 50, 63 o 70 relacionamos
                    if( ( $request->recordQuotation['records'][0]['tube_id'] == 3 OR $request->recordQuotation['records'][0]['tube_id'] == 4 OR $request->recordQuotation['records'][0]['tube_id'] == 5 OR $request->recordQuotation['records'][0]['tube_id'] == 6 OR $request->recordQuotation['records'][0]['tube_id'] == 6 ) OR (INT)$request->recordQuotation['chain_id'] === 2  OR (INT)$request->recordQuotation['extra_id'] === 1 OR ( (INT)$request->recordQuotation['division'] > 1 AND (INT)$request->recordQuotation['quotation_product_id'] === 1 )  /* OR ( (INT)$request->recordQuotation['counterweight_bar_id'] === 2 OR $request->recordQuotation['counterweight_bar_id'] === 4 ) */ ) { $relation_accesories++; }
                    // relation perfil priv
                    if( $request->recordQuotation['is_perfil_priv'] AND (INT)$request->recordQuotation['quotation_product_id'] === 1 ) { $relation_perfil_priv++; }
                    // relation perfil priv
                    if( $request->recordQuotation['if_tensor'] AND (INT)$request->recordQuotation['quotation_product_id'] === 1 ) { $relation_tensor++; }


                    // creamos los registros
                    foreach ($request->recordQuotation['records'] as $record) {
                        $dataRecord[] = [
                            'quotation_id'         => $request->quotation_id,
                            'item_id'              => $item_id,
                            'article_id'           => $request->recordQuotation['article_id'],
                            'quotation_product_id' => $request->recordQuotation['quotation_product_id'],
                            'operation_id'         => $request->recordQuotation['operation_id'],
                            'quantity'             => 1,
                            'width'                => $record['width'],
                            'height'               => $record['height'],
                            'fall'                 => $request->recordQuotation['fall'],
                            'counterweight_bar_id' => $request->recordQuotation['counterweight_bar_id'],
                            'chain_id'             => $request->recordQuotation['chain_id'],
                            'height_chain'         => $record['height_chain'],
                            'mechanism_id'         => $record['mechanism_id'],
                            'default_mechanism_id' => $record['mechanism_id'],
                            'side_id'              => $record['side_id'],
                            'mechanism_side_id'    => $record['mechanism_side_id'],
                            'unit_id'              => $record['unit_id'],
                            'component_color_id'   => $request->recordQuotation['component_color_id'],
                            'commit'               => null,
                            'commit_client'        => $request->recordQuotation['detail'],
                            'awning_type_id'       => null, // tipo de toldo
                            'area_description'     => $request->recordQuotation['area'],
                            'relation_id'          => $relation_id,
                            'relation_bracket'     => $request->recordQuotation['is_bracket'] ? $relation_bracket : 0,
                            'is_inverted'          => $record['inverted'],
                            'is_heat_seal'         => 0, // falta definir termosello
                            'relation_motor'       => $request->recordQuotation['is_motor'] ? $relation_motor : 0,
                            'motor_id'             => $request->recordQuotation['is_motor'] ? $request->recordQuotation['motor_id'] : null,
                            'relation_cassette'    => $request->recordQuotation['is_cassette'] ? $relation_cassette : ( $request->recordQuotation['quotation_product_id'] == 2 ?  $relation_cassette  : 0 ),
                            'cassette_id'          => $request->recordQuotation['is_cassette'] ? $request->recordQuotation['cassete_id'] : null,
                            'relation_lambrequin'  => $request->recordQuotation['is_lambrequin'] ? $relation_lambrequin : 0,
                            'relation_accesories'  => ( $record['tube_id'] == 3 || $record['tube_id'] == 4  || $record['tube_id'] == 5 || $record['tube_id'] == 6  OR (INT)$request->recordQuotation['chain_id'] === 2 OR (INT)$request->recordQuotation['extra_id'] === 1  OR ( (INT)$request->recordQuotation['division'] > 1 AND (INT)$request->recordQuotation['quotation_product_id'] === 1 )/*OR ( (INT)$request->recordQuotation['counterweight_bar_id'] === 2 OR $request->recordQuotation['counterweight_bar_id'] === 4 )*/ ) ? $relation_accesories : null,
                            'lambrequin_id'        => null,
                            'is_tie_stripe'        => $request->recordQuotation['quotation_product_id'] == 2 ? 1 : 0,
                            'tube_id'              => $record['tube_id'],
                            'default_tube_id'      => $record['tube_id'],
                            'divisions'            => $request->recordQuotation['division'],
                            'is_velcro'            => 0,
                            'relation_perfil_priv' => ( $request->recordQuotation['is_perfil_priv'] AND (INT)$request->recordQuotation['quotation_product_id'] === 1 ) ? $relation_perfil_priv : 0,
                            'relation_tensor'      => ( $request->recordQuotation['if_tensor'] AND (INT)$request->recordQuotation['quotation_product_id'] === 1 ) ? $relation_tensor : 0,
                            'if_chain_height'      => $request->recordQuotation['if_chain_height'] ? 1 : 0,
                        ];
                        $initInsertedId++;
                        $item_id++;
                        $widthSum = $widthSum + $record['width'];
                    }

                    // creamos los extras
                    foreach ($request->recordQuotation['extra'] as $extraRecord) {
                        switch ($extraRecord['item']) {
                            case 'cassette':
                                $dataRecord[] = [
                                    'quotation_id'         => $request->quotation_id,
                                    'item_id'              => $item_id,
                                    'article_id'           => $extraRecord['article_id'],
                                    'quotation_product_id' => $extraRecord['product_id'],
                                    'operation_id'         => null,
                                    'quantity'             => 1,
                                    'width'                => $extraRecord['width'],
                                    'height'               => null,
                                    'fall'                 => null,
                                    'counterweight_bar_id' => null,
                                    'chain_id'             => null,
                                    'height_chain'         => null,
                                    'mechanism_id'         => null,
                                    'default_mechanism_id' => null,
                                    'side_id'              => null,
                                    'mechanism_side_id'    => null,
                                    'unit_id'              => $extraRecord['unit_id'],
                                    'component_color_id'   => $request->recordQuotation['component_color_id'],
                                    'commit'               => null,
                                    'commit_client'        => $request->recordQuotation['detail'],
                                    'awning_type_id'       => null, // tipo de toldo
                                    'area_description'     => $request->recordQuotation['area'],
                                    'relation_id'          => $relation_id,
                                    'relation_bracket'     => null,
                                    'is_inverted'          => null,
                                    'is_heat_seal'         => 0, // falta definir termosello
                                    'relation_motor'       => null,
                                    'motor_id'             => null,
                                    'relation_cassette'    => $relation_cassette,
                                    'cassette_id'          => null,
                                    'relation_lambrequin'  => null,
                                    'relation_accesories'  => null,
                                    'lambrequin_id'        => null,
                                    'is_tie_stripe'        => null,
                                    'tube_id'              => null,
                                    'default_tube_id'      => null,
                                    'divisions'            => null,
                                    'is_velcro'            => 0,
                                    'relation_perfil_priv' => null,
                                    'relation_tensor'      => null,
                                    'if_chain_height'      => null,
                                ];
                                $initInsertedId++;
                                $item_id++;
                                // Se agregan dos tapas al cassete
                                $dataRecord[] = [
                                    'quotation_id'         => $request->quotation_id,
                                    'item_id'              => $item_id,
                                    'article_id'           => 245,
                                    'quotation_product_id' => 4,
                                    'operation_id'         => null,
                                    'quantity'             => 2,
                                    'width'                => null,
                                    'height'               => null,
                                    'fall'                 => null,
                                    'counterweight_bar_id' => null,
                                    'chain_id'             => null,
                                    'height_chain'         => null,
                                    'mechanism_id'         => null,
                                    'default_mechanism_id' => null,
                                    'side_id'              => null,
                                    'mechanism_side_id'    => null,
                                    'unit_id'              => 3,
                                    'component_color_id'   => null,
                                    'commit'               => null,
                                    'commit_client'        => null,
                                    'awning_type_id'       => null, // tipo de toldo
                                    'area_description'     => null,
                                    'relation_id'          => $relation_id,
                                    'relation_bracket'     => null,
                                    'is_inverted'          => null,
                                    'is_heat_seal'         => 0, // falta definir termosello
                                    'relation_motor'       => null,
                                    'motor_id'             => null,
                                    'relation_cassette'    => null,
                                    'cassette_id'          => null,
                                    'relation_lambrequin'  => null,
                                    'relation_accesories'  => $relation_accesories,
                                    'lambrequin_id'        => null,
                                    'is_tie_stripe'        => null,
                                    'tube_id'              => null,
                                    'default_tube_id'      => null,
                                    'divisions'            => null,
                                    'is_velcro'            => 0,
                                    'relation_perfil_priv' => null,
                                    'relation_tensor'      => null,
                                    'if_chain_height'      => null,

                                ];
                                $initInsertedId++;
                                $item_id++;
                            break;
                            case 'motor':
                                $dataRecord[] = [
                                    'quotation_id'         => $request->quotation_id,
                                    'item_id'              => $item_id,
                                    'article_id'           => $extraRecord['article_id'],
                                    'quotation_product_id' => $extraRecord['product_id'],
                                    'operation_id'         => null,
                                    'quantity'             => 1,
                                    'width'                => null,
                                    'height'               => null,
                                    'fall'                 => null,
                                    'counterweight_bar_id' => null,
                                    'chain_id'             => null,
                                    'height_chain'         => null,
                                    'mechanism_id'         => null,
                                    'default_mechanism_id' => null,
                                    'side_id'              => null,
                                    'mechanism_side_id'    => null,
                                    'unit_id'              => $extraRecord['unit_id'],
                                    'component_color_id'   => null,
                                    'commit'               => null,
                                    'commit_client'        => $request->recordQuotation['detail'],
                                    'awning_type_id'       => null, // tipo de toldo
                                    'area_description'     => $request->recordQuotation['area'],
                                    'relation_id'          => $relation_id,
                                    'relation_bracket'     => null,
                                    'is_inverted'          => null,
                                    'is_heat_seal'         => 0, // falta definir termosello
                                    'relation_motor'       => $relation_motor,
                                    'motor_id'             => null,
                                    'relation_cassette'    => null,
                                    'cassette_id'          => null,
                                    'relation_lambrequin'  => null,
                                    'relation_accesories'  => null,
                                    'lambrequin_id'        => null,
                                    'is_tie_stripe'        => null,
                                    'tube_id'              => null,
                                    'default_tube_id'      => null,
                                    'divisions'            => null,
                                    'is_velcro'            => 0,
                                    'relation_perfil_priv' => null,
                                    'relation_tensor'      => null,
                                    'if_chain_height'      => null,
                                ];
                                $initInsertedId++;
                                $item_id++;

                                // Como es una division se agrega un SET DE MOTOR
                                if( (INT)$request->recordQuotation['division'] === 1 ) {
                                    $dataRecord[] = [
                                        'quotation_id'         => $request->quotation_id,
                                        'item_id'              => $item_id,
                                        'article_id'           => 264,
                                        'quotation_product_id' => 4,
                                        'operation_id'         => null,
                                        'quantity'             => 1,
                                        'width'                => null,
                                        'height'               => null,
                                        'fall'                 => null,
                                        'counterweight_bar_id' => null,
                                        'chain_id'             => null,
                                        'height_chain'         => null,
                                        'mechanism_id'         => null,
                                        'default_mechanism_id' => null,
                                        'side_id'              => null,
                                        'mechanism_side_id'    => null,
                                        'unit_id'              => 3,
                                        'component_color_id'   => null,
                                        'commit'               => null,
                                        'commit_client'        => null,
                                        'awning_type_id'       => null, // tipo de toldo
                                        'area_description'     => null,
                                        'relation_id'          => $relation_id,
                                        'relation_bracket'     => null,
                                        'is_inverted'          => null,
                                        'is_heat_seal'         => 0, // falta definir termosello
                                        'relation_motor'       => null,
                                        'motor_id'             => $relation_motor,
                                        'relation_cassette'    => null,
                                        'cassette_id'          => null,
                                        'relation_lambrequin'  => null,
                                        'relation_accesories'  => null,
                                        'lambrequin_id'        => null,
                                        'is_tie_stripe'        => null,
                                        'tube_id'              => null,
                                        'default_tube_id'      => null,
                                        'divisions'            => null,
                                        'is_velcro'            => 0,
                                        'relation_perfil_priv' => null,
                                        'relation_tensor'      => null,
                                        'if_chain_height'      => null,

                                    ];
                                }
                                // Como es una division se agrega un KIT PARA 2 LIENZOS
                                if( (INT)$request->recordQuotation['division'] === 2 ) {
                                    $dataRecord[] = [
                                        'quotation_id'         => $request->quotation_id,
                                        'item_id'              => $item_id,
                                        'article_id'           => 258,
                                        'quotation_product_id' => 4,
                                        'operation_id'         => null,
                                        'quantity'             => 1,
                                        'width'                => null,
                                        'height'               => null,
                                        'fall'                 => null,
                                        'counterweight_bar_id' => null,
                                        'chain_id'             => null,
                                        'height_chain'         => null,
                                        'mechanism_id'         => null,
                                        'side_id'              => null,
                                        'mechanism_side_id'    => null,
                                        'default_mechanism_id' => null,
                                        'unit_id'              => 3,
                                        'component_color_id'   => null,
                                        'commit'               => null,
                                        'commit_client'        => null,
                                        'awning_type_id'       => null, // tipo de toldo
                                        'area_description'     => null,
                                        'relation_id'          => $relation_id,
                                        'relation_bracket'     => null,
                                        'is_inverted'          => null,
                                        'is_heat_seal'         => 0, // falta definir termosello
                                        'relation_motor'       => null,
                                        'motor_id'             => $relation_motor,
                                        'relation_cassette'    => null,
                                        'cassette_id'          => null,
                                        'relation_lambrequin'  => null,
                                        'relation_accesories'  => null,
                                        'lambrequin_id'        => null,
                                        'is_tie_stripe'        => null,
                                        'tube_id'              => null,
                                        'default_tube_id'      => null,
                                        'divisions'            => null,
                                        'is_velcro'            => 0,
                                        'relation_perfil_priv' => null,
                                        'relation_tensor'      => null,
                                        'if_chain_height'      => null,

                                    ];
                                }
                                $initInsertedId++;
                                $item_id++;
                            break;
                            case 'lambrequin':

                                $is_velcro = 0;
                                if( $request->recordQuotation['is_velcro'] ) { $is_velcro = 1; }
                                $dataRecord[] = [
                                    'quotation_id'         => $request->quotation_id,
                                    'item_id'              => $item_id,
                                    'article_id'           => $extraRecord['article_id'],
                                    'quotation_product_id' => $extraRecord['product_id'],
                                    'operation_id'         => null,
                                    'quantity'             => 1,
                                    'width'                => $extraRecord['width'],
                                    'height'               => 0.14,
                                    'fall'                 => null,
                                    'counterweight_bar_id' => $request->recordQuotation['counterweight_bar_id'],
                                    'chain_id'             => null,
                                    'height_chain'         => null,
                                    'mechanism_id'         => null,
                                    'default_mechanism_id' => null,
                                    'side_id'              => null,
                                    'mechanism_side_id'    => null,
                                    'unit_id'              => $extraRecord['unit_id'],
                                    'component_color_id'   => $request->recordQuotation['component_color_id'],
                                    'commit'               => null,
                                    'commit_client'        => $request->recordQuotation['detail'],
                                    'awning_type_id'       => null, // tipo de toldo
                                    'area_description'     => $request->recordQuotation['area'],
                                    'relation_id'          => $relation_id,
                                    'relation_bracket'     => null,
                                    'is_inverted'          => $extraRecord['is_inverted'],
                                    'is_heat_seal'         => 0, // falta definir termosello
                                    'relation_motor'       => null,
                                    'motor_id'             => null,
                                    'relation_cassette'    => null,
                                    'cassette_id'          => null,
                                    'relation_lambrequin'  => $relation_lambrequin,
                                    'relation_accesories'  => null,
                                    'lambrequin_id'        => $request->recordQuotation['article_id'],
                                    'is_tie_stripe'        => null,
                                    'tube_id'              => null,
                                    'default_tube_id'      => null,
                                    'divisions'            => null,
                                    'is_velcro'            => $is_velcro,
                                    'relation_perfil_priv' => null,
                                    'relation_tensor'      => null,
                                    'if_chain_height'      => null,
                                ];
                                $initInsertedId++;
                                $item_id++;
                            break;
                        }
                    }
                    // SI hay un tubo de 50, 63, 70 agregamos el extra
                    if($record['tube_id'] == 3 || $record['tube_id'] == 4 || $record['tube_id'] == 5 || $record['tube_id'] == 6) {

                        $tube_article_id = 0;
                        $tube_unit_id = 0;
                        $adapter_article_id = 0;
                        $adapter_unit_id = 0;

                        switch ($record['tube_id']) {
                            case 3: // 50 astriado
                                $tube_article_id = 42;
                                $tube_unit_id = 2;
                                $adapter_article_id = 46;
                                $adapter_unit_id = 5;
                                $total_width = $widthSum;
                            break;
                            case 4: // 50 LISO
                                $tube_article_id = 43;
                                $tube_unit_id = 3;
                                $adapter_article_id = 46;
                                $adapter_unit_id = 5;
                                $total_width = null;
                            break;
                            case 5: // 63 mm
                                $tube_article_id = 44;
                                $tube_unit_id = 3;
                                $adapter_article_id = 47;
                                $adapter_unit_id = 5;
                                $total_width = null;
                            break;
                            case 6: // 70 mm
                                $tube_article_id = 45;
                                $tube_unit_id = 3;
                                $adapter_article_id = 48;
                                $adapter_unit_id = 5;
                                $total_width = null;
                            break;
                        }
                        // agregamos tubo
                        $dataRecord[] = [
                            'quotation_id'         => $request->quotation_id,
                            'item_id'              => $item_id,
                            'article_id'           => $tube_article_id,
                            'quotation_product_id' => 4,
                            'operation_id'         => null,
                            'quantity'             => 1,
                            'width'                => $total_width,
                            'height'               => null,
                            'fall'                 => null,
                            'counterweight_bar_id' => null,
                            'chain_id'             => null,
                            'height_chain'         => null,
                            'mechanism_id'         => null,
                            'default_mechanism_id' => null,
                            'side_id'              => null,
                            'mechanism_side_id'    => null,
                            'unit_id'              => $tube_unit_id,
                            'component_color_id'   => null,
                            'commit'               => null,
                            'commit_client'        => null,
                            'awning_type_id'       => null, // tipo de toldo
                            'area_description'     => null,
                            'relation_id'          => $relation_id,
                            'relation_bracket'     => null,
                            'is_inverted'          => null,
                            'is_heat_seal'         => 0, // falta definir termosello
                            'relation_motor'       => null,
                            'motor_id'             => null,
                            'relation_cassette'    => null,
                            'cassette_id'          => null,
                            'relation_lambrequin'  => null,
                            'relation_accesories'  => $relation_accesories,
                            'lambrequin_id'        => null,
                            'is_tie_stripe'        => null,
                            'tube_id'              => null,
                            'default_tube_id'      => null,
                            'divisions'            => null,
                            'is_velcro'            => 0,
                            'relation_perfil_priv' => null,
                            'relation_tensor'      => null,
                            'if_chain_height'      => null,

                        ];
                        $initInsertedId++;
                        $item_id++;
                        // agregamos el reductor
                        $dataRecord[] = [
                            'quotation_id'         => $request->quotation_id,
                            'item_id'              => $item_id,
                            'article_id'           => $adapter_article_id,
                            'quotation_product_id' => 4,
                            'operation_id'         => null,
                            'quantity'             => 1,
                            'width'                => null,
                            'height'               => null,
                            'fall'                 => null,
                            'counterweight_bar_id' => null,
                            'chain_id'             => null,
                            'height_chain'         => null,
                            'mechanism_id'         => null,
                            'default_mechanism_id' => null,
                            'side_id'              => null,
                            'mechanism_side_id'    => null,
                            'unit_id'              => $adapter_unit_id,
                            'component_color_id'   => null,
                            'commit'               => null,
                            'commit_client'        => null,
                            'awning_type_id'       => null, // tipo de toldo
                            'area_description'     => null,
                            'relation_id'          => $relation_id,
                            'relation_bracket'     => null,
                            'is_inverted'          => null,
                            'is_heat_seal'         => 0, // falta definir termosello
                            'relation_motor'       => null,
                            'motor_id'             => null,
                            'relation_cassette'    => null,
                            'cassette_id'          => null,
                            'relation_lambrequin'  => null,
                            'relation_accesories'  => $relation_accesories,
                            'lambrequin_id'        => null,
                            'is_tie_stripe'        => null,
                            'tube_id'              => null,
                            'default_tube_id'      => null,
                            'divisions'            => null,
                            'is_velcro'            => 0,
                            'relation_perfil_priv' => null,
                            'relation_tensor'      => null,
                            'if_chain_height'      => null,

                        ];
                        $initInsertedId++;
                        $item_id++;
                    }
                    // SI hay na cadena metalica la agregamos
                    if( (INT)$request->recordQuotation['chain_id'] === 2 ) {
                        foreach ($request->recordQuotation['records'] as $record) {
                            // agregamos el cadena metalica
                            $dataRecord[] = [
                                'quotation_id'         => $request->quotation_id,
                                'item_id'              => $item_id,
                                'article_id'           => 70,
                                'quotation_product_id' => 4,
                                'operation_id'         => null,
                                'quantity'             => 1,
                                'width'                => $record['height_chain'],
                                'height'               => null,
                                'fall'                 => null,
                                'counterweight_bar_id' => null,
                                'chain_id'             => null,
                                'height_chain'         => null,
                                'mechanism_id'         => null,
                                'default_mechanism_id' => null,
                                'side_id'              => null,
                                'mechanism_side_id'    => null,
                                'unit_id'              => 2,
                                'component_color_id'   => null,
                                'commit'               => null,
                                'commit_client'        => null,
                                'awning_type_id'       => null, // tipo de toldo
                                'area_description'     => null,
                                'relation_id'          => $relation_id,
                                'relation_bracket'     => null,
                                'is_inverted'          => null,
                                'is_heat_seal'         => 0, // falta definir termosello
                                'relation_motor'       => null,
                                'motor_id'             => null,
                                'relation_cassette'    => null,
                                'cassette_id'          => null,
                                'relation_lambrequin'  => null,
                                'relation_accesories'  => $relation_accesories,
                                'lambrequin_id'        => null,
                                'is_tie_stripe'        => null,
                                'tube_id'              => null,
                                'default_tube_id'      => null,
                                'divisions'            => null,
                                'is_velcro'            => 0,
                                'relation_perfil_priv' => null,
                                'relation_tensor'      => null,
                                'if_chain_height'      => null,
                            ];
                            $initInsertedId++;
                            $item_id++;
                        }
                    }
                    // SI hay bracket intermedio lo agredamos unicamente enrollables sin cassette
                    if( $request->recordQuotation['is_bracket'] AND (INT)$request->recordQuotation['quotation_product_id'] === 1 AND !$request->recordQuotation['is_cassette'] ) {
                        // agregamos el bracket
                        $dataRecord[] = [
                            'quotation_id'         => $request->quotation_id,
                            'item_id'              => $item_id,
                            'article_id'           => 100,
                            'quotation_product_id' => 4,
                            'operation_id'         => null,
                            'quantity'             => 1,
                            'width'                => null,
                            'height'               => null,
                            'fall'                 => null,
                            'counterweight_bar_id' => null,
                            'chain_id'             => null,
                            'height_chain'         => null,
                            'mechanism_id'         => null,
                            'default_mechanism_id' => null,
                            'side_id'              => null,
                            'mechanism_side_id'    => null,
                            'unit_id'              => 3,
                            'component_color_id'   => null,
                            'commit'               => null,
                            'commit_client'        => null,
                            'awning_type_id'       => null, // tipo de toldo
                            'area_description'     => null,
                            'relation_id'          => $relation_id,
                            'relation_bracket'     => null,
                            'is_inverted'          => null,
                            'is_heat_seal'         => 0, // falta definir termosello
                            'relation_motor'       => null,
                            'motor_id'             => null,
                            'relation_cassette'    => null,
                            'cassette_id'          => null,
                            'relation_lambrequin'  => null,
                            'relation_accesories'  => $relation_accesories,
                            'lambrequin_id'        => null,
                            'is_tie_stripe'        => null,
                            'tube_id'              => null,
                            'default_tube_id'      => null,
                            'divisions'            => null,
                            'is_velcro'            => 0,
                            'relation_perfil_priv' => null,
                            'relation_tensor'      => null,
                            'if_chain_height'      => null,
                        ];
                        $initInsertedId++;
                        $item_id++;
                    }
                    // SI lleva perfil de privacidad agregarlo
                    if( $request->recordQuotation['is_perfil_priv'] AND (INT)$request->recordQuotation['quotation_product_id'] === 1 ) {
                        // buscamos el color del perfil de privacidad
                        if( (INT)$request->recordQuotation['component_color_id'] === 4 ) {
                            $result = CArticle::select('id')->where('color_id',7)->where('model_id',59)->first();
                        } else {
                            $result = CArticle::select('id')->where('color_id',$request->recordQuotation['component_color_id'])->where('model_id',59)->first();
                        }
                        foreach ($request->recordQuotation['records'] as $record) {
                            // agregamos el bracket
                            $dataRecord[] = [
                                'quotation_id'         => $request->quotation_id,
                                'item_id'              => $item_id,
                                'article_id'           => $result->id,
                                'quotation_product_id' => 4,
                                'operation_id'         => null,
                                'quantity'             => 2,
                                'width'                => (DOUBLE)$record['height'],
                                'height'               => null,
                                'fall'                 => null,
                                'counterweight_bar_id' => null,
                                'chain_id'             => null,
                                'height_chain'         => null,
                                'mechanism_id'         => null,
                                'default_mechanism_id' => null,
                                'side_id'              => null,
                                'mechanism_side_id'    => null,
                                'unit_id'              => 2,
                                'component_color_id'   => null,
                                'commit'               => null,
                                'commit_client'        => null,
                                'awning_type_id'       => null, // tipo de toldo
                                'area_description'     => null,
                                'relation_id'          => $relation_id,
                                'relation_bracket'     => null,
                                'is_inverted'          => null,
                                'is_heat_seal'         => 0, // falta definir termosello
                                'relation_motor'       => null,
                                'motor_id'             => null,
                                'relation_cassette'    => null,
                                'cassette_id'          => null,
                                'relation_lambrequin'  => null,
                                'relation_accesories'  => null,
                                'lambrequin_id'        => null,
                                'is_tie_stripe'        => null,
                                'tube_id'              => null,
                                'default_tube_id'      => null,
                                'divisions'            => null,
                                'is_velcro'            => 0,
                                'relation_perfil_priv' => $relation_perfil_priv,
                                'relation_tensor'      => null,
                                'if_chain_height'      => null,
                            ];
                            $initInsertedId++;
                            $item_id++;
                        }
                    }

                    // SI hay tensor agregamos
                    if( $request->recordQuotation['if_tensor'] AND (INT)$request->recordQuotation['quotation_product_id'] === 1 ) {
                        // agregamos el tensor
                        $dataRecord[] = [
                            'quotation_id'         => $request->quotation_id,
                            'item_id'              => $item_id,
                            'article_id'           => 283,
                            'quotation_product_id' => 4,
                            'operation_id'         => null,
                            'quantity'             => $request->recordQuotation['division'],
                            'width'                => null,
                            'height'               => null,
                            'fall'                 => null,
                            'counterweight_bar_id' => null,
                            'chain_id'             => null,
                            'height_chain'         => null,
                            'mechanism_id'         => null,
                            'default_mechanism_id' => null,
                            'side_id'              => null,
                            'mechanism_side_id'    => null,
                            'unit_id'              => 3,
                            'component_color_id'   => null,
                            'commit'               => null,
                            'commit_client'        => null,
                            'awning_type_id'       => null, // tipo de toldo
                            'area_description'     => null,
                            'relation_id'          => $relation_id,
                            'relation_bracket'     => null,
                            'is_inverted'          => null,
                            'is_heat_seal'         => 0, // falta definir termosello
                            'relation_motor'       => null,
                            'motor_id'             => null,
                            'relation_cassette'    => null,
                            'cassette_id'          => null,
                            'relation_lambrequin'  => null,
                            'relation_accesories'  => null,
                            'lambrequin_id'        => null,
                            'is_tie_stripe'        => null,
                            'tube_id'              => null,
                            'default_tube_id'      => null,
                            'divisions'            => null,
                            'is_velcro'            => 0,
                            'relation_perfil_priv' => null,
                            'relation_tensor'      => $relation_tensor,
                            'if_chain_height'      => null,
                        ];
                        $initInsertedId++;
                        $item_id++;
                    }
                    // Si es base ovalada se agrega
                    // if( (INT)$request->recordQuotation['counterweight_bar_id'] === 2 OR $request->recordQuotation['counterweight_bar_id'] === 4 ) {
                    //     $widthCW = 0;
                    //     foreach ($request->recordQuotation['records'] as $record) {
                    //         $widthCW = $widthCW + $record['width'];
                    //     }
                    //     // agregamos la base ovalada
                    //     $dataRecord[] = [
                    //         'quotation_id'         => $request->quotation_id,
                    //         'item_id'              => $item_id,
                    //         'article_id'           => 255,
                    //         'quotation_product_id' => 4,
                    //         'operation_id'         => null,
                    //         'quantity'             => 1,
                    //         'width'                => $widthCW,
                    //         'height'               => null,
                    //         'fall'                 => null,
                    //         'counterweight_bar_id' => null,
                    //         'chain_id'             => null,
                    //         'height_chain'         => null,
                    //         'mechanism_id'         => null,
                    //         'default_mechanism_id' => null,
                    //         'side_id'              => null,
                    //         'mechanism_side_id'    => null,
                    //         'unit_id'              => 2,
                    //         'component_color_id'   => null,
                    //         'commit'               => null,
                    //         'commit_client'        => null,
                    //         'awning_type_id'       => null, // tipo de toldo
                    //         'area_description'     => null,
                    //         'relation_id'          => $relation_id,
                    //         'relation_bracket'     => null,
                    //         'is_inverted'          => null,
                    //         'is_heat_seal'         => 0, // falta definir termosello
                    //         'relation_motor'       => null,
                    //         'motor_id'             => null,
                    //         'relation_cassette'    => null,
                    //         'cassette_id'          => null,
                    //         'relation_lambrequin'  => null,
                    //         'relation_accesories'  => $relation_accesories,
                    //         'lambrequin_id'        => null,
                    //         'is_tie_stripe'        => null,
                    //         'tube_id'              => null,
                    //         'default_tube_id'      => null,
                    //         'divisions'            => null,
                    //     ];
                    //     $initInsertedId++;
                    //     $item_id++;
                    // }
                }
            }
            // ACCESORIOS
            if( $request->recordQuotation['quotation_product_id'] == 4) {
                // SERVICE TERMOSELLO
                if( $request->recordQuotation['model_id'] == 55) {
                    // Agregamos la relacion del termosello
                    $relation_heat_seal = 0;
                    // maximo relacion
                    $relationAll = null;
                    $relationAll = DQuotation::select(DB::raw('MAX(relation_heat_seal) as max_relation_heat_seal'))->where('quotation_id',$request->quotation_id)->first();
                    if(!is_null($relationAll['max_relation_heat_seal'])) {
                        $relation_heat_seal = $relationAll['max_relation_heat_seal'];
                    }
                    $relation_heat_seal++;
                    // Update Item
                    DQuotation::where('id',$request->item_select['id'])
                    ->update([
                        'is_heat_seal'       => 1,
                        'relation_heat_seal' => $relation_heat_seal,
                    ]);
                    $item_heat_seal = $request->item_select['id'];
                    //  Lienzo
                    $dataRecord[] = [
                        'quotation_id'         => $request->quotation_id,
                        'item_id'              => $item_id,
                        'article_id'           => $request->item_select['article_id'],
                        'quotation_product_id' => 5,
                        'quantity'             => 1,
                        'width'                => $request->item_select['width'],
                        'height'               => $request->recordQuotation['heigh_lienzo'],
                        'unit_id'              => 1,
                        'component_color_id'   => $request->item_select['component_color_id'],
                        'is_inverted'          => $request->item_select['is_inverted'],
                        'is_heat_seal'         => 1,
                        'relation_heat_seal'   => $relation_heat_seal,
                        'relation_id'          => $request->item_select['relation_id'],
                        'commit_client'        => $request->recordQuotation['detail'],
                    ];
                    $initInsertedId++;
                    $item_id++;
                    //  Servicio
                    $dataRecord[] = [
                        'quotation_id'         => $request->quotation_id,
                        'item_id'              => $item_id,
                        'article_id'           => $request->recordQuotation['article_id'],
                        'quotation_product_id' => $request->recordQuotation['quotation_product_id'],
                        'quantity'             => 1,
                        'width'                => $request->item_select['width'],
                        'height'               => null,
                        'unit_id'              => 2,
                        'component_color_id'   => null,
                        'is_inverted'          => null,
                        'is_heat_seal'         => null,
                        'relation_heat_seal'   => $relation_heat_seal,
                        'relation_id'          => $request->item_select['relation_id'],
                        'commit_client'        => $request->recordQuotation['detail'],
                    ];
                    $initInsertedId++;
                    $item_id++;
                } else if( $request->recordQuotation['model_id'] == 24 AND !$request->recordQuotation['only_bracket']) { // soporte dia y noche
                    $modelUpdate = 24;
                    // Agregamos la relacion del termosello
                    $relation_bracket_dn = 0;
                    // maximo relacion
                    $relationAll = null;
                    $relationAll = DQuotation::select(DB::raw('MAX(relation_bracket_dn) as max_relation_bracket_dn'))->where('quotation_id',$request->quotation_id)->first();
                    if(!is_null($relationAll['max_relation_bracket_dn'])) { $relation_bracket_dn = $relationAll['max_relation_bracket_dn']; }
                    $relation_bracket_dn++;
                    // actualizamos la relacion del bracket y cambiamos sus mecanismos a SL16
                    foreach ($request->items_selected as $key => $item) {

                        $modelDataUpdate[] = [
                            'id'                    =>$item['id'],
                            'quotation_product_id'  =>$item['quotation_product_id'],
                            'mechanism_id'          => 3,
                            'mechanism'             => 'SL16',
                            'relation_bracket_dn'   => $relation_bracket_dn,
                        ];
                        DQuotation::where('id',$item['id'])
                        ->where('quotation_product_id',1)
                        ->update([
                            'mechanism_id' => 3,
                        ]);

                        // log
                        $BTestLog = new BTestLog();
                        $BTestLog->user_id = $request->user_id;
                        $BTestLog->log = 'Se asigno bracket dia y noche';
                        $BTestLog->identifier_type = 'detail_quotation_id';
                        $BTestLog->identifier_number = $item['id'];
                        $BTestLog->identifier_text = $item['id'];
                        $BTestLog->description = 'Se asigno bracket dia y noche y mecanismo con id 3 SL16' ;
                        $BTestLog->save();

                        DQuotation::where('id',$item['id'])
                        ->update([
                            'relation_bracket_dn' => $relation_bracket_dn,
                        ]);
                    }

                    $relation_id++;
                    // aumentamos la relacion
                    $dataRecord[] = [
                        'quotation_id'         => $request->quotation_id,
                        'item_id'              => $item_id,
                        'article_id'           => $request->recordQuotation['article_id'],
                        'quotation_product_id' => $request->recordQuotation['quotation_product_id'],
                        'quantity'             => 1,
                        'width'                => null,
                        'height'               => null,
                        'unit_id'              => $request->recordQuotation['unit_id'],
                        'component_color_id'   => null,
                        'is_inverted'          => null,
                        'is_heat_seal'         => null,
                        'relation_id'          => $relation_id,
                        'relation_bracket_dn'  => $relation_bracket_dn,
                        'commit_client'        => $request->recordQuotation['detail'],
                    ];
                    $initInsertedId++;
                    $item_id++;

                } else if( $request->recordQuotation['model_id'] == 56 AND !$request->recordQuotation['only_control']) { // control
                    $modelUpdate = 56;
                    // Agregamos la relacion del termosello
                    $relation_control = 0;
                    // maximo relacion
                    $relationAll = null;
                    $relationAll = DQuotation::select(DB::raw('MAX(relation_control) as max_relation_control'))->where('quotation_id',$request->quotation_id)->first();
                    if(!is_null($relationAll['max_relation_control'])) { $relation_control = $relationAll['max_relation_control']; }
                    $relation_control++;
                    // actualizamos la relacion de control
                    foreach ($request->items_selected as $key => $item) {
                        $modelDataUpdate[] = [
                            'id'                    =>$item['id'],
                            'relation_control'      => $relation_control,
                            'channel'               => $item['channel'],
                        ];
                        DQuotation::where('id',$item['id'])
                        ->update([
                            'relation_control' => $relation_control,
                            'channel'          => $item['channel'],
                        ]);
                    }

                    $relation_id++;
                    // aumentamos la relacion
                    $dataRecord[] = [
                        'quotation_id'         => $request->quotation_id,
                        'item_id'              => $item_id,
                        'article_id'           => $request->recordQuotation['article_id'],
                        'quotation_product_id' => $request->recordQuotation['quotation_product_id'],
                        'quantity'             => 1,
                        'width'                => null,
                        'height'               => null,
                        'unit_id'              => $request->recordQuotation['unit_id'],
                        'component_color_id'   => null,
                        'is_inverted'          => null,
                        'is_heat_seal'         => null,
                        'relation_id'          => $relation_id,
                        'relation_control'     => $relation_control,
                        'commit_client'        => $request->recordQuotation['detail'],
                    ];
                    $initInsertedId++;
                    $item_id++;
                } else if( $request->recordQuotation['model_id'] == 25) { // Lambrequin
                    $is_velcro = 0;
                    if( $request->recordQuotation['is_velcro'] ) { $is_velcro = 1; }
                    // aumentamos la relacion
                    for ($i=0; $i < $request->recordQuotation['quantity'] ; $i++) {
                        $relation_id++;
                        $dataRecord[] = [
                            'quotation_id'         => $request->quotation_id,
                            'item_id'              => $item_id,
                            'article_id'           => $request->recordQuotation['article_id'],
                            'quotation_product_id' => $request->recordQuotation['quotation_product_id'],
                            'quantity'             => 1,
                            'width'                => $request->recordQuotation['width'],
                            'height'               => $request->recordQuotation['height'],
                            'lambrequin_id'        => $request->recordQuotation['lambrequin_id'],
                            'corbatin_id'          => $request->recordQuotation['corbatin_id'],
                            'fijo_id'              => $request->recordQuotation['fijo_id'],
                            'unit_id'              => $request->recordQuotation['unit_id'],
                            'counterweight_bar_id' => $request->recordQuotation['counterweight_bar_id'],
                            'component_color_id'   => $request->recordQuotation['component_color_id'],
                            'relation_id'          => $relation_id,
                            'is_velcro'            => $is_velcro,
                            'is_inverted'          => $request->recordQuotation['is_inverted'],
                            'commit_client'        => $request->recordQuotation['detail'],
                        ];
                        $initInsertedId++;
                        $item_id++;
                        # code...
                    }

                } else {
                    $is_velcro = 0;
                    if( $request->recordQuotation['is_velcro'] ) { $is_velcro = 1; }
                    // aumentamos la relacion
                    $relation_id++;
                    $dataRecord[] = [
                        'quotation_id'         => $request->quotation_id,
                        'item_id'              => $item_id,
                        'article_id'           => $request->recordQuotation['article_id'],
                        'quotation_product_id' => $request->recordQuotation['quotation_product_id'],
                        'quantity'             => $request->recordQuotation['quantity'],
                        'width'                => $request->recordQuotation['width'],
                        'height'               => $request->recordQuotation['height'],
                        'lambrequin_id'        => $request->recordQuotation['lambrequin_id'],
                        'corbatin_id'          => $request->recordQuotation['corbatin_id'],
                        'fijo_id'              => $request->recordQuotation['fijo_id'],
                        'unit_id'              => $request->recordQuotation['unit_id'],
                        'counterweight_bar_id' => $request->recordQuotation['counterweight_bar_id'],
                        'component_color_id'   => $request->recordQuotation['component_color_id'],
                        'relation_id'          => $relation_id,
                        'is_velcro'            => $is_velcro,
                        'commit_client'        => $request->recordQuotation['detail'],
                    ];
                    $initInsertedId++;
                    $item_id++;
                }
            }
            // DATA
            DQuotation::insert($dataRecord);
            $dQuotationAll = DQuotation::latest('id')->first();
            $lastInsertedId = $dQuotationAll->id;
            $initInsertedId = ($lastInsertedId - $initInsertedId)+1;

            $DQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_quotations.corbatin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_quotations.fijo_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE  CASE c_users.price_list_id WHEN 1 THEN c_articles.price WHEN 2 THEN c_articles.price_list_2 END END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','c_mechanisms.mechanism','d_quotations.relation_lambrequin','d_quotations.lambrequin_id','d_quotations.corbatin_id','d_quotations.fijo_id','d_quotations.is_velcro','d_quotations.relation_perfil_priv','d_quotations.relation_tensor','d_quotations.if_chain_height')
            ->join('e_quotations', 'e_quotations.id','d_quotations.quotation_id')
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
            ->whereBetween('d_quotations.id', [$initInsertedId, $lastInsertedId])
            ->get()
            ->toArray();

            // $DQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_quotations.corbatin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_quotations.fijo_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE c_articles.price END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','d_quotations.default_tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','c_mechanisms.mechanism','d_quotations.relation_lambrequin','d_quotations.lambrequin_id','d_quotations.fijo_id','d_quotations.corbatin_id','d_quotations.is_velcro','d_quotations.fijo_id','d_quotations.default_mechanism_id','d_quotations.relation_perfil_priv','d_quotations.relation_tensor','d_quotations.if_chain_height')
            // ->join('c_articles','c_articles.id','d_quotations.article_id')
            // ->join('c_products','c_products.id','d_quotations.quotation_product_id')
            // ->leftJoin('c_operations','c_operations.id','d_quotations.operation_id')
            // ->join('c_units','c_units.id','d_quotations.unit_id')
            // ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_quotations.mechanism_side_id')
            // ->leftJoin('c_chains','c_chains.id','d_quotations.chain_id')
            // ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_quotations.counterweight_bar_id')
            // ->leftJoin('c_colors','c_colors.id','d_quotations.component_color_id')
            // ->leftJoin('c_tubes','c_tubes.id','d_quotations.tube_id')
            // ->leftJoin('c_articles AS la','la.id','d_quotations.lambrequin_id')
            // ->leftJoin('c_articles AS cb','cb.id','d_quotations.corbatin_id')
            // ->leftJoin('c_articles AS fj','fj.id','d_quotations.fijo_id')
            // ->leftJoin('c_mechanisms','c_mechanisms.id','d_quotations.mechanism_id')
            // ->whereBetween('d_quotations.id', [$initInsertedId, $lastInsertedId])
            // ->get()
            // ->toArray();

            // REQUEST DISCOUNT
            $requestdiscount = EQuotationDiscountRequest::select('e_quotation_discount_requests.id','e_quotation_discount_requests.user_id','c_erp_info_users.short_name','e_quotation_discount_requests.quotation_id','e_quotation_discount_requests.discount','e_quotation_discount_requests.reason','e_quotation_discount_requests.is_approved')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_quotation_discount_requests.user_id','e_quotation_discount_requests.is_approved')
            ->where('e_quotation_discount_requests.is_active',1)
            ->where('e_quotation_discount_requests.is_approved',1)
            ->where('e_quotation_discount_requests.quotation_id',$request->quotation_id,)
            ->first();
            if(!is_null($requestdiscount)) { foreach ($DQuotation as $key => $dquo) { $DQuotation[$key]['request_discount'] = $requestdiscount->discount; } }
            // mandar cotizacion a los lideres
            $userLider = CErpInfoUser::select('user_id as id')->where('is_leader',1)->whereNotIn('user_id',[$request->user_id])->get();
            foreach ($userLider as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
            // mandar la cotizacion al cliente
            $client_socket = DSocketConnection::select('socket_id','user_id','user_type')->where('user_id',$request->client_id)->where('user_type','CLIE')->get();
            return response()->json([
                'success'         =>  true ,
                'quotation_id'    =>  $request->quotation_id,
                'quotationDetail' =>  $DQuotation,
                'requestdiscount' =>  $requestdiscount,
                'item_heat_seal'  =>  $item_heat_seal,
                'modelUpdate'     =>  $modelUpdate,
                'modelDataUpdate' =>  $modelDataUpdate,
                'users_socket'    =>  $users_socket,
                'client_socket'   =>  $client_socket,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function saveCopyRegsElement(Request $request)
    {
        // try {
            $initInsertedId = 0;
            // DATA
            $dataRecord = [];
            $item_id = 1;
            $relation_id = 0;
            $relation_motor = 0;
            $relation_cassette = 0;
            $relation_bracket = 0;
            $relation_bracket_dn = 0;
            $relation_accesories = 0;
            $relation_heat_seal = 0;
            $relation_control = 0;
            // buscaos el maximo del item para su consecutivo
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(item_id) as max_item_id'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_item_id'])) { $item_id = $relationAll['max_item_id'] + 1; }
            // maximo relacion
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(relation_id) as max_relation'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_relation'])) {
                $relation_id = $relationAll['max_relation'];
            }
            // maximo relacion Motor
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(relation_motor) as max_motor_relation'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_motor_relation'])) {
                $relation_motor = $relationAll['max_motor_relation'];
            }
            // maximo realacion cassette
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(relation_cassette) as max_cassette_relation'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_cassette_relation'])) {
                $relation_cassette = $relationAll['max_cassette_relation'];
            }
            // maximo realacion lambrequin
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(relation_lambrequin) as max_relation_lambrequin'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_relation_lambrequin'])) {
                $relation_lambrequin = $relationAll['max_relation_lambrequin'];
            }
            // maxima relacion bracket
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(relation_bracket) as max_bracket_relation'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_bracket_relation'])) {
                $relation_bracket = $relationAll['max_bracket_relation'];
            }
            // maxima relacion bracket dn
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(relation_bracket_dn) as max_relation_bracket_dn'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_relation_bracket_dn'])) {
                $relation_bracket_dn = $relationAll['max_relation_bracket_dn'];
            }
            // maximo realacion accesorio
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(relation_accesories) as max_relation_accesories'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_relation_accesories'])) {
                $relation_accesories = $relationAll['max_relation_accesories'];
            }
            // maximo relacion
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(relation_heat_seal) as max_relation_heat_seal'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_relation_heat_seal'])) {
                $relation_heat_seal = $relationAll['max_relation_heat_seal'];
            }
            // maximo relacion
            $relationAll = null;
            $relationAll = DQuotation::select(DB::raw('MAX(relation_control) as max_relation_control'))->where('quotation_id',$request->quotation_id)->first();
            if(!is_null($relationAll['max_relation_control'])) {
                $relation_control = $relationAll['max_relation_control'];
            }

            for ($i=0; $i < $request->copyRegsNum; $i++) { // cantidad seleccionada
                // aumentamos la relacion
                $relation_id++;
                // si tiene motor la relacionamos
                $relation_motor++;
                // si tiene cassette la relacionamos
                $relation_cassette++;
                // si tienen braquet intermedio la relacionamos
                $relation_bracket++;
                // si tienen braquet dia y noche
                $relation_bracket_dn++;
                // si tiene lambrequin la relacionamos
                $relation_lambrequin++;
                // si tienen tubo de 50 0 63 relacionamos
                $relation_accesories++;
                // si tienen relacion de termosello
                $relation_heat_seal++;
                // si tienen relacion de control
                $relation_control++;
                // cantidad seleccionada
                foreach ($request->copyElement as $key => $items) { // total de registros
                    // creamos los registros
                    $dataRecord[] = [
                        'quotation_id'         => $request->quotation_id,
                        'item_id'              => $item_id,
                        'article_id'           => $items['article_id'],
                        'quotation_product_id' => $items['quotation_product_id'],
                        'operation_id'         => $items['operation_id'],
                        'quantity'             => $items['quantity'],
                        'width'                => $items['width'],
                        'height'               => $items['height'],
                        'fall'                 => $items['fall'],
                        'counterweight_bar_id' => $items['counterweight_bar_id'],
                        'chain_id'             => $items['chain_id'],
                        'height_chain'         => $items['height_chain'],
                        'mechanism_id'         => $items['mechanism_id'],
                        'default_mechanism_id' => $items['mechanism_id'],
                        'side_id'              => $items['side_id'],
                        'mechanism_side_id'    => $items['mechanism_side_id'],
                        'unit_id'              => $items['unit_id'],
                        'component_color_id'   => $items['component_color_id'],
                        'commit'               => null,
                        'commit_client'        => $request->details,
                        'awning_type_id'       => null, // tipo de toldo
                        'area_description'     => $items['commit_client'],
                        'relation_id'          => $relation_id,
                        'relation_bracket'     => $items['relation_bracket'] > 0 ? $relation_bracket : 0,
                        'relation_bracket_dn'  => $items['relation_bracket_dn'] > 0 ? $relation_bracket_dn : 0,
                        'is_inverted'          => $items['is_inverted'],
                        'is_heat_seal'         => $items['is_heat_seal'],
                        'relation_motor'       => $items['relation_motor'] > 0 ? $relation_motor : 0,
                        'motor_id'             => $items['relation_motor'] > 0 ? $items['motor_id'] : null,
                        'relation_cassette'    => $items['relation_cassette'] > 0 ? $relation_cassette : 0,
                        'cassette_id'          => $items['cassette_id'],
                        'relation_lambrequin'  => $items['relation_lambrequin'] > 0 ? $relation_lambrequin : 0,
                        'relation_accesories'  => $items['relation_accesories'] > 0 ? $relation_accesories : 0,
                        'relation_heat_seal'   => $items['relation_heat_seal'] > 0 ? $relation_heat_seal : 0,
                        'relation_control'     => $items['relation_control'] > 0 ? $relation_control : 0,
                        'channel'              => $items['channel'],
                        'lambrequin_id'        => $items['lambrequin_id'],
                        'corbatin_id'          => $items['corbatin_id'],
                        'fijo_id'              => $items['fijo_id'],
                        'is_tie_stripe'        => $items['quotation_product_id'] == 2 ? 1 : 0,
                        'tube_id'              => $items['tube_id'],
                        'default_tube_id'      => $items['default_tube_id'],
                        'divisions'            => $items['divisions'],
                        'is_velcro'            => $items['is_velcro'],
                    ];
                    $initInsertedId++;
                    $item_id++;
                }
            }
            DQuotation::insert($dataRecord);
            $dQuotationAll = DQuotation::all();
            $lastInsertedId = $dQuotationAll->last()->id;
            $initInsertedId = ($lastInsertedId - $initInsertedId)+1;
            $DQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_quotations.corbatin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_quotations.fijo_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE  CASE c_users.price_list_id WHEN 1 THEN c_articles.price WHEN 2 THEN c_articles.price_list_2 END END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','c_mechanisms.mechanism','d_quotations.relation_lambrequin','d_quotations.lambrequin_id','d_quotations.corbatin_id','d_quotations.fijo_id','d_quotations.is_velcro','d_quotations.relation_perfil_priv','d_quotations.relation_tensor','d_quotations.if_chain_height')
            ->join('e_quotations', 'e_quotations.id','d_quotations.quotation_id')
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
            ->whereBetween('d_quotations.id', [$initInsertedId, $lastInsertedId])
            ->get();

            // $DQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_quotations.corbatin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_quotations.fijo_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE c_articles.price END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','d_quotations.default_tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','c_mechanisms.mechanism','d_quotations.relation_lambrequin','d_quotations.lambrequin_id','d_quotations.fijo_id','d_quotations.corbatin_id','d_quotations.is_velcro','d_quotations.default_mechanism_id','d_quotations.relation_perfil_priv','d_quotations.relation_tensor','d_quotations.if_chain_height')
            // ->join('c_articles','c_articles.id','d_quotations.article_id')
            // ->join('c_products','c_products.id','d_quotations.quotation_product_id')
            // ->leftJoin('c_operations','c_operations.id','d_quotations.operation_id')
            // ->join('c_units','c_units.id','d_quotations.unit_id')
            // ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_quotations.mechanism_side_id')
            // ->leftJoin('c_chains','c_chains.id','d_quotations.chain_id')
            // ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_quotations.counterweight_bar_id')
            // ->leftJoin('c_colors','c_colors.id','d_quotations.component_color_id')
            // ->leftJoin('c_tubes','c_tubes.id','d_quotations.tube_id')
            // ->leftJoin('c_articles AS la','la.id','d_quotations.lambrequin_id')
            // ->leftJoin('c_articles AS cb','cb.id','d_quotations.corbatin_id')
            // ->leftJoin('c_articles AS fj','fj.id','d_quotations.fijo_id')
            // ->leftJoin('c_mechanisms','c_mechanisms.id','d_quotations.mechanism_id')
            // ->whereBetween('d_quotations.id', [$initInsertedId, $lastInsertedId])
            // ->get();

            // mandar cotizacion a los lideres
            $userLider = CErpInfoUser::select('user_id as id')->where('is_leader',1)->whereNotIn('user_id',[$request->user_id])->get();
            foreach ($userLider as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
            // mandar la cotizacion al cliente
            $client_socket = DSocketConnection::select('socket_id','user_id','user_type')->where('user_id',$request->client_id)->where('user_type','CLIE')->get();
            return response()->json([
                'success'      =>  true ,
                'quotation_id' =>  $request->quotation_id,
                'quotationDetail' =>  $DQuotation,
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
     * @param  \App\Models\DQuotation  $dQuotation
     * @return \Illuminate\Http\Response
     */
    public function show(DQuotation $dQuotation, $quotation_id)
    {
        // try {
            $quotation =  self::individualQuotation($quotation_id);
            $clientSelected = CUser::where('id',$quotation['client_id'])->first();
            return response()->json([
                'success'      =>  true ,
                'quotation_id' =>  $quotation_id,
                'quotation'    =>  $quotation,
                'clientSelected' =>  $clientSelected,
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
     * @param  \App\Models\DQuotation  $dQuotation
     * @return \Illuminate\Http\Response
     */
    public function edit(DQuotation $dQuotation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DQuotation  $dQuotation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DQuotation $dQuotation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DQuotation  $dQuotation
     * @return \Illuminate\Http\Response
     */
    public function destroy(DQuotation $dQuotation, $quotation_id, $relation_id, $user_id, $client_id)
    {
        // try {
            $modelUpdate = 0;
            $modelDataUpdate = [];
            // buscamos la relacion del registro
            $foundQuotation = DQuotation::select('d_quotations.id','d_quotations.item_id','c_articles.model_id','d_quotations.relation_control','d_quotations.relation_bracket_dn')
            ->join('c_articles','c_articles.id','d_quotations.article_id')
            ->where('d_quotations.quotation_id',$quotation_id)
            ->where('d_quotations.relation_id',$relation_id)
            ->get();
            // si eliminamos un cassette, temenos que eomonar su relacion y regresar los mecanismos
            foreach ($foundQuotation->toArray() as $key => $od) {
                if((INT)$od['model_id'] === 24) {
                    //  verificamos que componente necesito
                    $detailQuoM = DQuotation::select('id','width','height','quotation_product_id','operation_id','divisions',DB::raw('CASE WHEN relation_cassette > 0 THEN 1 ELSE 0 END AS cassette') )
                    ->where('quotation_id',$quotation_id)
                    ->where('relation_bracket_dn',$od['relation_bracket_dn'])
                    ->whereIn('quotation_product_id',[1,2])
                    ->get();
                    //
                    foreach ($detailQuoM->toArray() as $key => $iu) {
                        $SetMechanism = new SetMechanism(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
                        $mechanismID =  $SetMechanism->mechanism($iu['width'],$iu['height'],$iu['quotation_product_id'],$iu['operation_id'],$iu['divisions'],$iu['cassette']) ;
                        $mechanisData = CMechanism::select('mechanism')->where('id',$mechanismID)->first();
                        $modelDataUpdate[] = [
                            'id'                  => $iu['id'],
                            'relation_bracket_dn' => 0,
                            'mechanism_id'        => $mechanismID,
                            'mechanism'           => $mechanisData->mechanism,
                        ];
                        DQuotation::where('id',$iu['id'])
                        ->update([
                            'relation_bracket_dn' => 0,
                            'mechanism_id'        => $mechanismID,
                        ]);
                        // log
                        $BTestLog = new BTestLog;
                        $BTestLog->user_id = $user_id;
                        $BTestLog->log = 'Elimino relacion brackert dia y bnoche.';
                        $BTestLog->identifier_type = 'detail_quotation_id';
                        $BTestLog->identifier_number = $iu['id'];
                        $BTestLog->identifier_text = $iu['id'];
                        $BTestLog->description = 'Se elimino el bracket dia y noche y cambio el mecanismo a ID '.$mechanismID ;
                        $BTestLog->save();
                    }
                    $modelUpdate = 24;
                }
                if((INT)$od['model_id'] === 56 && (INT)$od['relation_control'] > 0) {

                    // control
                    $updateQuotation = DQuotation::select('id')
                    ->where('relation_control',$od['relation_control'])
                    ->whereNotIn('quotation_product_id',[4])
                    ->get();

                    foreach ($updateQuotation->toArray() as $key => $iu) {
                        $modelDataUpdate[] = $iu;
                        DQuotation::where('id',$iu['id'])
                        ->update([
                            'relation_control' => 0,
                            'channel'          => null,
                        ]);
                    }
                    $modelUpdate = 56;
                }
            }

            // reorganizamos item_id
            $DQuotation = DQuotation::select('d_quotations.id','d_quotations.item_id','c_articles.model_id','d_quotations.relation_control')
            ->join('c_articles','c_articles.id','d_quotations.article_id')
            ->where('d_quotations.quotation_id',$quotation_id)
            ->where('d_quotations.relation_id','>',$relation_id)
            ->get();
            $updateItems = [];
            if(!empty($DQuotation->toArray())) {
                // Obtenemos el menor item_id
                $minQuotationItem = DQuotation::select(DB::raw('MIN(item_id) as min_item_id'))
                ->where('quotation_id',$quotation_id)
                ->where('relation_id',$relation_id)
                ->first();
                $item_id = $minQuotationItem['min_item_id'];
                // Actualizamos item
                foreach ($DQuotation->toArray() as $key => $items) {

                    $updateItems[] = [
                        'id'      => $items['id'],
                        'item_id' => $item_id,
                    ];
                    DQuotation::where('id',$items['id'])
                    ->update([ 'item_id' => $item_id ]);
                    $item_id++;
                }
            }
            $removeQuotation = DQuotation::select('d_quotations.id')
            ->where('quotation_id',$quotation_id)
            ->where('relation_id',$relation_id)
            ->get();
            DQuotation::where('relation_id',$relation_id)->where('quotation_id',$quotation_id)->delete();
            // mandar cotizacion a los lideres
            $userLider = CErpInfoUser::select('user_id as id')->where('is_leader',1)->whereNotIn('user_id',[$user_id])->get();
            foreach ($userLider as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
            // mandar la cotizacion al cliente
            $client_socket = DSocketConnection::select('socket_id','user_id','user_type')->where('user_id',$client_id)->where('user_type','CLIE')->get();
            return response()->json([
                'success'         =>  true ,
                'quotation_id'    =>  $quotation_id,
                'removeQuotation' =>  $removeQuotation,
                'modelUpdate'     =>  $modelUpdate,
                'modelDataUpdate' =>  $modelDataUpdate,
                'updateItems'     =>  $updateItems,
                'users_socket'    =>  $users_socket,
                'client_socket'   =>  $client_socket,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }


    public function updateArticle(Request $request)
    {
        // try {
            $recordsIDs = [];
            $widthCassette = 0;
            foreach ($request->relationItemsSelected as $record) {
                // buscamos el nuevo color del articvlo
                $article = CArticle::select('color_id')->where('id',$request->article_id)->first();
                // buscamos el color actual de la tela y de los componentes
                $itemDQ = DQuotation::select('article_id','component_color_id')->where('id',$record['id'])->first();
                // Buscamos el colr del articulo actual
                $articleAct = CArticle::select('color_id')->where('id',$itemDQ->article_id)->first();
                //
                if( $record['quotation_product_id'] == 1 or $record['quotation_product_id'] == 2 )  {
                    // realizamos el update
                    DQuotation::where('id',$record['id'])
                    ->update([
                        'article_id'            => $request->article_id,
                        'commit_client'         => $request->commit_client,
                        'area_description'      => $request->area_description,
                        'height'                => $record['newHeight'],
                        'width'                 => $record['newWidth'],
                        'is_inverted'           => $record['new_is_inverted'],
                        'component_color_id'    => (INT)$itemDQ->component_color_id !== (INT)$articleAct->color_id ? $itemDQ->component_color_id : $article['color_id'],
                    ]);
                    $widthCassette = $widthCassette = $record['newWidth'];
                    $recordsIDs[] = $record['id'];
                }
                if($record['quotation_product_id'] == 4 && $record['relation_cassette'] > 0 )  {
                    if( (INT)$itemDQ->component_color_id === (INT)$articleAct->color_id ) {
                        // buscamos el nuevo color del articvlo
                        $articleCassette = CArticle::select('id','color_id')->where('model_id',3)->where('color_id',$article['color_id'])->first();
                        // realizamos el update
                        DQuotation::where('id',$record['id'])
                        ->update([
                            'article_id'         => $articleCassette['id'],
                            'width'              => $widthCassette,
                            'component_color_id' => $articleCassette['color_id'],
                        ]);
                        $recordsIDs[] = $record['id'];
                    }
                }
            }

            $DQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_quotations.corbatin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_quotations.fijo_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE c_articles.price END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','d_quotations.default_tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','c_mechanisms.mechanism','d_quotations.relation_lambrequin','d_quotations.lambrequin_id','d_quotations.fijo_id','d_quotations.corbatin_id','d_quotations.fijo_id','d_quotations.is_velcro','d_quotations.default_mechanism_id','d_quotations.relation_perfil_priv','d_quotations.relation_tensor','d_quotations.if_chain_height')
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
            ->leftJoin('c_mechanisms','c_mechanisms.id','d_quotations.mechanism_id')
            ->whereIn('d_quotations.id', $recordsIDs)
            ->get()
            ->toArray();
            // REQUEST DISCOUNT
            $requestdiscount = EQuotationDiscountRequest::select('e_quotation_discount_requests.id','e_quotation_discount_requests.user_id','c_erp_info_users.short_name','e_quotation_discount_requests.quotation_id','e_quotation_discount_requests.discount','e_quotation_discount_requests.reason','e_quotation_discount_requests.is_approved')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_quotation_discount_requests.user_id','e_quotation_discount_requests.is_approved')
            ->where('e_quotation_discount_requests.is_active',1)
            ->where('e_quotation_discount_requests.is_approved',1)
            ->where('e_quotation_discount_requests.quotation_id',$request->quotation_id,)
            ->first();
            if(!is_null($requestdiscount)) {
                foreach ($DQuotation as $key => $dquo) {
                    $DQuotation[$key]['request_discount'] = $requestdiscount->discount;
                }
            }
            return response()->json([
                'success'           => true ,
                'quotation_id'      => $request->quotation_id,
                'details' => $DQuotation,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }

    }

    public function saveTubeChange(Request $request)
    {
        // try {
            $isDefaultTube = true;
            $extraAdd = [];
            $removeQuotation = [];
            $updateItems = [];
            $articlesAddQuotation = [];
            $relation_accesories = 0;
            // verificamos si el tubo a cambiar es el default o no
            $tubeDefault = DQuotation::select('default_tube_id','divisions','quotation_product_id',DB::raw('SUM(width) as total_width'))
            ->where('relation_id',$request->relation_id)
            ->where('quotation_id',$request->quotation_id)
            ->whereIn('quotation_product_id', [1,2])
            ->first();
            if( $tubeDefault->default_tube_id != $request->tube_id AND ( $request->tube_id == 3 || $request->tube_id == 4 || $request->tube_id == 5 || $request->tube_id == 6 ) ) {
                // Borramos si encontramos acceorios relacionados
                // removemos lo que no queremos
                $removeQuotation = DQuotation::select('d_quotations.id')
                ->join('c_articles','c_articles.id','d_quotations.article_id')
                ->where('d_quotations.quotation_id',$request->quotation_id)
                ->where('d_quotations.relation_id',$request->relation_id)
                ->where('d_quotations.quotation_product_id',4)
                ->whereNotIn('c_articles.model_id', [6,50,25,46,47,48,45,49,60,55]) // fascia y tapas - lambrequin
                ->get();
                DQuotation::where('relation_id',$request->relation_id)->where('quotation_id',$request->quotation_id)->whereIn('id',$removeQuotation)->delete();
                // reorganizamos item_id
                $DQuotation = DQuotation::select('d_quotations.id','d_quotations.item_id')
                ->where('quotation_id',$request->quotation_id)
                ->get();
                if(!empty($DQuotation->toArray())) {
                    $item_id = 1;
                    // Actualizamos item
                    foreach ($DQuotation->toArray() as $key => $items) {
                        $updateItems[] = [
                            'id'      => $items['id'],
                            'item_id' => $item_id,
                        ];
                        DQuotation::where('id',$items['id'])
                        ->update([ 'item_id' => $item_id ]);
                        $item_id++;
                    }
                }
                // capturamos item
                $item_save = [];
                $isDefaultTube = false;
                $item_id = 1;
                // buscaos el maximo del item para su consecutivo
                $itemAll = null;
                $itemAll = DQuotation::select(DB::raw('MAX(item_id) as max_item_id'))->where('quotation_id',$request->quotation_id)->first();
                if(!is_null($itemAll['max_item_id'])) { $item_id = $itemAll['max_item_id'] + 1; }
                // verificamos que el registro no tenga algun accesorio relacionado
                $relationAcc = DQuotation::where('relation_id',$request->relation_id)
                ->where('quotation_id',$request->quotation_id)
                ->where('relation_accesories','>',0)
                ->first();

                if(is_null($relationAcc)) {
                    // maximo realacion accesorio
                    $relationAll = null;
                    $relationAll = DQuotation::select(DB::raw('MAX(relation_accesories) as max_relation_accesories'))->where('quotation_id',$request->quotation_id)->first();
                    if(!is_null($relationAll['max_relation_accesories'])) { $relation_accesories = $relationAll['max_relation_accesories'] + 1; }
                } else {
                    $relation_accesories = $relationAcc->relation_accesories;
                }
                $tube_article_id = 0;
                $tube_unit_id = 0;
                $adapter_article_id = 0;
                $adapter_unit_id = 0;
                switch ($request->tube_id) {
                    case 3: // 50 astriado
                        $tube_article_id = 42;
                        $tube_unit_id = 2;
                        $adapter_article_id = 46;
                        $adapter_unit_id = 5;
                        $total_width = $tubeDefault->total_width;
                    break;
                    case 4: // 50 Liso
                        $tube_article_id = 43;
                        $tube_unit_id = 2;
                        $adapter_article_id = 46;
                        $adapter_unit_id = 5;
                        $total_width = $tubeDefault->total_width;
                    break;
                    case 5: // 63 mm
                        $tube_article_id = 44;
                        $tube_unit_id = 3;
                        $adapter_article_id = 47;
                        $adapter_unit_id = 5;
                        $total_width = null;
                    break;
                    case 6: // 70 mm
                        $tube_article_id = 45;
                        $tube_unit_id = 3;
                        $adapter_article_id = 48;
                        $adapter_unit_id = 5;
                        $total_width = null;
                    break;
                }
                // agregamos tubo
                $extraAdd[] = [
                    'quotation_id'         => $request->quotation_id,
                    'item_id'              => $item_id,
                    'article_id'           => $tube_article_id,
                    'quotation_product_id' => 4,
                    'quantity'             => 1,
                    'width'                => $total_width,
                    'height'               => null,
                    'unit_id'              => $tube_unit_id,
                    'relation_id'          => $request->relation_id,
                    'relation_accesories'  => $relation_accesories,
                ];
                $item_save[] = $item_id;
                $item_id++;
                // agregamos el reductor
                $extraAdd[] = [
                    'quotation_id'         => $request->quotation_id,
                    'item_id'              => $item_id,
                    'article_id'           => $adapter_article_id,
                    'quotation_product_id' => 4,
                    'quantity'             => $tubeDefault->divisions,
                    'width'                => null,
                    'height'               => null,
                    'unit_id'              => $adapter_unit_id,
                    'relation_id'          => $request->relation_id,
                    'relation_accesories'  => $relation_accesories,
                ];
                $item_save[] = $item_id;
                $item_id++;
                DQuotation::insert($extraAdd);
                // buscamos la info
                $articlesAddQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_quotations.corbatin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_quotations.fijo_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE c_articles.price END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','d_quotations.default_tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','c_mechanisms.mechanism','d_quotations.relation_lambrequin','d_quotations.lambrequin_id','d_quotations.fijo_id','d_quotations.corbatin_id','d_quotations.fijo_id','d_quotations.is_velcro','d_quotations.default_mechanism_id','d_quotations.relation_perfil_priv','d_quotations.relation_tensor','d_quotations.if_chain_height')
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
                ->leftJoin('c_mechanisms','c_mechanisms.id','d_quotations.mechanism_id')
                ->where('d_quotations.quotation_id', $request->quotation_id)
                ->whereIn('d_quotations.item_id', $item_save)
                ->get()
                ->toArray();
            } else if( $tubeDefault->default_tube_id == $request->tube_id AND ( $request->tube_id == 3 || $request->tube_id == 4 || $request->tube_id == 5 || $request->tube_id == 6 ) ) { // si tenemos que el tubo default es el mismo pero es tubo de 50 o 63
                // Borramos si encontramos acceorios relacionados
                // removemos lo que no queremos
                $removeQuotation = DQuotation::select('d_quotations.id')
                ->join('c_articles','c_articles.id','d_quotations.article_id')
                ->where('d_quotations.quotation_id',$request->quotation_id)
                ->where('d_quotations.relation_id',$request->relation_id)
                ->where('d_quotations.quotation_product_id',4)
                ->whereNotIn('c_articles.model_id', [6,50,25,46,47,48,45,49,60,55]) // fascia y tapas - lambrequin
                ->get();
                DQuotation::where('relation_id',$request->relation_id)->where('quotation_id',$request->quotation_id)->whereIn('id',$removeQuotation)->delete();
                // reorganizamos item_id
                $DQuotation = DQuotation::select('d_quotations.id','d_quotations.item_id')
                ->where('quotation_id',$request->quotation_id)
                ->get();
                if(!empty($DQuotation->toArray())) {
                    $item_id = 1;
                    // Actualizamos item
                    foreach ($DQuotation->toArray() as $key => $items) {
                        $updateItems[] = [
                            'id'      => $items['id'],
                            'item_id' => $item_id,
                        ];
                        DQuotation::where('id',$items['id'])
                        ->update([ 'item_id' => $item_id ]);
                        $item_id++;
                    }
                }
                // capturamos item
                $item_save = [];
                $isDefaultTube = false;
                $item_id = 1;
                // buscaos el maximo del item para su consecutivo
                $itemAll = null;
                $itemAll = DQuotation::select(DB::raw('MAX(item_id) as max_item_id'))->where('quotation_id',$request->quotation_id)->first();
                if(!is_null($itemAll['max_item_id'])) { $item_id = $itemAll['max_item_id'] + 1; }

                // verificamos que el registro no tenga algun accesorio relacionado
                $relationAcc = DQuotation::where('relation_id',$request->relation_id)
                ->where('quotation_id',$request->quotation_id)
                ->where('relation_accesories','>',0)
                ->first();
                if(is_null($relationAcc)) {
                    // maximo realacion accesorio
                    $relationAll = null;
                    $relationAll = DQuotation::select(DB::raw('MAX(relation_accesories) as max_relation_accesories'))->where('quotation_id',$request->quotation_id)->first();
                    if(!is_null($relationAll['max_relation_accesories'])) { $relation_accesories = $relationAll['max_relation_accesories'] + 1; }
                } else {
                    $relation_accesories = $relationAcc->relation_accesories;
                }

                $tube_article_id = 0;
                $tube_unit_id = 0;
                $adapter_article_id = 0;
                $adapter_unit_id = 0;
                switch ($request->tube_id) {
                    case 3: // 50 astriado
                        $tube_article_id = 42;
                        $tube_unit_id = 2;
                        $adapter_article_id = 46;
                        $adapter_unit_id = 3;
                        $total_width = $tubeDefault->total_width;
                    break;
                    case 4: // 50 Liso
                        $tube_article_id = 43;
                        $tube_unit_id = 2;
                        $adapter_article_id = 46;
                        $adapter_unit_id = 3;
                        $total_width = $tubeDefault->total_width;
                    break;
                    case 5: // 63 mm
                        $tube_article_id = 44;
                        $tube_unit_id = 3;
                        $adapter_article_id = 47;
                        $adapter_unit_id = 3;
                        $total_width = null;
                    break;
                    case 6: // 63 mm
                        $tube_article_id = 45;
                        $tube_unit_id = 3;
                        $adapter_article_id = 48;
                        $adapter_unit_id = 3;
                        $total_width = null;
                    break;
                }
                // agregamos tubo
                $extraAdd[] = [
                    'quotation_id'         => $request->quotation_id,
                    'item_id'              => $item_id,
                    'article_id'           => $tube_article_id,
                    'quotation_product_id' => 4,
                    'quantity'             => 1,
                    'width'                => $total_width,
                    'height'               => null,
                    'unit_id'              => $tube_unit_id,
                    'relation_id'          => $request->relation_id,
                    'relation_accesories'  => $relation_accesories,
                ];
                $item_save[] = $item_id;
                $item_id++;
                // agregamos el reductor
                $extraAdd[] = [
                    'quotation_id'         => $request->quotation_id,
                    'item_id'              => $item_id,
                    'article_id'           => $adapter_article_id,
                    'quotation_product_id' => 4,
                    'quantity'             => $tubeDefault->divisions,
                    'width'                => null,
                    'height'               => null,
                    'unit_id'              => $adapter_unit_id,
                    'relation_id'          => $request->relation_id,
                    'relation_accesories'  => $relation_accesories,
                ];
                $item_save[] = $item_id;
                $item_id++;
                DQuotation::insert($extraAdd);
                // buscamos la info
                $articlesAddQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_quotations.corbatin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_quotations.fijo_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE c_articles.price END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.relation_heat_seal','d_quotations.relation_bracket_dn','d_quotations.relation_control','d_quotations.channel','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','d_quotations.default_tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','c_mechanisms.mechanism','d_quotations.relation_lambrequin','d_quotations.lambrequin_id','d_quotations.fijo_id','d_quotations.corbatin_id','d_quotations.fijo_id','d_quotations.is_velcro','d_quotations.default_mechanism_id','d_quotations.relation_perfil_priv','d_quotations.relation_tensor','d_quotations.if_chain_height')
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
                ->leftJoin('c_mechanisms','c_mechanisms.id','d_quotations.mechanism_id')
                ->where('d_quotations.quotation_id', $request->quotation_id)
                ->whereIn('d_quotations.item_id', $item_save)
                ->get()
                ->toArray();
            } else {
                // removemos lo que no queremos
                $removeQuotation = DQuotation::select('d_quotations.id')
                ->join('c_articles','c_articles.id','d_quotations.article_id')
                ->where('d_quotations.quotation_id',$request->quotation_id)
                ->where('d_quotations.relation_id',$request->relation_id)
                ->where('d_quotations.quotation_product_id',4)
                ->whereNotIn('c_articles.model_id', [6,50,25,46,47,48,45,49,60,55]) // fascia y tapas - lambrequin
                ->get();
                DQuotation::where('relation_id',$request->relation_id)->where('quotation_id',$request->quotation_id)->whereIn('id',$removeQuotation)->delete();
                // reorganizamos item_id
                $DQuotation = DQuotation::select('d_quotations.id','d_quotations.item_id')
                ->where('quotation_id',$request->quotation_id)
                ->get();
                if(!empty($DQuotation->toArray())) {
                    $item_id = 1;
                    // Actualizamos item
                    foreach ($DQuotation->toArray() as $key => $items) {
                        $updateItems[] = [
                            'id'      => $items['id'],
                            'item_id' => $item_id,
                        ];
                        DQuotation::where('id',$items['id'])
                        ->update([ 'item_id' => $item_id ]);
                        $item_id++;
                    }
                }
            }

            // verificamos que el registro no tenga algun accesorio relacionado
            $relationAcc = DQuotation::where('relation_id',$request->relation_id)
            ->where('quotation_id',$request->quotation_id)
            ->where('relation_accesories','>',0)
            ->first();
            if(is_null($relationAcc)) {
                DQuotation::where('d_quotations.relation_id',$request->relation_id)
                ->join('c_articles','c_articles.id','d_quotations.article_id')
                ->where('d_quotations.quotation_id',$request->quotation_id)
                ->whereNotIn('c_articles.model_id', [6,50,25,46,47,48,45,49,60,55])
                ->update([ 'd_quotations.tube_id' => $request->tube_id, 'd_quotations.relation_accesories' => $relation_accesories ]);
            } else {
                DQuotation::where('d_quotations.relation_id',$request->relation_id)
                ->join('c_articles','c_articles.id','d_quotations.article_id')
                ->where('d_quotations.quotation_id',$request->quotation_id)
                ->whereNotIn('c_articles.model_id', [6,50,25,46,47,48,45,49,60,55])
                ->update([ 'd_quotations.tube_id' => $request->tube_id, 'd_quotations.relation_accesories' => $relationAcc->relation_accesories ]);
            }

            $tube = CTube::where('id',$request->tube_id)->first();
            return response()->json([
                'success'              => true ,
                'tube'                 => $tube,
                'relation_id'          => $request->relation_id,
                'quotation_id'         => $request->quotation_id,
                'isDefaultTube'        => $isDefaultTube,
                'articlesAddQuotation' => $articlesAddQuotation,
                'removeQuotation'      => $removeQuotation,
                'updateItems'          => $updateItems,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }

    }

    public function saveMechanismChange(Request $request)
    {
        // try {
            DQuotation::where('relation_id',$request->relation_id)
            ->where('quotation_id',$request->quotation_id)
            ->whereIn('quotation_product_id',[1,2])
            ->update([ 'mechanism_id' => $request->mechanism_id ]);

            // log
            $BTestLog = new BTestLog;
            $BTestLog->user_id = $request->user_id;
            $BTestLog->log = 'Actualización de mecanismo.';
            $BTestLog->identifier_type = 'quotation_id';
            $BTestLog->identifier_number = $request->quotation_id;
            $BTestLog->identifier_text = $request->quotation_id;
            $BTestLog->description = 'Se cambio el por un id '.$request->mechanism_id ;
            $BTestLog->save();

            $mechanism = CMechanism::select('id','mechanism')->where('id',$request->mechanism_id)->first();
            return response()->json([
                'success'   => true ,
                'mechanism' => $mechanism,
                'relation_id'          => $request->relation_id,
                'quotation_id'         => $request->quotation_id,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    private function individualQuotation($quotation_id) {
        $EQuotation =  EQuotation::select('e_quotations.id','e_quotations.user_id','c_erp_info_users.short_name as agent_name','c_erp_info_users.user_img as agent_img','c_erp_info_users.color as agent_color','e_quotations.client_id',DB::raw("CASE WHEN e_quotations.is_for_lead = 1 THEN CONCAT('L-',e_leads.company) ELSE c_users.full_name END AS client_name"),'c_users.user_email as client_email','c_users.discount as client_discount','c_users.user_img as client_img','e_quotations.status_id','c_status_quotations.status','e_quotations.proyect_name','e_quotations.payment_method_id','c_payment_methods.payment_method','e_quotations.payment_option_id','c_payment_options.payment_option','e_quotations.account_number','e_quotations.delivery_type_id','c_delivery_types.delivery','e_quotations.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_quotations.created_at')
        ->join('c_users','c_users.id','e_quotations.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_quotations','c_status_quotations.id','e_quotations.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_quotations.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_quotations.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_quotations.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_quotations.client_address_id')
        ->leftJoin('e_leads','e_leads.id','e_quotations.lead_id')
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
        ->where('quotation_id',$quotation_id)
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

    private function getDiscountData() {
        $discountData = DQuotationDiscount::select('discount_type_id','general_id','article_id','model_id','discount','date_start','date_end')
        ->where('is_active',1)
        ->get();
        return $discountData;
    }
}
