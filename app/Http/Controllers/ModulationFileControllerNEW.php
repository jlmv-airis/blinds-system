<?php

namespace App\Http\Controllers;

require app_path() . "/fpdf/fpdf.php";
require app_path() . "/fpdf/PDF_Code128.php";

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\classes\FPDF;
use App\classes\FPDFMR;
use App\Models\DOrder;
use App\Models\EMaterialRequest;
use PDF_Code128;

class ModulationFileController extends Controller
{
    public function downloadRequestFile($user_id,$file,$type,$materialRequestID) {

        switch ($file) {
            case 'tubes':
                $materialRequest = EMaterialRequest::select('production_date')->where('id',$materialRequestID)->first();
                // DETAILS
                $detailOrders = [];
                $statementOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",1,0,0,0.0,0.0,0.0,'','','','','')");
                $statementOrders->execute();
                do {  $resultOrders[] = $statementOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementOrders->nextRowSet());
                foreach (json_decode(json_encode($resultOrders[0]), true)  as $value) { $detailOrders[] = $value; }
                // TUBOS
                $tubeIDData = [];
                $statementTubes = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",2,0,0,0.0,0.0,0.0,'','','','','')");
                $statementTubes->execute();
                do {  $resultsTubes[] = $statementTubes->fetchAll(\PDO::FETCH_OBJ); } while ($statementTubes->nextRowSet());
                foreach (json_decode(json_encode($resultsTubes[0]), true)  as $value) { $tubeIDData[] = $value; }
                // PERFILES
                $perfilData = [];
                $statementPerf = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",3,0,0,0.0,0.0,0.0,'','','','','')");
                $statementPerf->execute();
                do {  $resultsPerf[] = $statementPerf->fetchAll(\PDO::FETCH_OBJ); } while ($statementPerf->nextRowSet());
                foreach (json_decode(json_encode($resultsPerf[0]), true)  as $value) { $perfilData[] = $value; }
                // BARRAS GIRO
                $twistbarData = [];
                $statementBG = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",4,0,0,0.0,0.0,0.0,'','','','','')");
                $statementBG->execute();
                do {  $resultsBG[] = $statementBG->fetchAll(\PDO::FETCH_OBJ); } while ($statementBG->nextRowSet());
                foreach (json_decode(json_encode($resultsBG[0]), true)  as $value) { $twistbarData[] = $value; }
                // CONTERWEIGHTS
                $counterweightData = [];
                $statementCW = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",5,0,0,0.0,0.0,0.0,'','','','','')");
                $statementCW->execute();
                do {  $resultsCW[] = $statementCW->fetchAll(\PDO::FETCH_OBJ); } while ($statementCW->nextRowSet());
                foreach (json_decode(json_encode($resultsCW[0]), true)  as $value) { $counterweightData[] = $value; }
                // LAMBREQUIN
                $lambrequinData = [];
                $statementLB = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",10,0,0,0.0,0.0,0.0,'','','','','')");
                $statementLB->execute();
                do {  $resultsLB[] = $statementLB->fetchAll(\PDO::FETCH_OBJ); } while ($statementLB->nextRowSet());
                foreach (json_decode(json_encode($resultsLB[0]), true)  as $value) { $lambrequinData[] = $value; }
                // CORBATIN
                $corbatinData = [];
                $statementCB = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",11,0,0,0.0,0.0,0.0,'','','','','')");
                $statementCB->execute();
                do {  $resultsCB[] = $statementCB->fetchAll(\PDO::FETCH_OBJ); } while ($statementCB->nextRowSet());
                foreach (json_decode(json_encode($resultsCB[0]), true)  as $value) { $corbatinData[] = $value; }
                // FIJO
                $fijoData = [];
                $statementFJ = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",14,0,0,0.0,0.0,0.0,'','','','','')");
                $statementFJ->execute();
                do {  $resultsFJ[] = $statementFJ->fetchAll(\PDO::FETCH_OBJ); } while ($statementFJ->nextRowSet());
                foreach (json_decode(json_encode($resultsFJ[0]), true)  as $value) { $fijoData[] = $value; }
                // DETAILS
                $detailClothsOrders = [];
                $statementClothsOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",9,0,0,0.0,0.0,0.0,'','','','','')");
                $statementClothsOrders->execute();
                do {  $resultClothsOrders[] = $statementClothsOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementClothsOrders->nextRowSet());
                foreach (json_decode(json_encode($resultClothsOrders[0]), true)  as $value) { $detailClothsOrders[] = $value; }
                // ALL DATA
                foreach ($detailOrders as $key => $do) {

                    $detailOrders[$key]['tube_id'] = null;
                    $detailOrders[$key]['tube'] = null;
                    $detailOrders[$key]['tube_width_discount'] = null;
                    $detailOrders[$key]['perfil'] = null;
                    $detailOrders[$key]['perfil_width_discount'] = null;
                    $detailOrders[$key]['counterweight'] = null;
                    $detailOrders[$key]['counterweight_width_discount'] = null;
                    $detailOrders[$key]['twistbar'] = null;
                    $detailOrders[$key]['twistbar_width_discount'] = null;

                    if(COUNT($tubeIDData) > 0 AND ( (INT)$do['tube_idt'] === 1 OR  (INT)$do['damage_tube'] === 1 ) ) {
                        foreach ($tubeIDData as $tube) {
                            if($tube['id'] === (INT)$do['id']) {
                                $detailOrders[$key]['tube_id'] = $tube['tube_id'];
                                $detailOrders[$key]['tube'] = $tube['tube'];
                                $detailOrders[$key]['tube_width_discount'] = $tube['width_discount'];
                            }
                        }
                    }
                    if(COUNT($perfilData) > 0 AND (INT)$do['damage_fascia'] === 1 ) {
                        foreach ($perfilData as $perfil) {
                            if($perfil['id'] === (INT)$do['id']) {
                                $detailOrders[$key]['perfil'] = $perfil['perfil'];
                                $detailOrders[$key]['perfil_width_discount'] = $perfil['width_discount'];
                            }
                        }
                    }
                    if(COUNT($counterweightData) > 0 AND ( (INT)$do['counterweight_idt'] === 1 OR  (INT)$do['damage_counterweight'] === 1 ) ) {
                        foreach ($counterweightData as $counterweight) {
                            if($counterweight['id'] === (INT)$do['id']) {
                                $detailOrders[$key]['counterweight'] = $counterweight['article'];
                                $detailOrders[$key]['counterweight_width_discount'] = $counterweight['width_discount'];
                            }
                        }
                    }
                    if(COUNT($twistbarData) > 0 AND $do['nomen'] == 'LS' ) {
                        foreach ($twistbarData as $twistbar) {
                            if($twistbar['id'] === (INT)$do['id']) {
                                $detailOrders[$key]['twistbar'] = $twistbar['article'];
                                $detailOrders[$key]['twistbar_width_discount'] = $twistbar['width_discount'];
                            }
                        }
                    }
                }
                switch ((INT)$type) {
                    case 1: // Files
                        $pdf = new FPDFMR(new PDF_Code128("L", "mm", "A4"));
                        return $pdf->createTubeFile($materialRequestID,$materialRequest->production_date,$detailOrders,$lambrequinData,$corbatinData,$fijoData,$detailClothsOrders,$type);
                    break;
                    case 2: // Stickers
                        $pdf = new FPDFMR(new PDF_Code128('L','mm',[26,77]));
                        return $pdf->createTubeFile($materialRequestID,$materialRequest->production_date,$detailOrders,$lambrequinData,$corbatinData,$fijoData,$detailClothsOrders,$type);
                    break;
                }
            break;
            case 'perfilesgal':
                $materialRequest = EMaterialRequest::select('production_date')->where('id',$materialRequestID)->first();
                // DETAILS
                $detailOrdersLambre = [];
                $detailOrdersCorba = [];
                $detailOrdersFijo = [];
                // LAMBREQUIN
                $statementOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",10,0,0,0.0,0.0,0.0,'','','','','')");
                $statementOrders->execute();
                do {  $resultOrders[] = $statementOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementOrders->nextRowSet());
                foreach (json_decode(json_encode($resultOrders[0]), true)  as $value) { $detailOrdersLambre[] = $value; }
                // CORBATIN
                $statementOrders2 = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",11,0,0,0.0,0.0,0.0,'','','','','')");
                $statementOrders2->execute();
                do {  $resultOrders2[] = $statementOrders2->fetchAll(\PDO::FETCH_OBJ); } while ($statementOrders2->nextRowSet());
                foreach (json_decode(json_encode($resultOrders2[0]), true)  as $value) { $detailOrdersCorba[] = $value; }
                // FIJO
                $statementOrders3 = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",14,0,0,0.0,0.0,0.0,'','','','','')");
                $statementOrders3->execute();
                do {  $resultOrders3[] = $statementOrders3->fetchAll(\PDO::FETCH_OBJ); } while ($statementOrders3->nextRowSet());
                foreach (json_decode(json_encode($resultOrders3[0]), true)  as $value) { $detailOrdersFijo[] = $value; }
                // DETAILS
                $detailClothsOrders = [];
                $statementClothsOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",9,0,0,0.0,0.0,0.0,'','','','','')");
                $statementClothsOrders->execute();
                do {  $resultClothsOrders[] = $statementClothsOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementClothsOrders->nextRowSet());
                foreach (json_decode(json_encode($resultClothsOrders[0]), true)  as $value) { $detailClothsOrders[] = $value; }
                //
                switch ((INT)$type) {
                    case 1: // Files
                        $pdf = new FPDFMR(new PDF_Code128("L", "mm", "A4"));
                        return $pdf->createperfilesgalFile($materialRequestID,$materialRequest->production_date,$detailOrdersLambre,$detailOrdersCorba,$detailOrdersFijo,$detailClothsOrders,$type);
                    break;
                    case 2: // Stickers
                        $pdf = new FPDFMR(new PDF_Code128('L','mm',[26,77]));
                        return $pdf->createperfilesgalFile($materialRequestID,$materialRequest->production_date,$detailOrdersLambre,$detailOrdersCorba,$detailOrdersFijo,$detailClothsOrders,$type);
                    break;
                }
            break;
            case 'clothsgal':
                $materialRequest = EMaterialRequest::select('production_date')->where('id',$materialRequestID)->first();
                // DETAILS
                $detailOrdersLambre = [];
                $detailOrdersCorba = [];
                $detailOrdersFijo = [];
                // LAMBREQUIN
                $statementOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",10,0,0,0.0,0.0,0.0,'','','','','')");
                $statementOrders->execute();
                do {  $resultOrders[] = $statementOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementOrders->nextRowSet());
                foreach (json_decode(json_encode($resultOrders[0]), true)  as $value) { $detailOrdersLambre[] = $value; }
                // CORBATIN
                $statementOrders2 = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",11,0,0,0.0,0.0,0.0,'','','','','')");
                $statementOrders2->execute();
                do {  $resultOrders2[] = $statementOrders2->fetchAll(\PDO::FETCH_OBJ); } while ($statementOrders2->nextRowSet());
                foreach (json_decode(json_encode($resultOrders2[0]), true)  as $value) { $detailOrdersCorba[] = $value; }
                // FIJO
                $statementOrders3 = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",14,0,0,0.0,0.0,0.0,'','','','','')");
                $statementOrders3->execute();
                do {  $resultOrders3[] = $statementOrders3->fetchAll(\PDO::FETCH_OBJ); } while ($statementOrders3->nextRowSet());
                foreach (json_decode(json_encode($resultOrders3[0]), true)  as $value) { $detailOrdersFijo[] = $value; }
                switch ((INT)$type) {
                    case 1: // Files
                        $pdf = new FPDFMR(new PDF_Code128("L", "mm", "A4"));
                        return $pdf->createpclothsgalFile($materialRequestID,$materialRequest->production_date,$detailOrdersLambre,$detailOrdersCorba,$detailOrdersFijo,$type);
                    break;
                    case 2: // Stickers
                        $pdf = new FPDFMR(new PDF_Code128('L','mm',[26,77]));
                        return $pdf->createpclothsgalFile($materialRequestID,$materialRequest->production_date,$detailOrdersLambre,$detailOrdersCorba,$detailOrdersFijo,$type);
                    break;
                }
            break;
            case 'cloths':
                $materialRequest = EMaterialRequest::select('production_date')->where('id',$materialRequestID)->first();
                // DETAILS
                $detailOrders = [];
                $graficOrders = [];
                $statementOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",9,0,0,0.0,0.0,0.0,'','','','','')");
                $statementOrders->execute();
                do {  $resultOrders[] = $statementOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementOrders->nextRowSet());
                foreach (json_decode(json_encode($resultOrders[0]), true)  as $value) { $detailOrders[] = $value; }
                foreach (json_decode(json_encode($resultOrders[1]), true)  as $value) { $graficOrders[] = $value; }
                // GENERAL DETAILS
                $generalOrders = [];
                $statementGeneralOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",15,0,0,0.0,0.0,0.0,'','','','','')");
                $statementGeneralOrders->execute();
                do {  $resultGeneralOrders[] = $statementGeneralOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementGeneralOrders->nextRowSet());
                foreach (json_decode(json_encode($resultGeneralOrders[0]), true)  as $value) { $generalOrders[] = $value; }

                switch ((INT)$type) {
                    case 1: // Files
                        $pdf = new FPDFMR(new PDF_Code128("L", "mm", "A4"));
                        return $pdf->createclothFile($materialRequestID,$materialRequest->production_date,$generalOrders,$detailOrders,$graficOrders,$type);
                    break;
                    case 2: // Stickers
                        $pdf = new FPDFMR(new PDF_Code128('L','mm',[26,77]));
                        return $pdf->createclothFile($materialRequestID,$materialRequest->production_date,$generalOrders,$detailOrders,$graficOrders,$type);
                    break;
                }
            break;
            case 'accesories':
                $materialRequest = EMaterialRequest::select('production_date')->where('id',$materialRequestID)->first();
                // DATA
                $detailOrders = [];
                $statementOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",6,0,0,0.0,0.0,0.0,'','','','','')");
                $statementOrders->execute();
                do {  $resultOrders[] = $statementOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementOrders->nextRowSet());
                foreach (json_decode(json_encode($resultOrders[0]), true)  as $value) { $detailOrders[] = $value; }
                // DETAILS
                $detailClothsOrders = [];
                $statementClothsOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",9,0,0,0.0,0.0,0.0,'','','','','')");
                $statementClothsOrders->execute();
                do {  $resultClothsOrders[] = $statementClothsOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementClothsOrders->nextRowSet());
                foreach (json_decode(json_encode($resultClothsOrders[0]), true)  as $value) { $detailClothsOrders[] = $value; }
                // ACCESORIOS RELACIONADOS
                $accesoriesData = [];
                $statementAccesories = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",7,0,0,0.0,0.0,0.0,'','','','','')");
                $statementAccesories->execute();
                do {  $resultsAccesories[] = $statementAccesories->fetchAll(\PDO::FETCH_OBJ); } while ($statementAccesories->nextRowSet());
                foreach (json_decode(json_encode($resultsAccesories[0]), true)  as $value) { $accesoriesData[] = $value; }
                // ACCESORIOS APARTE
                $accAptData = [];
                $statementAccApt = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",16,0,0,0.0,0.0,0.0,'','','','','')");
                $statementAccApt->execute();
                do {  $resultsAccApt[] = $statementAccApt->fetchAll(\PDO::FETCH_OBJ); } while ($statementAccApt->nextRowSet());
                foreach (json_decode(json_encode($resultsAccApt[0]), true)  as $value) { $accAptData[] = $value; }

                foreach ($detailOrders as $key => $do) {
                    $detailOrders[$key]['items_acc'] = [];
                    if(COUNT($accesoriesData) > 0) {
                        foreach ($accesoriesData as $acc) {
                            if( (INT)$acc['detail_order_id'] === (INT)$do['id']) {
                                $detailOrders[$key]['items_acc'][] = $acc;
                            }
                        }
                    }
                }
                switch ((INT)$type) {
                    case 1: // Files
                        return app(FPDFMR::class)->createAccesoriesFile($materialRequestID,$materialRequest->production_date,$detailOrders,$detailClothsOrders,$accAptData,$type);
                    break;
                    case 2: // Stickers
                        $pdf = new FPDFMR(new PDF_Code128('L','mm',[77,77]));
                        return $pdf->createAccesoriesFile($materialRequestID,$materialRequest->production_date,$detailOrders,$detailClothsOrders,$accAptData,$type);
                    break;
                }
            break;
            case 'mechanisms':
                $materialRequest = EMaterialRequest::select('production_date')->where('id',$materialRequestID)->first();
                // DATA
                $detailOrders = [];
                $statementOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",8,0,0,0.0,0.0,0.0,'','','','','')");
                $statementOrders->execute();
                do {  $resultOrders[] = $statementOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementOrders->nextRowSet());
                foreach (json_decode(json_encode($resultOrders[0]), true)  as $value) { $detailOrders[] = $value; }
                // DETAILS
                $detailClothsOrders = [];
                $statementClothsOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",9,0,0,0.0,0.0,0.0,'','','','','')");
                $statementClothsOrders->execute();
                do {  $resultClothsOrders[] = $statementClothsOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementClothsOrders->nextRowSet());
                foreach (json_decode(json_encode($resultClothsOrders[0]), true)  as $value) { $detailClothsOrders[] = $value; }
                switch ((INT)$type) {
                    case 1: // Files
                        return app(FPDFMR::class)->createMechanimsFile($materialRequestID,$materialRequest->production_date,$detailOrders,$detailClothsOrders,$type);
                    break;
                    case 2: // Stickers
                        $pdf = new FPDFMR(new PDF_Code128('L','mm',[26,77]));
                        return $pdf->createMechanimsFile($materialRequestID,$materialRequest->production_date,$detailOrders,$detailClothsOrders,$type);
                    break;
                }
            break;
            case 'packaging':
                $materialRequest = EMaterialRequest::select('production_date')->where('id',$materialRequestID)->first();
                // DATA
                $orders = [];
                $detailOrders = [];
                $statementOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",12,0,0,0.0,0.0,0.0,'','','','','')");
                $statementOrders->execute();
                do {  $resultOrders[] = $statementOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementOrders->nextRowSet());
                foreach (json_decode(json_encode($resultOrders[0]), true)  as $value) { $orders[] = $value; }
                // DETAIL
                $statementDetailOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",13,0,0,0.0,0.0,0.0,'','','','','')");
                $statementDetailOrders->execute();
                do {  $resultDetailOrders[] = $statementDetailOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementDetailOrders->nextRowSet());
                foreach (json_decode(json_encode($resultDetailOrders[0]), true)  as $value) { $detailOrders[] = $value; }


                $detailClothsOrders = [];
                $statementClothsOrders = DB::getPdo()->prepare("CALL sp_modulation(14,".$materialRequestID.",9,0,0,0.0,0.0,0.0,'','','','','')");
                $statementClothsOrders->execute();
                do {  $resultClothsOrders[] = $statementClothsOrders->fetchAll(\PDO::FETCH_OBJ); } while ($statementClothsOrders->nextRowSet());
                foreach (json_decode(json_encode($resultClothsOrders[0]), true)  as $value) { $detailClothsOrders[] = $value; }


                foreach ($orders as $key => $do) {
                    $orders[$key]['details'] = [];
                    foreach ($detailOrders as $detail) {
                        if((INT)$detail['order_id'] == (INT)$do['id']) {
                            $orders[$key]['details'][] = $detail;
                        }
                    }
                }
                switch ((INT)$type) {
                    case 1: // Files
                        $pdf = new FPDFMR(new PDF_Code128("L", "mm", "A4"));
                        return $pdf->createPackagingModulation($materialRequestID,$materialRequest->production_date,$orders,$detailClothsOrders,$type);
                    break;
                    case 2: // Stickers
                        $pdf = new FPDFMR(new PDF_Code128('L','mm',[77,77]));
                        return $pdf->createPackagingModulation($materialRequestID,$materialRequest->production_date,$orders,$detailClothsOrders,$type);
                    break;
                }
            break;
        }
    }

    public function downloadModulationFiles($user_id,$file,$type,$productLineID) {

        // try {
            $production_date = '';
            $line = 1;
            switch ($file) {
            //     case 'tubes':
            //         $tubes = [];
            //         $tubeIDData = [];
            //         $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(12,".$productLineID.",1,0,0,0.0,0.0,0.0,'','','','','')");
            //         $statementAcc->execute();
            //         do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
            //         foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $tubes[] = $value; }
            //         foreach (json_decode(json_encode($resultsAcc[1]), true)  as $value) { $tubeIDData[] = $value; }
            //         // estructuramos la informacion la informacion
            //         foreach ($tubes as $key => $tube) {
            //             $tubes[$key]['items'] = [];
            //             foreach ($tubeIDData as $item) {
            //                 if((INT)$tube['tube_id'] === (INT)$item['tube_id'] ) {
            //                     $tubes[$key]['items'][] = $item;
            //                 }
            //             }
            //         }

            //         switch ((INT)$type) {
            //             case 1: // Files
            //                 return app(FPDF::class)->createTubeModulation($productLineID,$line,$production_date,$tubes,$type);
            //             break;
            //             case 2: // Stickers
            //                 $pdf = new FPDF(new PDF_Code128('L','mm',[26,77]));
            //                 return $pdf->createTubeModulation($productLineID,$line,$production_date,$tubes,$type);
            //             break;
            //         }
            //     break;
            //     case 'twistbar':
            //         $twistbars = [];
            //         $twistbarData = [];
            //         $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(12,".$productLineID.",2,0,0,0.0,0.0,0.0,'','','','','')");
            //         $statementAcc->execute();
            //         do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
            //         foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $twistbars[] = $value; }
            //         foreach (json_decode(json_encode($resultsAcc[1]), true)  as $value) { $twistbarData[] = $value; }
            //         // estructuramos la informacion la informacion
            //         foreach ($twistbars as $key => $twistbar) {
            //             $twistbars[$key]['items'] = [];
            //             foreach ($twistbarData as $item) {
            //                 if((INT)$twistbar['twistbar_id'] === (INT)$item['twistbar_id'] ) {
            //                     $twistbars[$key]['items'][] = $item;
            //                 }
            //             }
            //         }
            //         switch ((INT)$type) {
            //             case 1: // Files
            //                 return app(FPDF::class)->createTwistbarModulation($productLineID,$line,$production_date,$twistbars,$type);
            //             break;
            //             case 2: // Stickers
            //                 // $pdf = new FPDF(new PDF_Code128('L','mm',[65,100]));
            //                 $pdf = new FPDF(new PDF_Code128('L','mm',[26,77]));
            //                 return $pdf->createTwistbarModulation($productLineID,$line,$production_date,$twistbars,$type);
            //             break;
            //         }
            //     break;

                // Por el momento este imprime el archivo general
                case 'perfiles':
                    // TUBOS
                    $tubes = [];
                    $tubeIDData = [];
                    $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(12,".$productLineID.",1,0,0,0.0,0.0,0.0,'','','','','')");
                    $statementAcc->execute();
                    do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
                    foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $tubes[] = $value; }
                    foreach (json_decode(json_encode($resultsAcc[1]), true)  as $value) { $tubeIDData[] = $value; }
                    // estructuramos la informacion la informacion
                    foreach ($tubes as $key => $tube) {
                        $tubes[$key]['items'] = [];
                        foreach ($tubeIDData as $item) {
                            if((INT)$tube['tube_id'] === (INT)$item['tube_id'] ) {
                                $tubes[$key]['items'][] = $item;
                            }
                        }
                    }

                    // PERFILES

                    $perfiles = [];
                    $perfilData = [];
                    $statementPerf = DB::getPdo()->prepare("CALL sp_modulation(12,".$productLineID.",3,0,0,0.0,0.0,0.0,'','','','','')");
                    $statementPerf->execute();
                    do {  $resultsPerf[] = $statementPerf->fetchAll(\PDO::FETCH_OBJ); } while ($statementPerf->nextRowSet());
                    foreach (json_decode(json_encode($resultsPerf[0]), true)  as $value) { $perfiles[] = $value; }
                    foreach (json_decode(json_encode($resultsPerf[1]), true)  as $value) { $perfilData[] = $value; }
                    // estructuramos la informacion la informacion
                    foreach ($perfiles as $key => $perfil) {
                        $perfiles[$key]['items'] = [];
                        foreach ($perfilData as $item) {
                            if((INT)$perfil['perfil_color_id'] === (INT)$item['perfil_color_id'] ) {
                                $perfiles[$key]['items'][] = $item;
                            }
                        }
                    }

                    // BARRAS GIRO

                    $twistbars = [];
                    $twistbarData = [];
                    $statementBG = DB::getPdo()->prepare("CALL sp_modulation(12,".$productLineID.",2,0,0,0.0,0.0,0.0,'','','','','')");
                    $statementBG->execute();
                    do {  $resultsBG[] = $statementBG->fetchAll(\PDO::FETCH_OBJ); } while ($statementBG->nextRowSet());
                    foreach (json_decode(json_encode($resultsBG[0]), true)  as $value) { $twistbars[] = $value; }
                    foreach (json_decode(json_encode($resultsBG[1]), true)  as $value) { $twistbarData[] = $value; }
                    // estructuramos la informacion la informacion
                    foreach ($twistbars as $key => $twistbar) {
                        $twistbars[$key]['items'] = [];
                        foreach ($twistbarData as $item) {
                            if((INT)$twistbar['twistbar_id'] === (INT)$item['twistbar_id'] ) {
                                $twistbars[$key]['items'][] = $item;
                            }
                        }
                    }

                    // CONTERWEIGHTS

                    $counterweights = [];
                    $counterweightColors = [];
                    $counterweightData = [];
                    $statementCW = DB::getPdo()->prepare("CALL sp_modulation(12,".$productLineID.",4,0,0,0.0,0.0,0.0,'','','','','')");
                    $statementCW->execute();
                    do {  $resultsCW[] = $statementCW->fetchAll(\PDO::FETCH_OBJ); } while ($statementCW->nextRowSet());
                    foreach (json_decode(json_encode($resultsCW[0]), true)  as $value) { $counterweights[] = $value; }
                    foreach (json_decode(json_encode($resultsCW[1]), true)  as $value) { $counterweightColors[] = $value; }
                    foreach (json_decode(json_encode($resultsCW[2]), true)  as $value) { $counterweightData[] = $value; }
                    // estructuramos la informacion la informacion
                    foreach ($counterweights as $key => $counterweight) {
                        $counterweights[$key]['colors'] = [];
                        foreach ($counterweightColors as $color) {
                            if((INT)$counterweight['counterweight_bar_id'] === (INT)$color['counterweight_bar_id'] ) {
                                $counterweights[$key]['colors'][] = $color;
                            }
                        }
                        foreach ($counterweights[$key]['colors'] as $key2 => $color){
                            $counterweights[$key]['colors'][$key2]['items'] = [];
                            foreach ($counterweightData as $item) {
                                if((INT)$color['counterweight_bar_id'] === (INT)$item['counterweight_bar_id'] AND (INT)$color['counterweight_color_id'] === (INT)$item['counterweight_color_id'] ) {
                                    $counterweights[$key]['colors'][$key2]['items'][] = $item;
                                }
                            }
                        }
                    }
                    switch ((INT)$type) {
                        case 1: // Files
                            return app(FPDF::class)->createAllPerfilesModulation($productLineID,$line,$tubes,$perfiles,$twistbars,$counterweights,$type);
                        break;
                        case 2: // Stickers
                            $pdf = new FPDF(new PDF_Code128('L','mm',[26,77]));
                            return $pdf->createPerfilModulation($line,$perfiles,$type);
                        break;
                    }
                break;
                // case 'counterweights':
                //     $counterweights = [];
                //     $counterweightColors = [];
                //     $counterweightData = [];
                //     $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(12,".$productLineID.",4,0,0,0.0,0.0,0.0,'','','','','')");
                //     $statementAcc->execute();
                //     do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
                //     foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $counterweights[] = $value; }
                //     foreach (json_decode(json_encode($resultsAcc[1]), true)  as $value) { $counterweightColors[] = $value; }
                //     foreach (json_decode(json_encode($resultsAcc[2]), true)  as $value) { $counterweightData[] = $value; }
                //     // estructuramos la informacion la informacion
                //     foreach ($counterweights as $key => $counterweight) {
                //         $counterweights[$key]['colors'] = [];
                //         foreach ($counterweightColors as $color) {
                //             if((INT)$counterweight['counterweight_bar_id'] === (INT)$color['counterweight_bar_id'] ) {
                //                 $counterweights[$key]['colors'][] = $color;
                //             }
                //         }
                //         foreach ($counterweights[$key]['colors'] as $key2 => $color){
                //             $counterweights[$key]['colors'][$key2]['items'] = [];
                //             foreach ($counterweightData as $item) {
                //                 if((INT)$color['counterweight_bar_id'] === (INT)$item['counterweight_bar_id'] AND (INT)$color['counterweight_color_id'] === (INT)$item['counterweight_color_id'] ) {
                //                     $counterweights[$key]['colors'][$key2]['items'][] = $item;
                //                 }
                //             }
                //         }
                //     }
                //     switch ((INT)$type) {
                //         case 1: // Files
                //             return app(FPDF::class)->createCounterweightsModulation($productLineID,$line,$production_date,$counterweights,$type);
                //         break;
                //         case 2: // Stickers
                //             $pdf = new FPDF(new PDF_Code128('L','mm',[26,77]));
                //             return $pdf->createCounterweightsModulation($productLineID,$line,$production_date,$counterweights,$type);
                //         break;
                //     }
                // break;
                // case 'cuts':
                //     $cuts = [];
                //     $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(12,".$productLineID.",5,0,0,0.0,0.0,0.0,'','','','','')");
                //     $statementAcc->execute();
                //     do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
                //     foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $cuts[] = $value; }
                //     switch ((INT)$type) {
                //         case 1: // Files
                //             $pdf = new FPDF(new PDF_Code128("L", "mm", "A4"));
                //             return $pdf->createCutsModulation($productLineID,$line,$production_date,$cuts,$file,$type);
                //         break;
                //         case 2: // Stickers
                //             $pdf = new FPDF(new PDF_Code128('L','mm',[26,77]));
                //             return $pdf->createCutsModulation($productLineID,$line,$production_date,$cuts,$file,$type);
                //         break;
                //     }
                // break;
                // case 'leveling':
                //     $cuts = [];
                //     $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(12,".$productLineID.",5,0,0,0.0,0.0,0.0,'','','','','')");
                //     $statementAcc->execute();
                //     do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
                //     foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $cuts[] = $value; }
                //     switch ((INT)$type) {
                //         case 1: // Files
                //             $pdf = new FPDF(new PDF_Code128("L", "mm", "A4"));
                //             return $pdf->createCutsModulation($productLineID,$line,$production_date,$cuts,$file,$type);
                //         break;
                //         case 2: // Stickers
                //             $pdf = new FPDF(new PDF_Code128('L','mm',[26,77]));
                //             return $pdf->createCutsModulation($productLineID,$line,$production_date,$cuts,$file,$type);
                //         break;
                //     }
                // break;
                // case 'mechanisms':
                //     $mechanism = [];
                //     $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(12,".$productLineID.",6,0,0,0.0,0.0,0.0,'','','','','')");
                //     $statementAcc->execute();
                //     do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
                //     foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $mechanism[] = $value; }
                //     // descargamos el tipo de archivo
                //     switch ((INT)$type) {
                //         case 1: // Files
                //             return app(FPDF::class)->createMechanismsModulation($productLineID,$line,$production_date,$mechanism,$file,$type);
                //         break;
                //         case 2: // Stickers
                //             $pdf = new FPDF(new PDF_Code128('L','mm',[26,77]));
                //             return $pdf->createMechanismsModulation($productLineID,$line,$production_date,$mechanism,$file,$type);
                //         break;
                //     }
                // break;
            }
        // } catch (\Throwable $th) {
        //         return response()->json([
        //             'success' => false ,
        //             'error'   => $th
        //         ], 200);
        // }
    }

    public function etgreen() {
        // Obtener descuento del articulo mediante $discountData temporalmente le agregamos 0 descuento
        $pdf = new FPDF(new PDF_Code128('L','mm',[65,100]));
        return $pdf->createLabelsDesc();
    }
}
