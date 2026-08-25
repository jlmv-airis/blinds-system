<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\classes\ValidateTokenFile;
// Imports
use App\Imports\ItemsOrderImport;
use App\Models\DTempOrder;

class ImportFileController extends Controller
{
    //
    protected $validateTokenFile;

    public function __construct(ValidateTokenFile $validateTokenFile)
    {
        $this->validateTokenFile = $validateTokenFile;
    }
    //
    public function importItemsOrder(Request $request) {
        // try {
            $validateTokenFile = $this->validateTokenFile->verifyToken();
            if($validateTokenFile) {
                $fileExcel = $request->file('fileExcel');
                $dataExcel = Excel::toArray(new ItemsOrderImport, $fileExcel);
                $dataExcel = $dataExcel[0];
                array_splice($dataExcel,0,1);
                $dataInsert = [];
                foreach ($dataExcel as $item) {
                    $dataInsert[] = [
                        "temp_order_id" => $request->temp_order_id,
                        "item_id" => $item[0],
                        "article" => $item[1],
                        "width" => $item[2],
                        "height" => $item[3],
                        "quantity" => $item[4],
                        "mechanism" => $item[5],
                        "side" => $item[6],
                    ];
                }
                DTempOrder::insert($dataInsert);
                return response()->json([
                    'success' => true,
                    'resultData' => $dataInsert,
                ], 200);
            } else {
                return response()->json([
                    'success' =>  false ,
                ], 400);
            }
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false,
        //         'error' => $th,
        //     ], 400);
        // }
    }
}
