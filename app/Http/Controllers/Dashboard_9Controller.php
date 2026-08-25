<?php

namespace App\Http\Controllers;

use App\Models\EOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Dashboard_9Controller extends Controller
{
    public function getDataDashboard9(Request $request) {
        // try {
            $data = [];
            switch ((INT)$request->report_type) {
                case 1: // Delivery Times
                    $EOrders = EOrder::select('id',DB::raw("'LS' AS nomen"),'authorization_date','packing_date','finalize_date')
                    ->whereBetween('authorization_date',[$request->dateInit." 00:00:00",$request->dateEnd." 23:59:59"])
                    ->get()
                    ->toArray();
                    foreach ($EOrders as $key => $order) {
                        $EOrders[$key]['production_days'] = 0;
                        $EOrders[$key]['production_hours'] = 0;
                        $EOrders[$key]['ending_days'] = 0;
                        $EOrders[$key]['ending_hours'] = 0;
                        $production_days=0;
                        $production_hours=0;
                        $ending_days=0;
                        $ending_hours=0;
                        //production
                        $fechaTemp = strtotime(date('Y-m-d',strtotime($order['authorization_date'])));
                        $fecha1 = strtotime(date('Y-m-d',strtotime($order['authorization_date'])));
                        $fecha2 = strtotime(date('Y-m-d',strtotime($order['packing_date'])));

                        for($fechaTemp ; $fechaTemp<=$fecha2; $fechaTemp=strtotime('+1 day ' . date('Y-m-d',$fechaTemp))){

                            if( (INT)date('w',$fechaTemp) !== 6 AND (INT)date('w',$fechaTemp) !== 0 ){
                                $segundos = 0;
                                if( (INT)$fecha1 === (INT)$fechaTemp ) { // fecha  inicial
                                    if((INT)$fecha1 === (INT)$fecha2) {
                                        $segundos = strtotime($order['packing_date']) - strtotime($order['authorization_date']);
                                    } else {
                                        $segundos = strtotime(date('Y-m-d',strtotime($order['authorization_date'])).' 24:00:00') - strtotime($order['authorization_date']);
                                    }
                                } else if( (INT)$fecha2 === (INT)$fechaTemp ) { // fecha  inicial
                                    $segundos = strtotime($order['packing_date']) - strtotime(date('Y-m-d',$fechaTemp).' 00:00:00');
                                } else {
                                    $segundos = strtotime(date('Y-m-d',$fechaTemp).' 24:00:00') - strtotime(date('Y-m-d',$fechaTemp).' 00:00:00');
                                }
                                $production_hours = $production_hours + round( $segundos / 3600 , 2) ;
                                $production_days++;
                            }
                        }
                        // authorization
                        $fechaTemp = strtotime(date('Y-m-d',strtotime($order['authorization_date'])));
                        $fecha1 = strtotime(date('Y-m-d',strtotime($order['authorization_date'])));
                        $fecha2 = strtotime(date('Y-m-d',strtotime($order['finalize_date'])));
                        for($fechaTemp ; $fechaTemp<=$fecha2; $fechaTemp=strtotime('+1 day ' . date('Y-m-d',$fechaTemp))){
                            // echo date('l-w',$fecha1).'<br>';
                            if( (INT)date('w',$fechaTemp) !== 6 AND (INT)date('w',$fechaTemp) !== 0 ){
                                $segundos = 0;
                                if( (INT)$fecha1 === (INT)$fechaTemp ) { // fecha  inicial
                                    if((INT)$fecha1 === (INT)$fecha2) {
                                        $segundos = strtotime($order['finalize_date']) - strtotime($order['authorization_date']);
                                    } else {
                                        $segundos = strtotime(date('Y-m-d',strtotime($order['authorization_date'])).' 24:00:00') - strtotime($order['authorization_date']);
                                    }
                                } else if( (INT)$fecha2 === (INT)$fechaTemp ) { // fecha  inicial
                                    $segundos = strtotime($order['finalize_date']) - strtotime(date('Y-m-d',$fechaTemp).' 00:00:00');
                                } else {
                                    $segundos = strtotime(date('Y-m-d',$fechaTemp).' 24:00:00') - strtotime(date('Y-m-d',$fechaTemp).' 00:00:00');
                                }
                                $ending_hours = $ending_hours + round( $segundos / 3600 , 2) ;
                                $ending_days++;
                            }
                        }
                        $EOrders[$key]['production_days'] = $production_days;
                        $EOrders[$key]['production_hours'] = $production_hours;
                        $EOrders[$key]['ending_days'] = $ending_days;
                        $EOrders[$key]['ending_hours'] = $ending_hours;
                    }
                    // interval typees
                    $day1Prod = $day2Prod = $day3Prod = $more3dayProd = $h24Prod = $h48Prod = $h72Prod = $more72Prod = $notDaysProd = $notHoursProd = 0;
                    $day1Ending = $day2Ending = $day3Ending = $more3dayEnding = $h24Ending = $h48Ending = $h72Ending = $more72Ending = $notDaysEnding = $notHoursEnding = 0;
                    foreach ($EOrders as  $detail) {
                        // Prductin
                        if( (INT)$detail['production_days'] === 0) { $notDaysProd++; }
                        if( (INT)$detail['production_days'] === 1) { $day1Prod++; }
                        if( (INT)$detail['production_days'] === 2) { $day2Prod++; }
                        if( (INT)$detail['production_days'] === 3) { $day3Prod++; }
                        if( (INT)$detail['production_days'] > 3) { $more3dayProd++; }
                        if( (INT)$detail['production_hours'] === 0) { $notHoursProd++; }
                        if( (DOUBLE)$detail['production_hours'] <= 24) { $h24Prod++; }
                        if( (DOUBLE)$detail['production_hours'] > 24 AND (DOUBLE)$detail['production_hours'] <= 48) { $h48Prod++; }
                        if( (DOUBLE)$detail['production_hours'] > 48 AND (DOUBLE)$detail['production_hours'] <= 72) { $h72Prod++; }
                        if( (DOUBLE)$detail['production_hours'] > 72) { $more72Prod++; }
                        //Ending
                        if( (INT)$detail['ending_days'] === 0) { $notDaysEnding++; }
                        if( (INT)$detail['ending_days'] === 1) { $day1Ending++; }
                        if( (INT)$detail['ending_days'] === 2) { $day2Ending++; }
                        if( (INT)$detail['ending_days'] === 3) { $day3Ending++; }
                        if( (INT)$detail['ending_days'] > 3) { $more3dayEnding++; }
                        if( (INT)$detail['ending_hours'] === 0) { $notHoursEnding++; }
                        if( (DOUBLE)$detail['ending_hours'] <= 24) { $h24Ending++; }
                        if( (DOUBLE)$detail['ending_hours'] > 24 AND (DOUBLE)$detail['ending_hours'] <= 48) { $h48Ending++; }
                        if( (DOUBLE)$detail['ending_hours'] > 48 AND (DOUBLE)$detail['ending_hours'] <= 72) { $h72Ending++; }
                        if( (DOUBLE)$detail['ending_hours'] > 72) { $more72Ending++; }
                    }
                    $intervals = [
                        'dates_prod' => [ 'day1' => $day1Prod, 'day2' => $day2Prod, 'day3' => $day3Prod, 'more3day' => $more3dayProd, 'notday' => $notDaysProd, ],
                        'hours_prod' => [ 'h24' => $h24Prod, 'h48' => $h48Prod, 'h72' => $h72Prod, 'more72' => $more72Prod, 'nothours' => $notHoursProd, ],
                        'dates_ending' => [ 'day1' => $day1Ending, 'day2' => $day2Ending, 'day3' => $day3Ending, 'more3day' => $more3dayEnding, 'notday' => $notDaysEnding ],
                        'hours_ending' => [ 'h24' => $h24Ending, 'h48' => $h48Ending, 'h72' => $h72Ending, 'more72' => $more72Ending, 'nothours' => $notHoursEnding, ],
                    ];
                    $data = [
                        'intervals' => $intervals,
                        'details' => $EOrders,
                    ];
                break;
            }
            return response()->json([
                "success" => true,
                "rowData" => $data,
                "report_type" => $request->report_type,
            ],200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         "success" => false,
        //         "error" => $th->getMessage(),
        //     ],400);
        // }
    }
}
