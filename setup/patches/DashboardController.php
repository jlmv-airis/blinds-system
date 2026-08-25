<?php

namespace App\Http\Controllers;

use App\classes\GetTotal;
use Illuminate\Http\Request;
use App\classes\WebService;
use App\Exports\pdpExport;
use App\Models\CFormCost;
use App\Models\DExternalInvoice;
use App\Models\DOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }
    public function getDataDasboardHome(Request $request) {
        try {

            $DExternalInvoice = DExternalInvoice::select('SERIE','FOLIO','CVE_DOC','CLIENT_ID','NOMBRE','DES_TOT_PORC','IMPORTE','STATUS','FECHA_DOC','CVE_VEND','AGENT_NAME','CVE_PEDI','CAN_TOT')
            ->whereBetween('date_invoice',[$request->dateInit,$request->dateEnd])
            ->get()
            ->toArray();
            $rowData1 = $this->webService->getDataLBDetails($request->all());
            $rowData2 = $this->webService->getDataRTDetails($request->all());
            $rowData3 = $this->webService->getDataLSDetails($request->all());
            if(!empty($DExternalInvoice) && isset($rowData3->items)) { $rowData3 = $this->getAssignExternalInvoice($rowData3,$DExternalInvoice); }
            if(isset($rowData3->items)) { $rowData3 = $this->getTotalCostDays($rowData3); }
            $rowDataInvLS = $this->webService->getDataLSInvoicesDate($request->all());
            if(!empty($DExternalInvoice) && isset($rowDataInvLS->items)) { $rowDataInvLS = $this->getAssignExternalInvLS($rowDataInvLS,$DExternalInvoice); }
            $costLS = isset($rowDataInvLS->items) ? $this->getTotalCost($rowDataInvLS->items) : [];

            $rowData = [
                'costLS' => $costLS,
                'LB' => $rowData1,
                'RT' => $rowData2,
                'LS' => $rowData3,
            ];
            return response()->json([
                "success" => true,
                "rowData" => $rowData,
            ],200);
        } catch (\Throwable $th) {
            return response()->json([
                "success" => false,
                "error" => $th->getMessage(),
            ],400);
        }
    }
    public function getDataDasboardCV(Request $request) {
        try {
            $rowDataRT = $this->webService->getDataCVRT($request->all());
            $rowDataLB = $this->webService->getDataCVLB($request->all());

            $emptyCV = (object) [
                'reportDays' => [],
                'totalDays' => [(object) ['tlus_90' => 0, 't90' => 0, 't60' => 0, 't30' => 0]],
                'totaltoPay' => 0,
                'overdueBalance' => 0,
                'paysDays' => (object) ['PUE' => 0, 'PPD' => 0, 'day_init' => date('Y-m-d'), 'day_end' => date('Y-m-d')],
                'infoInvoices' => [(object) ['InvCorriente' => [], 'Inv30' => [], 'Inv60' => [], 'Inv90' => [], 'InvPlus_90' => []]],
            ];

            if (empty($rowDataRT) || !isset($rowDataRT->reportDays)) $rowDataRT = $emptyCV;
            if (empty($rowDataLB) || !isset($rowDataLB->reportDays)) $rowDataLB = $emptyCV;

            return response()->json([
                "success" => true,
                "cvrt" => $rowDataRT,
                "cvlb" => $rowDataLB,
            ],200);
        } catch (\Throwable $th) {
            return response()->json([
                "success" => false,
                "error" => $th->getMessage(),
            ],400);
        }
    }

    public function downloadExcelPDP(Request $request) {
        $date_now = Carbon::now();
        switch ($request->opt) {
            case 1: // detail invoices
                $name = 'ROLLERTEX';
                $rowData = $this->webService->getDataCVRT($request->all());
            break;
            case 2:
                $name = 'LANSON_BECKMAN';
                $rowData = $this->webService->getDataCVLB($request->all());
            break;
        }
        $filterRowData = $rowData->reportDays;
        $file = Excel::raw(new pdpExport($filterRowData), \Maatwebsite\Excel\Excel::XLSX);
        return [
            "name" => "Cartera_vencida_".$name."_".$date_now.".xls",
            "file" => base64_encode($file),
        ];
    }

    private function getTotalCost($orders) {

        $ordersID = [];
        foreach ($orders as $key => $order) {
            $newData = trim(strtr($order->CVE_PEDI, " ", "|"));
            $orderExp = explode("|", $newData);
            if( (INT)COUNT($orderExp) === 1 ) {
                $id = preg_replace('/[^0-9]+/i', '', $order->CVE_PEDI);
                $nomen = preg_replace('/[^a-z]/iu', '', $order->CVE_PEDI);
                if($nomen == 'LS') { $ordersID[] = $id; }
            } else if( (INT)COUNT($orderExp) === 1 ) {
                foreach ($orderExp as $value) {
                    $id = preg_replace('/[^0-9]+/i', '', $value);
                    $nomen = preg_replace('/[^a-z]/iu', '', $value);
                    if($nomen == 'LS') { $ordersID[] = $id; }
                }
            }
        }

        $DOrders = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro')
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
        ->whereIn('order_id',$ordersID)
        ->get();

        $totalCost = 0 ;
        $testOrder = [];
        $costForm = CFormCost::where('is_active',1)->get()->toArray();
        foreach ($DOrders->toArray() as  $dOrder) {
            $cost =  app(GetTotal::class)->getIndividualCostOrder($dOrder,$costForm);
            if( (DOUBLE)$cost > 0 )  {
                $testOrder[] = [
                    'order_id' => $dOrder['order_id'],
                    'item_id' => $dOrder['item_id'],
                    'product_id' => $dOrder['product_id'],
                    'model_id' => $dOrder['model_id'],
                    'article' => $dOrder['article'],
                    'ancho' => $dOrder['width'],
                    'alto' => $dOrder['height'],
                    'cost' => $cost,
                ];
            }
            $totalCost = $totalCost + $cost;
        }
        // var_dump($testOrder);
        // dd();

        return (DOUBLE) ROUND($totalCost,2);
    }


    private function getAssignExternalInvoice($rowData,$DExternalInvoice) {

        $total_importe_all = 0;
        foreach ($rowData->items as $key => $items) {
            $total_importe_add = 0;
            foreach ($DExternalInvoice as $key2 => $DEInvoice) {
                if( $DEInvoice['FECHA_DOC'] == $items->FECHA_DOC ) {
                    $total_importe_add = (DOUBLE)$total_importe_add + (DOUBLE)$DEInvoice['CAN_TOT'];
                    $rowData->items[$key]->invoices[] = json_decode(json_encode($DEInvoice, JSON_FORCE_OBJECT));
                }
            }
            $rowData->items[$key]->TOTAL_IMPORTE = (DOUBLE)$rowData->items[$key]->TOTAL_IMPORTE + $total_importe_add;
            $total_importe_all = (DOUBLE)$rowData->items[$key]->TOTAL_IMPORTE + (DOUBLE)$total_importe_all;
        }
        $rowData->total_invoice[0]->TOTAL_IMPORTE = $total_importe_all;
        // Costo total:
        // $457,647.45
        return $rowData;
    }

    private function getAssignExternalInvLS($rowDataInvLS,$DExternalInvoice) {
        foreach ($rowDataInvLS->items as $key => $items) {
            foreach ($DExternalInvoice as $key2 => $DEInvoice) {
                $rowDataInvLS->items[] = json_decode(json_encode( [ "SERIE" => $DEInvoice['SERIE'],"FOLIO" => $DEInvoice['FOLIO'],"CVE_PEDI" => $DEInvoice['CVE_PEDI'], ], JSON_FORCE_OBJECT));

            }
        }
        return $rowDataInvLS;
    }

    private function getTotalCostDays($rowData) {

        $ordersID = [];
        foreach ($rowData->items as $key1 => $daysInvoices) {
            foreach ($daysInvoices->invoices as $key => $invoice) {
                $newData = trim(strtr($invoice->CVE_PEDI, " ", "|"));
                $orderExp = explode("|", $newData);
                $daysInvoices->invoices[$key]->order_id = 0;
                if( (INT)COUNT($orderExp) === 1 ) {
                    $id = preg_replace('/[^0-9]+/i', '', $invoice->CVE_PEDI);
                    $nomen = preg_replace('/[^a-z]/iu', '', $invoice->CVE_PEDI);
                    if($nomen == 'LS') {
                        $ordersID[] = $id;
                        $daysInvoices->invoices[$key]->order_id = $id;
                    }
                } else if( (INT)COUNT($orderExp) === 1 ) {
                    foreach ($orderExp as $value) {
                        $id = preg_replace('/[^0-9]+/i', '', $value);
                        $nomen = preg_replace('/[^a-z]/iu', '', $value);
                        if($nomen == 'LS') {
                            $ordersID[] = $id;
                            $daysInvoices->invoices[$key]->order_id = $id;
                        }
                    }
                }
            }
        }

        $DOrders = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro')
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
        ->whereIn('order_id',$ordersID)
        ->get();


        $costForm = CFormCost::where('is_active',1)->get()->toArray();
        foreach ($rowData->items as $key1 => $iitemInvoices) {
            $TotalAllCost = 0;
            $subtotal = 0;
            foreach ($iitemInvoices->invoices as $key => $invoice) {
                $totalCost = 0;
                foreach ($DOrders->toArray() as  $dOrder) {
                    if( (INT)$dOrder['order_id'] === (INT)$invoice->order_id) {
                        $cost =  app(GetTotal::class)->getIndividualCostOrder($dOrder,$costForm);
                        $totalCost = $totalCost + $cost;
                    }
                }
                $TotalAllCost = $TotalAllCost + $totalCost;
                $subtotal = (DOUBLE)$subtotal + (DOUBLE)$iitemInvoices->invoices[$key]->CAN_TOT;
                $iitemInvoices->invoices[$key]->cost = $totalCost;
                $iitemInvoices->invoices[$key]->utility = (DOUBLE) ROUND( (DOUBLE)$iitemInvoices->invoices[$key]->CAN_TOT  - (DOUBLE)$totalCost , 2 );
                $iitemInvoices->invoices[$key]->percentage = (DOUBLE) ROUND( ( ( (DOUBLE)$iitemInvoices->invoices[$key]->CAN_TOT  - (DOUBLE)$totalCost) / (DOUBLE)$iitemInvoices->invoices[$key]->CAN_TOT ) * 100 , 2 );
            }

            $rowData->items[$key1]->CANT_TOT = (DOUBLE) ROUND( $subtotal , 2 );
            $rowData->items[$key1]->COSTO_TOTAL = (DOUBLE) ROUND( $TotalAllCost , 2 );
            $rowData->items[$key1]->UTILIDAD_TOTAL = (DOUBLE) ROUND( (DOUBLE)$subtotal  - (DOUBLE)$TotalAllCost , 2 );
            $rowData->items[$key1]->PERCENTAGE = (DOUBLE) ROUND( ( ( (DOUBLE)$subtotal  - (DOUBLE)$TotalAllCost ) / (DOUBLE)$subtotal) * 100 , 2 );

        }
        return $rowData;
    }
}
