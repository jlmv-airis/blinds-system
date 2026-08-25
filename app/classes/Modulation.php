<?php
namespace App\classes;

use App\Models\CArticle;
use App\Models\CColor;
use App\Models\CCounterweightBar;
use App\Models\CTube;
use Exception;
use Illuminate\Support\Facades\DB;

class Modulation {
    public function modulationAlls($detailOrders,$ordersID,$guaranteeIDs) {
        // var_dump($detailOrders);
        // dd();
        $modulations = [];
        $modulations['tubes'] = $this->setModulationTubes($detailOrders);
        $modulations['perfiles'] = $this->setModulationPerfiles($ordersID,$guaranteeIDs);
        $modulations['counterweight'] = $this->setModulationCounterweight($detailOrders);
        $modulations['twistbar'] = $this->setModulationTwistbar($detailOrders);
        return $modulations;
    }

    private function setModulationTubes($detailOrders) {
        $filterDetailOrder = [];
        $modulation = [];
        foreach ($detailOrders as $detailOrder) {
            if($detailOrder['product_id'] == 2 OR $detailOrder['product_id'] == 1 ) {
                if( (INT)$detailOrder['type_reg'] === 1 ) {
                    $filterDetailOrder[] = $detailOrder;
                } else {
                    if( (INT)$detailOrder['capture_id'] === 1 ) {
                        $filterDetailOrder[] = $detailOrder;
                    } else {
                        if( (INT)$detailOrder['tube_id'] !== (INT)$detailOrder['ch_tube_id'] OR (INT)$detailOrder['damage_tube'] === 1 ){
                            $filterDetailOrder[] = $detailOrder;
                        }
                    }
                }
            }
        }
        // obtenemos los tubos
        $tubes = CTube::select()->get();
        foreach ($tubes as $tube) {
            $detailOrderPerTube = array_filter($filterDetailOrder, function ($item) use ($tube) { return (INT)$item['tube_id'] === (INT)$tube['id']; });
            $detailOrderPerTube = array_values($detailOrderPerTube);
            if (count($detailOrderPerTube) > 0) {
                $shuffle = $pp = $ps = $increment = 0;
                $shuffleTarget = 10; // minimo 2 shufles por items
                $max = 5.75;
                $min = $minInit = 5.70;
                $objModulation = [];
                $detailOrderPerTube = $this->setWidthdiscount($detailOrderPerTube,'TUBE');
                $modulation[] = [
                    'tube_id' => $tube['id'],
                    'items' => $this->modulationML($detailOrderPerTube, $min, $max, $shuffle, $shuffleTarget, $pp, $ps, $increment, $objModulation, $minInit),
                ];
            }
        }
        return $modulation;
    }

    private function setModulationPerfiles($ordersID,$guaranteeIDs) {
        $ordersID = substr($ordersID,1);
        $guaranteeIDs = substr($guaranteeIDs,1);
        $detailOrders = [];
        // ORDERS
        $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(11,1,0,0,0,0.0,0.0,0.0,'','','','".$ordersID."','')"); // se agrupa por cassette y se hacen los descuentos
        $statementAcc->execute();
        do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
        foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $detailOrders[] = $value; }
        // GUARANTEE
        $statementGr = DB::getPdo()->prepare("CALL sp_modulation(11,2,0,0,0,0.0,0.0,0.0,'','','','".$guaranteeIDs."','')"); // se agrupa por cassette y se hacen los descuentos
        $statementGr->execute();
        do {  $resultsGr[] = $statementGr->fetchAll(\PDO::FETCH_OBJ); } while ($statementGr->nextRowSet());
        foreach (json_decode(json_encode($resultsGr[0]), true)  as $valueGr ) { $detailOrders[] = $valueGr; }


        $filterDetailOrder = [];
        $modulation = [];
        foreach ($detailOrders as $detailOrder) {
            if($detailOrder['product_id'] == 2 OR $detailOrder['relation_cassette'] > 0 ) {
                if( (INT)$detailOrder['type_reg'] === 1 ) {
                    $filterDetailOrder[] = $detailOrder;
                } else {
                    if( (INT)$detailOrder['capture_id'] === 1 ) {
                        $filterDetailOrder[] = $detailOrder;
                    } else {
                        if( (INT)$detailOrder['component_color_id'] !== (INT)$detailOrder['ch_component_color_id'] OR (INT)$detailOrder['damage_fascia'] === 1 ) {
                            $filterDetailOrder[] = $detailOrder;
                        }
                    }
                }
            }
        }
        // obtenemos los tubos
        $colors = CColor::get();
        foreach ($colors as $color) {
            $detailOrderPerColor = array_filter($filterDetailOrder, function ($item) use ($color) { return (INT)$item['component_color_id'] === (INT)$color['id']; });
            $detailOrderPerColor = array_values($detailOrderPerColor);
            if (count($detailOrderPerColor) > 0) {
                $shuffle = $pp = $ps = $increment = 0;
                $shuffleTarget = 10; // minimo 2 shufles por items
                $max = 5.75;
                $min = $minInit = 5.70;
                $objModulation = [];
                $modulation[] = [
                    'color_id' => $color['id'],
                    'items' => $this->modulationML($detailOrderPerColor, $min, $max, $shuffle, $shuffleTarget, $pp, $ps, $increment, $objModulation, $minInit),
                ];
            }
        }
        return $modulation;
    }

    private function setModulationCounterweight($detailOrders) {
        $filterDetailOrder = [];
        $modulation = [];
        foreach ($detailOrders as $detailOrder) {
            if( $detailOrder['product_id'] == 2 OR $detailOrder['product_id'] == 1  OR  $detailOrder['model_id'] == 25 ) {
                if( (INT)$detailOrder['type_reg'] === 1 ) {
                    $filterDetailOrder[] = $detailOrder;
                } else {
                    if( (INT)$detailOrder['capture_id'] === 1 ) {
                        $filterDetailOrder[] = $detailOrder;
                    } else {
                        if( (INT)$detailOrder['counterweight_bar_id'] !== (INT)$detailOrder['ch_counterweight_bar_id'] OR (INT)$detailOrder['damage_counterweight'] === 1 ){
                            $filterDetailOrder[] = $detailOrder;
                        }
                    }
                }
            }
        }
        // obtenemos los tubos
        $colors = CColor::get();
        $counterweights = CCounterweightBar::get();
        foreach ($counterweights as $key => $counterweight) {
            $detailOrderPerCounterweiht = array_filter($filterDetailOrder, function ($item) use ($counterweight) { return (INT)$item['counterweight_bar_id'] === (INT)$counterweight['id']; });
            $detailOrderPerCounterweiht = array_values($detailOrderPerCounterweiht);
            if (count($detailOrderPerCounterweiht) > 0) {
                $detailColors = [];
                foreach ($colors as $color) {
                    $detailOrderPerColor = array_filter($detailOrderPerCounterweiht, function ($item) use ($color) { return (INT)$item['component_color_id'] === (INT)$color['id']; });
                    $detailOrderPerColor = array_values($detailOrderPerColor);
                    if (count($detailOrderPerColor) > 0) {
                        $shuffle = $pp = $ps = $increment = 0;
                        $shuffleTarget = 10; // minimo 2 shufles por items
                        $max = 5.75;
                        $min = $minInit = 5.70;
                        $objModulation = [];
                        $detailOrderPerColor = $this->setWidthdiscount($detailOrderPerColor,'COUNTERWEIGHT');
                        $detailColors[] = [
                            'color_id' => $color['id'],
                            'items' => $this->modulationML($detailOrderPerColor, $min, $max, $shuffle, $shuffleTarget, $pp, $ps, $increment, $objModulation, $minInit),
                        ];
                    }
                }
                $modulation[] = [
                    'counterweight_bar_id' => $counterweight['id'],
                    'colors' =>  $detailColors,
                ];
            }
        }
        return $modulation;
    }

    private function setModulationTwistbar($detailOrders) {
        $filterDetailOrder = [];
        $modulation = [];
        foreach ($detailOrders as $detailOrder) {
            if($detailOrder['product_id'] == 2 ) {
                if( (INT)$detailOrder['type_reg'] === 1 ) {
                    $filterDetailOrder[] = $detailOrder;
                } else {
                    if( (INT)$detailOrder['capture_id'] === 1 ) {
                        $filterDetailOrder[] = $detailOrder;
                    } else {
                        if( (INT)$detailOrder['counterweight_bar_id'] !== (INT)$detailOrder['ch_counterweight_bar_id'] OR (INT)$detailOrder['damage_counterweight'] === 1 ){
                            $filterDetailOrder[] = $detailOrder;
                        }
                    }
                }
            }
        }
        // obtenemos los tubos
        $colors = CColor::get();
        $twistbars = CArticle::select('id','article','color_id','model_id')->whereIn('model_id',[14,26])->where('is_active',1)->get(); // obtenemos las barras de giro

        foreach ($twistbars as $key => $twistbar) {
            $detailOrderPerTwistbar = [];
            foreach ($filterDetailOrder as $keyDO => $detailOrder) {
                if( (INT)$detailOrder['counterweight_bar_id'] === 3) { // buscamos solo barra giro
                    if((INT)$twistbar['model_id'] === 14 ) {
                        $filterDetailOrder[$keyDO]['component_color_id'] = 1; // por el momento solo es blanca
                        $filterDetailOrder[$keyDO]['twistbar_id'] = 24; // por el momento solo es blanca
                        $filterDetailOrder[$keyDO]['twistbar'] = 'BARRA DE GIRO EJE WHITE';
                        $detailOrderPerTwistbar[] = $filterDetailOrder[$keyDO];
                    }
                }
                if( (INT)$detailOrder['counterweight_bar_id'] === 5) { // buscamos solo barra giro
                    if( (INT)$detailOrder['component_color_id'] === (INT)$twistbar['color_id'] AND (INT)$twistbar['model_id'] === 26 ) {
                        $filterDetailOrder[$keyDO]['twistbar_id'] = $twistbar['id'];
                        $filterDetailOrder[$keyDO]['twistbar'] = $twistbar['article'];
                        $detailOrderPerTwistbar[] = $filterDetailOrder[$keyDO];
                    }
                }
            }
            if (count($detailOrderPerTwistbar) > 0) {
                $detailColors = [];
                foreach ($colors as $color) {
                    $detailOrderPerColor = array_filter($detailOrderPerTwistbar, function ($item) use ($color) { return (INT)$item['component_color_id'] === (INT)$color['id']; });
                    $detailOrderPerColor = array_values($detailOrderPerColor);
                    if (count($detailOrderPerColor) > 0) {
                        $shuffle = $pp = $ps = $increment = 0;
                        $shuffleTarget = 10; // minimo 2 shufles por items
                        $max = 5.75;
                        $min = $minInit = 5.70;
                        $objModulation = [];
                        $detailOrderPerColor = $this->setWidthdiscount($detailOrderPerColor,'TWISTBAR');
                        $detailColors[] = [
                            'color_id' => $color['id'],
                            'items' => $this->modulationML($detailOrderPerColor, $min, $max, $shuffle, $shuffleTarget, $pp, $ps, $increment, $objModulation, $minInit),
                        ];
                    }
                }
                $modulation[] = [
                    'twistbar_id' => $twistbar['id'],
                    'colors' =>  $detailColors,
                ];
            }
        }
        return $modulation;
    }

    private function modulationML($detailOrders, $min, $max, $shuffle, $shuffleTarget, $pp, $ps, $increment, $objModulation, $minInit) {
        // try {
            $detailOrders = $this->shuffleItems($detailOrders);
            $index = [];
            $sum = 0;
            $ps = 0;
            $e = 0;
            $aux = [];
            $item = $detailOrders[$pp];
            $sum += $item['discount_width'];
            array_push($index, $pp);
            do {
                $sum_items = array_reduce($detailOrders, function ($carry, $item) {
                    return $carry += $item['discount_width'];
                });

                if ($sum >= $min && $sum <= $max) {
                    $increment++;
                    for ($i = 0; $i < count($index); $i++) {
                        array_push($aux, $detailOrders[$index[$i]]);
                        unset($detailOrders[$index[$i]]);
                    }
                    $item_result = array();
                    $item_result['set_id'] = $increment;
                    $item_result['moduled_items'] = $aux;
                    array_push($objModulation, $item_result);
                    $detailOrders = array_values($detailOrders);
                    $shuffle = $ps = $pp = 0;
                    $min = $minInit;
                    break;
                } else {
                    // echo $sum_items.' $sum_items <br>';
                    if ($sum_items >= $max) {
                        if ($pp != $ps) {
                            $item = $detailOrders[$ps];
                            $sum += $item['discount_width'];
                            $e++;
                            array_push($index, $ps);
                            if ($sum >= $min && $sum <= $max) {
                                $increment++;
                                for ($i = 0; $i < count($index); $i++) {
                                    array_push($aux, $detailOrders[$index[$i]]);
                                    unset($detailOrders[$index[$i]]);
                                }
                                $item_result = array();
                                $item_result['set_id'] = $increment;
                                $item_result['moduled_items'] = $aux;
                                array_push($objModulation, $item_result);
                                $detailOrders = array_values($detailOrders);
                                $shuffle = $ps = $pp = $pp = 0;
                                $min = $minInit;
                                break;
                            } else {
                                if ($sum <= $max) {
                                    $ps++;
                                } else {
                                    $sum -= $item['discount_width'];
                                    unset($index[$e]);
                                    $index = array_values($index);
                                    $ps++;
                                    $e--;
                                }
                            }
                        } else {
                            $ps++;
                        }
                        if ($ps == count($detailOrders)) {
                            $sum = $ps = 0;
                            $pp++;
                            $index = [];
                            if ($pp == count($detailOrders)) {
                                if ($shuffle < $shuffleTarget) {
                                    $ps = $pp = $e = 0;
                                    $index = [];
                                    $detailOrders = $this->shuffleItems($detailOrders);
                                    $shuffle++;
                                    break;
                                } else {
                                    $shuffle = $ps = $pp = $e = 0;
                                    $index = [];
                                    $min = $min - 0.05;
                                    break;
                                }
                            } else {
                                break;
                            }
                        }
                    } else {
                        $increment++;
                        $aux = $detailOrders;
                        $item_result = array();
                        $item_result['set_id'] = $increment;
                        $item_result['moduled_items'] = $aux;
                        array_push($objModulation, $item_result);
                        $detailOrders = [];
                        break;
                    }
                }
                if($min <= 0){
                    break;
                }
            } while ($ps < count($detailOrders));
            if (count($detailOrders) > 0 ) {
                return $this->modulationML($detailOrders, $min, $max, $shuffle, $shuffleTarget, $pp, $ps, $increment, $objModulation, $minInit);
            } else {
                return $objModulation;
            }

        // } catch (Exception $exception) {
        //     return response()->json([
        //         "ok" => false,
        //         "error" => $exception->getMessage(),
        //     ]);
        // }

    }

    private function shuffleItems($items)
    {
        $newItems = [];
        $indexes = range(0, count($items) - 1);
        shuffle($indexes);
        foreach ($indexes as $index) {
            array_push($newItems, $items[$index]);
        }
        $maxItem = array_reduce($items, function ($a, $b) {
            return $a ? ($a['discount_width'] > $b['discount_width'] ? $a : $b) : $b;
        });
        $firstItem = $newItems[0];
        $maxItemIndex = array_search($maxItem, $newItems);
        $newItems[0] = $newItems[$maxItemIndex];
        $newItems[$maxItemIndex] = $firstItem;
        return $newItems;
    }

    private function setWidthdiscount($detailOrders,$type)
    {
        switch ($type) {
            case 'TUBE':
                foreach ($detailOrders as $key => $dOrders) {
                    $detailOrders[$key]['discount_width'] = ROUND((DOUBLE)$dOrders['width'] - (DOUBLE)$dOrders['tube_discount'] ,3 );
                }
            break;
            case 'PERFIL':
                foreach ($detailOrders as $key => $dOrders) {
                    $detailOrders[$key]['discount_width'] = ROUND((DOUBLE)$dOrders['width'] - (DOUBLE)$dOrders['fascia_discount'] ,3 );
                }
            break;
            case 'COUNTERWEIGHT':
                foreach ($detailOrders as $key => $dOrders) {
                    $detailOrders[$key]['discount_width'] = ROUND((DOUBLE)$dOrders['width'] - (DOUBLE)$dOrders['counterweight_discount'] ,3 );
                }
            break;
            case 'TWISTBAR':
                foreach ($detailOrders as $key => $dOrders) {
                    $detailOrders[$key]['discount_width'] = ROUND((DOUBLE)$dOrders['width'] - (DOUBLE)$dOrders['turn_bar_discount'] ,3 );
                }
            break;
        }
        return $detailOrders;
    }
    /*
    private function setTubeID($orders) {

        /*
            11` // TUBO 38MM ligero
            10 // TUBO 38MM SHEER
            1 // TUBO 38ENROLLABLE
            2 // TUBO 1/12 REFORZADO
            3 // TUBO 1/2''
            5 // TUBO 45 SLIM GAP
            6 // TUBO 2-1/2"
            7 // TUBO 3"
        */
/*
        foreach ($orders as $key => $order) {
            foreach ($order as $key2 => $item) {
                $order[$key2]['tube_id'] = 0;
                switch ($item['product_id']) {
                    case 1: // Enrollable
                        switch ($item['operation_id']) {
                            case 1: // Manual
                                $order[$key2]['tube_id'] = 10;
                            break;
                            case 2: // Motorizada
                                if($item['mm_motor'] == 25 ) {
                                    $order[$key2]['tube_id'] = 10;
                                } else {
                                    $order[$key2]['tube_id'] = 3;
                                }
                            break;
                        }
                    break;
                    case 2: // Sheer Elegance
                        switch ($item['operation_id']) {
                            case 1: // Manual
                            break;
                            case 2: // Motorizada
                                if($item['width'] < 2.6 AND $item['height'] < 3.6 ) {
                                    if($item['mm_motor'] == 45) {
                                        if( $item['model_motor_id'] == 3 ) { //  aqui va el id de modelo de motor tradicional
                                            $order[$key2]['tube_id'] = 6;
                                        } else {
                                            $order[$key2]['tube_id'] = 5;
                                        }
                                    } else if($item['mm_motor'] == 25) {
                                        if( $item['width'] < 2.2 AND $item['height'] < 2.6 ) {
                                            $order[$key2]['tube_id'] = 1;
                                        } else if ( $item['width'] < 1.3 AND $item['height'] >= 2.6 AND $item['height'] < 2.8 ) {
                                            $order[$key2]['tube_id'] = 1;
                                        } else if ( $item['width'] >= 2.3 AND $item['width'] < 2.6 AND $item['height'] < 3.1 ) {
                                            $order[$key2]['tube_id'] = 2;
                                        } else if ( $item['width'] >= 2.2 AND $item['width'] < 2.3 AND $item['height'] < 3.6) {
                                            $order[$key2]['tube_id'] = 2;
                                        } else if ( $item['width'] >= 1.3 AND  $item['width'] < 2.2 AND $item['height'] >= 2.6 AND $item['height'] < 3.6 ) {
                                            $order[$key2]['tube_id'] = 2;
                                        } else if ( $item['width'] < 1.3 AND $item['height'] >= 2.8 AND $item['height'] < 3.6 ) {
                                            $order[$key2]['tube_id'] = 2;
                                        }
                                    } else {
                                        $order[$key2]['tube_id'] = 3;
                                    }
                                } else if( $item['width'] >= 2.6 AND $item['width'] < 3.3 AND $item['height'] < 2.6 ) {
                                    if( $item['model_motor_id'] == 3 ) { //  aqui va el id de modelo de motor tradicional
                                        $order[$key2]['tube_id'] = 6;
                                    } else {
                                        if( $item['mm_motor'] == 35 AND $item['divisions'] != 1) { // sacar el total de divisiones
                                            $order[$key2]['tube_id'] = 3;
                                        } else {
                                            $order[$key2]['tube_id'] = 5;
                                        }
                                    }
                                } else if( $item['width'] >= 2.6 AND $item['width'] < 3.1 AND $item['height'] >= 2.6  AND $item['height'] < 3.1 ) {
                                    if( $item['model_motor_id'] == 3 ) { //  aqui va el id de modelo de motor tradicional
                                        $order[$key2]['tube_id'] = 6;
                                    } else {
                                        if( $item['mm_motor'] == 35 AND $item['divisions'] != 1) { // sacar el total de divisiones
                                            $order[$key2]['tube_id'] = 3;
                                        } else {
                                            $order[$key2]['tube_id'] = 5;
                                        }
                                    }
                                } else if( $item['width'] >= 2.6 AND $item['width'] < 3.0 AND $item['height'] >= 3.1 AND $item['height'] < 3.6 ) {
                                    if( $item['model_motor_id'] == 3 ) { //  aqui va el id de modelo de motor tradicional
                                        $order[$key2]['tube_id'] = 6;
                                    } else {
                                        if( $item['mm_motor'] == 35 AND $item['divisions'] != 1) { // sacar el total de divisiones
                                            $order[$key2]['tube_id'] = 3;
                                        } else {
                                            $order[$key2]['tube_id'] = 5;
                                        }
                                    }
                                } else if( $item['width'] < 2.6 AND $item['height'] >= 3.6 AND $item['height'] < 4.1 ) {
                                    if( $item['model_motor_id'] == 3 ) { //  aqui va el id de modelo de motor tradicional
                                        $order[$key2]['tube_id'] = 6;
                                    } else {
                                        if( $item['mm_motor'] == 35 AND $item['divisions'] != 1) { // sacar el total de divisiones
                                            $order[$key2]['tube_id'] = 3;
                                        } else {
                                            $order[$key2]['tube_id'] = 5;
                                        }
                                    }
                                } else if( $item['width'] < 2.0 AND $item['height'] >= 4.1 AND $item['height'] <= 5.0 ) {
                                    if( $item['model_motor_id'] == 3 ) { //  aqui va el id de modelo de motor tradicional
                                        $order[$key2]['tube_id'] = 6;
                                    } else {
                                        if( $item['mm_motor'] == 35 AND $item['divisions'] != 1) { // sacar el total de divisiones
                                            $order[$key2]['tube_id'] = 3;
                                        } else {
                                            $order[$key2]['tube_id'] = 5;
                                        }
                                    }
                                }
                            break;
                        }
                    break;
                }
            }
        }
/*

        CASE WHEN producto ='Duo Line' THEN
                CASE WHEN operacion = 'Motorizada' THEN CASE WHEN mm.mm = 25 THEN 10 ELSE 3 END ELSE 10 END
            ELSE
                    CASE WHEN operacion = 'Motorizada' THEN
                                CASE WHEN ROUND(ancho,3) < 2.6 AND ROUND(alto,3) < 3.6 THEN
                                    CASE WHEN mm.mm = 45 THEN
                                        CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE 5 END
                                    WHEN mm.mm = 25 THEN
                                        CASE WHEN ROUND(ancho,3) < 2.2 AND ROUND(alto,3) < 2.6 THEN 1
                                            WHEN ROUND(ancho,3) < 1.3 AND ROUND(alto,3) >= 2.6 AND ROUND(alto,3) < 2.8 THEN 1
                                            WHEN ROUND(ancho,3) >= 2.3 AND ROUND(ancho,3) < 2.6 AND ROUND(alto,3) < 3.1 THEN 2
                                            WHEN ROUND(ancho,3) >= 2.2 AND ROUND(ancho,3) < 2.3 AND ROUND(alto,3) < 3.6 THEN 2
                                            WHEN ROUND(ancho,3) >= 1.3 AND ROUND(ancho,3) < 2.2 AND ROUND(alto,3) >= 2.6 AND ROUND(alto,3) < 3.6 THEN 2
                                            WHEN ROUND(ancho,3) < 1.3 AND ROUND(alto,3) >= 2.8 AND ROUND(alto,3) < 3.6 THEN 2
                                        END
                                    ELSE 3 END
                                WHEN ROUND(ancho,3) >= 2.6 AND ROUND(ancho,3) < 3.3 AND ROUND(alto,3) < 2.6 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6      ELSE CASE WHEN mm.mm = 35 AND no_lienzos != 1 THEN 3 ELSE 5 END END
                                WHEN ROUND(ancho,3) >= 2.6 AND ROUND(ancho,3) < 3.1 AND ROUND(alto,3) >= 2.6 AND ROUND(alto,3) < 3.1 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 35 AND no_lienzos != 1 THEN 3 ELSE 5 END END
                                WHEN ROUND(ancho,3) >= 2.6 AND ROUND(ancho,3) < 3.0 AND ROUND(alto,3) >= 3.1 AND ROUND(alto,3) < 3.6 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 35 AND no_lienzos != 1 THEN 3 ELSE 5 END END
                                WHEN ROUND(ancho,3) < 2.6 AND ROUND(alto,3) >= 3.6 AND ROUND(alto,3) < 4.1 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 35 AND no_lienzos != 1 THEN 3 ELSE 5 END END
                                WHEN ROUND(ancho,3) < 2.0 AND ROUND(alto,3) >= 4.1 AND ROUND(alto,3) <= 5.0 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 35 AND no_lienzos != 1 THEN 3 ELSE 5 END END





                                WHEN ROUND(ancho,3) >= 3.3 AND ROUND(ancho,3) <= 5.0 AND ROUND(alto,3) < 2.6 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 45 OR mm.mm = 35 THEN 5 ELSE 6 END END
                                WHEN ROUND(ancho,3) >= 3.1 AND ROUND(ancho,3) <= 5.0 AND ROUND(alto,3) >= 2.6 AND ROUND(alto,3) < 2.7 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 45 OR mm.mm = 35 THEN 5 ELSE 6 END END
                                WHEN ROUND(ancho,3) >= 3.1 AND ROUND(ancho,3) < 4.6 AND ROUND(alto,3) >= 2.7 AND ROUND(alto,3) < 3.0 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 45 OR mm.mm = 35 THEN 5 ELSE 6 END END
                                WHEN ROUND(ancho,3) >= 3.1 AND ROUND(ancho,3) < 4.1 AND ROUND(alto,3) >= 3.0 AND ROUND(alto,3) < 3.1 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 45 OR mm.mm = 35 THEN 5 ELSE 6 END END
                                WHEN ROUND(ancho,3) >= 3.0 AND ROUND(ancho,3) < 4.1 AND ROUND(alto,3) >= 3.1 AND ROUND(alto,3) < 3.6 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 45 OR mm.mm = 35 THEN 5 ELSE 6 END END
                                WHEN ROUND(ancho,3) >= 2.6 AND ROUND(ancho,3) < 3.6 AND ROUND(alto,3) >= 3.6 AND ROUND(alto,3) < 4.1 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 45 OR mm.mm = 35 THEN 5 ELSE 6 END END
                                WHEN ROUND(ancho,3) >= 2.0 AND ROUND(ancho,3) < 3.3 AND ROUND(alto,3) >= 4.1 AND ROUND(alto,3) < 4.6 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 45 OR mm.mm = 35 THEN 5 ELSE 6 END END
                                WHEN ROUND(ancho,3) >= 2.0 AND ROUND(ancho,3) < 3.0 AND ROUND(alto,3) >= 4.6 AND ROUND(alto,3) <= 5.0 THEN CASE WHEN mm.modelo = 'MOTOR' THEN 6 ELSE CASE WHEN mm.mm = 45 OR mm.mm = 35 THEN 5 ELSE 6 END END

                                WHEN ROUND(ancho,3) >= 4.6 AND ROUND(ancho,3) <= 5.0 AND ROUND(alto,3) >= 2.7 AND ROUND(alto,3) < 3.0 THEN 7
                                WHEN ROUND(ancho,3) >= 4.1 AND ROUND(ancho,3) <= 5.0 AND ROUND(alto,3) >= 3.0 AND ROUND(alto,3) < 3.6 THEN 7
                                WHEN ROUND(ancho,3) >= 3.6 AND ROUND(ancho,3) <= 5.0 AND ROUND(alto,3) >= 3.6 AND ROUND(alto,3) < 4.1 THEN 7
                                WHEN ROUND(ancho,3) >= 3.3 AND ROUND(ancho,3) < 4.5 AND ROUND(alto,3) >= 4.1 AND ROUND(alto,3) < 4.6 THEN 7
                                WHEN ROUND(ancho,3) >= 3.0 AND ROUND(ancho,3) < 3.3 AND ROUND(alto,3) >= 4.6 AND ROUND(alto,3) <= 5.0 THEN 7
                                WHEN ROUND(ancho,3) < 3.0 AND ROUND(alto,3) > 5.0 THEN 7
                            END
                ELSE
                    CASE
                        -- TUBO 38MM ligero
                        WHEN ROUND(ancho,3) < 1.3 AND ROUND(alto,3) < 1.6 THEN 11
                        WHEN ROUND(ancho,3) < 0.9 AND ROUND(alto,3) >= 1.6 AND ROUND(alto,3) < 1.7 THEN 11
                        -- TUBO 38ENROLLABLE
                        WHEN ROUND(ancho,3) >= 1.3 AND ROUND(ancho,3) < 2.2 AND ROUND(alto,3) < 2.6 THEN 1
                        WHEN ROUND(ancho,3) >= 0.9 AND ROUND(ancho,3) < 1.3 AND ROUND(alto,3) >= 1.6 AND ROUND(alto,3) < 2.8 THEN 1
                        WHEN ROUND(ancho,3) < 0.9 AND ROUND(alto,3) >= 1.7 AND ROUND(alto,3) < 2.8 THEN 1
                        -- TUBO 1/12 REFORZADO
                        WHEN ROUND(ancho,3) >= 2.3 AND ROUND(ancho,3) < 2.6 AND ROUND(alto,3) < 3.1 THEN 2
                        WHEN ROUND(ancho,3) >= 2.2 AND ROUND(ancho,3) < 2.3 AND ROUND(alto,3) < 3.6 THEN 2
                        WHEN ROUND(ancho,3) >= 1.3 AND ROUND(ancho,3) < 2.2 AND ROUND(alto,3) >= 2.6 AND ROUND(alto,3) < 3.6 THEN 2
                        WHEN ROUND(ancho,3) < 1.3 AND ROUND(alto,3) >= 2.8 AND ROUND(alto,3) < 3.6 THEN 2
                        -- TUBO 45 SLIM GAP / TUBO 1/12 REFORZADO cuando llevan cassette o bracket
                        WHEN ROUND(ancho,3) >= 2.6 AND ROUND(ancho,3) < 3.3 AND ROUND(alto,3) < 2.6 THEN CASE WHEN cassette_cot > 0 OR bracket_cot > 0 THEN 2 ELSE 5 END
                        WHEN ROUND(ancho,3) >= 2.6 AND ROUND(ancho,3) < 3.1 AND ROUND(alto,3) >= 2.6 AND ROUND(alto,3) < 3.1 THEN CASE WHEN cassette_cot > 0 OR bracket_cot > 0 THEN 2 ELSE 5 END
                        WHEN ROUND(ancho,3) >= 2.3 AND ROUND(ancho,3) < 3.0 AND ROUND(alto,3) >= 3.1 AND ROUND(alto,3) < 3.6 THEN CASE WHEN cassette_cot > 0 OR bracket_cot > 0 THEN 2 ELSE 5 END
                        WHEN ROUND(ancho,3) < 2.6 AND ROUND(alto,3) >= 3.6 AND ROUND(alto,3) < 4.1 THEN CASE WHEN cassette_cot > 0 OR bracket_cot > 0 THEN 2 ELSE 5 END
                        WHEN ROUND(ancho,3) < 2.0 AND ROUND(alto,3) >= 4.1 AND ROUND(alto,3) <= 5.0 THEN CASE WHEN cassette_cot > 0 OR bracket_cot > 0 THEN 2 ELSE 5 END
                        -- TUBO 2-1/2"
                        WHEN ROUND(ancho,3) >= 3.3 AND ROUND(ancho,3) <= 5.0 AND ROUND(alto,3) < 2.6 THEN 6
                        WHEN ROUND(ancho,3) >= 3.1 AND ROUND(ancho,3) <= 5.0 AND ROUND(alto,3) >= 2.6 AND ROUND(alto,3) < 2.7  THEN 6
                        WHEN ROUND(ancho,3) >= 3.1 AND ROUND(ancho,3) < 4.6 AND ROUND(alto,3) >= 2.7 AND ROUND(alto,3) < 3.0 THEN 6
                        WHEN ROUND(ancho,3) >= 3.1 AND ROUND(ancho,3) < 4.1 AND ROUND(alto,3) >= 3.0 AND ROUND(alto,3) < 3.1  THEN 6
                        WHEN ROUND(ancho,3) >= 3.0 AND ROUND(ancho,3) < 4.1 AND ROUND(alto,3) >= 3.1 AND ROUND(alto,3) < 3.6 THEN 6
                        WHEN ROUND(ancho,3) >= 2.6 AND ROUND(ancho,3) < 3.6 AND ROUND(alto,3) >= 3.6 AND ROUND(alto,3) < 4.1 THEN 6
                        WHEN ROUND(ancho,3) >= 2.0 AND ROUND(ancho,3) < 3.3 AND ROUND(alto,3) >= 4.1 AND ROUND(alto,3) < 4.6 THEN 6
                        WHEN ROUND(ancho,3) >= 2.0 AND ROUND(ancho,3) < 3.0 AND ROUND(alto,3) >= 4.6 AND ROUND(alto,3) <= 5.0 THEN 6
                        -- TUBO 3"
                        WHEN ROUND(ancho,3) >= 4.6 AND ROUND(ancho,3) <= 5.0 AND ROUND(alto,3) >= 2.7 AND ROUND(alto,3) < 3.0 THEN 7
                        WHEN ROUND(ancho,3) >= 4.1 AND ROUND(ancho,3) <= 5.0 AND ROUND(alto,3) >= 3.0 AND ROUND(alto,3) < 3.6 THEN 7
                        WHEN ROUND(ancho,3) >= 3.6 AND ROUND(ancho,3) <= 5.0 AND ROUND(alto,3) >= 3.6 AND ROUND(alto,3) < 4.1 THEN 7
                        WHEN ROUND(ancho,3) >= 3.3 AND ROUND(ancho,3) < 4.5 AND ROUND(alto,3) >= 4.1 AND ROUND(alto,3) < 4.6 THEN 7
                        WHEN ROUND(ancho,3) >= 3.0 AND ROUND(ancho,3) < 3.3 AND ROUND(alto,3) >= 4.6 AND ROUND(alto,3) <= 5.0 THEN 7
                        WHEN ROUND(ancho,3) < 3.0 AND ROUND(alto,3) > 5.0 then 7

                    END
                END -- end de operation
            END -- end de product_id
            AS tube_id
            */
   /* }*/
}