<?php

namespace App\classes;

use App\Models\CProvider;
use Carbon\Carbon;
use PDF_Code128;
use App\classes\GetTotal;

use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FPDFMR
{
    private $pdf = null;
    private $tRowPerCol = 49;
    protected $webService;

    public function __construct(PDF_Code128 $pdf)
    {
        $this->pdf = $pdf;
    }

    public function createTubeFile($materialRequestID,$production_date,$detailOrders,$lambrequinData,$corbatinData,$fijoData,$detailClothsOrders,$type)
    {
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // FILE
                $itemStop = 0;
                $itemsPerPage = 25;
                if(count($detailOrders) < $itemsPerPage ) { $itemStop = count($detailOrders); } else { $itemStop = $itemsPerPage; }
                $initReg = 0;
                $totalPages = ceil(count($detailOrders) / $itemsPerPage);
                for ($i = 1; $i <= $totalPages; $i++) {
                    $this->tubesFileLandscapeHeader('Hoja corte perfiles',21,$materialRequestID,$production_date);
                    // ITEMS
                    $x = 28;
                    while ( $initReg < $itemStop ) {
                        if( (INT)$detailOrders[$initReg]['tube_idt'] === 1 OR  (INT)$detailOrders[$initReg]['damage_tube'] === 1 OR  (INT)$detailOrders[$initReg]['damage_fascia'] === 1 OR (INT)$detailOrders[$initReg]['counterweight_idt'] === 1 OR  (INT)$detailOrders[$initReg]['damage_counterweight'] === 1 ) {
                            $this->pdf->SetFont("Arial", "", 9);
                            $this->pdf->SetXY(2, $x);
                            $this->pdf->Cell(23, 6, $detailOrders[$initReg]['nomen'].' '.$detailOrders[$initReg]['order_id'], 1, 1, "C", true);
                            $this->pdf->SetXY(24, $x);
                            $this->pdf->Cell(14, 6, $detailOrders[$initReg]['item_id'], 1, 1, "C", true);
                            $this->pdf->SetXY(38, $x);
                            $this->pdf->Cell(15, 6, $detailOrders[$initReg]['width'], 1, 1, "C", true);
                            $this->pdf->SetXY(53, $x);
                            $this->pdf->Cell(15, 6, $detailOrders[$initReg]['height'], 1, 1, "C", true);
                            $this->pdf->SetXY(68, $x);
                            $this->pdf->Cell(30, 6, utf8_decode($detailOrders[$initReg]['product']), 1, 1, "C", true);
                            $this->pdf->SetXY(98, $x);
                            $this->pdf->Cell(40, 6, $detailOrders[$initReg]['color_name'], 1, 1, "C", true);
                            $this->pdf->SetFont("Arial", "", 6);
                            $this->pdf->SetXY(138, $x);
                            $this->pdf->Cell(39, 6, $detailOrders[$initReg]['tube'].' '.$detailOrders[$initReg]['tube_width_discount'] , 1, 1, "C", true);
                            $this->pdf->SetXY(177, $x);
                            $this->pdf->Cell(39, 6, $detailOrders[$initReg]['perfil'].' '.$detailOrders[$initReg]['perfil_width_discount'] , 1, 1, "C", true);
                            $this->pdf->SetXY(216, $x);
                            $this->pdf->Cell(39, 6, $detailOrders[$initReg]['counterweight'].' '.$detailOrders[$initReg]['counterweight_width_discount'] , 1, 1, "C", true);
                            $this->pdf->SetXY(255, $x);
                            $this->pdf->Cell(39, 6, $detailOrders[$initReg]['twistbar'].' '.$detailOrders[$initReg]['twistbar_width_discount'] , 1, 1, "C", true);
                            $x = $x + 6;
                        }
                        $initReg++;
                    }
                    $itemStop = $itemStop + $itemsPerPage;
                    $this->pdf->SetFont("Arial", "", 12);
                    if($itemStop > count($detailOrders) ) { $itemStop = count($detailOrders) ; }
                    $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));

                }
                // LAMBREQUIN
                if(COUNT($lambrequinData) > 0 ) {
                    $itemStop = 0;
                    $itemsPerPage = 25;
                    if(count($lambrequinData) < $itemsPerPage ) { $itemStop = count($lambrequinData); } else { $itemStop = $itemsPerPage; }
                    $initReg = 0;
                    $totalPages = ceil(count($lambrequinData) / $itemsPerPage);
                    for ($i = 1; $i <= $totalPages; $i++) {
                        $this->lambrequinFileLandscapeHeader('Hoja corte lamberquin',30,$materialRequestID,$production_date);
                        // ITEMS
                        $x = 28;
                        while ( $initReg < $itemStop ) {
                            $this->pdf->SetFont("Arial", "", 9);
                            $this->pdf->SetXY(2, $x);
                            $this->pdf->Cell(23, 6, $lambrequinData[$initReg]['nomen'].' '.$lambrequinData[$initReg]['order_id'], 1, 1, "C", true);
                            $this->pdf->SetXY(24, $x);
                            $this->pdf->Cell(14, 6, $lambrequinData[$initReg]['item_id'], 1, 1, "C", true);
                            $this->pdf->SetXY(38, $x);
                            $this->pdf->Cell(15, 6, $lambrequinData[$initReg]['width'], 1, 1, "C", true);
                            $this->pdf->SetXY(53, $x);
                            $this->pdf->Cell(15, 6, $lambrequinData[$initReg]['height'], 1, 1, "C", true);
                            $this->pdf->SetXY(68, $x);
                            $this->pdf->Cell(80, 6, utf8_decode($lambrequinData[$initReg]['product']), 1, 1, "C", true);
                            $this->pdf->SetXY(148, $x);
                            $this->pdf->Cell(40, 6, $lambrequinData[$initReg]['color_name'], 1, 1, "C", true);
                            $this->pdf->SetFont("Arial", "", 6);
                            $this->pdf->SetXY(188, $x);
                            $this->pdf->Cell(53, 6, $lambrequinData[$initReg]['article_base'].' '.$lambrequinData[$initReg]['width_discount_base'] , 1, 1, "C", true);
                            $this->pdf->SetXY(241, $x);
                            if((INT)$lambrequinData[$initReg]['is_velcro']) {
                                $this->pdf->Cell(53, 6,'VELCRO DE 19MM', 1, 1, "C", true);
                            } else {
                                $this->pdf->Cell(53, 6, 'Riel Universal '.$lambrequinData[$initReg]['width_discount_riel'] , 1, 1, "C", true);
                            }
                            $x = $x + 6;
                            $initReg++;
                        }
                        $itemStop = $itemStop + $itemsPerPage;
                        $this->pdf->SetFont("Arial", "", 12);
                        if($itemStop > count($lambrequinData) ) { $itemStop = count($lambrequinData) ; }
                        $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));

                    }
                }

                // CORBATIN
                if(COUNT($corbatinData) > 0 ) {
                    $itemStop = 0;
                    $itemsPerPage = 25;
                    if(count($corbatinData) < $itemsPerPage ) { $itemStop = count($corbatinData); } else { $itemStop = $itemsPerPage; }
                    $initReg = 0;
                    $totalPages = ceil(count($corbatinData) / $itemsPerPage);
                    for ($i = 1; $i <= $totalPages; $i++) {
                        $this->corbatinFileLandscapeHeader('Hoja corte corbatin',23,$materialRequestID,$production_date);
                        // ITEMS
                        $x = 28;
                        while ( $initReg < $itemStop ) {
                            $this->pdf->SetFont("Arial", "", 9);
                            $this->pdf->SetXY(2, $x);
                            $this->pdf->Cell(23, 6, $corbatinData[$initReg]['nomen'].' '.$corbatinData[$initReg]['order_id'], 1, 1, "C", true);
                            $this->pdf->SetXY(24, $x);
                            $this->pdf->Cell(14, 6, $corbatinData[$initReg]['item_id'], 1, 1, "C", true);
                            $this->pdf->SetXY(38, $x);
                            $this->pdf->Cell(15, 6, $corbatinData[$initReg]['width'], 1, 1, "C", true);
                            $this->pdf->SetXY(53, $x);
                            $this->pdf->Cell(15, 6, $corbatinData[$initReg]['height'], 1, 1, "C", true);
                            $this->pdf->SetXY(68, $x);
                            $this->pdf->Cell(80, 6, utf8_decode($corbatinData[$initReg]['product']), 1, 1, "C", true);
                            $this->pdf->SetXY(148, $x);
                            $this->pdf->Cell(40, 6, $corbatinData[$initReg]['color_name'], 1, 1, "C", true);
                            $this->pdf->SetFont("Arial", "", 6);
                            $this->pdf->SetXY(188, $x);
                            $this->pdf->Cell(53, 6, $corbatinData[$initReg]['article_base'].' '.$corbatinData[$initReg]['width_discount_base'] , 1, 1, "C", true);
                            $this->pdf->SetXY(241, $x);
                            $this->pdf->Cell(53, 6, 'Velcro '.$corbatinData[$initReg]['width_discount_velcro'] , 1, 1, "C", true);
                            $x = $x + 6;
                            $initReg++;
                        }
                        $itemStop = $itemStop + $itemsPerPage;
                        $this->pdf->SetFont("Arial", "", 12);
                        if($itemStop > count($corbatinData) ) { $itemStop = count($corbatinData) ; }
                        $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));

                    }
                }
                // FIJO
                if(COUNT($fijoData) > 0 ) {
                    $itemStop = 0;
                    $itemsPerPage = 25;
                    if(count($fijoData) < $itemsPerPage ) { $itemStop = count($fijoData); } else { $itemStop = $itemsPerPage; }
                    $initReg = 0;
                    $totalPages = ceil(count($fijoData) / $itemsPerPage);
                    for ($i = 1; $i <= $totalPages; $i++) {
                        $this->fijoFileLandscapeHeader('Hoja corte fijo',23,$materialRequestID,$production_date);
                        // ITEMS
                        $x = 28;
                        while ( $initReg < $itemStop ) {
                            $this->pdf->SetFont("Arial", "", 9);
                            $this->pdf->SetXY(2, $x);
                            $this->pdf->Cell(23, 6, $fijoData[$initReg]['nomen'].' '.$fijoData[$initReg]['order_id'], 1, 1, "C", true);
                            $this->pdf->SetXY(24, $x);
                            $this->pdf->Cell(14, 6, $fijoData[$initReg]['item_id'], 1, 1, "C", true);
                            $this->pdf->SetXY(38, $x);
                            $this->pdf->Cell(15, 6, $fijoData[$initReg]['width'], 1, 1, "C", true);
                            $this->pdf->SetXY(53, $x);
                            $this->pdf->Cell(15, 6, $fijoData[$initReg]['height'], 1, 1, "C", true);
                            $this->pdf->SetXY(68, $x);
                            $this->pdf->Cell(120, 6, utf8_decode($fijoData[$initReg]['product']), 1, 1, "C", true);
                            $this->pdf->SetXY(188, $x);
                            $this->pdf->Cell(50, 6, $fijoData[$initReg]['color_name'], 1, 1, "C", true);
                            $this->pdf->SetXY(238, $x);
                            $this->pdf->Cell(53, 6, 'Velcro de 19MM '.$fijoData[$initReg]['width_discount_velcro'] , 1, 1, "C", true);
                            $x = $x + 6;
                            $initReg++;
                        }
                        $itemStop = $itemStop + $itemsPerPage;
                        $this->pdf->SetFont("Arial", "", 12);
                        if($itemStop > count($fijoData) ) { $itemStop = count($fijoData) ; }
                        $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));

                    }
                }
                $nameFile = "corte-tubos-";
            break;
            case 2: // LABELS

                $this->pdf->SetAutoPageBreak(true,2);
                foreach ($detailOrders as $key => $do) {
                    if( (INT)$do['tube_idt'] === 1 OR  (INT)$do['damage_tube'] === 1 OR  (INT)$do['damage_fascia'] === 1 OR (INT)$do['counterweight_idt'] === 1 OR  (INT)$do['damage_counterweight'] === 1 ) {
                        $y = 5;
                        $this->pdf->AddPage();
                        $this->pdf->SetFont("Arial", "B", 9);
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Cell(20, 4, $do['nomen'].' '.$do['order_id'].' - '.$do['item_id'], 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 9);
                        $product = 'ENR';
                        if($do['product_id'] == 2) {$product = 'SHE'; }
                        $this->pdf->SetXY(21, 1);
                        $this->pdf->Cell(15, 4, $product, 0, 1, "C", false);
                        $this->pdf->SetXY(36, 1);
                        $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('d-m-y'), 0, 1, "C", false);
                        $this->pdf->SetXY(54, 1);
                        $heightFinal = $do['height'];
                        if($do['relation_heat_seal'] > 0) {
                            $othersClothsSum = $this->foundThermo($do['relation_heat_seal'],$detailClothsOrders);
                            $heightFinal = $heightFinal + $othersClothsSum['height'];
                        }
                        $this->pdf->Cell(22, 4, number_format($do['width'],3).' x '.number_format($heightFinal,3), 0, 1, "C", false);
                        // PERFIL
                        if(!is_null($do['perfil']) AND (INT)$do['damage_fascia'] === 1 ) {
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(1, $y);
                            $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['perfil']))), 0, 1, "L", false);
                            $this->pdf->SetXY(51, $y);
                            $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(56, $y);
                            $this->pdf->Cell(20, 4,  number_format($do['perfil_width_discount'],3), 0, 1, "C", false);
                            $y = $y + 4;
                        }
                        // TUBE
                        if((INT)$do['tube_idt'] === 1 OR  (INT)$do['damage_tube'] === 1) {
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(1, $y);
                            $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['tube']))), 0, 1, "L", false);
                            $this->pdf->SetXY(51, $y);
                            $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(56, $y);
                            $this->pdf->Cell(20, 4,  number_format($do['tube_width_discount'],3), 0, 1, "C", false);
                            $y = $y + 4;
                        }
                        // COUNTER
                        if((INT)$do['counterweight_idt'] === 1 OR  (INT)$do['damage_counterweight'] === 1) {
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(1, $y);
                            $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['counterweight']))), 0, 1, "L", false);
                            $this->pdf->SetXY(51, $y);
                            $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(56, $y);
                            $this->pdf->Cell(20, 4,  number_format($do['counterweight_width_discount'],3), 0, 1, "C", false);
                            $y = $y + 4;
                        }
                        // turn
                        if(!is_null($do['twistbar'])) {
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(1, $y);
                            $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['twistbar']))), 0, 1, "L", false);
                            $this->pdf->SetXY(51, $y);
                            $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(56, $y);
                            $this->pdf->Cell(20, 4,  number_format($do['twistbar_width_discount'],3), 0, 1, "C", false);
                            $y = $y + 4;
                        }
                        //
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(1, 21);
                        $this->pdf->Cell(8, 3, 'Ctrl. ', 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "B", 8);
                        $mechanismSide = 'IZQ';
                        if($do['mechanism_side_id'] == 2) {$mechanismSide = 'DER'; }
                        $this->pdf->SetXY(9, 21);
                        $this->pdf->Cell(10, 3, strtoupper($mechanismSide), 0, 1, "L", false);
                        //
                        $this->pdf->SetXY(29, 21);
                        $this->pdf->Cell(8, 3, 'Acc. ', 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->SetXY(37, 21);
                        $this->pdf->Cell(20, 3, strtoupper($do['operation']), 0, 1, "L", false);
                        //
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(63,24,'Perfiles');
                    }


                    // $this->pdf->SetXY(1, 1);
                    // $this->pdf->Image("img/lansonshades.jpeg", 4, 1, 0, 4);

                    // $this->pdf->SetFont("Arial", "", 12);
                    // $this->pdf->SetXY(40, 1);
                    // $this->pdf->Cell(35, 5, $do['order_id'].' - '.$do['item_id'], 0, 1, "R", false);

                    // $this->pdf->SetFont("Arial", "", 6);
                    // $this->pdf->Text(4,8,utf8_decode(strtoupper($do['product'])));
                    // $this->pdf->Text(4,12,utf8_decode($do['tube']));
                    // $this->pdf->Text(45,12,$do['tube_width_discount']);
                    // $this->pdf->Text(4,16,utf8_decode($do['counterweight']));
                    // $this->pdf->Text(45,16,$do['counterweight_width_discount']);
                    // $this->pdf->Text(4,20,utf8_decode(strtoupper($do['perfil'])));
                    // $this->pdf->Text(45,20,$do['perfil_width_discount']);
                    // $this->pdf->Text(4,24,utf8_decode($do['twistbar']));
                    // $this->pdf->Text(45,24,$do['twistbar_width_discount']);

                    // // LEFT
                    // $this->pdf->Text(62,9,utf8_decode('Medidas'));
                    // $this->pdf->Text(62,13,$do['width'].' x '.$do['height']);

                    // // code
                    // $this->pdf->Code128(58, 16, $do['id'], 18, 6);
                    // $this->pdf->Text(60,25,Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('Y-m-d'));
                }
                $nameFile = "etiquetas-corte-tubos-";
            break;
        }
        // $file = $this->pdf->Output("");
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile.'MR'.$materialRequestID."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createclothFile($materialRequestID,$production_date,$orders,$detailOrders,$graficOrders,$type)
    {
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // FILE
                // dd($graficOrders);
                $this->pdf->SetAutoPageBreak(true,2);
                $this->cutGraficFileLandscapeHeader('Grafica de corte telas',27,$materialRequestID,$production_date);
                $x = 2;
                $this->pdf->SetDrawColor(230,230,230);
                $this->pdf->SetXY($x, 18);
                $this->pdf->Cell(70, 190, '', 1, 1, "C", false);
                $this->pdf->SetDrawColor(0,0,0);
                // creamos los cortesx

                $xs1 = 2;
                $xs2 = 76;
                $xs3 = 150;
                $xs4 = 224;
                // if((INT)$rowNumber === 5 ) { $x = 240;
                $maxHeight = 0;
                $maxHeigthNumber = 0;
                $sumWidth = 0;
                $joinOld = 1;
                $articleIdOld = 0;
                $articleWidthOld = 0;
                $articleLotOld = '';
                $cutArticleOld = $graficOrders[0]['article_id'];
                $sumWidthOld = 0;
                $numberJoins = 0;
                // $cutWidthOld = $graficOrders[0]['width_lot'];
                $y = 18;
                $rowNumber = 1;

                foreach ($graficOrders as $cut) {
                    if( $cut['width_lot'] === 3 ) { $pixelZise = 0.23333; }
                    if( $cut['width_lot'] == 2.9 ) { $pixelZise = 0.24111; }
                    if( $cut['width_lot'] == 2.5 ) { $pixelZise = 0.27999; }
                    if( $cut['width_lot'] == 2 ) { $pixelZise = 0.34888; }
                    if( $cut['width_lot'] == 2.99 ) { $pixelZise = 0.23333; }


                    $width = $pixelZise * ( $cut['width_discount'] * 100 );
                    $height = $pixelZise * ( $cut['height_add'] * 100 );

                    $widthText = $cut['width_discount'];
                    $heightText = $cut['height_add'];
                    $sumWidth = $sumWidth + $cut['width_discount'];

                    // join del mismo articulo
                    if( (INT)$cut['join_id'] === (INT)$joinOld AND (INT)$cutArticleOld === (INT)$cut['article_id'] AND $cut['lot'] == $articleLotOld ) {
                        $numberJoins++;
                        // MAX HEIGHT
                        if( !is_null($cut['join_id'] ) ) {
                            if( $height > $maxHeight ) {
                                $maxHeight = $height;
                                $maxHeigthNumber = $cut['height_add'];
                                $sumWidth = $cut['width_discount'];
                                $numberJoins = 0;
                            }
                        } else {
                            $y = $y +  $maxHeight;
                            if((INT)$rowNumber === 1 ) { $x = $xs1; }
                            if((INT)$rowNumber === 2 ) { $x = $xs2; }
                            if((INT)$rowNumber === 3 ) { $x = $xs3; }
                            if((INT)$rowNumber === 4 ) { $x = $xs4; }
                            // if((INT)$rowNumber === 5 ) { $x = 240; }
                            $maxHeight = $height;
                            $maxHeigthNumber = $cut['height_add'];
                            $sumWidth = $cut['width_discount'];
                            $numberJoins = 0;
                        }
                    } else {
                        $y = $y + $maxHeight;
                        if((INT)$rowNumber === 1 ) { $x = $xs1; }
                        if((INT)$rowNumber === 2 ) { $x = $xs2; }
                        if((INT)$rowNumber === 3 ) { $x = $xs3; }
                        if((INT)$rowNumber === 4 ) { $x = $xs4; }
                        // if((INT)$rowNumber === 5 ) { $x = 240; }
                        $maxHeight = $height;
                        $maxHeigthNumber = $cut['height_add'];
                        $sumWidth = $cut['width_discount'];
                        $numberJoins = 0;
                    }
                    //
                    if((INT)$cut['article_id'] !== (INT)$articleIdOld ) {
                        $plusY = 5;
                    } else {
                        $plusY = 0;
                    }

                    // si vemos que rebasa el limite seteamos todo
                    if( (DOUBLE)($y+$plusY+$maxHeight) > (DOUBLE)200) {
                        $rowNumber++;
                        // sec page
                        if( (INT)$rowNumber === 5 ) {
                            $this->cutGraficFileLandscapeHeader('Grafica de corte telas',27,$materialRequestID,$production_date);
                            $x = 2;
                            $this->pdf->SetDrawColor(230,230,230);
                            $this->pdf->SetXY($x, 18);
                            $this->pdf->Cell(70, 190, '', 1, 1, "C", false);
                            $this->pdf->SetDrawColor(0,0,0);
                            $rowNumber = 1;
                        } else {
                            if((INT)$rowNumber === 1 ) { $x = $xs1; }
                            if((INT)$rowNumber === 2 ) { $x = $xs2; }
                            if((INT)$rowNumber === 3 ) { $x = $xs3; }
                            if((INT)$rowNumber === 4 ) { $x = $xs4; }
                            // if((INT)$rowNumber === 5 ) { $x = 240; }
                            $this->pdf->SetDrawColor(230,230,230);
                            $this->pdf->SetXY($x, 18);
                            $this->pdf->Cell(70, 190, '', 1, 1, "C", false);
                            $this->pdf->SetDrawColor(0,0,0);
                        }
                        // creamos los cortes
                        $maxHeight = $height;
                        $maxHeigthNumber = $cut['height_add'];
                        $joinOld = 1;
                        $articleIdOld = 0;
                        $articleWidthOld = 0;
                        $articleLotOld = '';
                        $y = 18;
                    }

                    // HEADER ARTICLE
                    if((INT)$cut['article_id'] !== (INT)$articleIdOld ) {
                        $widtLotHeader = ' / RECORTES ';
                        if($cut['width_lot'] != 2.99) { $widtLotHeader = number_format($cut['width_lot'],2); }
                        $cutArticleOld = $cut['article_id'];
                        $this->pdf->setFillColor(211,211,211);
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->SetXY($x, $y);
                        $this->pdf->Cell(70, 5, utf8_decode($cut['article']).' '.$widtLotHeader.' '.$cut['lot'] , 0, 1, "C", true);
                        // $this->pdf->Cell(50, 5, '', 0, 1, "C", true);
                        $y = $y + 5;
                        $this->pdf->setFillColor(255,255,255);
                    } else if( (DOUBLE)$cut['width_lot'] !== (DOUBLE)$articleWidthOld  ) {
                        $widtLotHeader = ' / RECORTES ';
                        if($cut['width_lot'] != 2.99) { $widtLotHeader = number_format($cut['width_lot'],2); }
                        $cutArticleOld = $cut['article_id'];
                        $this->pdf->setFillColor(211,211,211);
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->SetXY($x, $y);
                        $this->pdf->Cell(70, 5,utf8_decode($cut['article']).' '.$widtLotHeader.' '.$cut['lot'], 0, 1, "C", true);
                        // $this->pdf->Cell(50, 5, '', 0, 1, "C", true);
                        $y = $y + 5;
                        $this->pdf->setFillColor(255,255,255);
                    }
                    else if( $cut['lot'] != $articleLotOld ) {
                        $widtLotHeader = number_format($cut['width_lot'],2);
                        $cutArticleOld = $cut['article_id'];
                        $this->pdf->setFillColor(211,211,211);
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->SetXY($x, $y);
                        $this->pdf->Cell(70, 5,utf8_decode($cut['article']).' '.$widtLotHeader.' '.$cut['lot'], 0, 1, "C", true);
                        // $this->pdf->Cell(50, 5, '', 0, 1, "C", true);
                        $y = $y + 5;
                        $this->pdf->setFillColor(255,255,255);
                    }

                    // $this->pdf->SetXY($x, $y+12);
                    // $this->pdf->Cell($height, 4,$y, 0, 1, "L", false);
                    // $this->pdf->SetXY($x, $y+16);
                    // $this->pdf->Cell($height, 4,$maxHeight, 0, 1, "L", false);

                    // MAX HEIGHT
                    $this->pdf->SetFont("Arial", "", 7);
                    if(is_null($cut['join_id'])) {

                        $sumWidthOld = (DOUBLE)$cut['width_lot'] - (DOUBLE)$sumWidth;
                        // CLOTH
                        $this->pdf->SetXY($x, $y);
                        $this->pdf->Cell($width, $height,'', 1, 1, "C", false);
                        // verificamos si tiene cassete
                        if($cut['relation_cassette'] > 0 AND (INT)$cut['product_id'] === 1 ) {
                            $heigthCassette = $pixelZise * ( 0.10 * 100 );
                            $this->pdf->SetXY($x, $y + $height - $heigthCassette);
                            $this->pdf->SetFont("Arial", "", 6);
                            $this->pdf->Cell($width, $heigthCassette, 'Cassete  0.10', 1, 1, "C", false);
                            $this->pdf->SetFont("Arial", "", 7);
                        }
                        // DATOS
                        if((INT)$cut['is_corbatin'] === 0 AND (INT)$cut['is_lambrequin'] === 0) { // NOT IS CORBATIN
                            $this->pdf->SetXY($x, $y+1);
                            $this->pdf->Cell($height, 4, $cut['nomen'].$cut['order_id']." - ".$cut['item_id'], 0, 1, "L", false);
                            $this->pdf->SetXY($x, $y+4);
                            $this->pdf->Cell($height, 4,$widthText." X ", 0, 1, "L", false);
                            $this->pdf->SetXY($x, $y+7);
                            $this->pdf->Cell($height, 4,$heightText, 0, 1, "L", false);
                        }
                        if((INT)$cut['is_corbatin'] === 1) { // NOT IS CORBATIN
                            $this->pdf->SetFont("Arial", "B", 6);
                            $this->pdf->TextWithRotation($x+2.5,$y+$height-2,'CORBATIN '.$cut['nomen'].$cut['order_id']." - ".$cut['item_id'].' / '.$widthText." X ".$heightText ,90,0);
                            $this->pdf->SetFont("Arial", "", 7);
                        } else if( (INT)$cut['is_lambrequin'] === 1 ) {
                            $this->pdf->SetFont("Arial", "B", 6);
                            $this->pdf->SetXY($x, $y);
                            $this->pdf->Cell($height, 4, $cut['nomen'].$cut['order_id']." - ".$cut['item_id'], 0, 1, "L", false);
                            $this->pdf->SetXY($x, $y+2);
                            $this->pdf->Cell($height, 4,$widthText." X ".$heightText, 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "", 7);
                        }
                        // IS INVERTIDA
                        if((INT)$cut['is_inverted'] === 1) {
                            $this->pdf->SetFont("Arial", "B", 6);
                            $this->pdf->SetXY($x, $y+10);
                            $this->pdf->Cell($height, 4,'ROTAR TELA', 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "", 7);
                        }

                        if((INT)$cut['product_id'] === 1 AND ( $sumWidthOld >= 1  AND $maxHeigthNumber  >= 1.4  )) {
                            $widtRestore = $pixelZise * ( $sumWidthOld * 100 );
                            $heightRestore = $pixelZise * ( $maxHeigthNumber * 100 );

                            $this->pdf->SetXY($x + $width, $y);
                            $this->pdf->Cell($widtRestore, $heightRestore,'', 1, 1, "C", false);
                            $this->pdf->SetXY($x + $width, $y+6);
                            $this->pdf->Cell($widtRestore, 4,$sumWidthOld." X ", 0, 1, "L", false);
                            $this->pdf->SetXY($x + $width, $y+9);
                            $this->pdf->Cell($widtRestore, 4,$maxHeigthNumber, 0, 1, "L", false);
                            $this->pdf->SetXY($x + $width, $y+12);
                            $this->pdf->Cell($widtRestore, 4,'Ubica: ', 0, 1, "L", false);
                            $this->pdf->Image("img/check.png", $x + $width + 2, $y, 7, 7);
                        } else if( (INT)$cut['product_id'] === 1 ) {
                            $widtRestore = $pixelZise * ( $sumWidthOld * 100 );
                            $heightRestore = $pixelZise * ( $maxHeigthNumber * 100 );
                            if($widtRestore > 0) {
                                $this->pdf->SetXY($x + $width, $y);
                                $this->pdf->Cell($widtRestore, $heightRestore,'', 1, 1, "C", false);
                                if($sumWidthOld > 0.10) {
                                    $this->pdf->SetXY($x + $width, $y+6);
                                    $this->pdf->Cell($widtRestore, 4,$sumWidthOld." X ", 0, 1, "L", false);
                                    $this->pdf->SetXY($x + $width, $y+9);
                                    $this->pdf->Cell($widtRestore, 4,$maxHeigthNumber, 0, 1, "L", false);
                                }
                            }
                            $this->pdf->Image("img/bad.png", $x + $width, $y+2, 3, 3);
                        }
                        if((INT)$cut['product_id'] === 2 AND ( $sumWidthOld >= 1  AND $maxHeigthNumber  >= 2.4  )) {
                            $widtRestore = $pixelZise * ( $sumWidthOld * 100 );
                            $heightRestore = $pixelZise * ( $maxHeigthNumber * 100 );
                            $this->pdf->SetXY($x + $width, $y);
                            $this->pdf->Cell($widtRestore, $heightRestore,'', 1, 1, "C", false);
                            $this->pdf->SetXY($x + $width, $y+6);
                            $this->pdf->Cell($widtRestore, 4,$sumWidthOld." X ", 0, 1, "L", false);
                            $this->pdf->SetXY($x + $width, $y+9);
                            $this->pdf->Cell($widtRestore, 4,$maxHeigthNumber, 0, 1, "L", false);
                            $this->pdf->SetXY($x + $width, $y+12);
                            $this->pdf->Cell($widtRestore, 4,'Ubica: ', 0, 1, "L", false);
                            $this->pdf->Image("img/check.png", $x + $width + 2, $y, 7, 7);
                        } else if( (INT)$cut['product_id'] === 2 ) {
                            $widtRestore = $pixelZise * ( $sumWidthOld * 100 );
                            $heightRestore = $pixelZise * ( $maxHeigthNumber * 100 );
                            if($widtRestore > 0) {
                                $this->pdf->SetXY($x + $width, $y);
                                $this->pdf->Cell($widtRestore, $heightRestore,'', 1, 1, "C", false);
                                if($sumWidthOld > 0.10) {
                                    $this->pdf->SetXY($x + $width, $y+6);
                                    $this->pdf->Cell($widtRestore, 4,$sumWidthOld." X ", 0, 1, "L", false);
                                    $this->pdf->SetXY($x + $width, $y+9);
                                    $this->pdf->Cell($widtRestore, 4,$maxHeigthNumber, 0, 1, "L", false);
                                }
                            }
                            $this->pdf->Image("img/bad.png", $x + $width, $y+2, 3, 3);
                        }


                        $sumWidth = 0;
                        // SET rownumber
                        if((INT)$rowNumber === 1 ) { $x = $xs1; }
                        if((INT)$rowNumber === 2 ) { $x = $xs2; }
                        if((INT)$rowNumber === 3 ) { $x = $xs3; }
                        if((INT)$rowNumber === 4 ) { $x = $xs4; }
                        // if((INT)$rowNumber === 5 ) { $x = 240; }

                    } else { // grafica para los joins


                        if((INT)$cut['is_recorte'] === 0 ) {
                            // CLOTH
                            $this->pdf->SetXY($x, $y);
                            $this->pdf->Cell($width, $height, '', 1, 1, "C", false);
                            // verificamos si tiene cassete
                            if($cut['relation_cassette'] > 0 AND (INT)$cut['product_id'] === 1) {
                                $heigthCassette = $pixelZise * ( 0.10 * 100 );
                                $this->pdf->SetXY($x, $y + $height - $heigthCassette);
                                $this->pdf->SetFont("Arial", "", 6);
                                $this->pdf->Cell($width, $heigthCassette, 'Cassete  0.10', 1, 1, "C", false);
                                $this->pdf->SetFont("Arial", "", 7);
                            }
                            // DATOS
                            if((INT)$cut['is_corbatin'] === 0 AND (INT)$cut['is_lambrequin'] === 0) { // NOT IS CORBATIN
                                $this->pdf->SetXY($x, $y+1);
                                $this->pdf->Cell($height, 4, $cut['nomen'].$cut['order_id']." - ".$cut['item_id'], 0, 1, "L", false);
                                $this->pdf->SetXY($x, $y+4);
                                $this->pdf->Cell($height, 4,$widthText." X ", 0, 1, "L", false);
                                $this->pdf->SetXY($x, $y+7);
                                $this->pdf->Cell($height, 4,$heightText, 0, 1, "L", false);
                            }
                            if((INT)$cut['is_corbatin'] === 1) {
                                $this->pdf->SetFont("Arial", "B", 6);
                                $this->pdf->TextWithRotation($x+2.5,$y+$height-2,'CORBATIN '.$cut['nomen'].$cut['order_id']." - ".$cut['item_id'].' / '.$widthText." X ".$heightText ,90,0);
                                $this->pdf->SetFont("Arial", "", 7);
                            }
                            if((INT)$cut['is_lambrequin'] === 1) {
                                $this->pdf->SetFont("Arial", "B", 6);
                                $this->pdf->SetXY($x, $y);
                                $this->pdf->Cell($height, 4, $cut['nomen'].$cut['order_id']." - ".$cut['item_id'], 0, 1, "L", false);
                                $this->pdf->SetXY($x, $y+2);
                                $this->pdf->Cell($height, 4,$widthText." X ".$heightText, 0, 1, "L", false);
                                $this->pdf->SetFont("Arial", "", 7);
                            }
                            // IS INVERTIDA
                            if((INT)$cut['is_inverted'] === 1) {
                                $this->pdf->SetFont("Arial", "B", 6);
                                $this->pdf->SetXY($x, $y+10);
                                $this->pdf->Cell($height, 4,'ROTAR TELA', 0, 1, "L", false);
                                $this->pdf->SetFont("Arial", "", 7);
                            }
                            $x = $x + $width;
                        } else {
                            // CLOTH

                            if($width > 0) {
                                $this->pdf->SetXY($x, $y);
                                $this->pdf->Cell($width, $height, '', 1, 1, "C", false);
                            }
                            // DATOS
                            if((INT)$cut['product_id'] === 1 AND ( (DOUBLE)$widthText >= 1  AND (DOUBLE)$heightText  >= 1.4  )) {
                                $this->pdf->Image("img/check.png", $x+2, $y, 7, 7);
                                $this->pdf->SetXY($x, $y+12);
                                $this->pdf->Cell($height, 4,'Ubica: ', 0, 1, "L", false);
                            } else if((INT)$cut['product_id'] === 1 ) {
                                $this->pdf->Image("img/bad.png", $x, $y+2, 3, 3);
                            }
                            if((INT)$cut['product_id'] === 2 AND ( (DOUBLE)$widthText >= 1  AND (DOUBLE)$heightText >= 2.4  )) {
                                $this->pdf->Image("img/check.png", $x+2, $y, 7, 7);
                                $this->pdf->SetXY($x, $y+12);
                                $this->pdf->Cell($height, 4,'Ubica: ', 0, 1, "L", false);
                            } else if((INT)$cut['product_id'] === 2 ) {
                                $this->pdf->Image("img/bad.png", $x, $y+2, 3, 3);
                            }
                            if($widthText > 0.10) {
                                $this->pdf->SetXY($x, $y+6);
                                $this->pdf->Cell($height, 4,$widthText." X ", 0, 1, "L", false);
                                $this->pdf->SetXY($x, $y+9);
                                $this->pdf->Cell($height, 4,$heightText, 0, 1, "L", false);
                            }
                            $x = $x + $width;
                        }
                    }


                    $joinOld = $cut['join_id'];
                    $articleIdOld = $cut['article_id'];
                    $articleWidthOld = $cut['width_lot'];
                    $articleLotOld = $cut['lot'];
                }
                // DATA
                $itemStop = 0;
                $itemsPerPage = 13;
                if(count($detailOrders) < $itemsPerPage ) { $itemStop = count($detailOrders); } else { $itemStop = $itemsPerPage; }
                $initReg = 0;
                $totalPages = ceil(count($detailOrders) / $itemsPerPage);
                for ($i = 1; $i <= $totalPages; $i++) {

                    $this->cutFileLandscapeHeader('Hoja corte telas',16,$materialRequestID,$production_date);
                    // ITEMS
                    $x = 28;
                    $this->pdf->SetFont("Arial", "", 8);
                    while ( $initReg < $itemStop ) {

                        $this->pdf->SetXY(2, $x);
                        $this->pdf->Cell(23, 6, $detailOrders[$initReg]['nomen'].' '.$detailOrders[$initReg]['order_id'], 1, 1, "C", true);
                        $this->pdf->SetXY(24, $x);
                        $this->pdf->Cell(14, 6, $detailOrders[$initReg]['item_id'], 1, 1, "C", true);
                        $this->pdf->SetXY(37, $x);
                        $this->pdf->Cell(63, 6, utf8_decode($detailOrders[$initReg]['article']) , 1, 1, "C", true);
                        $this->pdf->SetXY(100, $x);
                        $this->pdf->Cell(15, 6, $detailOrders[$initReg]['width'], 1, 1, "C", true);
                        $this->pdf->SetXY(115, $x);
                        $this->pdf->Cell(15, 6, $detailOrders[$initReg]['height'], 1, 1, "C", true);
                        $this->pdf->SetXY(130, $x);
                        $this->pdf->Cell(30, 6, utf8_decode($detailOrders[$initReg]['product']), 1, 1, "C", true);
                        $this->pdf->SetXY(160, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($detailOrders[$initReg]['operation']) , 1, 1, "C", true);
                        $this->pdf->SetXY(180, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($detailOrders[$initReg]['counterweight_bar']) , 1, 1, "C", true);
                        $this->pdf->SetXY(200, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($detailOrders[$initReg]['mechanism_side']) , 1, 1, "C", true);
                        $this->pdf->SetXY(220, $x);
                        $this->pdf->Cell(20, 6, utf8_decode($detailOrders[$initReg]['fall']) , 1, 1, "C", true);
                        $this->pdf->SetXY(240, $x);
                        $this->pdf->Cell(15, 6, $detailOrders[$initReg]['height_chain'] , 1, 1, "C", true);
                        $this->pdf->SetXY(255, $x);
                        $this->pdf->Cell(25, 6, $detailOrders[$initReg]['mechanism']  , 1, 1, "C", true);
                        $this->pdf->SetXY(280, $x);
                        $this->pdf->Cell(15, 6, $detailOrders[$initReg]['ubica']  , 1, 1, "C", true);
                        $x = $x + 6;

                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->setFillColor(241,241,241);
                        $this->pdf->SetXY(2, $x);
                        $this->pdf->Cell(293, 6, utf8_decode(' '.$this->getComment($detailOrders[$initReg],$detailOrders)), 1, 1, "L", true);
                        // $this->pdf->SetXY(220, $x);
                        // $this->pdf->Cell(75, 6, utf8_decode(' Alineación: '.$detailOrders[$initReg]['relation']), 1, 1, "L", true);
                        $this->pdf->setFillColor(255,255,255);
                        $x = $x + 6;
                        $initReg++;
                    }
                    $itemStop = $itemStop + $itemsPerPage;
                    $this->pdf->SetFont("Arial", "", 12);
                    if($itemStop > count($detailOrders) ) { $itemStop = count($detailOrders) ; }
                    $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));
                }
                // GENERAL
                $itemStop = 0;
                $itemsPerPage = 14;
                if(count($orders) < $itemsPerPage ) { $itemStop = count($orders); } else { $itemStop = $itemsPerPage; }
                $initReg = 0;
                $totalPages = ceil(count($orders) / $itemsPerPage);

                for ($i = 1; $i <= $totalPages; $i++) {
                    $this->cutFileOrdersHeader('General',8,$materialRequestID,$production_date);;
                    // ITEMS
                    $x = 28;
                    $this->pdf->SetFont("Arial", "", 9);
                    while ( $initReg < $itemStop ) {

                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(2, $x);
                        $this->pdf->Cell(23, 6, $orders[$initReg]['nomen'].' '.$orders[$initReg]['order_id'], 1, 1, "C", true);
                        $this->pdf->SetXY(25, $x);
                        $this->pdf->Cell(14, 6, $orders[$initReg]['item_id'], 1, 1, "C", true);
                        $this->pdf->SetXY(39, $x);
                        $this->pdf->Cell(63, 6, utf8_decode($orders[$initReg]['article']) , 1, 1, "C", true);
                        $this->pdf->SetXY(102, $x);
                        $this->pdf->Cell(14, 6, $orders[$initReg]['width'] , 1, 1, "C", true);
                        $this->pdf->SetXY(116, $x);
                        $this->pdf->Cell(14, 6, $orders[$initReg]['height'] , 1, 1, "C", true);
                        $this->pdf->SetXY(130, $x);
                        $this->pdf->Cell(30, 6, $orders[$initReg]['product'], 1, 1, "C", true);
                        $this->pdf->SetXY(160, $x);
                        $this->pdf->Cell(20, 6, $orders[$initReg]['operation'], 1, 1, "C", true);

                        $this->pdf->SetXY(180, $x);
                        $this->pdf->Cell(20, 6, $orders[$initReg]['counterweight_bar'], 1, 1, "C", true);
                        $this->pdf->SetXY(200, $x);
                        $this->pdf->Cell(20, 6, $orders[$initReg]['mechanism_side'], 1, 1, "C", true);
                        $this->pdf->SetXY(220, $x);
                        $this->pdf->Cell(20, 6, $orders[$initReg]['fall'] , 1, 1, "C", true);
                        $this->pdf->SetXY(240, $x);
                        $this->pdf->Cell(15, 6, $orders[$initReg]['height_chain'], 1, 1, "C", true);

                        $this->pdf->SetXY(255, $x);
                        $this->pdf->Cell(10, 6, $orders[$initReg]['quantity'] , 1, 1, "C", true);
                        $this->pdf->SetXY(265, $x);
                        $this->pdf->Cell(15, 6, '', 1, 1, "C", true);
                        $this->pdf->SetXY(280, $x);
                        $this->pdf->Cell(15, 6, '', 1, 1, "C", true);
                        $this->pdf->SetFont("Arial", "", 9);
                        $this->pdf->SetXY(275, $x);
                        // $this->pdf->Cell(20, 6, '$'.number_format($subtotal,2), 1, 1, "C", true);

                        $x = $x + 6;
                        $this->pdf->SetFont("Arial", "", 7);
                        $this->pdf->setFillColor(241,241,241);
                        $this->pdf->SetXY(2, $x);
                        $this->pdf->Cell(293, 6, utf8_decode(' '.$this->getFullComment($orders[$initReg],$orders)), 1, 1, "L", true);
                        $this->pdf->setFillColor(255,255,255);
                        $x = $x + 6;
                        $initReg++;
                    }
                    $itemStop = $itemStop + $itemsPerPage;
                    $this->pdf->SetFont("Arial", "", 12);
                    if($itemStop > count($orders) ) { $itemStop = count($orders) ; }
                    $this->pdf->Text(260, 205, utf8_decode("Página ".$i." de ".$totalPages));
                }

                $nameFile = "corte-telas-";
            break;
            case 2: // LABELS

                $this->pdf->SetAutoPageBreak(true,2);
                // dd($detailOrders);
                foreach ($detailOrders as $key => $do) {
                    if( (INT)$do['product_id'] === 1 OR (INT)$do['product_id'] === 2 ) {

                        if( (INT)$do['cloth_idt'] === 1 OR  (INT)$do['damage_fabric'] === 1 ) {
                            // NEW
                            $this->pdf->AddPage();

                            $this->pdf->SetFont("Arial", "B", 9);
                            $this->pdf->SetXY(1, 1);
                            $this->pdf->Cell(20, 4, $do['nomen'].$do['order_id'].' - '.$do['item_id'], 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "", 9);
                            $product = 'ENR';
                            if($do['product_id'] == 2) {$product = 'SHE'; }
                            $this->pdf->SetXY(21, 1);
                            $this->pdf->Cell(15, 4, $product, 0, 1, "C", false);
                            $this->pdf->SetXY(36, 1);
                            $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('d-m-y'), 0, 1, "C", false);
                            $heightFinal = $do['height'];
                            if($do['relation_heat_seal'] > 0) {
                                $othersClothsSum = $this->foundThermo($do['relation_heat_seal'],$detailOrders);
                                $heightFinal = $heightFinal + $othersClothsSum['height'];
                            }
                            $this->pdf->SetXY(54, 1);
                            $this->pdf->Cell(22, 4, number_format($do['width'],3).' x '.number_format($heightFinal,3), 0, 1, "C", false);
                            // ARTICLE
                            if( (INT)$do['is_lambrequin'] === 0 AND (INT)$do['is_lambrequin'] === 0 ) {
                                $this->pdf->SetFont("Arial", "", 8);
                                $this->pdf->SetXY(1, 5);
                                $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['article']))), 0, 1, "L", false);
                                $this->pdf->SetXY(51, 5);
                                $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                                $this->pdf->SetFont("Arial", "", 8);
                                $this->pdf->SetXY(56, 5);
                                $this->pdf->Cell(20, 4, number_format($do['width_discount_ni'],3).' x '.number_format($do['height_add_ni'],3), 0, 1, "C", false);
                                $this->pdf->SetFont("Arial", "", 8);
                            }
                            // OTHERS ARTICLES
                            // Thermo
                            if($do['relation_heat_seal'] > 0 AND $do['nomen'] == 'LS') {
                                $othersCloths = $this->foundThermo($do['relation_heat_seal'],$detailOrders);
                                $this->pdf->SetFont("Arial", "", 8);
                                $this->pdf->SetXY(1, 8);
                                $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($othersCloths['article']))), 0, 1, "L", false);
                                $this->pdf->SetXY(51, 8);
                                $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                                $this->pdf->SetFont("Arial", "", 8);
                                $this->pdf->SetXY(56, 8);
                                $this->pdf->Cell(20, 4, number_format($othersCloths['width_discount_ni'],3).' x '.number_format($othersCloths['height_add_ni'],3), 0, 1, "C", false);
                                $this->pdf->SetFont("Arial", "", 8);
                            }
                            // LAMBREQUIN
                            if($do['relation_lambrequin'] > 0 AND $do['nomen'] == 'LS') {
                                $othersCloths = $this->fountLambrequin($do['relation_lambrequin'],$detailOrders);
                                $this->pdf->SetFont("Arial", "", 8);
                                $this->pdf->SetXY(1, 8);
                                $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($othersCloths['article']))), 0, 1, "L", false);
                                $this->pdf->SetXY(51, 8);
                                $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                                $this->pdf->SetFont("Arial", "", 8);
                                $this->pdf->SetXY(56, 8);
                                $this->pdf->Cell(20, 4, number_format($othersCloths['width_discount_ni'],3).' x '.number_format($othersCloths['height_add_ni'],3), 0, 1, "C", false);
                                $this->pdf->SetFont("Arial", "", 8);
                            }
                            // FASCIA
                            if($do['relation_cassette'] > 0 AND (INT)$do['product_id'] === 1) {
                                $this->pdf->SetFont("Arial", "", 8);
                                $this->pdf->SetXY(1, 11);
                                $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['article']))), 0, 1, "L", false);
                                $this->pdf->SetXY(51, 11);
                                $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                                $this->pdf->SetFont("Arial", "", 8);
                                $this->pdf->SetXY(56, 11);
                                $this->pdf->Cell(20, 4, number_format($do['width'],3).' x '.number_format(0.10,3), 0, 1, "C", false);
                                $this->pdf->SetFont("Arial", "", 8);
                            }
                            //
                            $this->pdf->SetXY(1, 17);
                            $this->pdf->Cell(8, 3, 'Acc. ', 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "B", 8);
                            $this->pdf->SetXY(9, 17);
                            $this->pdf->Cell(20, 3, strtoupper($do['operation']), 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(29, 17);
                            $this->pdf->Cell(8, 3, 'Ctrl. ', 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "B", 8);
                            $mechanismSide = 'IZQ';
                            if($do['mechanism_side_id'] == 2) {$mechanismSide = 'DER'; }
                            $this->pdf->SetXY(37, 17);
                            $this->pdf->Cell(10, 3, strtoupper($mechanismSide), 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "U", 8);
                            $this->pdf->SetXY(47, 17);
                            $this->pdf->Cell(29, 3, utf8_decode($do['area_description']), 0, 1, "L", false);
                            //
                            $this->pdf->SetFont("Arial", "B", 8);
                            $this->pdf->Text(70,24,'Tela');

                            $this->pdf->SetFont("Arial", "", 6);
                            $this->pdf->SetXY(1, 20);
                            $this->pdf->MultiCell(70, 4, utf8_decode('obs. '.$this->getComment($do,$detailOrders)), 0, "L", false);
                        }
                        // // OLD
                        // $this->pdf->AddPage();
                        // $this->pdf->SetXY(1, 1);
                        // $this->pdf->Image("img/lansonshades.jpeg", 4, 1, 0, 4);

                        // $this->pdf->SetFont("Arial", "", 12);
                        // $this->pdf->SetXY(40, 1);
                        // $this->pdf->Cell(35, 5, $do['order_id'].' - '.$do['item_id'], 0, 1, "R", false);

                        // $this->pdf->SetFont("Arial", "", 6);
                        // $this->pdf->Text(4,8,utf8_decode(strtoupper($do['product'])));
                        // $this->pdf->Text(4,11,utf8_decode($do['article']));
                        // $this->pdf->Text(43,11,$do['width_discount'].' x '.$do['height_add']);
                        // $this->pdf->Text(4,14,utf8_decode('Acc: '.$do['operation']));
                        // $this->pdf->Text(20,14,utf8_decode('Control: '.$do['mechanism_side']));


                        // $this->pdf->SetFont("Arial", "", 5);
                        // $this->pdf->SetXY(3, 16);
                        // $this->pdf->MultiCell(50, 2, utf8_decode('Comentario: '.$this->getComment($do,$detailOrders)), 0, "L", false);
                        // //$this->pdf->Multicell(50,9,"This is a multi-line text string\nNew line\nNew line");

                        // // $this->pdf->Text(4,16,utf8_decode($do['counterweight']));
                        // // $this->pdf->Text(45,16,$do['counterweight_width_discount']);
                        // // $this->pdf->Text(4,20,utf8_decode(strtoupper($do['perfil'])));
                        // // $this->pdf->Text(45,20,$do['perfil_width_discount']);
                        // // $this->pdf->Text(4,24,utf8_decode($do['twistbar']));
                        // // $this->pdf->Text(45,24,$do['twistbar_width_discount']);

                        // // LEFT
                        // $this->pdf->Text(65,9,utf8_decode('Medidas'));
                        // $this->pdf->Text(65,12,$do['width'].' x '.$do['height']);

                        // // // lefts
                        // // $this->pdf->Text(48,14,$item['width_discount']);
                        // // $this->pdf->Text(60,14,$item['ubica']);

                        // // code
                        // $this->pdf->Code128(58, 16, $do['id'], 18, 6);
                        // $this->pdf->Text(60,25,Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('Y-m-d'));
                        // // $this->pdf->SetFont("Arial", "B", 8);
                        // // $this->pdf->Text(48,10,'Corte');
                        // // $this->pdf->Text(60,10,utf8_decode('Ubicación'));
                        // // $this->pdf->Text(48,25,'Creado:');
                    }
                    /* if( (INT)$do['product_id'] === 4 AND (INT)$do['is_corbatin'] === 1) {

                            // NEW
                            $this->pdf->AddPage();

                            $this->pdf->SetFont("Arial", "B", 9);
                            $this->pdf->SetXY(1, 1);
                            $this->pdf->Cell(20, 4, $do['order_id'].' - '.$do['item_id'], 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "", 9);
                            $product = 'ENR';
                            if($do['product_id'] == 2) {$product = 'SHE'; }
                            $this->pdf->SetXY(21, 1);
                            $this->pdf->Cell(15, 4, $product, 0, 1, "C", false);
                            $this->pdf->SetXY(36, 1);
                            $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('d-m-y'), 0, 1, "C", false);
                            $this->pdf->SetXY(54, 1);
                            $this->pdf->Cell(22, 4, number_format($do['width'],3).' x '.number_format($do['height'],3), 0, 1, "C", false);
                            // ARTICLE
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(1, 5);
                            $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['article']))), 0, 1, "L", false);
                            $this->pdf->SetXY(51, 5);
                            $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                            $this->pdf->SetFont("Arial", "B", 8);
                            $this->pdf->SetXY(56, 5);
                            $this->pdf->Cell(20, 4, number_format($do['width_discount_ni'],3).' x '.number_format($do['height_add_ni'],3), 0, 1, "C", false);
                            $this->pdf->SetFont("Arial", "", 8);
                            //
                            $this->pdf->SetFont("Arial", "B", 9);
                            $this->pdf->SetXY(1, 17);
                            $this->pdf->Cell(8, 3, 'CORBATIN', 0, 1, "L", false);
                            //
                            $this->pdf->SetFont("Arial", "B", 8);
                            $this->pdf->Text(70,24,'Tela');

                            $this->pdf->SetFont("Arial", "", 6);
                            $this->pdf->SetXY(1, 20);
                            $this->pdf->MultiCell(70, 4, utf8_decode('obs. '.$this->getComment($do,$detailOrders)), 0, "L", false);

                    } */

                }


                $nameFile = "etiquetas-corte-telas-";
            break;
        }
        // $file = $this->pdf->Output() ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile.'MR'.$materialRequestID."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createpclothsgalFile($materialRequestID,$production_date,$detailOrdersLambre,$detailOrdersCorba,$detailOrdersFijo,$type)
    {
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // FILE

                $nameFile = "corte-telasgal-";
            break;
            case 2: // LABELS

                $this->pdf->SetAutoPageBreak(true,2);
                foreach ($detailOrdersLambre as $key => $do) {

                    if( (INT)$do['capture_id'] === 1) { // nueva
                        $this->pdf->AddPage();

                        $this->pdf->SetFont("Arial", "B", 9);
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Cell(20, 4, $do['nomen'].' '.$do['order_id'].' - '.$do['item_id'], 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 9);
                        $product = 'ENR';
                        if($do['product_id'] == 2) {$product = 'SHE'; }
                        $this->pdf->SetXY(21, 1);
                        $this->pdf->Cell(15, 4, $product, 0, 1, "C", false);
                        $this->pdf->SetXY(36, 1);
                        $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('d-m-y'), 0, 1, "C", false);
                        $this->pdf->SetXY(54, 1);
                        $this->pdf->Cell(22, 4, number_format($do['width'],3).' x '.number_format($do['height'],3), 0, 1, "C", false);
                        // ARTICLE
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(1, 5);
                        $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['article']))), 0, 1, "L", false);
                        $this->pdf->SetXY(51, 5);
                        $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(56, 5);
                        $this->pdf->Cell(20, 4, number_format($do['width_discount_ni'],3).' x '.number_format($do['height_add_ni'],3), 0, 1, "C", false);
                        //
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(63,24,'Tela/Gal');
                    }

                }

                foreach ($detailOrdersCorba as $key => $do2) {

                    if( (INT)$do['capture_id'] === 1) { // nueva
                        $this->pdf->AddPage();

                        $this->pdf->SetFont("Arial", "B", 9);
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Cell(20, 4, $do2['nomen'].' '.$do2['order_id'].' - '.$do2['item_id'], 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 9);
                        $product = 'ENR';
                        if($do2['product_id'] == 2) {$product = 'SHE'; }
                        $this->pdf->SetXY(21, 1);
                        $this->pdf->Cell(15, 4, $product, 0, 1, "C", false);
                        $this->pdf->SetXY(36, 1);
                        $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $do2['created_at'])->format('d-m-y'), 0, 1, "C", false);
                        $this->pdf->SetXY(54, 1);
                        $this->pdf->Cell(22, 4, number_format($do2['width'],3).' x '.number_format($do2['height'],3), 0, 1, "C", false);
                        // ARTICLE
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(1, 5);
                        $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do2['article']))), 0, 1, "L", false);
                        $this->pdf->SetXY(51, 5);
                        $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(56, 5);
                        $this->pdf->Cell(20, 4, number_format($do2['width_discount_ni'],3).' x '.number_format($do2['height_add_ni'],3), 0, 1, "C", false);
                        //
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(63,24,'Tela/Gal');
                    }

                }
                // FIJO
                foreach ($detailOrdersFijo as $key => $do3) {
                    if( (INT)$do['capture_id'] === 1) { // nueva
                        $this->pdf->AddPage();

                        $this->pdf->SetFont("Arial", "B", 9);
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Cell(20, 4, $do3['nomen'].' '.$do3['order_id'].' - '.$do3['item_id'], 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 9);
                        $product = 'ENR';
                        if($do3['product_id'] == 2) {$product = 'SHE'; }
                        $this->pdf->SetXY(21, 1);
                        $this->pdf->Cell(15, 4, $product, 0, 1, "C", false);
                        $this->pdf->SetXY(36, 1);
                        $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $do3['created_at'])->format('d-m-y'), 0, 1, "C", false);
                        $this->pdf->SetXY(54, 1);
                        $this->pdf->Cell(22, 4, number_format($do3['width'],3).' x '.number_format($do3['height'],3), 0, 1, "C", false);
                        // ARTICLE
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(1, 5);
                        $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do3['article']))), 0, 1, "L", false);
                        $this->pdf->SetXY(51, 5);
                        $this->pdf->Cell(5, 4, 'de:', 0, 1, "C", false);
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(56, 5);
                        $this->pdf->Cell(20, 4, number_format($do3['width_discount_ni'],3).' x '.number_format($do3['height_add_ni'],3), 0, 1, "C", false);
                        //
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(63,24,'Tela/Gal');
                    }

                }
                $nameFile = "etiquetas-telasgal-";
            break;
        }
        // $file = $this->pdf->Output("") ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile.'MR'.$materialRequestID."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createAccesoriesFile($materialRequestID,$production_date,$detailOrders,$detailClothsOrders,$accAptData,$type)
    {
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // FILE
                $nameFile = "accesorios-";
            break;
            case 2: // LABELS
                // dd($detailOrders);
                $this->pdf->SetAutoPageBreak(true,2);
                foreach ($detailOrders as $key => $do) {
                    if( (INT)$do['damage_fabric'] === 1 ||  (INT)$do['capture_id'] === 1) {
                        $y = 10;
                        $this->pdf->AddPage();
                        $this->pdf->SetFont("Arial", "B", 9);
                        $this->pdf->SetXY(1, 4);
                        $this->pdf->Cell(20, 4, $do['nomen'].' '.$do['order_id'].' - '.$do['item_id'], 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 9);
                        $product = 'ENR';
                        if($do['product_id'] == 2) {$product = 'SHE'; }
                        $this->pdf->SetXY(21, 4);
                        $this->pdf->Cell(15, 4, $product, 0, 1, "C", false);
                        $this->pdf->SetXY(36, 4);
                        $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('d-m-y'), 0, 1, "C", false);
                        $this->pdf->SetXY(54, 4);
                        $heightFinal = $do['height'];
                        if($do['relation_heat_seal'] > 0) {
                            $othersClothsSum = $this->foundThermo($do['relation_heat_seal'],$detailClothsOrders);
                            $heightFinal = $heightFinal + $othersClothsSum['height'];
                        }
                        $this->pdf->Cell(22, 4, number_format($do['width'],3).' x '.number_format($heightFinal,3), 0, 1, "C", false);
                        // ACCESORIOS
                        if((INT)$do['relation_lambrequin'] > 0 || !is_null($do['lambrequin_id'])) {
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(1, $y);
                            $this->pdf->Cell(50, 4, utf8_decode('Lambrequin:'), 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "B", 8);
                            $this->pdf->SetXY(56, $y);
                            $this->pdf->Cell(20, 4, '1', 0, 1, "C", false);
                            $this->pdf->SetFont("Arial", "", 8);
                            $y = $y + 4;
                        }
                        if(!is_null($do['corbatin_id'])) {
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(1, $y);
                            $this->pdf->Cell(50, 4, utf8_decode('Corbatin:'), 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "B", 8);
                            $this->pdf->SetXY(56, $y);
                            $this->pdf->Cell(20, 4, '1', 0, 1, "C", false);
                            $this->pdf->SetFont("Arial", "", 8);
                            $y = $y + 4;
                        }

                        if(is_null($do['corbatin_id']) AND is_null($do['lambrequin_id'])) {
                            $this->pdf->SetFont("Arial", "", 8);
                            // MECHANISM
                            if((INT)$do['operation_id'] === 1) { // Manual
                                if( (INT)$do['product_id'] === 2  OR ( (INT)$do['product_id'] === 1 AND (INT)$do['relation_cassette'] > 0 )) {

                                } else {
                                    $this->pdf->SetXY(1, $y);
                                    $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower('soporte Mecanismo '.$do['mechanism'].' '.$do['color_name']))), 0, 1, "L", false);
                                    $this->pdf->SetFont("Arial", "B", 8);
                                    $this->pdf->SetXY(56, $y);
                                    $this->pdf->Cell(20, 4, '1', 0, 1, "C", false);
                                }
                            } else {
                                // if( (INT)$do['relation_control'] === 0 OR is_null($do['relation_control']) ){
                                    $this->pdf->SetXY(1, $y);
                                    $this->pdf->Cell(50, 4, utf8_decode(ucfirst( strtolower( 'soporte '.$do['motor_article']))), 0, 1, "L", false);
                                    $this->pdf->SetFont("Arial", "B", 8);
                                    $this->pdf->SetXY(56, $y);
                                    $this->pdf->Cell(20, 4, '1', 0, 1, "C", false);
                                // }
                            }
                            $this->pdf->SetFont("Arial", "", 8);
                            $y = $y + 4;
                        }
                        foreach ($do['items_acc'] as $item) {
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(1, $y);
                            $this->pdf->Cell(50, 4, ucfirst( strtolower( utf8_decode($item['article']))), 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(56, $y);
                            $this->pdf->Cell(20, 4, $item['quantity'], 0, 1, "C", false);
                            $y = $y + 4;
                        }

                        //
                        $this->pdf->SetXY(1, 62);
                        $this->pdf->Cell(8, 3, 'Acc. ', 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->SetXY(9, 62);
                        $this->pdf->Cell(20, 3, strtoupper($do['operation']), 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(38, 62);
                        $this->pdf->Cell(8, 3, 'Ctrl. ', 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "B", 8);
                        $mechanismSide = 'IZQ';
                        if($do['mechanism_side_id'] == 2) {$mechanismSide = 'DER'; }
                        $this->pdf->SetXY(46, 62);
                        $this->pdf->Cell(10, 3, strtoupper($mechanismSide), 0, 1, "L", false);
                        //
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(60,74,'Accesorios');

                        $this->pdf->SetFont("Arial", "", 6);
                        $this->pdf->SetXY(1, 66);
                        $this->pdf->MultiCell(70, 4, utf8_decode('obs. '.$this->getComment($do,$detailOrders)), 0, "L", false);
                    }

                        // $this->pdf->SetXY(1, 1);
                        // $this->pdf->Image("img/lansonshades.jpeg", 4, 1, 0, 4);

                        // $this->pdf->SetFont("Arial", "", 12);
                        // $this->pdf->SetXY(40, 1);
                        // $this->pdf->Cell(35, 5, $do['order_id'].' - '.$do['item_id'], 0, 1, "R", false);

                        // $this->pdf->SetFont("Arial", "", 6);
                        // $this->pdf->Text(4,8,utf8_decode(strtoupper($do['product'])));
                        // $y = 12;
                        // // LEFT
                        // $this->pdf->Text(65,9,utf8_decode('Medidas'));
                        // $this->pdf->Text(65,12,$do['width'].' x '.$do['height']);

                        // // code
                        // $this->pdf->Code128(58, 16, $do['id'], 18, 6);
                        // $this->pdf->Text(60,25,Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('Y-m-d'));
                }


                foreach ($accAptData as $key => $acc) {
                    $y = 10;
                    $this->pdf->AddPage();
                    $this->pdf->SetFont("Arial", "B", 9);
                    $this->pdf->SetXY(1, 4);
                    $this->pdf->Cell(20, 4, $acc['nomen'].' '.$acc['order_id'].' - '.$acc['item_id'], 0, 1, "L", false);
                    $this->pdf->SetFont("Arial", "", 9);
                    $this->pdf->SetXY(21, 4);
                    $this->pdf->Cell(15, 4, 'ACC', 0, 1, "C", false);
                    $this->pdf->SetXY(36, 4);
                    $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $acc['created_at'])->format('d-m-y'), 0, 1, "C", false);
                    $this->pdf->SetXY(54, 4);
                    $this->pdf->SetFont("Arial", "", 8);
                    $this->pdf->SetXY(1, $y);
                    $this->pdf->Cell(50, 4, ucfirst( strtolower( utf8_decode($acc['article']))), 0, 1, "L", false);
                    $this->pdf->SetFont("Arial", "", 8);
                    $this->pdf->SetXY(56, $y);
                    $this->pdf->Cell(20, 4, $acc['quantity'], 0, 1, "C", false);
                }

                $nameFile = "etiquetas-accesorios-";
            break;
        }
        // $file = $this->pdf->Output("") ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile.'MR'.$materialRequestID."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }




    public function createMechanimsFile($materialRequestID,$production_date,$detailOrders,$detailClothsOrders,$type)
    {
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // FILE

                $nameFile = "corte-cadena-";
            break;
            case 2: // LABELS
                $this->pdf->SetAutoPageBreak(true,2);
                // dd($detailOrders);
                foreach ($detailOrders as $key => $do) {

                    if( (INT)$do['height_chain_idt'] === 1 OR  (INT)$do['chain_idt'] === 1 OR  (INT)$do['damage_chain'] === 1 OR  (INT)$do['mechanism_idt'] === 1 OR  (INT)$do['damage_mechanism'] === 1 OR  (INT)$do['damage_motor'] === 1 OR  (INT)$do['chain_idt'] === 1 ) {
                        $this->pdf->AddPage();
                        $y = 5;
                        $this->pdf->SetFont("Arial", "B", 9);
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Cell(20, 4, $do['nomen'].' '.$do['order_id'].' - '.$do['item_id'], 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 9);
                        $product = 'ENR';
                        if($do['product_id'] == 2) {$product = 'SHE'; }
                        $this->pdf->SetXY(21, 1);
                        $this->pdf->Cell(15, 4, $product, 0, 1, "C", false);
                        $this->pdf->SetXY(36, 1);
                        $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('d-m-y'), 0, 1, "C", false);
                        $this->pdf->SetXY(54, 1);
                        $heightFinal = $do['height'];
                        if($do['relation_heat_seal'] > 0) {
                            $othersClothsSum = $this->foundThermo($do['relation_heat_seal'],$detailClothsOrders);
                            $heightFinal = $heightFinal + $othersClothsSum['height'];
                        }
                        $this->pdf->Cell(22, 4, number_format($do['width'],3).' x '.number_format($heightFinal,3), 0, 1, "C", false);
                        // ARTICLE
                        $this->pdf->SetFont("Arial", "", 8);
                        if((INT)$do['operation_id'] === 1) { // Manual
                            if( (INT)$do['mechanism_idt'] === 1 OR  (INT)$do['damage_mechanism'] === 1 ) {
                                if( (INT)$do['mechanism_id'] === 2) {
                                    $this->pdf->SetXY(1, $y);
                                    $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower('Mecanismo '.$do['mechanism'].' Blanco'))), 0, 1, "L", false);
                                    $this->pdf->SetFont("Arial", "", 8);
                                    $this->pdf->SetXY(56, $y);
                                    $this->pdf->Cell(20, 4, '1', 0, 1, "C", false);
                                    $y = $y + 4;
                                } else {
                                    $this->pdf->SetXY(1, $y);
                                    $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower('Mecanismo '.$do['mechanism'].' '.$do['color_name']))), 0, 1, "L", false);
                                    $this->pdf->SetFont("Arial", "", 8);
                                    $this->pdf->SetXY(56, $y);
                                    $this->pdf->Cell(20, 4, '1', 0, 1, "C", false);
                                    $y = $y + 4;
                                }
                            }
                        } else {
                            if( (INT)$do['damage_motor'] === 1 ) {
                                $this->pdf->SetXY(1, $y);
                                $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['motor_article']))), 0, 1, "L", false);
                                $this->pdf->SetFont("Arial", "", 8);
                                $this->pdf->SetXY(56, $y);
                                $this->pdf->Cell(20, 4, '1', 0, 1, "C", false);
                                $y = $y + 4;
                            }
                        }
                        // CADENA
                        $heightThermo = 0;
                        if($do['relation_heat_seal'] > 0) {
                            $othersClothsSum = $this->foundThermo($do['relation_heat_seal'],$detailClothsOrders);
                            $heightThermo = $othersClothsSum['height'];
                        }
                        $heightFinal = $heightThermo + $do['height'];
                        $heightFinalChain = $heightThermo + $do['height_chain'];
                        $this->pdf->SetFont("Arial", "", 8);
                        if( (INT)$do['operation_id'] === 1 ) {
                            if( (INT)$do['product_id'] === 1 ) {
                                if( (INT)$do['chain_idt'] === 1 OR  (INT)$do['damage_chain'] === 1 ) {
                                    if( $do['chain_id'] == 1) {
                                        $this->pdf->SetXY(1, $y);
                                        $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower('Cadena '.$do['chain'].' '.$do['color_name']))), 0, 1, "L", false);
                                    }
                                    if( $do['chain_id'] == 2) {
                                        $this->pdf->SetXY(1, $y);
                                        $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower('Cadena '.$do['chain']))), 0, 1, "L", false);
                                    }
                                    $this->pdf->SetFont("Arial", "", 8);
                                    $this->pdf->SetXY(56, $y);
                                    $this->pdf->Cell(20, 4, $this->getchainSize($heightFinalChain,$heightFinal,$do['mechanism_id'],$do['relation_cassette'],$do['tube_id'],$do['product_id']), 0, 1, "C", false);
                                    $y = $y + 4;
                                }
                            }
                            if( (INT)$do['product_id'] === 2 ) {

                                if( (INT)$do['chain_idt'] === 1 OR  (INT)$do['damage_chain'] === 1 ) {
                                    if( $do['chain_id'] == 1) {
                                        $this->pdf->SetXY(1, $y);
                                        $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower('Cadena '.$do['chain'].' '.$do['color_name']))), 0, 1, "L", false);
                                    }
                                    if( $do['chain_id'] == 2) {
                                        $this->pdf->SetXY(1, $y);
                                        $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower('Cadena '.$do['chain']))), 0, 1, "L", false);
                                    }
                                    $this->pdf->SetFont("Arial", "", 8);
                                    $this->pdf->SetXY(56, $y);
                                    $this->pdf->Cell(20, 4, $this->getchainSize($do['height_chain'],$do['height'],$do['mechanism_id'],$do['relation_cassette'],$do['tube_id'],$do['product_id']), 0, 1, "C", false);
                                    $y = $y + 4;
                                    // Contrapeso  cadena
                                    $this->pdf->SetFont("Arial", "", 8);
                                    $this->pdf->SetXY(1, $y);
                                    $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower('TENSOR CADENA CLEAR'))), 0, 1, "L", false);
                                    $this->pdf->SetFont("Arial", "", 8);
                                    $this->pdf->SetXY(56, $y);
                                    $this->pdf->Cell(20, 4, '1', 0, 1, "C", false);
                                    $y = $y + 4;
                                }

                            }
                        }

                        //
                        $this->pdf->SetXY(1, 18);
                        $this->pdf->Cell(8, 3, 'Acc. ', 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->SetXY(9, 18);
                        $this->pdf->Cell(20, 3, strtoupper($do['operation']), 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(29, 18);
                        $this->pdf->Cell(8, 3, 'Ctrl. ', 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "B", 8);
                        $mechanismSide = 'IZQ';
                        if($do['mechanism_side_id'] == 2) {$mechanismSide = 'DER'; }
                        $this->pdf->SetXY(37, 18);
                        $this->pdf->Cell(10, 3, strtoupper($mechanismSide), 0, 1, "L", false);

                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(47, 18);
                        $this->pdf->Cell(10, 3, 'Alt. Cad. ', 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(59, 18);
                        $this->pdf->Cell(10, 3, $heightFinalChain, 0, 1, "L", false);
                        //
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(57,24,'Componentes');
                    }


                    // $this->pdf->SetXY(1, 1);
                    // $this->pdf->Image("img/lansonshades.jpeg", 4, 1, 0, 4);

                    // $this->pdf->SetFont("Arial", "", 12);
                    // $this->pdf->SetXY(40, 1);
                    // $this->pdf->Cell(35, 5, $do['order_id'].' - '.$do['item_id'], 0, 1, "R", false);

                    // $this->pdf->SetFont("Arial", "", 6);
                    // $this->pdf->Text(4,8,utf8_decode(strtoupper($do['product'])));

                    // if( $do['chain_id'] == 1) { $this->pdf->Text(4,12,strtoupper('Cadena '.utf8_decode($do['chain']).' '.utf8_decode($do['color_name']))); }
                    // if( $do['chain_id'] == 2) { $this->pdf->Text(4,12,strtoupper('Cadena '.utf8_decode($do['chain']))); }
                    // $this->pdf->Text(42,12,$do['height_chain']);
                    // $this->pdf->Text(4,16,strtoupper('Mecanismo '.utf8_decode($do['color_name'])));
                    // $this->pdf->Text(42,16,$do['mechanism']);
                    // $this->pdf->Text(4,20,'CONTROL: ');
                    // $this->pdf->Text(42,20,utf8_decode(strtoupper($do['mechanism_side'])));

                    // // LEFT
                    // $this->pdf->Text(62,9,utf8_decode('Medidas'));
                    // $this->pdf->Text(62,13,$do['width'].' x '.$do['height']);

                    // // code
                    // $this->pdf->Code128(58, 16, $do['id'], 18, 6);
                    // $this->pdf->Text(60,25,Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('Y-m-d'));
                }

                $nameFile = "etiquetas-componentes-";
            break;
        }
        // $file = $this->pdf->Output("") ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile.'MR'.$materialRequestID."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createperfilesgalFile($materialRequestID,$production_date,$detailOrdersLambre,$detailOrdersCorba,$detailOrdersFijo,$detailClothsOrders,$type)
    {
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // FILE

                $nameFile = "corte-perfilesgal-";
            break;
            case 2: // LABELS

                $this->pdf->SetAutoPageBreak(true,2);
                foreach ($detailOrdersLambre as $key => $do) {
                    if( (INT)$do['capture_id'] === 1 ) {
                        $this->pdf->AddPage();

                        $this->pdf->SetFont("Arial", "B", 9);
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Cell(20, 4, $do['order_id'].' - '.$do['item_id'], 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 9);
                        $product = 'ENR';
                        if($do['product_id'] == 2) {$product = 'SHE'; }
                        $this->pdf->SetXY(21, 1);
                        $this->pdf->Cell(15, 4, $product, 0, 1, "C", false);
                        $this->pdf->SetXY(36, 1);
                        $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('d-m-y'), 0, 1, "C", false);
                        $this->pdf->SetXY(54, 1);
                        $this->pdf->Cell(22, 4, number_format($do['width'],3).' x '.number_format($do['height'],3), 0, 1, "C", false);
                        // ARTICLE
                        if( (INT)$do['is_velcro'] === 1 ) {
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(1, 5);
                            $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['article_velcro']))), 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(56, 5);
                            $this->pdf->Cell(20, 4, number_format($do['width_discount_velcro'],3), 0, 1, "C", false);
                        } else {
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(1, 5);
                            $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['article_riel']))), 0, 1, "L", false);
                            $this->pdf->SetFont("Arial", "", 8);
                            $this->pdf->SetXY(56, 5);
                            $this->pdf->Cell(20, 4, number_format($do['width_discount_riel'],3), 0, 1, "C", false);
                        }
                        //
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(1, 9);
                        $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do['article_base']))), 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(56, 9);
                        $this->pdf->Cell(20, 4, number_format($do['width_discount_base'],3), 0, 1, "C", false);
                        //
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(63,24,'Perfil/Gal');
                    }
                }

                foreach ($detailOrdersCorba as $key => $do2) {
                    if( (INT)$do['capture_id'] === 1 ) {
                        $this->pdf->AddPage();

                        $this->pdf->SetFont("Arial", "B", 9);
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Cell(20, 4, $do2['order_id'].' - '.$do2['item_id'], 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 9);
                        $product = 'ENR';
                        if($do2['product_id'] == 2) {$product = 'SHE'; }
                        $this->pdf->SetXY(21, 1);
                        $this->pdf->Cell(15, 4, $product, 0, 1, "C", false);
                        $this->pdf->SetXY(36, 1);
                        $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $do2['created_at'])->format('d-m-y'), 0, 1, "C", false);
                        $this->pdf->SetXY(54, 1);
                        $this->pdf->Cell(22, 4, number_format($do2['width'],3).' x '.number_format($do2['height'],3), 0, 1, "C", false);
                        // ARTICLE
                        $this->pdf->SetXY(1, 5);
                        $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do2['article_velcro']))), 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(56, 5);
                        $this->pdf->Cell(20, 4, number_format($do2['width_discount_velcro'],3), 0, 1, "C", false);
                        //
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(1, 9);
                        $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do2['article_base']))), 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(56, 9);
                        $this->pdf->Cell(20, 4, number_format($do2['width_discount_base'],3), 0, 1, "C", false);
                        //
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(63,24,'Perfil/Gal');
                    }
                }
                // FIJO
                foreach ($detailOrdersFijo as $key => $do2) {
                    if( (INT)$do['capture_id'] === 1 ) {
                        $this->pdf->AddPage();

                        $this->pdf->SetFont("Arial", "B", 9);
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Cell(20, 4, $do2['order_id'].' - '.$do2['item_id'], 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 9);
                        $product = 'ENR';
                        if($do2['product_id'] == 2) {$product = 'SHE'; }
                        $this->pdf->SetXY(21, 1);
                        $this->pdf->Cell(15, 4, $product, 0, 1, "C", false);
                        $this->pdf->SetXY(36, 1);
                        $this->pdf->Cell(18, 4, Carbon::createFromFormat('Y-m-d H:i:s', $do2['created_at'])->format('d-m-y'), 0, 1, "C", false);
                        $this->pdf->SetXY(54, 1);
                        $this->pdf->Cell(22, 4, number_format($do2['width'],3).' x '.number_format($do2['height'],3), 0, 1, "C", false);
                        // ARTICLE
                        $this->pdf->SetXY(1, 5);
                        $this->pdf->Cell(50, 4, utf8_decode(ucfirst(strtolower($do2['article_velcro']))), 0, 1, "L", false);
                        $this->pdf->SetFont("Arial", "", 8);
                        $this->pdf->SetXY(56, 5);
                        $this->pdf->Cell(20, 4, number_format($do2['width_discount_velcro'],3), 0, 1, "C", false);
                        //
                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(63,24,'Perfil/Gal');
                    }
                }
                $nameFile = "etiquetas-perfilesgal-";
            break;
        }
        // $file = $this->pdf->Output("") ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile.'MR'.$materialRequestID."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }

    public function createPackagingModulation($materialRequestID,$production_date,$orders,$detailOrders,$type)
    {
        $nameFile = '';
        switch ((INT)$type) {
            case 1: // FILE
                $nameFile = "corte-empaque-";
            break;
            case 2: // LABELS
                $this->pdf->SetAutoPageBreak(true,2);
                foreach ($orders as $key => $order) {
                    $totalPages  =  count($order['details']);
                    $pageCount = 1;
                    foreach ($order['details'] as $key => $do) {
                        $this->pdf->AddPage();
                        $this->pdf->SetFont("Arial", "B", 24);
                        $this->pdf->SetXY(1, 1);
                        $this->pdf->Cell(45, 15, $do['nomen'].$do['order_id'].' - '.$do['item_id'], 0, 1, "L", false);
                        // created at
                        $this->pdf->SetFont("Arial", "", 17);
                        $this->pdf->SetXY(46, 5);
                        $this->pdf->Cell(30, 8, Carbon::createFromFormat('Y-m-d H:i:s', $do['created_at'])->format('d-m-y'), 0, 1, "C", false);
                        // ARTICLE
                        $this->pdf->SetFont("Arial", "", 10);
                        $product = 'ENROLLABLE ';
                        if($do['product_id'] == 2) {$product = ''; }
                        $this->pdf->SetXY(1, 14);
                        $this->pdf->MultiCell(75, 4, $product.$do['article'], 0, "C", false);
                        // MEd
                        $this->pdf->SetFont("Arial", "B", 20);
                        $this->pdf->SetXY(1, 23);
                        $heightFinal = $do['height'];
                        if($do['relation_heat_seal'] > 0) {
                            $othersClothsSum = $this->foundThermo($do['relation_heat_seal'],$detailOrders);
                            $heightFinal = $heightFinal + $othersClothsSum['height'];
                        }
                        $this->pdf->Cell(75, 6, number_format($do['width'],3).' x '.number_format($heightFinal,3), 0, 1, "C", false);
                        //  PAGES
                        $this->pdf->SetFont("Arial", "B", 30);
                        $this->pdf->SetXY(1, 29);
                        $this->pdf->Cell(75, 15, $pageCount.' de '.$totalPages, 0, 1, "C", false);
                        // AREA
                        $this->pdf->SetFont("Arial", "U", 16);
                        $this->pdf->SetXY(1, 43);
                        $this->pdf->MultiCell(75, 6, utf8_decode($do['area_description']), 0, "C", false);
                        //CODE
                        if($do['nomen'] == 'GLS') {
                            $this->pdf->Code128(17, 60, 'GLS '.$do['id'], 44, 10);
                            $this->pdf->SetFont("Arial", "", 6);
                            $this->pdf->SetXY(17, 70);
                            $this->pdf->Cell(44, 3, 'GLS '.$do['id'], 0, 1, "C", false);
                        } else {
                            $this->pdf->Code128(17, 60, $do['id'], 44, 10);
                            $this->pdf->SetFont("Arial", "", 6);
                            $this->pdf->SetXY(17, 70);
                            $this->pdf->Cell(44, 3, $do['id'], 0, 1, "C", false);
                        }

                        $this->pdf->SetFont("Arial", "B", 8);
                        $this->pdf->Text(63,74,'Empaque');

                        $pageCount++;
                    }

                }
                $nameFile = "etiquetas-empaque-";
            break;
        }
        // $file = $this->pdf->Output("") ;
        // exit;
        $file = $this->pdf->Output("S") ;
        return [
            "name" => $nameFile.'MR'.$materialRequestID."-".$production_date.".pdf",
            "file" => base64_encode($file),
        ];
    }


    // PRIVATEE
    private function getchainSize($height_chain,$height,$mechanism_id,$relation_cassette,$tube_id,$product_id) {
        $heightChain = '';
        if( (DOUBLE)$height_chain !== (DOUBLE)$height AND (INT)$product_id === 1 ) {
            $heightChain = number_format(($height_chain * 2),3);
        } else {
            if((INT)$relation_cassette > 0 AND (INT)$tube_id === 3 ) {
                $heightChain = number_format(($height_chain * 2),3);
            } else if((INT)$mechanism_id === 2 AND (INT)$relation_cassette > 0 AND (INT)$tube_id !== 3 ) {
                if((INT)$product_id === 2) {
                    $heightChain = number_format(($height_chain * 2),3);
                } else {
                    $heightChain = number_format(($height_chain * 1.6),3);
                }
            } else if((INT)$mechanism_id === 3 ) {
                if((DOUBLE)$height_chain !== (DOUBLE)$height ) {
                    $heightChain = number_format(($height_chain * 2),3);
                } else {
                    $heightChain = number_format(($height_chain * 1.6),3);
                }
            } else if((INT)$mechanism_id === 6 ) {
                $heightChain = number_format((($height_chain * 2)*1.1),3);
            } else {
                $heightChain = number_format(($height_chain * 2),3);
            }
        }
        return $heightChain;
    }
    private function fountLambrequin($relation_lambrequin,$details) {
        $lambrequin = [];
        foreach ($details as  $item) {
            if( (INT)$item['relation_lambrequin'] === (INT)$relation_lambrequin AND $item['product_id'] === 4  ) {
                $lambrequin = $item;
            }
        }
        return $lambrequin;
    }
    private function foundThermo($relation_heat_seal,$details) {
        $heatSeal = [];
        foreach ($details as  $item) {
            if( (INT)$item['relation_heat_seal'] === (INT)$relation_heat_seal AND $item['product_id'] === 5  ) {
                $heatSeal = $item;
            }
        }
        return $heatSeal;
    }

    private function getComment($item,$cuts) {
        $textDetail = '';
        switch ((INT)$item['product_id']) {
            case 1:
                if((INT)$item['is_inverted'] === 1) { $textDetail .= 'Rotar tela, '; }
                if((INT)$item['relation_cassette'] > 0) {
                    if((INT)$item['divisions'] > 1 ) {
                        if((INT)$item['relation_cassette'] > 0) { $textDetail .= $this->isCasseteDetail($item,$cuts); }
                    } else {
                        $textDetail .= 'Con cassette, ';
                    }
                }
                if((INT)$item['relation_lambrequin'] > 0) { $textDetail .= 'Con Lambrequin, '; }
                if((INT)$item['relation_bracket'] > 0) { $textDetail .= $this->isBracketRelation($item,$cuts); }
            break;
            case 2:
                if((INT)$item['divisions'] > 1 ) { $textDetail .= $this->isCasseteDetail($item,$cuts); }
            break;
        }

        $textDetail .= $item['commit_client'] !== null ? ' '.$item['commit_client'] : '';
        return substr($textDetail,0,-2);

    }

    private function getFullComment($item,$cuts) {
        $textDetail = '';
        switch ((INT)$item['product_id']) {
            case 1:
                if((INT)$item['is_inverted'] === 1) { $textDetail .= 'Rotar tela, '; }
                if((INT)$item['is_heat_seal'] === 1) { $textDetail .= 'Termosellada, '; }
                if((INT)$item['relation_cassette'] > 0) { if((INT)$item['divisions'] > 1 ) { if((INT)$item['relation_cassette'] > 0) { $textDetail .= $this->isCasseteDetail($item,$cuts); } } else { $textDetail .= 'Con cassette, '; } }
                if((INT)$item['relation_lambrequin'] > 0) { $textDetail .= 'Con Lambrequin, '; }
                if((INT)$item['relation_bracket'] > 0) { $textDetail .= $this->isBracketRelation($item,$cuts); }
                if((INT)$item['relation_bracket_dn'] > 0 ) { $textDetail .= 'En Soporte dia y noche, '; }
                if((INT)$item['relation_control'] > 0 ) { $textDetail .= 'Asignado a control, canal '.$item['channel'].', ';  }
                if((INT)$item['mechanism_id'] > 0 ) { $textDetail .= $item['mechanism'].', '; }
                $textDetail .= $item['tube'].', ';
            break;
            case 2:
                if((INT)$item['divisions'] > 1 ) { $textDetail .= $this->isCasseteDetail($item,$cuts); }
                if((INT)$item['relation_bracket_dn'] > 0 ) { $textDetail .= 'En Soporte dia y noche, '; }
                if((INT)$item['mechanism_id'] > 0 ) { $textDetail .= $item['mechanism'].', '; }
                $textDetail .= $item['tube'].', ';
            break;
            case 4:
                // Si es un cassete
                if((INT)$item['relation_cassette'] > 0) { $textDetail .= $this->isCasseteDetail($item,$cuts); }
                // Si es un lambrequin
                if((INT)$item['relation_lambrequin'] > 0) { $textDetail .= $this->isLambrequinDetail($item,$cuts); }
                // Si es un motor
                if((INT)$item['relation_motor'] > 0) { $textDetail .= $this->isMotorDetail($item,$cuts); }
                // accesorio relacionado
                if((INT)$item['relation_accesories'] > 0) { $textDetail .= $this->isAccesoriesRelation($item,$cuts); }
                // accesorio relacionado
                if((INT)$item['relation_heat_seal'] > 0) { $textDetail .= $this->isServicioThermoRelation($item,$cuts); }
                // Soporte dia y noche
                if((INT)$item['relation_bracket_dn'] > 0 ) { $textDetail .= $this->isBracketDNRelation($item,$cuts); }
                // Control
                if((INT)$item['relation_control'] > 0 ) { $textDetail .= $this->isControlRelation($item,$cuts); }
                // is Velcro
                if((INT)$item['is_velcro'] === 1) { $textDetail .= 'Con velcro, '; }
            break;
            case 5:
                if((INT)$item['is_heat_seal'] === 1) {  $textDetail .= $this->isThermoRelation($item,$cuts); }
            break;
        }

        $textDetail .= $item['commit_client'] !== null ? ' '.$item['commit_client'] : '';
        return substr($textDetail,0,-2);

    }

    private function isCasseteDetail($item,$cuts) {

        if( (INT)$item['product_id'] === 1 OR (INT)$item['product_id'] === 2 ) { $textDetail = 'Misma fascia [ '; } else { $textDetail = 'Fascia para [ '; }
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_cassette'] === (INT)$item['relation_cassette'] AND  (INT)$cut['order_id'] === (INT)$item['order_id'] AND ( (INT)$cut['product_id'] === 1 OR (INT)$cut['product_id'] === 2 ) ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function isBracketRelation($item,$cuts) {
        $textDetail = 'Soporte intermedio [ ';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_bracket'] === (INT)$item['relation_bracket'] AND  (INT)$cut['order_id'] === (INT)$item['order_id'] ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function isLambrequinDetail($item,$cuts) {
        $textDetail = 'Lambrequin para partida [ ';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if((INT)$cut['relation_lambrequin'] === (INT) $item['relation_lambrequin'] ) {
                if((INT)$cut['product_id'] === 1 ) {
                    $numberItems .= $cut['item_id'].',';
                }
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }


    private function isMotorDetail($item,$cuts) {
        $textDetail = 'Motores para partidas [';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_motor'] === (INT)$item['relation_motor'] AND (INT)$cut['order_id'] === (INT)$item['order_id']  AND ( (INT)$cut['product_id'] === 1 OR  (INT)$cut['product_id'] === 2 ) ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function isAccesoriesRelation($item,$cuts) {
        $textDetail = 'Adecuación partidas [';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_accesories'] === (INT)$item['relation_accesories'] AND (INT)$cut['order_id'] === (INT)$item['order_id'] AND ( (INT)$cut['product_id'] === 1 OR  (INT)$cut['product_id'] === 2 ) ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function isThermoRelation($item,$cuts) {
        $textDetail = 'Lienzo a termosellar con partida [';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_heat_seal'] === (INT)$item['relation_heat_seal'] AND (INT)$cut['order_id'] === (INT)$item['order_id'] AND ( (INT)$cut['product_id'] === 1 OR  (INT)$cut['product_id'] === 2 ) ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function isServicioThermoRelation($item,$cuts) {
        $textDetail = 'Servicio para partida [';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_heat_seal'] === (INT)$item['relation_heat_seal'] AND (INT)$cut['order_id'] === (INT)$item['order_id']  AND ( (INT)$cut['product_id'] === 1 OR  (INT)$cut['product_id'] === 2 ) ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }


    private function isBracketDNRelation($item,$cuts) {
        $textDetail = 'Soporte día y noche partidas [';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_bracket_dn'] === (INT)$item['relation_bracket_dn'] AND (INT)$cut['order_id'] === (INT)$item['order_id']  AND ( (INT)$cut['product_id'] === 1 OR  (INT)$cut['product_id'] === 2 ) ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function isControlRelation($item,$cuts) {
        $textDetail = 'Control para partidas [';
        $numberItems = '';
        foreach ($cuts as $key => $cut) {
            if( (INT)$cut['relation_control'] === (INT)$item['relation_control'] AND (INT)$cut['order_id'] === (INT)$item['order_id'] AND ( (INT)$cut['product_id'] === 1 OR  (INT)$cut['product_id'] === 2 ) ) {
                $numberItems .= $cut['item_id'].',';
            }
        }
        $textDetail .= substr($numberItems,0,-1).' ], ' ;
        return $textDetail;
    }

    private function cutFileLandscapeHeader($section,$x,$materialRequestID,$production_date) {

        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);

        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");

        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(249,14,"Fecha:");
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->Text(264,8,"MR-".$materialRequestID);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Text(264,14,$production_date);
        $this->pdf->SetFont("Arial", "", 9);

        $this->pdf->setFillColor(211,211,211);
        $this->pdf->SetXY(2, 20);
        $this->pdf->Cell(23, 8, 'Pedido', 1, 1, "C", true);
        $this->pdf->SetXY(24, 20);
        $this->pdf->Cell(14, 8, 'Item ID', 1, 1, "C", true);
        $this->pdf->SetXY(37, 20);
        $this->pdf->Cell(63, 8, utf8_decode('Artículo') , 1, 1, "C", true);
        $this->pdf->SetXY(100, 20);
        $this->pdf->Cell(15, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(115, 20);
        $this->pdf->Cell(15, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(130, 20);
        $this->pdf->Cell(30, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(160, 20);
        $this->pdf->Cell(20, 8, utf8_decode('Operación') , 1, 1, "C", true);
        $this->pdf->SetXY(180, 20);
        $this->pdf->Cell(20, 8, 'Base' , 1, 1, "C", true);
        $this->pdf->SetXY(200, 20);
        $this->pdf->Cell(20, 8, 'Control' , 1, 1, "C", true);
        $this->pdf->SetXY(220, 20);
        $this->pdf->Cell(20, 8, utf8_decode('Caída') , 1, 1, "C", true);
        $this->pdf->SetXY(240, 20);
        $this->pdf->Cell(15, 8, utf8_decode('A. Cad.') , 1, 1, "C", true);
        $this->pdf->SetXY(255, 20);
        $this->pdf->Cell(25, 8, utf8_decode('Mecanismo') , 1, 1, "C", true);
        $this->pdf->SetXY(280, 20);
        $this->pdf->Cell(15, 8, utf8_decode('Ubica') , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function cutFileOrdersHeader($section,$x,$materialRequestID,$production_date) {

        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);

        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");

        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(249,14,"Fecha:");
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->Text(264,8,"MR-".$materialRequestID);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Text(264,14,$production_date);
        $this->pdf->SetFont("Arial", "", 9);

        $this->pdf->setFillColor(211,211,211);
        $this->pdf->SetXY(2, 20);
        $this->pdf->Cell(24, 8, 'Pedido', 1, 1, "C", true);
        $this->pdf->SetXY(25, 20);
        $this->pdf->Cell(14, 8, 'Item ID', 1, 1, "C", true);
        $this->pdf->SetXY(39, 20);
        $this->pdf->Cell(63, 8, utf8_decode('Artículo') , 1, 1, "C", true);
        $this->pdf->SetXY(102, 20);
        $this->pdf->Cell(14, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(116, 20);
        $this->pdf->Cell(14, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(130, 20);
        $this->pdf->Cell(30, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(160, 20);
        $this->pdf->Cell(20, 8, utf8_decode('Operación') , 1, 1, "C", true);
        $this->pdf->SetXY(180, 20);
        $this->pdf->Cell(20, 8, 'Base' , 1, 1, "C", true);
        $this->pdf->SetXY(200, 20);
        $this->pdf->Cell(20, 8, 'Control' , 1, 1, "C", true);
        $this->pdf->SetXY(220, 20);
        $this->pdf->Cell(20, 8, utf8_decode('Caída') , 1, 1, "C", true);
        $this->pdf->SetXY(240, 20);
        $this->pdf->Cell(15, 8, utf8_decode('A. Cad.') , 1, 1, "C", true);
        $this->pdf->SetXY(255, 20);
        $this->pdf->Cell(10, 8, utf8_decode('Cant.') , 1, 1, "C", true);
        $this->pdf->SetXY(265, 20);
        $this->pdf->Cell(15, 8, utf8_decode('Ubica') , 1, 1, "C", true);
        $this->pdf->SetXY(280, 20);
        $this->pdf->Cell(15, 8, utf8_decode('Check') , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function tubesFileLandscapeHeader($section,$x,$materialRequestID,$production_date) {

        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);

        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");

        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(249,14,"Fecha:");
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->Text(264,8,"MR-".$materialRequestID);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Text(264,14,$production_date);
        $this->pdf->SetFont("Arial", "", 9);

        $this->pdf->setFillColor(211,211,211);
        $this->pdf->SetXY(2, 20);
        $this->pdf->Cell(23, 8, 'Pedido', 1, 1, "C", true);
        $this->pdf->SetXY(24, 20);
        $this->pdf->Cell(14, 8, 'Item ID', 1, 1, "C", true);
        $this->pdf->SetXY(38, 20);
        $this->pdf->Cell(15, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(53, 20);
        $this->pdf->Cell(15, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(68, 20);
        $this->pdf->Cell(30, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(98, 20);
        $this->pdf->Cell(40, 8, utf8_decode('Color') , 1, 1, "C", true);
        $this->pdf->SetXY(138, 20);
        $this->pdf->Cell(39, 8, 'Tubo' , 1, 1, "C", true);
        $this->pdf->SetXY(177, 20);
        $this->pdf->Cell(39, 8, 'Perfil' , 1, 1, "C", true);
        $this->pdf->SetXY(216, 20);
        $this->pdf->Cell(39, 8, 'Contrapeso' , 1, 1, "C", true);
        $this->pdf->SetXY(255, 20);
        $this->pdf->Cell(39, 8, 'Eje' , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function lambrequinFileLandscapeHeader($section,$x,$materialRequestID,$production_date) {

        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);

        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");

        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(249,14,"Fecha:");
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->Text(264,8,"MR-".$materialRequestID);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Text(264,14,$production_date);
        $this->pdf->SetFont("Arial", "", 9);

        $this->pdf->setFillColor(211,211,211);
        $this->pdf->SetXY(2, 20);
        $this->pdf->Cell(23, 8, 'Pedido', 1, 1, "C", true);
        $this->pdf->SetXY(24, 20);
        $this->pdf->Cell(14, 8, 'Item ID', 1, 1, "C", true);
        $this->pdf->SetXY(38, 20);
        $this->pdf->Cell(15, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(53, 20);
        $this->pdf->Cell(15, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(68, 20);
        $this->pdf->Cell(80, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(148, 20);
        $this->pdf->Cell(40, 8, utf8_decode('Color') , 1, 1, "C", true);
        $this->pdf->SetXY(188, 20);
        $this->pdf->Cell(53, 8, 'Contrapeso' , 1, 1, "C", true);
        $this->pdf->SetXY(241, 20);
        $this->pdf->Cell(53, 8, 'Accesorio' , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function corbatinFileLandscapeHeader($section,$x,$materialRequestID,$production_date) {

        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);

        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");

        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(249,14,"Fecha:");
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->Text(264,8,"MR-".$materialRequestID);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Text(264,14,$production_date);
        $this->pdf->SetFont("Arial", "", 9);

        $this->pdf->setFillColor(211,211,211);
        $this->pdf->SetXY(2, 20);
        $this->pdf->Cell(23, 8, 'Pedido', 1, 1, "C", true);
        $this->pdf->SetXY(24, 20);
        $this->pdf->Cell(14, 8, 'Item ID', 1, 1, "C", true);
        $this->pdf->SetXY(38, 20);
        $this->pdf->Cell(15, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(53, 20);
        $this->pdf->Cell(15, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(68, 20);
        $this->pdf->Cell(80, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(148, 20);
        $this->pdf->Cell(40, 8, utf8_decode('Color') , 1, 1, "C", true);
        $this->pdf->SetXY(188, 20);
        $this->pdf->Cell(53, 8, 'Contrapeso' , 1, 1, "C", true);
        $this->pdf->SetXY(241, 20);
        $this->pdf->Cell(53, 8, 'Velcro' , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function fijoFileLandscapeHeader($section,$x,$materialRequestID,$production_date) {

        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);

        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");

        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(249,14,"Fecha:");
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->Text(264,8,"MR-".$materialRequestID);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Text(264,14,$production_date);
        $this->pdf->SetFont("Arial", "", 9);

        $this->pdf->setFillColor(211,211,211);
        $this->pdf->SetXY(2, 20);
        $this->pdf->Cell(23, 8, 'Pedido', 1, 1, "C", true);
        $this->pdf->SetXY(24, 20);
        $this->pdf->Cell(14, 8, 'Item ID', 1, 1, "C", true);
        $this->pdf->SetXY(38, 20);
        $this->pdf->Cell(15, 8, 'Ancho' , 1, 1, "C", true);
        $this->pdf->SetXY(53, 20);
        $this->pdf->Cell(15, 8, 'Alto' , 1, 1, "C", true);
        $this->pdf->SetXY(68, 20);
        $this->pdf->Cell(120, 8, 'Producto' , 1, 1, "C", true);
        $this->pdf->SetXY(188, 20);
        $this->pdf->Cell(50, 8, utf8_decode('Color') , 1, 1, "C", true);
        $this->pdf->SetXY(238, 20);
        $this->pdf->Cell(53, 8, 'Velcro' , 1, 1, "C", true);
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function cutGraficFileLandscapeHeader($section,$x,$materialRequestID,$production_date) {
        $this->pdf->AddPage();
        $this->pdf->SetXY(1, 1);
        $this->pdf->Image("img/lansonshades.jpeg", 4, 2, 0, 10);
        $this->pdf->SetFont("Arial", "", 15);
        $this->pdf->SetXY(17, 5.5);
        $this->pdf->Cell($x, 4,$section, 0, 0, "L");
        $this->pdf->SetFont("Arial", "", 12);
        $this->pdf->Text(249,14,"Fecha:");
        $this->pdf->SetFont("Arial", "B", 12);
        $this->pdf->SetTextColor(255, 4, 0);
        $this->pdf->Text(249,8,"MR-".$materialRequestID);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Text(264,14,$production_date);
        $this->pdf->SetFont("Arial", "", 9);
    }
}