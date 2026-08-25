<?php

namespace App\Http\Controllers\BI;

use App\Http\Controllers\Controller;
use App\classes\WebService;
use App\Exports\dashboar1Export;
use App\Exports\Dashboard1ClientsExport;
use App\Exports\Dashboard1ProductsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class Dashboard_1Controller extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }
    public function getDataDashboard1(Request $request) {
        // try {
            switch ($request->invoice_type) {
                case 1: // Invoices
                    // Type report
                    switch ($request->report_type) {
                        case 1: // Clients
                            $rowData = $this->webService->getInvoiceClientReport($request->all());
                        break;
                        case 2: // Invoices
                            $rowData = $this->webService->getInvoiceReport($request->all());
                        break;
                        case 3: // Products
                            $rowData = $this->webService->getInvoiceProductsReport($request->all());
                        break;
                        case 4: // Vendedores
                            $rowData = $this->webService->getInvoiceSellersReport($request->all());
                        break;
                    }
                break;
                case 2: // Credit Notes
                    // Type report
                    switch ($request->report_type) {
                        case 1:
                            $rowData = $this->webService->getCreditNotesReport($request->all());
                        break;
                    }
                break;
                case 3: // Returns
                    // Type report
                    switch ($request->report_type) {
                        case 1: //
                            $rowData = $this->webService->getReturnsReport($request->all());
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


    public function getDetailTypeInvoicesD1(Request $request) {
        // try {
            switch ($request->invoice_type) {
                case 1: // Invoices
                    $rowData = $this->webService->getDetailsInvoiceReport($request->all());
                break;
                case 2: // Credit Notes
                    $rowData = $this->webService->getDetailsCreditNotesReport($request->all());
                break;
                case 3: // Returns
                    $rowData = $this->webService->getDetailsReturnsReport($request->all());
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

    public function downloadExcelD1(Request $request) {

        switch ($request->opt) {
            case 1: // detail invoices
                $rowData1 = $this->webService->getDownloadInvoicesDetails($request->all());
                $rowData2 = $this->webService->getDownloadCreditNotesDetails($request->all());
                $rowData3 = $this->webService->getDownloadReturnsDetails($request->all());
                $rowData = [
                    'invoices' => $rowData1->items,
                    'credit_notes' => $rowData2->items,
                    'returns' => $rowData3->items,
                ];
                $file = Excel::raw(new dashboar1Export($rowData), \Maatwebsite\Excel\Excel::XLSX);
                return [
                    "name" => "Detalle_facturación_LANSON_BECKMAN_".$request->dateInit."_to_".$request->dateEnd.".xlsx",
                    "file" => base64_encode($file),
                ];
            break;
            case 2: // detail product
                $rowData = $this->webService->getInvoiceProductsReport($request->all());
                $rowDataGral = [
                    'products' => $rowData->items,
                    'products_per_clients' => $rowData->items_per_client,
                ];
                $file = Excel::raw(new Dashboard1ProductsExport($rowDataGral), \Maatwebsite\Excel\Excel::XLSX);
                return [
                    "name" => "Detalle_Productos_LANSON_BECKMAN_".$request->dateInit."_to_".$request->dateEnd.".xlsx",
                    "file" => base64_encode($file),
                ];
            break;
            case 3: // detail Clients
                $rowData = $this->webService->getInvoiceClientDownload($request->all());
                $file = Excel::raw(new Dashboard1ClientsExport($rowData->items), \Maatwebsite\Excel\Excel::XLSX);
                return [
                    "name" => "Detalle_Clientes_LANSON_BECKMAN_".$request->dateInit."_to_".$request->dateEnd.".xlsx",
                    "file" => base64_encode($file),
                ];
            break;
        }

        // return Excel::download(new BladeExport($data), 'export.xlsx');
    }

}
