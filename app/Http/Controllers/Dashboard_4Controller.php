<?php

namespace App\Http\Controllers;

use App\classes\GetTotal;
use Illuminate\Http\Request;
use App\classes\WebService;
use App\Exports\dashboar4Export;
use App\Exports\Dashboard4ClientsExport;
use App\Exports\Dashboard4ProductsExport;
use App\Models\CFormCost;
use App\Models\DExternalInvoice;
use App\Models\DOrder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class Dashboard_4Controller extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }
    public function getDataDashboard4(Request $request) {
        // try {
            switch ($request->invoice_type) {
                case 1: // Invoices
                    // Type report
                    switch ($request->report_type) {
                        case 1: // Clients
                            $clientExternalInvoice = DExternalInvoice::select(DB::raw('CLIENT_ID AS CVE_CLPV'),'NOMBRE',DB::raw('0 AS TOTAL_IMPORTE, 0 AS COSTO_TOTAL, 0 AS UTILIDAD_TOTAL, 0 AS PERCENTAGE'),'AGENT_NAME')
                            ->whereBetween('date_invoice',[$request->dateInit,$request->dateEnd])
                            ->groupBy('CLIENT_ID')
                            ->get()
                            ->toArray();
                            $DExternalInvoice = DExternalInvoice::select('SERIE','FOLIO','CVE_DOC',DB::raw('CLIENT_ID AS CVE_CLPV'),'NOMBRE','DES_TOT_PORC','IMPORTE','STATUS','FECHA_DOC','CVE_PEDI','CAN_TOT')
                            ->whereBetween('date_invoice',[$request->dateInit,$request->dateEnd])
                            ->get()
                            ->toArray();
                            $rowData = $this->webService->getInvoiceClientReportLS($request->all());
                            if(!empty($DExternalInvoice)) { $rowData = $this->getAssignExternalClient($rowData,$DExternalInvoice,$clientExternalInvoice); }
                            $rowData = $this->getTotalCostClients($rowData);
                        break;
                        case 2: // Invoices
                            $DExternalInvoice = DExternalInvoice::select('SERIE','FOLIO','CVE_DOC','CLIENT_ID','NOMBRE','DES_TOT_PORC','IMPORTE','STATUS','FECHA_DOC','CVE_VEND','AGENT_NAME','CVE_PEDI','CAN_TOT')
                            ->whereBetween('date_invoice',[$request->dateInit,$request->dateEnd])
                            ->get()
                            ->toArray();
                            $rowData = $this->webService->getInvoiceReportLS($request->all());
                            if(!empty($DExternalInvoice)) { $rowData = $this->getAssignExternalInvoice($rowData,$DExternalInvoice); }
                            $rowData = $this->getTotalCostInvoice($rowData);
                        break;
                        case 3: // Products
                            $rowData = $this->webService->getInvoiceProductsReportLS($request->all());
                        break;
                        case 4: // Vendedores
                            $rowData = $this->webService->getInvoiceSellersReportLS($request->all());
                        break;
                    }
                break;
                case 2: // Credit Notes
                    // Type report
                    switch ($request->report_type) {
                        case 1:
                            $rowData = $this->webService->getCreditNotesReportLS($request->all());
                        break;
                    }
                break;
                case 3: // Returns
                    // Type report
                    switch ($request->report_type) {
                        case 1: //
                            $rowData = $this->webService->getReturnsReportLS($request->all());
                        break;
                    }
                break;
            }
            return response()->json([
                "success" => true,
                "rowData" => $rowData,
            ],200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         "success" => false,
        //         "error" => $th->getMessage(),
        //     ],400);
        // }
    }


    public function getDetailTypeInvoicesD4(Request $request) {
        // try {
            switch ($request->invoice_type) {
                case 1: // Invoices
                    $rowData = $this->webService->getDetailsInvoiceReportLS($request->all());
                break;
                case 2: // Credit Notes
                    $rowData = $this->webService->getDetailsCreditNotesReportLS($request->all());
                break;
                case 3: // Returns
                    $rowData = $this->webService->getDetailsReturnsReportLS($request->all());
                break;
            }
            return response()->json([
                "success" => true,
                "rowData" => $rowData->items,
                "invoiceData" => $rowData->invoiceData,
            ],200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         "success" => false,
        //         "error" => $th->getMessage(),
        //     ],400);
        // }
    }

    public function downloadExcelD4(Request $request) {

        switch ($request->opt) {
            case 1: // detail invoices
                $DExternalInvoice = DExternalInvoice::select('CVE_DOC','FECHA_DOC',DB::raw('CLIENT_ID AS CVE_CLPV, NOMBRE AS CLIENT_NAME, "KITPERS" AS CVE_ART, "KIT DE PERSIANAS" AS DESCR, "PZA" AS UNI_MED, "1.000000" AS CANT , CAN_TOT AS PREC, "0" AS COST, CAN_TOT AS TOTAL, "0" AS COSTO, "0" AS UTILIDAD, "0" AS PERCENTAGE'),'CVE_VEND',DB::raw('AGENT_NAME AS NOMBRE'),'CVE_PEDI')
                ->whereBetween('date_invoice',[$request->dateInit,$request->dateEnd])
                ->get()
                ->toArray();

                $rowData1 = $this->webService->getDownloadInvoicesDetailsLS($request->all());
                if(!empty($DExternalInvoice)) { $rowData1 = $this->getAssignExternalInvoiceDownload($rowData1,$DExternalInvoice); }
                $rowData1 = $this->getTotalCostInvoiceExcel($rowData1);
                $rowData2 = $this->webService->getDownloadCreditNotesDetailsLS($request->all());
                $rowData3 = $this->webService->getDownloadReturnsDetailsLS($request->all());
                $rowData = [
                    'invoices' => $rowData1->items,
                    'credit_notes' => $rowData2->items,
                    'returns' => $rowData3->items,
                ];
                $file = Excel::raw(new dashboar4Export($rowData), \Maatwebsite\Excel\Excel::XLSX);
                return [
                    "name" => "Detalle_facturación_LANSON_SHADES_".$request->dateInit."_to_".$request->dateEnd.".xlsx",
                    "file" => base64_encode($file),
                ];
            break;
            case 2: // detail product
                $rowData = $this->webService->getInvoiceProductsReportLS($request->all());
                $rowDataGral = [
                    'products' => $rowData->items,
                    'products_per_clients' => $rowData->items_per_client,
                ];
                $file = Excel::raw(new Dashboard4ProductsExport($rowDataGral), \Maatwebsite\Excel\Excel::XLSX);
                return [
                    "name" => "Detalle_Productos_LANSON_SHADES_".$request->dateInit."_to_".$request->dateEnd.".xlsx",
                    "file" => base64_encode($file),
                ];
            break;
            case 3: // detail Clients

                $clientExternalInvoice = DExternalInvoice::select(DB::raw('CLIENT_ID AS CVE_CLPV'),'NOMBRE',DB::raw('0 AS TOTAL_IMPORTE, 0 AS COSTO_TOTAL, 0 AS UTILIDAD_TOTAL, 0 AS PERCENTAGE,AGENT_NAME AS AGENTE'))
                ->whereBetween('date_invoice',[$request->dateInit,$request->dateEnd])
                ->groupBy('CLIENT_ID')
                ->get()
                ->toArray();
                $DExternalInvoice = DExternalInvoice::select('SERIE','FOLIO','CVE_DOC',DB::raw('CLIENT_ID AS CVE_CLPV'),'NOMBRE','DES_TOT_PORC','IMPORTE','STATUS','FECHA_DOC','CVE_PEDI','CAN_TOT')
                ->whereBetween('date_invoice',[$request->dateInit,$request->dateEnd])
                ->get()
                ->toArray();
                $rowData = $this->webService->getInvoiceClientReportLS($request->all());
                if(!empty($DExternalInvoice)) { $rowData = $this->getAssignExternalClient($rowData,$DExternalInvoice,$clientExternalInvoice); }
                $rowData = $this->getTotalCostClients($rowData);
                // dd($rowData->items);
                $clients = [];
                foreach ($rowData->items as $client) {
                    $clients[] = [
                        'CVE_CLPV' => $client->CVE_CLPV,
                        'NOMBRE' => $client->NOMBRE,
                        'CANT_TOT' => $client->CANT_TOT,
                        'TOTAL_IMPORTE' => $client->TOTAL_IMPORTE,
                        'COSTO_TOTAL' => $client->COSTO_TOTAL,
                        'UTILIDAD_TOTAL' => $client->UTILIDAD_TOTAL,
                        'PERCENTAGE' => $client->PERCENTAGE,
                        'AGENTE' => $client->AGENTE,
                    ];
                }
                $file = Excel::raw(new Dashboard4ClientsExport($clients), \Maatwebsite\Excel\Excel::XLSX);
                return [
                    "name" => "Detalle_Clientes_LANSON_SHADES_".$request->dateInit."_to_".$request->dateEnd.".xlsx",
                    "file" => base64_encode($file),
                ];
            break;
        }

        // return Excel::download(new BladeExport($data), 'export.xlsx');
    }


    private function getTotalCostInvoice($rowData) {

        $ordersID = [];
        foreach ($rowData->items as $key => $order) {
            $newData = trim(strtr($order->CVE_PEDI, " ", "|"));
            $orderExp = explode("|", $newData);
            $rowData->items[$key]->order_id = 0;
            if( (INT)COUNT($orderExp) === 1 ) {
                $id = preg_replace('/[^0-9]+/i', '', $order->CVE_PEDI);
                $nomen = preg_replace('/[^a-z]/iu', '', $order->CVE_PEDI);
                if($nomen == 'LS') {
                    $ordersID[] = $id;
                    $rowData->items[$key]->order_id = $id;
                }
            } else if( (INT)COUNT($orderExp) === 1 ) {
                foreach ($orderExp as $value) {
                    $id = preg_replace('/[^0-9]+/i', '', $value);
                    $nomen = preg_replace('/[^a-z]/iu', '', $value);
                    if($nomen == 'LS') {
                        $ordersID[] = $id;
                        $rowData->items[$key]->order_id = $id;
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

        $totalCost = 0 ;
        $TotalAllCost = 0;
        $test = [];
        $costForm = CFormCost::where('is_active',1)->get()->toArray();
        foreach ($rowData->items as $key => $order) {
            $totalCost = 0;
            foreach ($DOrders->toArray() as  $dOrder) {
                if( (INT)$dOrder['order_id'] === (INT)$order->order_id) {
                    $cost =  app(GetTotal::class)->getIndividualCostOrder($dOrder,$costForm);
                    $totalCost = $totalCost + $cost;
                    if( (INT)$dOrder['order_id'] === 3253 ) {
                        // $test[] = [
                        //     'order_id' => $dOrder['order_id'],
                        //     'item_id' => $dOrder['item_id'],
                        //     'product_id' => $dOrder['product_id'],
                        //     'operation_id' => $dOrder['operation_id'],
                        //     'model_id' => $dOrder['model_id'],
                        //     'width' => $dOrder['width'],
                        //     'height' => $dOrder['height'],
                        //     'cost' => $cost,
                        // ];
                    }
                }
            }
            $TotalAllCost = $TotalAllCost + $totalCost;
            $rowData->items[$key]->cost = $totalCost;
            $rowData->items[$key]->utility = (DOUBLE) ROUND( (DOUBLE)$rowData->items[$key]->CAN_TOT  - (DOUBLE)$totalCost , 2 );
            $rowData->items[$key]->percentage = (DOUBLE) ROUND( ( ( (DOUBLE)$rowData->items[$key]->CAN_TOT  - (DOUBLE)$totalCost) / (DOUBLE)$rowData->items[$key]->CAN_TOT ) * 100 , 2 );
        }

        $rowData->total_invoice[0]->COSTO_TOTAL = (DOUBLE) ROUND( $TotalAllCost , 2 );
        $rowData->total_invoice[0]->UTILIDAD_TOTAL = (DOUBLE) ROUND( (DOUBLE)$rowData->total_invoice[0]->TOTAL_IMPORTE  - (DOUBLE)$rowData->total_invoice[0]->COSTO_TOTAL , 2 );
        $rowData->total_invoice[0]->PERCENTAGE = (DOUBLE) ROUND( ( ( (DOUBLE)$rowData->total_invoice[0]->TOTAL_IMPORTE  - (DOUBLE)$rowData->total_invoice[0]->COSTO_TOTAL ) / (DOUBLE)$rowData->total_invoice[0]->TOTAL_IMPORTE ) * 100 , 2 );

        return $rowData;
    }

    private function getTotalCostClients($rowData) {

        $ordersID = [];
        foreach ($rowData->items as $key1 => $clients) {
            foreach ($clients->invoices as $key => $invoice) {
                $newData = trim(strtr($invoice->CVE_PEDI, " ", "|"));
                $orderExp = explode("|", $newData);
                $clients->invoices[$key]->order_id = 0;
                if( (INT)COUNT($orderExp) === 1 ) {
                    $id = preg_replace('/[^0-9]+/i', '', $invoice->CVE_PEDI);
                    $nomen = preg_replace('/[^a-z]/iu', '', $invoice->CVE_PEDI);
                    if($nomen == 'LS') {
                        $ordersID[] = $id;
                        $clients->invoices[$key]->order_id = $id;
                    }
                } else if( (INT)COUNT($orderExp) === 1 ) {
                    foreach ($orderExp as $value) {
                        $id = preg_replace('/[^0-9]+/i', '', $value);
                        $nomen = preg_replace('/[^a-z]/iu', '', $value);
                        if($nomen == 'LS') {
                            $ordersID[] = $id;
                            $clients->invoices[$key]->order_id = $id;
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
        $totTest = 0;
        foreach ($rowData->items as $key1 => $clients) {
            $TotalAllCost = 0;
            $subtotal = 0;
            foreach ($clients->invoices as $key => $invoice) {
                $totalCost = 0;
                foreach ($DOrders->toArray() as  $dOrder) {
                    if( (INT)$dOrder['order_id'] === (INT)$invoice->order_id) {
                        $cost =  app(GetTotal::class)->getIndividualCostOrder($dOrder,$costForm);
                        $totalCost = $totalCost + $cost;
                    }
                }
                $TotalAllCost = $TotalAllCost + $totalCost;
                $subtotal = (DOUBLE)$subtotal + (DOUBLE)$clients->invoices[$key]->CAN_TOT;
                $clients->invoices[$key]->cost = $totalCost;
                $clients->invoices[$key]->utility = (DOUBLE) ROUND( (DOUBLE)$clients->invoices[$key]->CAN_TOT  - (DOUBLE)$totalCost , 2 );
                $clients->invoices[$key]->percentage = (DOUBLE) ROUND( ( ( (DOUBLE)$clients->invoices[$key]->CAN_TOT  - (DOUBLE)$totalCost) / (DOUBLE)$clients->invoices[$key]->CAN_TOT ) * 100 , 2 );
            }
            $rowData->items[$key1]->CANT_TOT = (DOUBLE) ROUND( $subtotal , 2 );
            $totTest = (DOUBLE)$totTest + (DOUBLE)$subtotal;

            $rowData->items[$key1]->COSTO_TOTAL = (DOUBLE) ROUND( $TotalAllCost , 2 );
            $rowData->items[$key1]->UTILIDAD_TOTAL = (DOUBLE) ROUND( (DOUBLE)$subtotal  - (DOUBLE)$TotalAllCost , 2 );
            $rowData->items[$key1]->PERCENTAGE = (DOUBLE) ROUND( ( ( (DOUBLE)$subtotal  - (DOUBLE)$TotalAllCost ) / (DOUBLE)$subtotal) * 100 , 2 );

        }
        return $rowData;
    }

    private function getTotalCostInvoiceExcel($rowData) {

        $ordersID = [];
        foreach ($rowData->items as $key => $order) {
            $newData = trim(strtr($order->CVE_PEDI, " ", "|"));
            $orderExp = explode("|", $newData);
            $rowData->items[$key]->order_id = 0;
            if( (INT)COUNT($orderExp) === 1 ) {
                $id = preg_replace('/[^0-9]+/i', '', $order->CVE_PEDI);
                $nomen = preg_replace('/[^a-z]/iu', '', $order->CVE_PEDI);
                if($nomen == 'LS') {
                    $ordersID[] = $id;
                    $rowData->items[$key]->order_id = $id;
                }
            } else if( (INT)COUNT($orderExp) === 1 ) {
                foreach ($orderExp as $value) {
                    $id = preg_replace('/[^0-9]+/i', '', $value);
                    $nomen = preg_replace('/[^a-z]/iu', '', $value);
                    if($nomen == 'LS') {
                        $ordersID[] = $id;
                        $rowData->items[$key]->order_id = $id;
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

        $totalCost = 0 ;
        $costForm = CFormCost::where('is_active',1)->get()->toArray();
        foreach ($rowData->items as $key => $order) {
            $totalCost = 0;
            foreach ($DOrders->toArray() as  $dOrder) {
                if( (INT)$dOrder['order_id'] === (INT)$order->order_id) {
                    $cost =  app(GetTotal::class)->getIndividualCostOrder($dOrder,$costForm);
                    $totalCost = $totalCost + $cost;
                }
            }
            if( (INT)$totalCost !== 0 ) {
                $rowData->items[$key]->COST = $totalCost;
                $rowData->items[$key]->UTILIDAD = (DOUBLE) ROUND( (DOUBLE)$rowData->items[$key]->TOTAL  - (DOUBLE)$totalCost , 2 );
                $rowData->items[$key]->PERCENTAGE = (DOUBLE) ROUND( ( ( (DOUBLE)$rowData->items[$key]->TOTAL  - (DOUBLE)$totalCost) / (DOUBLE)$rowData->items[$key]->TOTAL ) * 100 , 2 );
            } else {
                $rowData->items[$key]->COST = 0;
                $rowData->items[$key]->UTILIDAD = 0;
                $rowData->items[$key]->PERCENTAGE = 0;
            }
        }
        return $rowData;
    }

    private function getAssignExternalInvoice($rowData,$DExternalInvoice) {
        $total_importe_all = 0;
        foreach ($DExternalInvoice as $key2 => $DEInvoice) {
            $rowData->items[] = json_decode(json_encode($DEInvoice, JSON_FORCE_OBJECT));
            $total_importe_all = (DOUBLE)$total_importe_all + (DOUBLE)$DEInvoice['CAN_TOT'];
        }
        echo $rowData->total_invoice[0]->TOTAL_IMPORTE;
        $rowData->total_invoice[0]->TOTAL_IMPORTE = (DOUBLE)$rowData->total_invoice[0]->TOTAL_IMPORTE + (DOUBLE)$total_importe_all;
        return $rowData;
    }

    private function getAssignExternalInvoiceDownload($rowData,$DExternalInvoice) {
        foreach ($DExternalInvoice as $key2 => $DEInvoice) {
            $rowData->items[] = json_decode(json_encode($DEInvoice, JSON_FORCE_OBJECT));
        }
        return $rowData;
    }

    private function getAssignExternalClient($rowData,$DExternalInvoice,$clientExternalInvoice) {
        // Agregamos al cliente si no se encuentra
        foreach ($clientExternalInvoice as $client) {
            $client_nt_found = true;
            foreach ($rowData->items as $clientRaw) {
                if( (INT)$client['CVE_CLPV'] === (INT)$clientRaw->CVE_CLPV ) {
                    $client_nt_found = false;
                }
            }
            if($client_nt_found) {
                $rowData->items[] = json_decode(json_encode($client, JSON_FORCE_OBJECT));;
            }
        }
        //
        $total_importe_all = 0;
        $total_importe_all_test = 0;
        foreach ($rowData->items as $key => $client) {
            $total_importe_client = 0;
            $client_found = false;
            foreach ($DExternalInvoice as $key2 => $DEInvoice) {
                if( (INT)$DEInvoice['CVE_CLPV'] === (INT)$client->CVE_CLPV ) {
                    $client_found = true;
                    $rowData->items[$key]->invoices[] = json_decode(json_encode($DEInvoice, JSON_FORCE_OBJECT));
                    $total_importe_client = (DOUBLE)$total_importe_client + (DOUBLE)$DEInvoice['IMPORTE'];
                    $total_importe_all = (DOUBLE)$total_importe_all + (DOUBLE)$DEInvoice['IMPORTE'];
                }
            }
            if($client_found) {
                $rowData->items[$key]->TOTAL_IMPORTE = (DOUBLE)$rowData->items[$key]->TOTAL_IMPORTE + (DOUBLE)$total_importe_client;
            }
            $total_importe_all_test = (DOUBLE)$rowData->items[$key]->TOTAL_IMPORTE + (DOUBLE)$total_importe_all_test;
        }
        $rowData->total_invoice[0]->TOTAL_IMPORTE = (DOUBLE)$rowData->total_invoice[0]->TOTAL_IMPORTE + (DOUBLE)$total_importe_all;
        return $rowData;
    }
}
