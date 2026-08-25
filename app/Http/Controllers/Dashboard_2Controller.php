<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\classes\WebService;
use App\Exports\dashboar2Export;
use App\Exports\Dashboard2ClientsExport;
use App\Exports\Dashboard2ProductsExport;
use Maatwebsite\Excel\Facades\Excel;

class Dashboard_2Controller extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }
    public function getDataDashboard2(Request $request) {
        // try {
            switch ($request->invoice_type) {
                case 1: // Invoices
                    // Type report
                    switch ($request->report_type) {
                        case 1: // Clients
                            $rowData = $this->webService->getInvoiceClientReportRT($request->all());
                        break;
                        case 2: // Invoices
                            $rowData = $this->webService->getInvoiceReportRT($request->all());
                        break;
                        case 3: // Products
                            $rowData = $this->webService->getInvoiceProductsReportRT($request->all());
                        break;
                        case 4: // Vendedores
                            $rowData = $this->webService->getInvoiceSellersReportRT($request->all());
                        break;
                    }
                break;
                case 2: // Credit Notes
                    // Type report
                    switch ($request->report_type) {
                        case 1:
                            $rowData = $this->webService->getCreditNotesReportRT($request->all());
                        break;
                    }
                break;
                case 3: // Returns
                    // Type report
                    switch ($request->report_type) {
                        case 1: //
                            $rowData = $this->webService->getReturnsReportRT($request->all());
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

    public function getDetailTypeInvoicesD2(Request $request) {
        // try {
            switch ($request->invoice_type) {
                case 1: // Invoices
                    $rowData = $this->webService->getDetailsInvoiceReportRT($request->all());
                break;
                case 2: // Credit Notes
                    $rowData = $this->webService->getDetailsCreditNotesReportRT($request->all());
                break;
                case 3: // Returns
                    $rowData = $this->webService->getDetailsReturnsReportRT($request->all());
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

    public function downloadExcelD2(Request $request) {

        switch ($request->opt) {
            case 1: // detail invoices
                $rowData1 = $this->webService->getDownloadInvoicesDetailsRT($request->all());
                $rowData2 = $this->webService->getDownloadCreditNotesDetailsRT($request->all());
                $rowData3 = $this->webService->getDownloadReturnsDetailsRT($request->all());
                $rowData = [
                    'invoices' => $rowData1->items,
                    'credit_notes' => $rowData2->items,
                    'returns' => $rowData3->items,
                ];
                $file = Excel::raw(new dashboar2Export($rowData), \Maatwebsite\Excel\Excel::XLSX);
                return [
                    "name" => "Detalle_facturación_ROLLERTEX_".$request->dateInit."_to_".$request->dateEnd.".xlsx",
                    "file" => base64_encode($file),
                ];
            break;
            case 2: // detail product
                $rowData = $this->webService->getInvoiceProductsReportRT($request->all());
                $rowDataGral = [
                    'products' => $rowData->items,
                    'products_per_clients' => $rowData->items_per_client,
                ];
                $file = Excel::raw(new Dashboard2ProductsExport($rowDataGral), \Maatwebsite\Excel\Excel::XLSX);
                return [
                    "name" => "Detalle_Productos_ROLLERTEX_".$request->dateInit."_to_".$request->dateEnd.".xlsx",
                    "file" => base64_encode($file),
                ];
            break;
            case 3: // detail Clients
                $rowData = $this->webService->getInvoiceClientDownloadRT($request->all());
                $file = Excel::raw(new Dashboard2ClientsExport($rowData->items), \Maatwebsite\Excel\Excel::XLSX);
                return [
                    "name" => "Detalle_Clientes_ROLLERTEX".$request->dateInit."_to_".$request->dateEnd.".xlsx",
                    "file" => base64_encode($file),
                ];
            break;
        }

        // return Excel::download(new BladeExport($data), 'export.xlsx');
    }
}
