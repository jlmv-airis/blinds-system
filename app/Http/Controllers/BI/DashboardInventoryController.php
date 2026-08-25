<?php

namespace App\Http\Controllers\bi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\classes\WebService;
use App\Exports\dashboardInventoryExport;

class DashboardInventoryController extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }

    public function downloadInventoryLotsExcel(Request $request) {
        // obtenemos el inventario
        $dataInventory = [];
        $dataInventoryLots = [];
        $rowData = $this->webService->getMInventoryRT();
        foreach ($rowData->items as $key => $item) {
            $dataInventory[] = [
                'CVE_ART' => $item->CVE_ART,
                'DESCR' => $item->DESCR,
                'UNI_MED' => $item->UNI_MED,
                'EXIST' => $item->EXIST,
            ];
        }
        // Obtenemos los lotes
        $rowDataLotes = $this->webService->getLotesRT();
        foreach ($rowDataLotes->items as $key => $item) {
            $dataInventoryLots[] = [
                'CVE_ART' => $item->CVE_ART,
                'DESCR' => '',
                'LOTE' => $item->LOTE,
                'CVE_ALM' => $item->CVE_ALM,
                'EXIST' => $item->CANTIDAD,
            ];
        }
        // agregamos descripcion al lote
        foreach ($dataInventory as $inv) {
            foreach ($dataInventoryLots as $key =>  $lots) {
                if($inv['CVE_ART'] == $lots['CVE_ART']) {
                    $dataInventoryLots[$key]['DESCR'] = $inv['DESCR'];
                }
            }
        }
        $inventory = [
            'inventory' => $dataInventory,
            'lotes' => $dataInventoryLots,
        ];
        $file = Excel::raw(new dashboardInventoryExport($inventory), \Maatwebsite\Excel\Excel::XLSX);
        return [
            "name" => "inventario_ROLLERTEX.xlsx",
            "file" => base64_encode($file),
        ];
    }
}
