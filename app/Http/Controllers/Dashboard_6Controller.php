<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\classes\WebService;
use App\Exports\dashboarInvoice6Export;
use App\Models\CAgent;
use App\Models\CMeta;
use App\Models\CSaeInventory;
use Maatwebsite\Excel\Facades\Excel;

class Dashboard_6Controller extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }
    public function getDataDashboard6(Request $request) {
        // try {

            $rowData = $this->webService->getInvoiceReport($request->all());
            $rowDataClients = $this->webService->getClienLB($request->all());
            $rowDataAgents = $this->webService->getAgentsLB($request->all());
            $agentsFinal = $rowDataAgents->items;
            $meta = CMeta::select('meta')->where('companie_id',3)->where('type',"metas_lb")->where('month_search',$request->month)->where('year_search',$request->year)->first();  // Lanson Beckman
            $agents = CAgent::select('id_erp','meta')->get()->toArray();
            foreach ($agentsFinal as $key => $agent) {
                $agentsFinal[$key]->meta = 0;
                foreach ($agents as  $agentTemp) {
                    if( (INT)$agent->CVE_VEND === (INT)$agentTemp['id_erp'] ) {
                        $agentsFinal[$key]->meta = $agentTemp['meta'];
                    }
                }
            }

            return response()->json([
                "success" => true,
                "rowDataInvoice" => $rowData->items,
                "rowDataClients" => $rowDataClients->items,
                "rowDataAgents"  => $agentsFinal,
                "meta"          => $meta->meta,
            ],200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         "success" => false,
        //         "error" => $th->getMessage(),
        //     ],400);
        // }
    }

    public function getDataDashboard6LastYear(Request $request) {
        try {
            $rowData = $this->webService->getInvoiceReport($request->all());
            return response()->json([
                "success" => true,
                "rowData" => $rowData->items,
            ],200);
        } catch (\Throwable $th) {
            return response()->json([
                "success" => false,
                "error" => $th->getMessage(),
            ],400);
        }
    }

    public function getDataDashboard6_4(Request $request) {
        // try {
            $rowDataInventory = $this->webService->getInventory($request->all());
            $setSegment = $rowDataInventory->items;
            $tx8 = CSaeInventory::select('sku','product')->where('companie_id',3)->where('segment','tx8')->get()->toArray();
            foreach ($setSegment as $key => $inv) {
                $setSegment[$key]->SEGMENT = 'LB';
                foreach ($tx8 as $tx) { if( (INT)$inv->CVE_ART === (INT)$tx['sku'] ) { $setSegment[$key]->SEGMENT = 'tx8'; } }
            }
            $rowDataInvoiceDetails = $this->webService->getInvoicesDetails($request->all());
            $invoiceDetails = $rowDataInvoiceDetails->items;
            foreach ($invoiceDetails as $key => $idet) {
                $invoiceDetails[$key]->SEGMENT = 'LB';
                foreach ($tx8 as $tx) { if( (INT)$idet->CVE_ART === (INT)$tx['sku'] ) { $invoiceDetails[$key]->SEGMENT = 'tx8'; } }
            }
            // META
            $meta = CMeta::select('meta')->where('companie_id',3)->where('type',"metas_tx8")->where('month_search',$request->month)->where('year_search',$request->year)->first();  // Lanson Beckman
            return response()->json([
                "success"               => true,
                "rowDataInventory"      => $setSegment,
                "rowDataInvoiceDetails" => $invoiceDetails,
                "meta"                  => $meta->meta,
            ],200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         "success" => false,
        //         "error" => $th->getMessage(),
        //     ],400);
        // }
    }

    public function getDataDashboard65(Request $request) {
        try {
            $rowDataClients = $this->webService->getClienLB($request->all());
            return response()->json([
                "success" => true,
                "rowDataClients" => $rowDataClients->items,
            ],200);
        } catch (\Throwable $th) {
            return response()->json([
                "success" => false,
                "error" => $th->getMessage(),
            ],400);
        }
    }

    public function downloadExcelInvoice(Request $request) {
        $rowData = $this->webService->getInvoiceReport($request->all());
        $file = Excel::raw(new dashboarInvoice6Export($rowData->items), \Maatwebsite\Excel\Excel::XLSX);
        return [
            "name" => "invoice_data_".$request->dateInit."_to_".$request->dateEnd.".xlsx",
            "file" => base64_encode($file),
        ];
    }
}
