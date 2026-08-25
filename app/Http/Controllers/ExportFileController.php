<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\classes\ValidateTokenFile;
use App\Exports\ClientsExport;
use App\Exports\itemsTempExport;
use App\classes\WebService;

class ExportFileController extends Controller
{
    //
    protected $webService;
    protected $validateTokenFile;

    public function __construct(ValidateTokenFile $validateTokenFile,WebService $webService)
    {
        $this->validateTokenFile = $validateTokenFile;
        $this->webService = $webService;
    }
    public function downloadImportOrder()
    {
         //
        try {
            $validateTokenFile = $this->validateTokenFile->verifyToken();
            if($validateTokenFile) {
                return Excel::download( new itemsTempExport, 'Importar_items_pedido.csv');
            } else {
                return response()->json([
                    'success' =>  false ,
                ], 400);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   => $th,
            ], 400);
        }
    }

    public function downloadClients()
    {
         //
        try {

            $rowData = $this->webService->getClienRT();
            $file = Excel::raw(new ClientsExport($rowData->items), \Maatwebsite\Excel\Excel::XLSX);
            return [
                "name" => "Clients_data.xlsx",
                "file" => base64_encode($file),
            ];
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   => $th,
            ], 400);
        }
    }
}
