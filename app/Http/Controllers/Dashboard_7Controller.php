<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\classes\WebService;
use App\Exports\dashboar7FileClientsExport;
use App\Models\CAgent;
use App\Models\CMeta;
use App\Models\CUser;
use App\Models\DExternalInvoice;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class Dashboard_7Controller extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }
    public function getDataDashboard7(Request $request) {
        // try {
            $clientExternalInvoice = DExternalInvoice::select(DB::raw('CLIENT_ID AS CVE_CLPV'),'NOMBRE',DB::raw('0 AS TOTAL_IMPORTE, 0 AS COSTO_TOTAL, 0 AS UTILIDAD_TOTAL, 0 AS PERCENTAGE'),'AGENT_NAME')
            ->whereBetween('date_invoice',[$request->dateInit,$request->dateEnd])
            ->groupBy('CLIENT_ID')
            ->get()
            ->toArray();
            $DExternalInvoice = DExternalInvoice::select('SERIE','FOLIO','CVE_DOC','CLIENT_ID','NOMBRE','DES_TOT_PORC','IMPORTE','STATUS','FECHA_DOC','CVE_VEND','AGENT_NAME','CVE_PEDI','CAN_TOT')
            ->whereBetween('date_invoice',[$request->dateInit,$request->dateEnd])
            ->get()
            ->toArray();
            $rowData = $this->webService->getInvoiceReportLS($request->all());
            if(!empty($DExternalInvoice)) { $rowData = $this->getAssignExternalInvoice($rowData,$DExternalInvoice); }
            $rowDataClients = $this->webService->getClienLS($request->all());
            $processClients = $this->proccessClients($rowDataClients->items);
            $rowDataAgents = $this->webService->getAgentsLS($request->all());
            $agentsFinal = $rowDataAgents->items;
            return response()->json([
                "success" => true,
                "rowDataInvoice" => $rowData->items,
                "rowDataInvoicePast" => $rowData->itemsPast,
                "rowDataClients" => $processClients,
                "rowDataAgents"  => $agentsFinal,
            ],200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         "success" => false,
        //         "error" => $th->getMessage(),
        //     ],400);
        // }
    }

    public function downladFileClientsD7(Request $request) {
        // try {


            $file = Excel::raw(new dashboar7FileClientsExport($request->data), \Maatwebsite\Excel\Excel::XLSX);
            return [
                "name" => "Detalle_ventas_clientes.xlsx",
                "file" => base64_encode($file),
            ];
            return response()->json([
                "success" => true,
                "rowDataInvoice" => $rowData->items,
                "rowDataClients" => $rowDataClients->items,
                "rowDataAgents"  => $agentsFinal,
            ],200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         "success" => false,
        //         "error" => $th->getMessage(),
        //     ],400);
        // }
    }

    // PRIVATE
    private function proccessClients($clientsERP) {
        $clientsSistem = CUser::select('id','type_user_id')->get();
        foreach ($clientsERP as $key => $clientERP) {
            $clientsERP[$key]->TYPE_USER_ID = 1;
            foreach ($clientsSistem as  $clientSys) {
                if(
                    (INT)$clientSys['id'] === (INT)$clientERP->CLAVE) {
                    $clientsERP[$key]->TYPE_USER_ID = (INT)$clientSys['type_user_id'];
                }
            }
        }
        return $clientsERP;
    }

    private function getAssignExternalInvoice($rowData,$DExternalInvoice) {
        $total_importe_all = 0;
        foreach ($DExternalInvoice as $key2 => $DEInvoice) {
            $rowData->items[] = json_decode(json_encode($DEInvoice, JSON_FORCE_OBJECT));
            $total_importe_all = (DOUBLE)$total_importe_all + (DOUBLE)$DEInvoice['IMPORTE'];
        }
        $rowData->total_invoice[0]->TOTAL_IMPORTE = (DOUBLE)$rowData->total_invoice[0]->TOTAL_IMPORTE + (DOUBLE)$total_importe_all;
        return $rowData;
    }
}
