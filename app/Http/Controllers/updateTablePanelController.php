<?php

namespace App\Http\Controllers;

use App\Models\CChain;
use App\Models\CCounterweightBar;
use App\Models\CMechanismSide;
use App\Models\DQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\classes\SetMechanism;
use App\Models\BTestLog;
use App\Models\CMechanism;
use App\Models\CTestLog;

class updateTablePanelController extends Controller
{

    public function updateTablePanel(Request $request) {
        // try {
            // RELATION
            $detailQuo = DQuotation::select('relation_id','quotation_id','relation_accesories','relation_cassette','mechanism_id','height_chain','height','quotation_product_id','operation_id')->where('id',$request->id)->first();
            $name_row = '';
            switch ($request->opt) {
                case 1: // Quantity
                    DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                    ->where('relation_id',$detailQuo['relation_id'])
                    ->update(['quantity' => $request->data,]);
                    $name_row = 'quantity';
                    return response()->json([
                        'success'       => true,
                        'dataSend'      =>  $request->data,
                        'relation_id'   => $detailQuo['relation_id'],
                        'name_row'      => $name_row,
                        'type_identify' => 'relation_id',
                    ], 200);
                break;
                case 2: // chain
                    $removeQuotation = [];
                    $updateItems = [];
                    $articlesAddQuotation = [];
                    // si es cadema metalica agregamos un item extra, si es cadena plastica borramos la cadena metalica asignada
                    switch ($request->data) {
                        case 1: // Plastica                            // removemos lo que no queremos
                            $removeQuotation = DQuotation::select('d_quotations.id')
                            ->where('quotation_id',$detailQuo['quotation_id'])
                            ->where('relation_accesories',$detailQuo['relation_accesories'])
                            ->where('article_id',70)
                            ->get();
                            // Eliminamos si tiene una cadena metalica asignada
                            DQuotation::where('quotation_id',$detailQuo['quotation_id'])->where('relation_accesories',$detailQuo['relation_accesories'])->where('article_id',70)->delete();
                            // reorganizamos item_id
                            $DQuotation = DQuotation::select('d_quotations.id','d_quotations.item_id')
                            ->where('quotation_id',$detailQuo['quotation_id'])
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
                        break;
                        case 2: // Metalica
                            $item_id = 1;
                            $relation_accesories = 1;
                            // buscaos el maximo del item para su consecutivo
                            $relationAll = null;
                            $relationAll = DQuotation::select(DB::raw('MAX(item_id) as max_item_id'))->where('quotation_id',$detailQuo['quotation_id'])->first();
                            if(!is_null($relationAll['max_item_id'])) { $item_id = $relationAll['max_item_id'] + 1; }
                            if( (INT)$detailQuo['relation_accesories'] === 0 ) {
                                // maximo realacion accesorio
                                $relationAll = null;
                                $relationAll = DQuotation::select(DB::raw('MAX(relation_accesories) as max_relation_accesories'))->where('quotation_id',$detailQuo['quotation_id'])->first();
                                if(!is_null($relationAll['max_relation_accesories'])) {
                                    $relation_accesories = $relationAll['max_relation_accesories'] + 1;
                                }
                                // Actualizamos el detail quotation para asignarle el nuevo relation accesories
                                DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                                ->where('id',$request->id)
                                ->update(['relation_accesories' => $relation_accesories,]);

                            } else {
                                $relation_accesories = $detailQuo['relation_accesories'];
                            }
                            // buscamos el numero de persianas por relacion
                            $detailQuoItems = DQuotation::select('relation_id','quotation_id','height_chain','quantity')
                            ->where('quotation_id',$detailQuo['quotation_id'])
                            ->where('relation_id',$detailQuo['relation_id'])
                            ->whereIn('quotation_product_id',[1,2])
                            ->get();
                            foreach ($detailQuoItems as $od) {
                                $dataRecord[] = [
                                    'quotation_id'         => $od['quotation_id'],
                                    'item_id'              => $item_id,
                                    'article_id'           => 70,
                                    'quotation_product_id' => 4,
                                    'operation_id'         => null,
                                    'quantity'             => $od['quantity'],
                                    'width'                => $od['height_chain'],
                                    'height'               => null,
                                    'fall'                 => null,
                                    'counterweight_bar_id' => null,
                                    'chain_id'             => null,
                                    'height_chain'         => null,
                                    'mechanism_id'         => null,
                                    'side_id'              => null,
                                    'mechanism_side_id'    => null,
                                    'unit_id'              => 2,
                                    'component_color_id'   => null,
                                    'commit'               => null,
                                    'commit_client'        => null,
                                    'awning_type_id'       => null, // tipo de toldo
                                    'area_description'     => null,
                                    'relation_id'          => $od['relation_id'],
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
                                $item_id++;
                            }
                            DQuotation::insert($dataRecord);
                            // buscamos la info
                            $articlesAddQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE c_articles.article END AS article , CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE c_articles.price END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','d_quotations.default_tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','d_quotations.relation_lambrequin','d_quotations.lambrequin_id')
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
                            ->where('quotation_id',$detailQuo['quotation_id'])
                            ->where('relation_accesories',$detailQuo['relation_accesories'])
                            ->where('article_id',70)
                            ->get()
                            ->toArray();

                        break;
                    }
                    DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                    ->where('relation_id',$detailQuo['relation_id'])
                    ->update(['chain_id' => $request->data,]);
                    $name_row = 'chain';
                    $chain = CChain::select('id','chain')->where('id',$request->data)->first();
                    return response()->json([
                        'success'              => true,
                        'chain_id'             =>  $chain->id,
                        'chain'                =>  $chain->chain,
                        'relation_id'          => $detailQuo['relation_id'],
                        'name_row'             => $name_row,
                        'articlesAddQuotation' => $articlesAddQuotation,
                        'removeQuotation'      => $removeQuotation,
                        'updateItems'          => $updateItems,
                        'type_identify'        => 'relation_id',
                    ], 200);
                break;
                case 3: // Fall
                    DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                    ->where('relation_id',$detailQuo['relation_id'])
                    ->update(['fall' => $request->data,]);
                    $name_row = 'fall';
                    return response()->json([
                        'success'       => true,
                        'fall'          =>  $request->data,
                        'relation_id'   => $detailQuo['relation_id'],
                        'name_row'      => $name_row,
                        'type_identify' => 'relation_id',
                    ], 200);
                break;
                case 4: // Counterweight
                    $removeQuotation = [];
                    $updateItems = [];
                    $articlesAddQuotation = [];
                    $otherArticle = false;
                    /*
                    // Si es base ovalada se agrega
                    if( (INT)$request->data === 2 OR $request->data === 4 ) {
                        // si ya contamos con un accesorio de alguno de los dos contrapesos eliminamos
                        $removeQuotation = DQuotation::select('d_quotations.id')
                        ->where('quotation_id',$detailQuo['quotation_id'])
                        ->where('relation_accesories',$detailQuo['relation_accesories'])
                        ->whereIn('article_id',[255,268])
                        ->get();
                        if(COUNT($removeQuotation) > 0) {
                            $otherArticle = true;
                            // Eliminamos si tiene contrapeoss asignados
                            DQuotation::where('quotation_id',$detailQuo['quotation_id'])->where('relation_accesories',$detailQuo['relation_accesories'])->whereIn('article_id',[255,268])->delete();
                            // reorganizamos item_id
                            $DQuotation = DQuotation::select('d_quotations.id','d_quotations.item_id')
                            ->where('quotation_id',$detailQuo['quotation_id'])
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
                        // iniciamos el nuevo i
                        $item_id = 1;
                        $relation_accesories = 1;
                        // buscaos el maximo del item para su consecutivo
                        $relationAll = null;
                        $relationAll = DQuotation::select(DB::raw('MAX(item_id) as max_item_id'))->where('quotation_id',$detailQuo['quotation_id'])->first();
                        if(!is_null($relationAll['max_item_id'])) { $item_id = $relationAll['max_item_id'] + 1; }

                        if( (INT)$detailQuo['relation_accesories'] === 0 ) {
                            // maximo realacion accesorio
                            $relationAll = null;
                            $relationAll = DQuotation::select(DB::raw('MAX(relation_accesories) as max_relation_accesories'))->where('quotation_id',$detailQuo['quotation_id'])->first();
                            if(!is_null($relationAll['max_relation_accesories'])) {
                                $relation_accesories = $relationAll['max_relation_accesories'] + 1;
                            }
                            // Actualizamos el detail quotation para asignarle el nuevo relation accesories
                            DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                            ->where('id',$request->id)
                            ->update([
                                'relation_accesories' => $relation_accesories,
                            ]);

                        } else {
                            $relation_accesories = $detailQuo['relation_accesories'];
                        }
                        // buscamos el numero de persianas por relacion
                        $detailQuoItems = DQuotation::select('relation_id','quotation_id','width','quantity')
                        ->where('quotation_id',$detailQuo['quotation_id'])
                        ->where('relation_id',$detailQuo['relation_id'])
                        ->whereIn('quotation_product_id',[1,2])
                        ->get();
                        $counterweightBarSelect = 0;
                        switch ((INT)$request->data) {
                            case 2:
                                $counterweightBarSelect = 255;
                            break;
                            case 4:
                                $counterweightBarSelect = 268;
                            break;
                        }
                        foreach ($detailQuoItems as $od) {
                            // agregamos la base ovalada
                            $dataRecord[] = [
                                'quotation_id'         => $od['quotation_id'],
                                'item_id'              => $item_id,
                                'article_id'           => $counterweightBarSelect,
                                'quotation_product_id' => 4,
                                'operation_id'         => null,
                                'quantity'             => $od['quantity'],
                                'width'                => $od['width'],
                                'height'               => null,
                                'fall'                 => null,
                                'counterweight_bar_id' => null,
                                'chain_id'             => null,
                                'height_chain'         => null,
                                'mechanism_id'         => null,
                                'side_id'              => null,
                                'mechanism_side_id'    => null,
                                'unit_id'              => 2,
                                'component_color_id'   => null,
                                'commit'               => null,
                                'commit_client'        => null,
                                'awning_type_id'       => null, // tipo de toldo
                                'area_description'     => null,
                                'relation_id'          => $od['relation_id'],
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
                            $item_id++;
                        }
                        // insertamos regist
                        DQuotation::insert($dataRecord);
                        // buscamos la info
                        $articlesAddQuotation = DQuotation::select('d_quotations.id','d_quotations.quotation_id','d_quotations.item_id',DB::raw('null as color_item'),'d_quotations.article_id',DB::raw('CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE c_articles.article END AS article , CASE WHEN d_quotations.lambrequin_id IS NOT NULL AND d_quotations.quotation_product_id = 4 THEN la.lambrequin_price ELSE c_articles.price END AS price'),'c_articles.model_id',DB::raw('0 as article_discount, 0 as request_discount'),'d_quotations.quantity','d_quotations.width','d_quotations.height','d_quotations.quotation_product_id','c_products.product','d_quotations.operation_id','c_operations.operation','d_quotations.fall','d_quotations.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_quotations.chain_id','c_chains.chain','d_quotations.height_chain','d_quotations.side_id','d_quotations.mechanism_side_id','c_mechanism_sides.mechanism_side','d_quotations.unit_id','c_units.unit','d_quotations.component_color_id','c_colors.color as color_component','d_quotations.commit','d_quotations.commit_client','d_quotations.awning_type_id','d_quotations.area_description','d_quotations.relation_id','d_quotations.relation_bracket','d_quotations.is_inverted','d_quotations.is_heat_seal','d_quotations.relation_cassette','d_quotations.cassette_id','d_quotations.relation_motor','d_quotations.motor_id','d_quotations.relation_accesories','d_quotations.is_tie_stripe',DB::raw('0 as is_menu'),'d_quotations.tube_id','d_quotations.default_tube_id','c_tubes.tube','d_quotations.divisions','d_quotations.mechanism_id','d_quotations.relation_lambrequin','d_quotations.lambrequin_id')
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
                        ->where('quotation_id',$detailQuo['quotation_id'])
                        ->where('relation_accesories',$detailQuo['relation_accesories'])
                        ->whereIn('article_id',[255,268])
                        ->get()
                        ->toArray();
                    }  else { // si econtramos un adiccional de contrapeso lo eliminamos
                        $removeQuotation = DQuotation::select('d_quotations.id')
                        ->where('quotation_id',$detailQuo['quotation_id'])
                        ->where('relation_accesories',$detailQuo['relation_accesories'])
                        ->whereIn('article_id',[255,268])
                        ->get();
                        // Eliminamos si tiene una cadena metalica asignada
                        DQuotation::where('quotation_id',$detailQuo['quotation_id'])->where('relation_accesories',$detailQuo['relation_accesories'])->whereIn('article_id',[255,268])->delete();
                        // reorganizamos item_id
                        $DQuotation = DQuotation::select('d_quotations.id','d_quotations.item_id')
                        ->where('quotation_id',$detailQuo['quotation_id'])
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
                    } */
                    // actualizamos el contrapeso
                    DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                    ->where('relation_id',$detailQuo['relation_id'])
                    ->update(['counterweight_bar_id' => $request->data,]);
                    $name_row = 'counterweight_bar';
                    $counterweight = CCounterweightBar::select('id','counterweight_bar')->where('id',$request->data)->first();
                    return response()->json([
                        'success'              => true,
                        'counterweight_bar_id' =>  $counterweight->id,
                        'counterweight_bar'    =>  $counterweight->counterweight_bar,
                        'relation_id'          => $detailQuo['relation_id'],
                        'name_row'             => $name_row,
                        'articlesAddQuotation' => $articlesAddQuotation,
                        'removeQuotation'      => $removeQuotation,
                        'updateItems'          => $updateItems,
                        'otherArticle'         => $otherArticle,
                        'type_identify'        => 'relation_id',
                    ], 200);
                break;
                case 5: // Heigth
                    DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                    ->where('id',$request->id)
                    ->update(['height' => $request->data,]);
                    $name_row = 'height';
                    //  verificamos la nueva medida y si requiere cambio ode componente
                    $detailQuoM = DQuotation::select('width',DB::raw('MAX(height) AS height'),'quotation_product_id','operation_id','divisions',DB::raw('CASE WHEN relation_cassette > 0 THEN 1 ELSE 0 END AS cassette') )
                    ->where('quotation_id',$detailQuo['quotation_id'])
                    ->where('relation_id',$detailQuo['relation_id'])
                    ->whereIn('quotation_product_id',[1,2])
                    ->groupBy('relation_id')->first();
                    $SetMechanism = new SetMechanism(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
                    $mechanismID =  $SetMechanism->mechanism($detailQuoM['width'],$detailQuoM['height'],$detailQuoM['quotation_product_id'],$detailQuoM['operation_id'],$detailQuoM['divisions'],$detailQuoM['cassette']) ;
                    DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                    ->where('relation_id',$detailQuo['relation_id'])
                    ->whereIn('quotation_product_id',[1,2])
                    ->update([ 'mechanism_id' => $mechanismID ]);
                    $mechanism =  CMechanism::where('id',$mechanismID)->first();
                    // log
                    $BTestLog = new BTestLog;
                    $BTestLog->user_id = $request->user_id;
                    $BTestLog->log = 'Actualización de alto.';
                    $BTestLog->identifier_type = 'detail_quotation_id';
                    $BTestLog->identifier_number = $request->id;
                    $BTestLog->identifier_text = $request->id;
                    $BTestLog->description = 'Se cambio alto y mecanismo con id '.$mechanismID ;
                    $BTestLog->save();

                    return response()->json([
                        'success'       => true,
                        'height'        =>  $request->data,
                        'id'            => $request->id,
                        'name_row'      => $name_row,
                        'type_identify' => 'id',
                        'relation_id'   => $detailQuo['relation_id'],
                        'mechanism_id'  => $mechanismID,
                        'mechanism'     => (INT)$detailQuo['operation_id'] === 1 ? $mechanism->mechanism : '',
                    ], 200);
                break;
                case 6: // Width
                    DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                    ->where('id',$request->id)
                    ->whereNotNull('width')
                    ->update([
                        'width' => $request->data['width'],
                    ]);
                    // Inverteed
                    DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                    ->where('relation_id',$detailQuo['relation_id'])
                    ->whereIn('quotation_product_id',[1,2])
                    ->update([
                        'is_inverted' => $request->data['is_inverted']
                    ]);
                    $name_row = 'width';
                    $mechanism =  '';
                    $mechanismID =  '';
                    //  verificamos la nueva medida y si requiere cambio de componente
                    if( (INT)$detailQuo['quotation_product_id'] ===  1 OR (INT)$detailQuo['quotation_product_id'] ===  2 ) {
                        $detailQuoM = DQuotation::select('width',DB::raw('MAX(height) AS height'),'quotation_product_id','operation_id','divisions',DB::raw('CASE WHEN relation_cassette > 0 THEN 1 ELSE 0 END AS cassette') )
                        ->where('quotation_id',$detailQuo['quotation_id'])
                        ->where('relation_id',$detailQuo['relation_id'])
                        ->whereIn('quotation_product_id',[1,2])
                        ->groupBy('relation_id')->first();
                        $SetMechanism = new SetMechanism(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
                        $mechanismID =  $SetMechanism->mechanism($detailQuoM['width'],$detailQuoM['height'],$detailQuoM['quotation_product_id'],$detailQuoM['operation_id'],$detailQuoM['divisions'],$detailQuoM['cassette']) ;
                        DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                        ->where('relation_id',$detailQuo['relation_id'])
                        ->whereIn('quotation_product_id',[1,2])
                        ->update([ 'mechanism_id' => $mechanismID ]);
                        // log
                        $BTestLog = new BTestLog;
                        $BTestLog->user_id = $request->user_id;
                        $BTestLog->log = 'Actualización de ancho.';
                        $BTestLog->identifier_type = 'detail_quotation_id';
                        $BTestLog->identifier_number = $request->id;
                        $BTestLog->identifier_text = $request->id;
                        $BTestLog->description = 'Se cambio ancho y mecanismo con id '.$mechanismID ;
                        $BTestLog->save();
                        // si tiene un perfil relacionado lo cambiamos  el ancho
                        DQuotation::join('c_articles','c_articles.id','d_quotations.article_id')
                        ->where('d_quotations.quotation_id',$detailQuo['quotation_id'])
                        ->where('d_quotations.relation_cassette',$detailQuo['relation_cassette'])
                        ->where('c_articles.model_id',6)
                        ->update(['d_quotations.width' => $request->data['width'],]);
                        // buscamos el nombre del mecanismo

                        $mechanism =  CMechanism::where('id',$mechanismID)->first();
                    }

                    return response()->json([
                        'success'       => true,
                        'width'         => $request->data['width'],
                        'id'            => $request->id,
                        'relation_id'   => $detailQuo['relation_id'],
                        'is_inverted'   => $request->data['is_inverted'],
                        'name_row'      => $name_row,
                        'type_identify' => 'relation_id',
                        'mechanism_id'  => $mechanismID,
                        'mechanism'     => (INT)$detailQuo['operation_id'] === 1 ? $mechanism->mechanism : '',
                    ], 200);
                break;
                case 7: // Mechanism side
                    DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                    ->where('id',$request->id)
                    ->update([
                        'mechanism_side_id' => $request->data,
                    ]);
                    $name_row = 'mechanism_side';
                    $mechanismSide = CMechanismSide::select('id','mechanism_side')->where('id',$request->data)->first();
                    return response()->json([
                        'success'           => true,
                        'mechanism_side_id' => $request->data,
                        'mechanism_side'    => $mechanismSide->mechanism_side,
                        'id'                => $request->id,
                        'name_row'          => $name_row,
                        'type_identify'     => 'id',
                    ], 200);
                break;
                case 8: // height_chain
                    DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                    ->where('id',$request->id)
                    ->update(['height_chain' => $request->data,]);
                    // si la cadena es menor al default y tenemos un mecanismo xroll lo cambiamos a SL16
                    if((INT)$detailQuo['mechanism_id'] === 5 AND (DOUBLE)($detailQuo['height'] * 2 ) > (DOUBLE)$request->data AND (INT)$detailQuo['quotation_product_id'] === 1 AND (INT)$detailQuo['operation_id'] === 1 ) {
                        DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                        ->where('id',$request->id)
                        ->update(['mechanism_id' => 3]);
                        // log
                        $BTestLog = new BTestLog;
                        $BTestLog->user_id = $request->user_id;
                        $BTestLog->log = 'Actualización de ancho.';
                        $BTestLog->identifier_type = 'detail_quotation_id';
                        $BTestLog->identifier_number = $request->id;
                        $BTestLog->identifier_text = $request->id;
                        $BTestLog->description = 'Se cambio Alto cadena y mecanismo con id 3' ;
                        $BTestLog->save();
                    }
                    // si tenemos un mecanismo sl16 pero un ancho menor para un xroll y la cadena es mayor o igual,  el mecanismo se cambia a xroll
                    if((INT)$detailQuo['mechanism_id'] === 3 AND (DOUBLE)$detailQuo['height'] < 2.2 AND (DOUBLE)$request->data >= (DOUBLE)($detailQuo['height'] * 2 ) AND (INT)$detailQuo['quotation_product_id'] === 1 AND (INT)$detailQuo['operation_id'] === 1 ) {
                        DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                        ->where('id',$request->id)
                        ->update(['mechanism_id' => 5]);
                        // log
                        $BTestLog = new BTestLog;
                        $BTestLog->user_id = $request->user_id;
                        $BTestLog->log = 'Actualización de ancho.';
                        $BTestLog->identifier_type = 'detail_quotation_id';
                        $BTestLog->identifier_number = $request->id;
                        $BTestLog->identifier_text = $request->id;
                        $BTestLog->description = 'Se cambio Alto cadena y mecanismo con id 5' ;
                        $BTestLog->save();
                    }
                    // si  la cadeena es metalica, cambiamos el ancho
                    DQuotation::where('quotation_id',$detailQuo['quotation_id'])
                    ->where('relation_accesories',$detailQuo['relation_accesories'])
                    ->where('article_id',70)
                    ->update(['height' => $request->data,]);


                    $name_row = 'height_chain';
                    return response()->json([
                        'success'              => true,
                        'height_chain'         => $request->data,
                        'id'                   => $request->id,
                        'name_row'             => $name_row,
                        'type_identify'        => 'id',
                        'hc'                   => ($detailQuo['height'] * 2),
                        'quotation_product_id' => (INT)$detailQuo['quotation_product_id'],
                        'operation_id'         => (INT)$detailQuo['operation_id'],
                        'relation_accesories'  => (INT)$detailQuo['relation_accesories'],
                    ], 200);
                break;
            }
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }
}
