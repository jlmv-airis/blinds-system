<?php
namespace App\classes;

use App\Models\DOrder;

class GetTotal {
    public function getTotalQuotation($items,$client_discount) { // genera el subtotal de una sola partida

        $subtotalQuotation = $subtotalInit = $discountQuotation = $disc1 = 0;
        foreach ($items as $key => $item) {
            $subtotalInit = 0;
            switch ($item['unit_id']) { // aqui asignamos el metro total dependiendo de la unidad
                case 1: // M2
                    $mtsr = $width = $height = 0;
                    if((DOUBLE)$item['width'] < 1 ) { $width = 1; } else { $width = (DOUBLE)$item['width']; }
                    if((DOUBLE)$item['height'] < 1 ) { $height = 1; } else { $height = (DOUBLE)$item['height']; }
                    $mtsr =  $width * $height;
                    $subtotalInit = ( round($mtsr,2) *  $item['price'] ) * $item['quantity'];
                break;
                case 2: // ML  // SI SE VENDEN ARTICULOS QUE SE COBREN POR MERTO LINEAL EN UN FUTURO AJUSTAR DATOS A JAVASCRIPT
                    $mts = 0;
                    if( $item['quotation_product_id'] ===  4 ) { // ACCESORIOS
                        $mts = $item['width'];
                    }
                    $subtotalInit = ($mts * $item['price']) * $item['quantity'];
                break;
                case 3: // Pieza
                    $subtotalInit = $item['price'] * $item['quantity'];
                break;
                case 4: // Paquete
                    $subtotalInit = $item['price'] * $item['quantity'];
                break;
                case 5: // Kit
                    $subtotalInit = $item['price'] * $item['quantity'];
                break;
                case 6: // Servicio
                    $subtotalInit = $item['price'] * $item['quantity'];
                break;
            }
            $subtotalQuotation  = $subtotalQuotation  + $subtotalInit;
            $disc1 = $subtotalInit - ( $subtotalInit * ( $client_discount / 100 ) );
            $disc2 = $disc1  - ( $disc1  * ( $item['article_discount'] / 100 ) );
            $discount = $disc2  - ( $disc2  * ( $item['request_discount'] / 100 ) );

            $discountQuotation = $discountQuotation + ( $subtotalInit - $discount );
        }
        $subtotal = ($subtotalQuotation - $discountQuotation);
        return [
            'discount' => $discountQuotation ,
            'subtotal' => $subtotalQuotation,
            'iva'      =>  $subtotal * 0.16 ,
            'total'    => $subtotal * 1.16 ,
        ];
    }

    public function getIndividualTotalQuotation($item,$client_discount) { // genera el subtotal de una sola partida

        $subtotalQuotation = $subtotalInit = $discountQuotation = $disc1 = $subtotalInit = 0;
        switch ($item['unit_id']) { // aqui asignamos el metro total dependiendo de la unidad
            case 1: // M2
                $mtsr = $width = $height = 0;
                if((DOUBLE)$item['width'] < 1 ) { $width = 1; } else { $width = (DOUBLE)$item['width']; }
                if((DOUBLE)$item['height'] < 1 ) { $height = 1; } else { $height = (DOUBLE)$item['height']; }
                $mtsr =  $width * $height;
                $subtotalInit = ( round($mtsr,2) *  $item['price'] ) * $item['quantity'];
            break;
            case 2: // ML  // SI SE VENDEN ARTICULOS QUE SE COBREN POR MERTO LINEAL EN UN FUTURO AJUSTAR DATOS A JAVASCRIPT
                $mts = 0;
                if( $item['quotation_product_id'] ===  4 ) { // ACCESORIOS
                    $mts = $item['width'];
                }
                $subtotalInit = ($mts * $item['price']) * $item['quantity'];
            break;
            case 3: // Pieza
                $subtotalInit = $item['price'] * $item['quantity'];
            break;
            case 4: // Paquete
                $subtotalInit = $item['price'] * $item['quantity'];
            break;
            case 5: // Kit
                $subtotalInit = $item['price'] * $item['quantity'];
            break;
            case 6: // Servicio
                $subtotalInit = $item['price'] * $item['quantity'];
            break;
        }
        $subtotalQuotation  = $subtotalQuotation  + $subtotalInit;
        $disc1 = $subtotalInit - ( $subtotalInit * ( $client_discount / 100 ) );
        $disc2 = $disc1  - ( $disc1  * ( $item['article_discount'] / 100 ) );
        $discount = $disc2  - ( $disc2  * ( $item['request_discount'] / 100 ) );

        $discountQuotation = $discountQuotation + ( $subtotalInit - $discount );

        $subtotal = ($subtotalQuotation - $discountQuotation);
        return $subtotal;
    }


    // ORDER


    public function getTotalOrder($items) { // genera el subtotal de una sola partida

        $subtotalOrder = $subtotalInit = $discountOrder = $disc1 = 0;
        foreach ($items as $key => $item) {
            $subtotalInit = 0;
            switch ($item['unit_id']) { // aqui asignamos el metro total dependiendo de la unidad
                case 1: // M2
                    $mtsr = $width = $height = 0;
                    if((DOUBLE)$item['width'] < 1 ) { $width = 1; } else { $width = (DOUBLE)$item['width']; }
                    if((DOUBLE)$item['height'] < 1 ) { $height = 1; } else { $height = (DOUBLE)$item['height']; }
                    $mtsr =  $width * $height;
                    $subtotalInit = ( round($mtsr,2) *  $item['price'] );
                break;
                case 2: // ML  // SI SE VENDEN ARTICULOS QUE SE COBREN POR MERTO LINEAL EN UN FUTURO AJUSTAR DATOS A JAVASCRIPT
                    $mts = 0;
                    if( $item['product_id'] ===  4 ) { // ACCESORIOS
                        $mts = $item['width'];
                    }
                    $subtotalInit = ($mts * $item['price']) * $item['quantity'];
                break;
                case 3: // Pieza
                    $subtotalInit = $item['price'] * $item['quantity'];
                break;
                case 4: // Paquete
                    $subtotalInit = $item['price'] * $item['quantity'];
                break;
                case 5: // Kit
                    $subtotalInit = $item['price'] * $item['quantity'];
                break;
                case 6: // Servicio
                    $subtotalInit = $item['price'] * $item['quantity'];
                break;
            }
            $subtotalOrder  = $subtotalOrder  + $subtotalInit;
            $disc1 = $subtotalInit - ( $subtotalInit * ( $item['discount1'] / 100 ) );
            $disc2 = $disc1  - ( $disc1  * ( $item['discount2'] / 100 ) );
            $discount = $disc2  - ( $disc2  * ( $item['discount3'] / 100 ) );

            $discountOrder = $discountOrder + ( $subtotalInit - $discount );
        }
        $subtotal = ($subtotalOrder - $discountOrder);
        return [
            'discount' => $discountOrder ,
            'subtotal' => $subtotalOrder,
            'iva'      =>  $subtotal * 0.16 ,
            'total'    => $subtotal * 1.16 ,
        ];
    }

    public function getIndividualTotalOrder($item) { // genera el subtotal de una sola partida

        $subtotalQuotation = $subtotalInit = $discountQuotation = $disc1 = $subtotalInit = 0;
        switch ($item['unit_id']) { // aqui asignamos el metro total dependiendo de la unidad
            case 1: // M2
                $mtsr = $width = $height = 0;
                if((DOUBLE)$item['width'] < 1 ) { $width = 1; } else { $width = (DOUBLE)$item['width']; }
                if((DOUBLE)$item['height'] < 1 ) { $height = 1; } else { $height = (DOUBLE)$item['height']; }
                $mtsr =  $width * $height;
                $subtotalInit = ( round($mtsr,2) *  $item['price'] );
            break;
            case 2: // ML  // SI SE VENDEN ARTICULOS QUE SE COBREN POR MERTO LINEAL EN UN FUTURO AJUSTAR DATOS A JAVASCRIPT
                $mts = 0;
                if( $item['product_id'] ===  4 ) { // ACCESORIOS
                    $mts = $item['width'];
                }
                $subtotalInit = ($mts * $item['price']) * $item['quantity'];
            break;
            case 3: // Pieza
                $subtotalInit = $item['price'] * $item['quantity'];
            break;
            case 4: // Paquete
                $subtotalInit = $item['price'] * $item['quantity'];
            break;
            case 5: // Kit
                $subtotalInit = $item['price'] * $item['quantity'];
            break;
            case 6: // Servicio
                $subtotalInit = $item['price'] * $item['quantity'];
            break;
        }
        $subtotalQuotation  = $subtotalQuotation  + $subtotalInit;
        $disc1 = $subtotalInit - ( $subtotalInit * ( $item['discount1'] / 100 ) );
        $disc2 = $disc1  - ( $disc1  * ( $item['discount2'] / 100 ) );
        $discount = $disc2  - ( $disc2  * ( $item['discount3'] / 100 ) );

        $discountQuotation = $discountQuotation + ( $subtotalInit - $discount );

        $subtotal = ($subtotalQuotation - $discountQuotation);
        return $subtotal;
    }

     // COST
    public function getIndividualCostOrder($item,$costForm) {
        $cost = 0;
        if ( ( ( (INT)$item['relation_accesories'] === 0 || $item['relation_accesories'] === null || (INT)$item['model_id'] === 16 ) &&  (INT)$item['product_id'] === 4  ) || ( (INT)$item['product_id'] === 1 ||  (INT)$item['product_id'] === 2 ||  (INT)$item['product_id'] === 5 ) ) {
            $width = $this->getM2Matriz($item['width'],1,$item['product_id']);
            $height = $this->getM2Matriz($item['height'],2,$item['product_id']);
            $cloth = 0;
            $tube = 0;
            $accML = 0;
            $accTC = 0;
            $cad = 0;
            $mecha = 0;
            $accCDC = 0;
            $accBIPF = 0;
            $accAll = 0;
            $accLamb = 0;
            $lie = 0;
            switch ((INT)$item['product_id']) {
                case 1: // Enrollables
                    //CLOTH
                    $costClocth = $this->getFormCost($item,$costForm,1);
                    $sumHeight = 0;
                    $restTube = 0;
                    if( (INT)$width['tube_id'] === 2 ){ $sumHeight = 0.25; $restTube = 0.03; }
                    if( (INT)$width['tube_id'] === 3 ){ $sumHeight = 0.3; $restTube = 0.035; }
                    if( (INT)$width['tube_id'] === 5 ){ $sumHeight = 0.5; $restTube = 0.035; }
                    if( (INT)$width['tube_id'] === 6 ){ $sumHeight = 0.5; $restTube = 0.04; }
                    $cloth = (DOUBLE)ROUND ( ( ( $costClocth * ( (DOUBLE)$width['ml'] - (DOUBLE)$restTube) ) * ( (DOUBLE)$height['ml'] + (DOUBLE)$sumHeight) ) , 2);
                    // TUBE
                    $costTube = $this->getFormCost($width['tube_id'],$costForm,2);
                    $tube =  (DOUBLE) ROUND( ( $costTube * ( $width['ml'] - (DOUBLE)$restTube)),2 );
                    // ADD / BASE
                    $costAddBase = $this->getFormCost($item,$costForm,3);
                    $accML =  (DOUBLE) ( $costAddBase * $width['ml'] );
                    // TAP / ACC CAD
                    $costTapCad = $this->getFormCost($item,$costForm,4);
                    $accTC =  (DOUBLE) ROUND( $costTapCad , 2 );
                    // CAD
                    $costCad = $this->getFormCost($item,$costForm,5);
                    $cad =  (DOUBLE) ROUND( ( $costCad * $height['ml'] * 2 ) , 2 );
                    // MECHA
                    $mechanismID = $this->getMechanism($width['ml'],$height['ml'],$item['product_id']);
                    $costMechaAcctube = $this->getFormCost(['id' => $item['id'],'product_id' => $item['product_id'],'tube_id' => $width['tube_id'],'mechanism_id' => $mechanismID ],$costForm,6);
                    $mecha =  $costMechaAcctube;
                    //
                    $cost =  (DOUBLE) ROUND( ( ( (DOUBLE)$cloth + (DOUBLE)$tube + (DOUBLE)$accML + (DOUBLE)$accCDC + (DOUBLE)$accTC + (DOUBLE)$cad + (DOUBLE)$mecha + (DOUBLE)$accBIPF ) *  1.05 ) ,2  ) ;
                break;
                case 2: // Sheer Elegance
                    //CLOTH
                    $costClocth = $this->getFormCost($item,$costForm,1);
                    $sumHeight = 0;
                    $restTube = 0;
                    if( (INT)$width['tube_id'] === 1 ){ $sumHeight = 0.3; $restTube = 0.007; }
                    if( (INT)$width['tube_id'] === 2 ){ if( (DOUBLE)$width['ml'] === 2.5 ) { $sumHeight = 0.3; $restTube = 0.03; } else { $sumHeight = 0.3; $restTube = 0.007;  } }
                    if( (INT)$width['tube_id'] === 3 ){ $sumHeight = 0.3; $restTube = 0.03; }
                    $cloth = (DOUBLE) ROUND( ( $costClocth * ( (DOUBLE)$width['ml'] - (DOUBLE)$restTube ) * ( 2 * ( (DOUBLE)$height['ml'] + (DOUBLE)$sumHeight ) ) ) , 2 );
                    // TUBE
                    $costTube = $this->getFormCost($width['tube_id'],$costForm,2);
                    $tube =  (DOUBLE) ROUND( ( $costTube * ( $width['ml'] - (DOUBLE)$restTube ) ) , 2 );
                    // ADD / BASE
                    $costAddBase = $this->getFormCost($item,$costForm,3);
                    $accML =  (DOUBLE)  ROUND( ( $costAddBase * $width['ml'] ) , 2 );
                    // CDC
                    $costCDC = $this->getFormCost($item,$costForm,7);
                    $accCDC =  (DOUBLE) ROUND( ( $costCDC * $width['ml'] * 4 ) , 2 );
                    // MECHA
                    $mechanismID = $this->getMechanism($width['ml'],$height['ml'],$item['product_id']);
                    $costMechaAcctube = $this->getFormCost(['id' => $item['id'],'product_id' => $item['product_id'],'tube_id' => $width['tube_id'],'mechanism_id'  => $mechanismID ],$costForm,6);
                    $mecha =  $costMechaAcctube;
                    // TAP / ACC CAD
                    $costTapCad = $this->getFormCost($item,$costForm,4);
                    $accTC =  (DOUBLE) ROUND( $costTapCad , 2 );
                    // CAD
                    $costCad = $this->getFormCost($item,$costForm,5);
                    $cad =  (DOUBLE) ROUND( ( $costCad * $height['ml'] * 2 ) , 2 );
                    // BIPF
                    $totalBrackets = $this->getTotalBrackets($width['ml'],$height['ml']);
                    $costAccBIPF = $this->getFormCost($item,$costForm,8);
                    $accBIPF =  (DOUBLE) ROUND ( ( $costAccBIPF * $totalBrackets ) , 2 );
                    //
                    $cost =  (DOUBLE) ROUND  ( ( ( (DOUBLE)$cloth + (DOUBLE)$tube + (DOUBLE)$accML + (DOUBLE)$accCDC + (DOUBLE)$accTC + (DOUBLE)$cad + (DOUBLE)$mecha + (DOUBLE)$accBIPF ) *  1.05 ) , 2 );
                break;
                case 4: // Accesorios
                    if( ( $item['relation_accesories'] === null || (INT)$item['relation_accesories'] === 0 ) && (INT)$item['model_id'] !== 25 && (INT)$item['model_id'] !== 51 && (INT)$item['model_id'] !== 16 && (INT)$item['model_id'] !== 6 && (INT)$item['model_id'] !== 59 ) {
                        $accAll = (DOUBLE)$accAll + ( (DOUBLE)$item['cost'] * (DOUBLE)$item['quantity']  );
                        //
                        $cost = (DOUBLE) ROUND ( ( (DOUBLE)$cost + (DOUBLE)$accAll ) , 2 );
                    } else if ( (INT)$item['model_id'] === 25 ) { // Lambrequin
                        // CLOTH
                        $costClocth = $this->getFormCost($item,$costForm,1);
                        $cloth = (DOUBLE)ROUND ( ( $costClocth * (DOUBLE) 0.25 )  , 2 );
                        // ADD / BASE
                        $costAddBase = $this->getFormCost($item,$costForm,3);
                        $accML =  (DOUBLE) ROUND( $costAddBase , 2);
                        // TAP / ACC CAD
                        $costTapCad = $this->getFormCost($item,$costForm,4);
                        $accTC =  (DOUBLE) ROUND( $costTapCad , 2 );
                        // ACC LAMB
                        $costAccLamb = $this->getFormCost($item,$costForm,9);
                        $accLamb =  (DOUBLE) ROUND( $costAccLamb , 2 );
                        $totalBrackets = $this->getTotalBrackets($width['ml'],$height['ml']);
                        $costAccBIPF = $this->getFormCost($item,$costForm,8);
                        $accBIPF =  (DOUBLE) ROUND( ( $costAccBIPF * $totalBrackets ) , 2 );
                        //
                        $cost = (DOUBLE)ROUND( ( (DOUBLE)$width['ml'] * ( (DOUBLE)$cloth + (DOUBLE)$accML + (DOUBLE)$accLamb ) +  (DOUBLE)$accTC + (DOUBLE)$accBIPF )  , 2 );
                        // if( (INT)$item['order_id'] === 2288 && (INT)$item['item_id'] === 3) {
                        //     // echo $width['ml'].' - Cloth '.$cloth.' - ACC '.$accML.' - ACCLAM '.$accLamb;
                        //     echo $cost;
                        //     dd();
                        // }
                    } else if ( (INT)$item['model_id'] === 51 ) { // Corbatin
                        // CLOTH
                        $costClocth = $this->getFormCost($item,$costForm,1);
                        $cloth = (DOUBLE) ROUND( $costClocth , 2 );
                        // ADD / BASE
                        $costAddBase = $this->getFormCost($item,$costForm,3);
                        $accML =  (DOUBLE)ROUND( ( $costAddBase * $width['ml'] ) ,2 );
                        // TAP / ACC CAD
                        $costTapCad = $this->getFormCost($item,$costForm,4);
                        $accTC =  (DOUBLE) ROUND( $costTapCad , 2 );
                        //
                        $cost = (DOUBLE) ROUND( ( ( (DOUBLE)$cloth * ( (DOUBLE)$height['ml'] * 0.1 ) ) + ( 0.1 * (DOUBLE)$accML ) +  (DOUBLE)$accTC ) , 2 );
                    } else if ( (INT)$item['model_id'] === 16 ) { // Cadena metalica
                        //
                        $cost = (DOUBLE) ROUND( ( ( (DOUBLE)$item['cost'] * (DOUBLE)$item['width'] ) * (DOUBLE)$item['quantity'] ) , 2 );
                    } else if ( (INT)$item['model_id'] === 6 ) { // Fascia
                        //
                        if( (INT)$item['relation_cassette'] > 0 ) {
                            $getCloth = DOrder::select('d_orders.product_id','c_articles.model_id')
                            ->join('c_articles','c_articles.id','d_orders.article_id')
                            ->where('d_orders.order_id',$item['order_id'])
                            ->whereIn('d_orders.product_id', [1,2])
                            ->where('d_orders.relation_cassette',$item['relation_cassette'])
                            ->first();
                            // CLOTH
                            $costClocth = $this->getFormCost($getCloth->toArray(),$costForm,1);
                            $cloth = (DOUBLE) ROUND( $costClocth , 2 );
                            // MECHA SL16
                            $mechanismID = 3;
                            $costMechaAcctube = $this->getFormCost( [ 'id' => $item['id'], 'product_id' => $item['product_id'], 'tube_id' => $width['tube_id'], 'mechanism_id'  => $mechanismID ],$costForm,6);
                            $mechaOne = $costMechaAcctube;
                            // MECHA SL8
                            $mechanismID = 2;
                            $costMechaAcctube = $this->getFormCost( [ 'id' => $item['id'], 'product_id' => $item['product_id'], 'tube_id' => $width['tube_id'], 'mechanism_id'  => $mechanismID ],$costForm,6);
                            $mechaTwo = $costMechaAcctube;
                            // CDC
                            $costCDC = $this->getFormCost($item,$costForm,13);
                            $accCDC =  (DOUBLE) ROUND( ( $costCDC * 3 ) , 2 );
                            // FASCIA
                            $costTapCad = $this->getFormCost($item,$costForm,11);
                            $accTC =  (DOUBLE) ROUND( $costTapCad , 2 );
                            // FASCIA ACC
                            $costTapCad = $this->getFormCost($item,$costForm,12);
                            $accFSC =  (DOUBLE) ROUND( $costTapCad , 2 );
                            // BIPF
                            $totalBrackets = $this->getTotalBrackets($width['ml'],0);
                            $costAccBIPF = $this->getFormCost($item,$costForm,8);
                            $accBIPF =  (DOUBLE) ROUND ( ( $costAccBIPF * $totalBrackets ) , 2 );
                            //
                            $cost = (DOUBLE) ROUND(  ( (DOUBLE)$width['ml'] * ( (  (DOUBLE)$cloth * 0.1 )  + (DOUBLE)$accTC + (DOUBLE)$accCDC ) + $accFSC + ( $mechaTwo - $mechaOne ) + $accBIPF ) , 2 );
                        } else {
                            $cost = (DOUBLE) ROUND( ( ( (DOUBLE)$item['cost'] * (DOUBLE)$item['width'] ) * (DOUBLE)$item['quantity'] ) , 2 );
                        }
                    } else if ( (INT)$item['model_id'] === 59 ) { // Privacidad
                        // Perfil Privacidad
                        $costAPP = $this->getFormCost($item,$costForm,14);
                        $accAPP =  (DOUBLE) ROUND( ( $costAPP * 2 ) , 2 );
                        // Felpa
                        $costFLP = $this->getFormCost($item,$costForm,15);
                        $accFLP =  (DOUBLE) ROUND( ( $costFLP * 4 ) , 2 );
                        //
                        $cost = (DOUBLE) ROUND( (DOUBLE)$width['ml'] * ( (DOUBLE)$accAPP + (DOUBLE)$accFLP ) , 2 );
                    }
                break;
                case 5: // Lienzo
                    $costClocth = $this->getFormCost($item,$costForm,1);
                    $cloth = (DOUBLE) ROUND( ( $costClocth * (DOUBLE)0.25 ) ,2 );
                    $lie = (DOUBLE)$lie + ( ( ((DOUBLE)$item['width'] * (DOUBLE)$item['height'] * (DOUBLE)$item['quantity'] )  ) * (DOUBLE)$cloth );
                    //
                    $cost = (DOUBLE) ROUND( ( (DOUBLE)$cost + (DOUBLE)$lie ) , 2 );
                break;
            }


        }
        return (DOUBLE) ROUND($cost , 2);
    }


    public function getM2Matriz($meter,$opt,$product_id) {
        $ml = 0;
        $tube_id = 0;
        if( (DOUBLE)ROUND($meter,2) <= 0.5 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 0.5; if( (INT)$product_id === 1 ) { $tube_id = 2; } if((INT)$product_id === 2) { $tube_id = 1; } }
        if( (DOUBLE)ROUND($meter,2) > 0.5 && (DOUBLE)ROUND($meter,2) <= 0.8 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 0.8; if( (INT)$product_id === 1 ) { $tube_id = 2; } if((INT)$product_id === 2) { $tube_id = 1; }  }
        if( (DOUBLE)ROUND($meter,2) > 0.8 && (DOUBLE)ROUND($meter,2) <= 1 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 1; if( (INT)$product_id === 1 ) { $tube_id = 2; } if((INT)$product_id === 2) { $tube_id = 1; }  }
        if( (DOUBLE)ROUND($meter,2) > 1 && (DOUBLE)ROUND($meter,2) <= 1.2 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 1.2; if( (INT)$product_id === 1 ) { $tube_id = 2; } if((INT)$product_id === 2) { $tube_id = 1; }  }
        if( (DOUBLE)ROUND($meter,2) > 1.2 && (DOUBLE)ROUND($meter,2) <= 1.4 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 1.4 ; if( (INT)$product_id === 1 ) { $tube_id = 2; } if((INT)$product_id === 2) { $tube_id = 1; } }
        if( (DOUBLE)ROUND($meter,2) > 1.4 && (DOUBLE)ROUND($meter,2) <= 1.6 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 1.6 ; if( (INT)$product_id === 1 ) { $tube_id = 2; } if((INT)$product_id === 2) { $tube_id = 1; } }
        if( (DOUBLE)ROUND($meter,2) > 1.6 && (DOUBLE)ROUND($meter,2) <= 1.8 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 1.8 ; if( (INT)$product_id === 1 ) { $tube_id = 2; } if((INT)$product_id === 2) { $tube_id = 1; } }
        if( (DOUBLE)ROUND($meter,2) > 1.8 && (DOUBLE)ROUND($meter,2) <= 2 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 2; if( (INT)$product_id === 1 ) { $tube_id = 2; } if((INT)$product_id === 2) { $tube_id = 1; } }
        if( (DOUBLE)ROUND($meter,2) > 2 && (DOUBLE)ROUND($meter,2) <= 2.2 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 2.2; $tube_id = 2; }
        if( (DOUBLE)ROUND($meter,2) > 2.2 && (DOUBLE)ROUND($meter,2) <= 2.4 && ( (INT)$opt === 2) ) { $ml = 2.4; }
        if( (DOUBLE)ROUND($meter,2) > 2.2 && (DOUBLE)ROUND($meter,2) <= 2.5 && ( (INT)$opt === 1 ) ) { $ml = 2.5; $tube_id = 2;  }
        if( (DOUBLE)ROUND($meter,2) > 2.4 && (DOUBLE)ROUND($meter,2) <= 2.6 && ( (INT)$opt === 2 ) ) { $ml = 2.6; $tube_id = 3; }
        if( (DOUBLE)ROUND($meter,2) > 2.5 && (DOUBLE)ROUND($meter,2) <= 2.6 && ( (INT)$opt === 1 ) ) { $ml = 2.6; $tube_id = 3;}
        if( (DOUBLE)ROUND($meter,2) > 2.6 && (DOUBLE)ROUND($meter,2) <= 2.8 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 2.8; $tube_id = 3; }
        if( (DOUBLE)ROUND($meter,2) > 2.8 && (DOUBLE)ROUND($meter,2) <= 3 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 3; $tube_id = 3; }
        if( (DOUBLE)ROUND($meter,2) > 3 && (DOUBLE)ROUND($meter,2) <= 3.2 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 3.2; $tube_id = 3; }
        if( (DOUBLE)ROUND($meter,2) > 3.2 && (DOUBLE)ROUND($meter,2) <= 3.4 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 3.4; if( (INT)$product_id === 1 ) { $tube_id = 5; } if((INT)$product_id === 2) { $tube_id = 3; } }
        if( (DOUBLE)ROUND($meter,2) > 3.4 && (DOUBLE)ROUND($meter,2) <= 3.6 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 3.6; if( (INT)$product_id === 1 ) { $tube_id = 5; } if((INT)$product_id === 2) { $tube_id = 3; } }
        if( (DOUBLE)ROUND($meter,2) > 3.6 && (DOUBLE)ROUND($meter,2) <= 3.8 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 3.8; if( (INT)$product_id === 1 ) { $tube_id = 5; } if((INT)$product_id === 2) { $tube_id = 3; } }
        if( (DOUBLE)ROUND($meter,2) > 3.8 && (DOUBLE)ROUND($meter,2) <= 4 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 4; if( (INT)$product_id === 1 ) { $tube_id = 5; } if((INT)$product_id === 2) { $tube_id = 3; } }
        if( (DOUBLE)ROUND($meter,2) > 4 && (DOUBLE)ROUND($meter,2) <= 4.2 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 4.2; if( (INT)$product_id === 1 ) { $tube_id = 6; } if((INT)$product_id === 2) { $tube_id = 3; } }
        if( (DOUBLE)ROUND($meter,2) > 4.2 && (DOUBLE)ROUND($meter,2) <= 4.4 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 4.4; if( (INT)$product_id === 1 ) { $tube_id = 6; } if((INT)$product_id === 2) { $tube_id = 3; } }
        if( (DOUBLE)ROUND($meter,2) > 4.4 && (DOUBLE)ROUND($meter,2) <= 4.6 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 4.6; if( (INT)$product_id === 1 ) { $tube_id = 6; } if((INT)$product_id === 2) { $tube_id = 3; } }
        if( (DOUBLE)ROUND($meter,2) > 4.6 && (DOUBLE)ROUND($meter,2) <= 4.8 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 4.8; if( (INT)$product_id === 1 ) { $tube_id = 6; } if((INT)$product_id === 2) { $tube_id = 3; } }
        if( (DOUBLE)ROUND($meter,2) > 4.8 && (DOUBLE)ROUND($meter,2) <= 5 && ( (INT)$opt === 1 || (INT)$opt === 2 ) ) { $ml = 5; if( (INT)$product_id === 1 ) { $tube_id = 6; } if((INT)$product_id === 2) { $tube_id = 3; } }
        return [
            'ml' => $ml,
            'tube_id' => $tube_id
        ];
    }

    public function getFormCost($item,$costForm,$opt) {
        $costTotal = 0;
        foreach ($costForm as $key => $cf) {
            switch ( (INT)$opt ) {
                case 1: // CLOTH
                    if( (INT)$item['product_id'] === 1 || (INT)$item['product_id'] === 2 || (INT)$item['product_id'] === 5 ) {
                        if( (INT)$cf['is_cloth'] === 1 && (INT)$item['model_id'] === (INT)$cf['model_id'] ) {
                            $costTotal = $cf['total_cost'];
                        }
                    }
                    if( (INT)$item['product_id'] ===   4  ) {
                        if( (INT)$cf['is_cloth'] === 1 && ( (INT)$item['la_model_id'] === (INT)$cf['model_id'] || (INT)$item['cb_model_id'] === (INT)$cf['model_id'] || (INT)$item['fj_model_id'] === (INT)$cf['model_id'] ) ) {
                            $costTotal = $cf['total_cost'];
                        }
                    }
                break;
                case 2: // TUBE
                    if( (INT)$item === (INT)$cf['tube_id'] && (INT)$cf['is_tube'] === 1 ) {
                        $costTotal = $cf['total_cost'];
                    }
                break;
                case 3: //  ADD / BASE
                    // BASE
                    if( (INT)$item['counterweight_bar_id'] === (INT)$cf['base_id'] && (INT)$cf['is_base'] === 1  ) {
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                        // if( Number.parseInt(item.id) === 931) {
                        //     console.log(cf.description)
                        //     console.log($cf['total_cost'];)
                        //     console.log($costTotal)
                        // }
                    }
                    // ADD
                    if( (INT)$cf['general_id'] === 1 ) { // inserto
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                    if( (INT)$cf['general_id'] === 3 && ( (INT)$item['product_id'] === 1 || (INT)$item['model_id'] === 25 || (INT)$item['model_id'] === 51 ) ) { // cinta doble cara
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                    // FASCIA
                    if( (INT)$cf['general_id'] === 2 && (INT)$item['product_id'] === 2 ) { // FASCIA PARA SHEER
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                    // VELCRO
                    if( (INT)$cf['is_corbatin'] === 1 && (INT)$item['model_id'] === 51 ) { // FASCIA PARA SHEER
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
                case 4: //  TAP / CAD
                    // TAP
                    if( (INT)$item['counterweight_bar_id'] === (INT)$cf['base_id'] && (INT)$cf['is_base'] === 2 ) {
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                    // CAD
                    if( (INT)$cf['is_chain'] === 2 && (INT)$item['model_id'] !== 25 && (INT)$item['model_id'] !== 51 ) { // ACC CAD
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                    // CONTRA CAD
                    if( (INT)$cf['is_chain'] === 3 && (INT)$item['product_id'] === 2 ) {
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                    // TAP RIEL
                    if( (INT)$cf['is_lambrequin'] === 3 && (INT)$item['model_id'] === 25 ) { // ACC CAD
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
                case 5: // CAD
                    if( (INT)$cf['is_chain'] === 1 ) {
                        $costTotal = $cf['total_cost'];
                    }
                break;
                case 6: // MECHA / ACC TUBE
                    if( (INT)$cf['mechanism_id'] === (INT)$item['mechanism_id'] ) { // MECHA
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                    if( (INT)$item['tube_id'] === (INT)$cf['tube_id'] && (INT)$cf['is_tube'] === 2 ) { // ACC TUBE
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                    if( (INT)$cf['general_id'] === 5 &&  (INT)$item['product_id'] === 2  && (INT)$item['mechanism_id'] !== 6 ) { // ACC FASCIA
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
                case 7: // CDC SHEER
                    if( (INT)$cf['general_id'] === 4 && ( (INT)$item['product_id'] === 2 || (INT)$item['model_id'] === 6 ) ) { // cinta doble cara
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
                case 8: // BIPF
                    if( (INT)$cf['general_id'] === 6 && ( (INT)$item['product_id'] === 2 || (INT)$item['model_id'] === 6 ) ) { // cinta doble cara
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                    if( (INT)$cf['is_lambrequin'] === 2 && (INT)$item['model_id'] === 25 ) {
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
                case 9: // ACC LAMB
                    if( (INT)$cf['is_lambrequin'] === 1 && (INT)$item['model_id'] === 25 ) {
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
                case 10: //BRAK LAM
                    if( (INT)$cf['is_lambrequin'] === 2 && (INT)$item['model_id'] === 25 ) {
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
                case 11: // FASCIA
                    if( (INT)$cf['general_id'] === 2 && (INT)$item['model_id'] === 6 ) {
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
                case 12: // FASCIA ACC
                    if( (INT)$cf['is_fascia'] === 1 && (INT)$cf['general_id'] === 5 && (INT)$item['model_id'] === 6 ) {
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
                case 13: // CDC ENR
                    if( (INT)$cf['general_id'] === 3 && ( (INT)$item['product_id'] === 1 || (INT)$item['model_id'] === 6 ) ) { // cinta doble cara
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
                case 14: // PERFIL PRIVACIDAD
                    if( (INT)$cf['is_priv'] === 1 && (INT)$item['model_id'] === 59 ) {
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
                case 15: // FELPA
                    if( (INT)$cf['is_priv'] === 2 && (INT)$item['model_id'] === 59 ) {
                        $costTotal = (DOUBLE)$costTotal + (DOUBLE)$cf['total_cost'];
                    }
                break;
            }
        }
        return $costTotal;
    }


    public function getMechanism($width,$height,$product_id) {
        $mechanismID = 0;
        // XROLL
        if( (DOUBLE)$height <= 2.2 && (DOUBLE)$width <= 3.2 && (INT)$product_id === 1 ) {
            $mechanismID = 5;
        }
        // SL16
        else if( (DOUBLE)$height >= 2.4 && (DOUBLE)$width <= 3.2  && (INT)$product_id === 1  ) {
            $mechanismID = 3;
        }
        // 24 LB
        else if((DOUBLE)$width >= 3.4  && (INT)$product_id === 1  ) {
            $mechanismID = 4;
        }
        // M SHEER
        else if((DOUBLE)$width < 2.5  && (INT)$product_id === 2 ) {
            $mechanismID = 6;
        }
        // SL8
        else if((DOUBLE)$width >= 2.5  && (INT)$product_id === 2 ) {
            $mechanismID = 2;
        }
        return $mechanismID;
    }


    public function getTotalBrackets($width,$height) {
        $totalBrackets = 0;
        if( (DOUBLE)$width <= 1.2 ) {
            $totalBrackets = 2;
        }
        else if( (DOUBLE)$width > 1.2 &&  (DOUBLE)$width <= 2  ) {
            $totalBrackets = 3;
        }
        else if( (DOUBLE)$width > 2 &&  (DOUBLE)$width <= 2.8  ) {
            $totalBrackets = 4;
        }
        else if( (DOUBLE)$width > 2.8 &&  (DOUBLE)$width <= 4  ) {
            $totalBrackets = 5;
        }
        else if( (DOUBLE)$width > 4  ) {
            $totalBrackets = 6;
        }
        return $totalBrackets;
    }
}