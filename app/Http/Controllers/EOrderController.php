<?php

namespace App\Http\Controllers;

require_once app_path() . "/fpdf/fpdf.php";
require_once app_path() . "/fpdf/PDF_Code128.php";

use App\classes\Logs;
use App\classes\Notifications;
use App\Models\CArticle;
use App\Models\CManufacturingCutting;
use App\Models\CMaterialRequest;
use App\Models\CProductionLine;
use App\Models\DErpAccessUser;
use App\Models\DMaterialRequest;
use App\Models\DModulation;
use App\Models\DMovement;
use App\Models\DOrder;
use App\Models\DSocketConnection;
use App\Models\EMaterialRequest;
use App\Models\EModulation;
use App\Models\EOrder;
use App\Models\EProductionLine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use App\classes\FPDF;
use App\Models\DViewNotification;
use App\Models\ENotification;
use PDF_Code128;
use App\classes\WebService;
use App\Exports\orderExcelExport;
use App\Models\CProductionLotCost;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use App\classes\GetTotal;
use App\classes\Modulation;
use App\Models\CFormCost;
use App\Models\DGuaranty;
use App\Models\DProductionLine;
use App\Models\DProductionLocation;
use App\Models\DSection;
use App\Models\EGuaranty;
use App\Models\ESection;

class EOrderController extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // try {
            $search = $request->search;
            $orders = $this->allOrders($request->page,$request->limit,'',$request->isSearch);
            //TOTALS REGS
            $pageCountData = EOrder::select( DB::raw('COUNT(*) as num') )
            ->join('c_users','c_users.id','e_orders.client_id')
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id');
            if( (INT)$request->isSearch) {
                $pageCountData->where( function($query) use ($search){
                    return $query
                    ->orWhere('e_orders.id','like','%'.$search.'%')
                    ->orWhere('e_orders.user_id','like','%'.$search.'%')
                    ->orWhere('c_erp_info_users.short_name','like','%'.$search.'%')
                    ->orWhere('e_orders.client_id','like','%'.$search.'%')
                    ->orWhere('c_users.full_name','like','%'.$search.'%')
                    ->orWhere('e_orders.proyect_name','like','%'.$search.'%');
                });
            }
            $pageCountData = $pageCountData->first();
            // COST
            $costForm = CFormCost::where('is_active',1)->get();
            // dd($orders);
            return response()->json([
                'success'   =>  true ,
                'pageCount' =>  ceil($pageCountData->num/$request->limit),
                'orders'    => $orders,
                'costForm'  => $costForm,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }
    public function getOrdersPags(Request $request)
    {
        // try {
            $search = $request->search;
            $orders = $this->allOrders($request->page,$request->limit,$search,$request->isSearch);
            //TOTALS REGS
            $pageCountData = EOrder::select( DB::raw('COUNT(*) as num') )
            ->join('c_users','c_users.id','e_orders.client_id')
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id');
            if( (INT)$request->isSearch) {
                $pageCountData->where( function($query) use ($search){
                    return $query
                    ->orWhere('e_orders.id','like','%'.$search.'%')
                    ->orWhere('e_orders.user_id','like','%'.$search.'%')
                    ->orWhere('e_orders.quotation_id','like','%'.$search.'%')
                    ->orWhere('c_erp_info_users.short_name','like','%'.$search.'%')
                    ->orWhere('e_orders.client_id','like','%'.$search.'%')
                    ->orWhere('c_users.full_name','like','%'.$search.'%')
                    ->orWhere('e_orders.proyect_name','like','%'.$search.'%');
                });
            }
            $pageCountData = $pageCountData->first();
            // COST
            $costForm = CFormCost::where('is_active',1)->get();
            // dd($orders);
            return response()->json([
                'success'   =>  true ,
                'pageCount' =>  ceil($pageCountData->num/$request->limit),
                'orders'    => $orders,
                'costForm'  => $costForm,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    public function downloadOrdersExcel(Request $request)
    {
        try {
            switch ((INT)$request->opt) {
                case 1: // one order
                    $nameFile = "Detalle_Pedido_".$request->order_id."";
                    $rowData = DOrder::select('d_orders.order_id','d_orders.item_id','d_orders.quantity','d_orders.width','d_orders.height',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE c_articles.article END AS article'),'c_mechanism_sides.mechanism_side','c_mechanisms.mechanism','d_orders.price','d_orders.area_description','d_orders.commit_client'  )
                    ->join('c_articles','c_articles.id','d_orders.article_id')
                    ->leftJoin('c_articles AS la','la.id','d_orders.lambrequin_id')
                    ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_orders.mechanism_side_id')
                    ->leftJoin('c_mechanisms','c_mechanisms.id','d_orders.mechanism_id')
                    ->where('order_id',$request->order_id)
                    ->get()
                    ->toArray();

                break;
                case 2: // per material request
                    $getOrders = DMaterialRequest::select('d_orders.order_id')
                    ->join('d_orders','d_orders.id','d_material_requests.detail_order_id')
                    ->where('d_material_requests.material_request_id',$request->request_id)
                    ->groupBy('d_orders.order_id')
                    ->get();
                    $ordersID = [];
                    foreach ($getOrders as $order) { $ordersID[] = $order['order_id']; }
                    $nameFile = "Detalle_Pedidos_Solicitud_".$request->request_id."";
                    $rowData = DOrder::select('d_orders.order_id','d_orders.item_id','d_orders.quantity','d_orders.width','d_orders.height',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE c_articles.article END AS article'),'c_mechanism_sides.mechanism_side','c_mechanisms.mechanism','d_orders.price','d_orders.area_description','d_orders.commit_client'  )
                    ->join('c_articles','c_articles.id','d_orders.article_id')
                    ->leftJoin('c_articles AS la','la.id','d_orders.lambrequin_id')
                    ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_orders.mechanism_side_id')
                    ->leftJoin('c_mechanisms','c_mechanisms.id','d_orders.mechanism_id')
                    ->whereIn('order_id',$ordersID)
                    ->orderBy('d_orders.order_id')
                    ->orderBy('d_orders.item_id')
                    ->get()
                    ->toArray();
                break;
            }

            $file = Excel::raw(new orderExcelExport($rowData), \Maatwebsite\Excel\Excel::XLSX);
            return [
                "name" => $nameFile.".xls",
                "file" => base64_encode($file),
            ];
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }

    public function getPreOrders(Request $request)
    {
        // try {
            $orders = $this->preOrdersAuth($request->check);
            return response()->json([
                'success' =>  true ,
                'orders'  => $orders,
                'check'   => $request->check,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    public function getMaterialRequestOrders()
    {
        // try {
            $orders = $this->allStatusOrders(2);
            $guarantee  = $this->getGuarantee();
            $allData = [];
            foreach ($orders as $key => $order) {
                $orders[$key]['type_reg'] = 1;
                $allData[] = $orders[$key];
            }
            foreach ($guarantee as $key2 => $warranty) {
                $guarantee[$key2]['type_reg'] = 2;
                $allData[] = $guarantee[$key2];
            }


            return response()->json([
                'success'      =>  true ,
                'orders' => $allData,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function getMaterialRequests()
    {
        // try {
            $EMaterialRequest = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
            ->where('e_material_requests.status_id',1)
            ->where('e_material_requests.is_active',1)
            ->orderBy('is_complete','asc')
            ->get();
            $materialRequestIDS = [];
            foreach ($EMaterialRequest as $mr) { $materialRequestIDS[] = $mr['id']; }
            $DMaterialRequest = DMaterialRequest::select('d_material_requests.detail_order_id','d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit', 'd_material_requests.cost',DB::raw('SUM(d_material_requests.quantity) AS quantity, (SUM(d_material_requests.quantity) * d_material_requests.cost ) AS total') )
            ->join('c_articles','c_articles.id','d_material_requests.article_id')
            ->join('c_units','c_units.id','d_material_requests.unit_id')
            ->groupBy('d_material_requests.detail_order_id','d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit','d_material_requests.cost')
            ->whereIn('d_material_requests.material_request_id',$materialRequestIDS)
            ->get();
            $materialRequests = $this->setMaterialRequests($EMaterialRequest->toArray(),$DMaterialRequest->toArray());
            // invetory rollertex
            $rowData = [];
            // $rowData = $this->webService->getInventoryRT();
            // var_dump($rowData);
            return response()->json([
                'success'      =>  true ,
                'materialRequests' => $materialRequests,
                'inventory' => !$rowData ? [] : $rowData->items,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function getMaterialAssortment(Request $request)
    {
        // try {
            $DMaterialRequest = [];
            $EMaterialRequest = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.request_user_id','ur.short_name AS request_short_name','e_material_requests.request_date','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
            ->join('c_erp_info_users AS ur','ur.user_id','e_material_requests.request_user_id')
            ->where('e_material_requests.status_id',2)
            ->get();
            // CLOTHS
            $statementCloth = DB::getPdo()->prepare("CALL sp_modulation(9,0,1,0,0,0.0,0.0,0.0,'','','','','')"); // 1 - Rollertex
            $statementCloth->execute();
            do {  $resultsCloth[] = $statementCloth->fetchAll(\PDO::FETCH_OBJ); } while ($statementCloth->nextRowSet());
            foreach (json_decode(json_encode($resultsCloth[0]), true) as $value) { $DMaterialRequest[] = $value; }
            // ACC
            $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(10,0,1,0,0,0.0,0.0,0.0,'','','','','')"); // 1 - Rollertex
            $statementAcc->execute();
            do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
            foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $DMaterialRequest[] = $value; }


            $materialRequestIDS = [];
            foreach ($EMaterialRequest as $mr) { $materialRequestIDS[] = $mr['id']; }
            $DMaterialRequest = DMaterialRequest::select('d_material_requests.detail_order_id','d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit', 'd_material_requests.cost',DB::raw('SUM(d_material_requests.quantity) AS quantity, (SUM(d_material_requests.quantity) * d_material_requests.cost ) AS total') )
            ->join('c_articles','c_articles.id','d_material_requests.article_id')
            ->join('c_units','c_units.id','d_material_requests.unit_id')
            ->where('d_material_requests.provider_id',1)
            ->groupBy('d_material_requests.detail_order_id','d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit','d_material_requests.cost')
            ->whereIn('d_material_requests.material_request_id',$materialRequestIDS)
            ->get();
            $materialAssortment = $this->setMaterialRequestsWH($EMaterialRequest->toArray(),$DMaterialRequest->toArray());
            // SECIONS
            $ESection = ESection::select('e_sections.id','e_sections.user_id','cu.short_name','e_sections.request_user_id','ru.short_name AS request_short_name','e_sections.request_date',DB::raw('0 AS is_complete,0 AS check1,0 AS invoice_id'),'e_sections.created_at','c_companies.nomen','e_sections.company_id','c_companies.company')
            ->join('c_erp_info_users AS cu','cu.user_id','e_sections.user_id')
            ->join('c_erp_info_users AS ru','ru.user_id','e_sections.request_user_id')
            ->join('c_companies','c_companies.id','e_sections.company_id')
            ->where('e_sections.status_id',4)
            ->get()
            ->toArray();
            $company_id = $request->company_id;
            $DSection = DSection::select('d_sections.id','d_sections.section_id','d_sections.sku','c_inventory_products.product','d_sections.section')
            ->leftJoin('c_inventory_products', function($join) use($company_id){
                $join->on('c_inventory_products.sku', '=', 'd_sections.sku')
                    ->where('c_inventory_products.company_id', '=', $company_id);
            })
            ->join('e_sections','e_sections.id','d_sections.section_id')
            ->where('e_sections.status_id',4)
            ->get()
            ->toArray();
            $sections = $this->setSections($ESection,$DSection);

            $dataMA = [];
            foreach ($materialAssortment as  $ma) { $dataMA[] = $ma; }
            foreach ($sections as  $sec) { $dataMA[] = $sec; }

            return response()->json([
                'success'      =>  true ,
                'materialAssortment' => $dataMA,
            ], 200);

        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    public function getCheckMaterialAssortment()
    {
        try {
            $DMaterialRequest = [];
            $EMaterialRequest = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.request_user_id','ur.short_name AS request_short_name','e_material_requests.request_date','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
            ->join('c_erp_info_users AS ur','ur.user_id','e_material_requests.request_user_id')
            ->where('e_material_requests.status_id',2)
            ->where('e_material_requests.check1',0)
            ->get();
            // CLOTHS
            $statementCloth = DB::getPdo()->prepare("CALL sp_modulation(9,0,1,0,0,0.0,0.0,0.0,'','','','','')"); // 1 - Rollertex
            $statementCloth->execute();
            do {  $resultsCloth[] = $statementCloth->fetchAll(\PDO::FETCH_OBJ); } while ($statementCloth->nextRowSet());
            foreach (json_decode(json_encode($resultsCloth[0]), true) as $value) { $DMaterialRequest[] = $value; }
            // ACC
            $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(10,0,1,0,0,0.0,0.0,0.0,'','','','','')"); // 1 - Rollertex
            $statementAcc->execute();
            do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
            foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $DMaterialRequest[] = $value; }

            $materialRequestIDS = [];
            foreach ($EMaterialRequest as $mr) { $materialRequestIDS[] = $mr['id']; }
            $DMaterialRequest = DMaterialRequest::select('d_material_requests.detail_order_id','d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit', 'd_material_requests.cost',DB::raw('SUM(d_material_requests.quantity) AS quantity, (SUM(d_material_requests.quantity) * d_material_requests.cost ) AS total') )
            ->join('c_articles','c_articles.id','d_material_requests.article_id')
            ->join('c_units','c_units.id','d_material_requests.unit_id')
            ->where('d_material_requests.provider_id',1)
            ->groupBy('d_material_requests.detail_order_id','d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit','d_material_requests.cost')
            ->whereIn('d_material_requests.material_request_id',$materialRequestIDS)
            ->get();
            $checkMaterialAssortment = $this->setMaterialRequests($EMaterialRequest->toArray(),$DMaterialRequest->toArray());
            return response()->json([
                'success'      =>  true ,
                'checkMaterialAssortment' => $checkMaterialAssortment,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 200);
        }
    }

    public function getValidateMaterial(Request $request)
    {
        // try {
            $DMaterialRequest = [];
            $EMaterialRequest = EMaterialRequest::select('e_material_requests.id','e_material_requests.request_user_id','c_erp_info_users.short_name as request_short_name','e_material_requests.provider_user_id','ur.short_name AS provider_short_name','e_material_requests.material_assortment_date','e_material_requests.request_date','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.request_user_id')
            ->join('c_erp_info_users AS ur','ur.user_id','e_material_requests.provider_user_id')
            ->where('e_material_requests.status_id',3)
            ->get();
            // CLOTHS
            $statementCloth = DB::getPdo()->prepare("CALL sp_modulation(9,0,1,0,0,0.0,0.0,0.0,'','','','','')"); // 1 - Rollertex
            $statementCloth->execute();
            do {  $resultsCloth[] = $statementCloth->fetchAll(\PDO::FETCH_OBJ); } while ($statementCloth->nextRowSet());
            foreach (json_decode(json_encode($resultsCloth[0]), true) as $value) { $DMaterialRequest[] = $value; }
            // ACC
            $statementAcc = DB::getPdo()->prepare("CALL sp_modulation(10,0,1,0,0,0.0,0.0,0.0,'','','','','')"); // 1 - Rollertex
            $statementAcc->execute();
            do {  $resultsAcc[] = $statementAcc->fetchAll(\PDO::FETCH_OBJ); } while ($statementAcc->nextRowSet());
            foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $DMaterialRequest[] = $value; }

            $materialRequestIDS = [];
            foreach ($EMaterialRequest as $mr) { $materialRequestIDS[] = $mr['id']; }
            $DMaterialRequest = DMaterialRequest::select('d_material_requests.detail_order_id','d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit', 'd_material_requests.cost',DB::raw('SUM(d_material_requests.quantity) AS quantity, (SUM(d_material_requests.quantity) * d_material_requests.cost ) AS total') )
            ->join('c_articles','c_articles.id','d_material_requests.article_id')
            ->join('c_units','c_units.id','d_material_requests.unit_id')
            ->where('d_material_requests.provider_id',1)
            ->groupBy('d_material_requests.detail_order_id','d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit','d_material_requests.cost')
            ->whereIn('d_material_requests.material_request_id',$materialRequestIDS)
            ->get();
            $validMaterial = $this->setMaterialRequestsWH($EMaterialRequest->toArray(),$DMaterialRequest->toArray());
            // SECIONS
            $ESection = ESection::select('e_sections.id','e_sections.request_user_id','cu.short_name AS request_short_name','e_sections.provider_user_id','pu.short_name AS provider_short_name','e_sections.request_date','e_sections.material_assortment_date',DB::raw('0 AS is_complete,0 AS check1,0 AS invoice_id'),'e_sections.created_at','c_companies.nomen','e_sections.company_id','c_companies.company')
            ->join('c_erp_info_users AS cu','cu.user_id','e_sections.request_user_id')
            ->join('c_erp_info_users AS pu','pu.user_id','e_sections.provider_user_id')
            ->join('c_companies','c_companies.id','e_sections.company_id')
            ->where('e_sections.status_id',5)
            ->get()
            ->toArray();
            $company_id = $request->company_id;
            $DSection = DSection::select('d_sections.id','d_sections.section_id','d_sections.sku','c_inventory_products.product','d_sections.section')
            ->leftJoin('c_inventory_products', function($join) use($company_id){
                $join->on('c_inventory_products.sku', '=', 'd_sections.sku')
                    ->where('c_inventory_products.company_id', '=', $company_id);
            })
            ->join('e_sections','e_sections.id','d_sections.section_id')
            ->where('e_sections.status_id',5)
            ->get()
            ->toArray();
            $sections = $this->setSections($ESection,$DSection);

            $dataMA = [];
            foreach ($validMaterial as  $ma) { $dataMA[] = $ma; }
            foreach ($sections as  $sec) { $dataMA[] = $sec; }


            return response()->json([
                'success'       =>  true ,
                'validMaterial' => $dataMA,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function getProductionOrders()
    {
        // try {
            $orders = $this->allStatusOrders(7);
            $guarantee = $this->allStatusGuarantee(3);
            $allData = [];
            foreach ($orders as $key => $order) {
                $orders[$key]['type_reg'] = 1;
                $allData[] = $orders[$key];
            }
            foreach ($guarantee as $key2 => $warranty) {
                $guarantee[$key2]['type_reg'] = 2;
                $allData[] = $guarantee[$key2];
            }

            $stopOrders = $this->allStatusOrders(14);
            $ordersAssign = $this->allAssignOrders();
            $orderDetailID = [];
            foreach ($ordersAssign as $order) { foreach ($order['details'] as $detialOrder) { $orderDetailID[] = $detialOrder['id']; } }
            $materialRequest = DMaterialRequest::select('d_material_requests.material_request_id','d_orders.order_id')
            ->join('d_orders','d_orders.id','d_material_requests.detail_order_id')
            ->whereIn('d_material_requests.detail_order_id',$orderDetailID)
            ->groupBy('d_material_requests.material_request_id','d_orders.order_id')
            ->get();
            foreach ($ordersAssign as $key => $order) {
                $ordersAssign[$key]['material_request_id'] = null;
                foreach ($materialRequest as $mr) {
                    if($mr['order_id'] == $order['id']) {
                        $ordersAssign[$key]['material_request_id'] = $mr['material_request_id'];
                    }
                }
            }
            foreach ($stopOrders as $key => $order) {
                $stopOrders[$key]['material_request_id'] = null;
                foreach ($materialRequest as $mr) {
                    if($mr['order_id'] == $order['id']) {
                        $stopOrders[$key]['material_request_id'] = $mr['material_request_id'];
                    }
                }
            }
            $productionLines = CProductionLine::select('id','line','capacity')->where('is_active',1)->get();
            $productionLocations = DProductionLocation::select('id','location','row','col')->where('is_active',1)->where('is_occupied',0)->get();
            // $modulations = EProductionLine::select('e_production_lines.id','e_production_lines.user_id','e_production_lines.production_line_id','c_production_lines.line','e_production_lines.production_date')
            // ->join('c_production_lines','c_production_lines.id','e_production_lines.production_line_id')
            // ->get();
            $productionsRequests = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.production_date','e_material_requests.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id','e_production_lines.id AS production_line_id')
            ->leftJoin('e_production_lines','e_production_lines.material_request_id','e_material_requests.id')
            ->whereNotIn('e_material_requests.status_id',[1])
            ->whereNotNull('e_material_requests.production_date')
            ->orderBy('e_material_requests.is_complete','asc')
            ->get();
            $costForm = CFormCost::where('is_active',1)->get();
            return response()->json([
                'success'               => true ,
                'orders'                => $allData,
                'ordersAssign'          => $ordersAssign,
                'stopOrders'            => $stopOrders,
                'productionLines'       => $productionLines,
                'productionLocations'   => $productionLocations,
                'costForm'              => $costForm,
                // 'modulations'     => $modulations,
                'productionsRequests' => $productionsRequests,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function getPlOrders()
    {
        try {
            $orders = $this->allStatusPLOrders(7);
            $guarantee = $this->allStatusPLGuarantee(3);
            $allData = [];
            foreach ($orders as $key => $order) {
                $orders[$key]['type_reg'] = 1;
                $allData[] = $orders[$key];
            }
            foreach ($guarantee as $key2 => $warranty) {
                $guarantee[$key2]['type_reg'] = 2;
                $allData[] = $guarantee[$key2];
            }
            $productionLocations = DProductionLocation::select('id','location','row','col')
            ->where('is_active',1)
            ->where( function($query) {
                return $query
                ->where('is_occupied', 0)
                ->orWhere('id', '=', 257);
            })
            ->get();

            return response()->json([
                'success'               =>  true ,
                'orders'                => $allData,
                'productionLocations'   => $productionLocations,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EOrder  $eOrder
     * @return \Illuminate\Http\Response
     */
    public function show(EOrder $eOrder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EOrder  $eOrder
     * @return \Illuminate\Http\Response
     */
    public function edit(EOrder $eOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EOrder  $eOrder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EOrder $eOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EOrder  $eOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(EOrder $eOrder)
    {
        //
    }

    public function setStatus(Request $request)
    {
        // try {
            switch ((INT)$request->check) {
                case 1:
                    EOrder::where('id',$request->order_id)
                    ->update(['check1'=>1]);
                    // buscamos los usuarios del cambio
                    $users_ids = DErpAccessUser::select('user_id as id')
                    ->where('module_id', 4)
                    ->where('submodule_id', 10)
                    ->get();
                    //
                    $module_id = 4;
                    $submodule_id = 10;
                break;
                case 2:
                    EOrder::where('id',$request->order_id)
                    ->update([
                        'check2'=>1,
                    ]);
                    // buscamos los usuarios del cambio
                    $users_ids = DErpAccessUser::select('user_id as id')
                    ->where('module_id', 5)
                    ->where('submodule_id', 26)
                    ->get();
                    //
                    $module_id = 5;
                    $submodule_id = 26;
                break;
            }
            // LOG DELCHECK
            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
            $logs->createMovementLog($request->user_id,'Valido el pedido para su autorizacion',1,$module_id,$submodule_id,3,'order_id',$request->order_id,'Se realizó el check para la autorización del pedido.');
            // verificamos auth de los checks
            $checks = EOrder::select('check1','check2')->where('id',$request->order_id)->first();
            if( (INT)$checks->check1 === 1 AND (INT)$checks->check2 === 1 ) {
                $status_id = 0;
                $users_socket_ids = [];
                $users_socket_notifications_ids = [];
                $date_now = Carbon::now()->toDateTimeString();
                $only = true;
                $status_text = '';
                // Actualizamos el sttus
                $update = EOrder::where('is_active',1);
                $module_id = 4;
                $submodule_id = 10;
                $status_id = 2;
                $status_text = 'Nuevo';
                $update->where('status_id',1);
                // creamos notificaciones para solicitud  de material despues puede  cambiar a  quine le avisas
                $users_not_ids = DErpAccessUser::select('user_id as id')
                ->where('module_id', 6)
                ->where('submodule_id', 14)
                ->get();
                $to = '/warehouse/material';
                $message = [
                    "title"       => 'Solicitar material',
                    "description" => 'Tienes una nuevo pedido para solicitud de material ('.$request->order_id.')',
                    "icon"        => 'mdi-palette-swatch',
                    "icon_color"  => '#E7A426',
                ];
                $data_update = [
                    'status_id' => $status_id,
                    'authorization_date' => $date_now,
                ];
                // buscamos el id de la notificacion a cambiar
                $notificationPre = ENotification::select('id')
                ->where('identifier',$request->order_id)
                ->where('title','=','Pedido por aprobar')
                ->first();
                // Guardamos usuarios para el socket
                foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
                $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
                // cremos notificacion
                if($only) { $orderID =  $request->order_id; } else { $orderID =  null; }
                $notifications = new Notifications;
                $notification = $notifications->createNewNotification($orderID,1,0,$users_not_ids,$message,$to);
                foreach ($users_not_ids as $value_not) { $users_socket_notifications_ids[] = $value_not['id']; }
                $users_socket_notification = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_notifications_ids)->where('user_type','ERP')->get();
                // Guardamos en LOGS
                if($only) {
                    $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
                    $logs->createMovementLog($request->user_id,'Actualizó status de pedido',1,$module_id,$submodule_id,3,'order_id',$orderID,'Se actualizo status de pedido a '.$status_text);
                } else {
                    foreach ($request->order_id as $order) {
                        $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
                        $logs->createMovementLog($request->user_id,'Actualizó status de pedido',1,$module_id,$submodule_id,3,'order_id',$order['id'],'Se actualizo status de pedido a '.$status_text);
                    }
                }
                // Relizamos actualizacion
                if($only) { $update->where('id',$request->order_id); }
                $update->update($data_update);
                // GET ORDER
                $order = $this->getIndividualOrder($request->order_id);
                return response()->json([
                    'success'                   =>  true ,
                    'opt'                       => $request->opt,
                    'order_id'                  => $request->order_id,
                    'users_socket'              => $users_socket,
                    'users_socket_notification' => $users_socket_notification,
                    'notification'              => $notification,
                    'pre_notification_id'       => $notificationPre->id,
                    'is_change_mr'              => true,
                    'check'                     => $request->check,
                    'order'                     => $order
                ], 200);
            } else {
                // Guardamos usuarios para el socket
                foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
                $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();

                return response()->json([
                    'success'   =>  true ,
                    'is_change_mr' => false,
                    'order_id'  => $request->order_id,
                    'users_socket'              => $users_socket,
                    'check'     => $request->check
                ], 200);
            }
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function createMaterialRequest(Request $request)
    {
        // try {
            // ARTICLES
            $articles = CArticle::select('c_articles.id','c_articles.article','c_articles.unit_id','c_articles.cost','c_articles.model_id','c_models.product_id','width_lot')
            ->join('c_models','c_models.id', 'c_articles.model_id')
            ->get();
            // REQUESTS
            $materialRequests = CMaterialRequest::select('c_material_requests.*','c_articles.article',)
            ->join('c_articles','c_articles.id','c_material_requests.article_id')
            ->where('c_material_requests.is_active',1)
            ->get();
            // CUTS
            $cuts = CManufacturingCutting::select(   'id', 'product_id', 'operation_id', 'side_id', 'mechanism_side_id', 'divisions', 'is_cassette', 'is_bracket', 'mm_motor', 'motor_model_id', 'tube_id', 'width_discount',  'counterweight_discount', 'covered_counterweight_discount', 'turn_bar_discount', 'tube_discount', 'fascia_discount' )
            ->where('is_active',1)
            ->get();
            // REQUEST
            $EmaterialRequest            = new EMaterialRequest;
            $EmaterialRequest->user_id   = $request->user_id;
            $EmaterialRequest->save();
            // // PRDUCTION LINE
            // $EProductionLine                        = new EProductionLine();
            // $EProductionLine->user_id               = $request->user_id;
            // $EProductionLine->material_request_id   = $EmaterialRequest->id;
            // $EProductionLine->production_line_id    = 1;
            // $EProductionLine->save();
            // MODULATION
            $EModulation                     = new EModulation;
            $EModulation->user_id            = $request->user_id;
            $EModulation->line_production_id = $EmaterialRequest->id; // por el momento el material Request id estara escrito en el  line  production id
            $EModulation->save();
            //
            $dataRequestRecord = [];
            $dataModulationRecord = [];
            $ordersIDs = [];
            $ordersIDsTxt = '';
            $guaranteeIDs = [];
            $guaranteeIDsTxt = '';
            $orderDetails = [];
            $ubica=1;
            foreach ( $request->orders as $order ) {
                // separamos los pedidos de las garantias para realizar su actualizacion
                if( $order['nomen'] == 'GLS' ||  $order['nomen'] == 'SLS' ) { $guaranteeIDs[] = $order['id']; $guaranteeIDsTxt .= ','.$order['id']; }
                if( $order['nomen'] == 'LS' ) { // temporal mientras se define si se llegara a explosionar o no
                    $ordersIDs[] = $order['id'];
                    $ordersIDsTxt .= ','.$order['id'];
                    foreach ($order['details'] as $key => $item) {
                        if( $item['product_id'] == 1 OR $item['product_id'] == 2 OR $item['product_id'] == 5) {
                            $requests = $this->requestItems($item,$articles,$materialRequests,$order['details'],$order['nomen']);
                            // $requests = $this->requestItemsGuarantee($item,$articles,$materialRequests,$order);
                            foreach ($requests as $request) {
                                $dataRequestRecord[] = [
                                    'material_request_id'  => $EmaterialRequest->id,
                                    'detail_order_id'      => $item['id'],
                                    // 'item_id'              => $request['item_id'],
                                    // 'article'              => $request['value'],
                                    'article_id'           => $request['article_id'],
                                    'unit_id'              => $request['unit_id'],
                                    'quantity'             => $request['quantity'],
                                    'cost'                 => $request['cost'],
                                    'width_lot'            => $request['width_lot'],
                                    'relation_item'        => !is_null($request['width_lot']) ? 1 : null ,
                                    'type_reg'             => $order['type_reg'],
                                ];
                            }
                            // la modulacion se realizara mediante la solicitud de material, pero luago cambiara de proceso
                            $modulations = $this->discountItems($item,$cuts,$order['details']);
                            $dataModulationRecord[] = [
                                'modulation_id'          => $EModulation->id,
                                'detail_order_id'        => $item['id'],
                                'height_add'             => $modulations['height_add'],
                                'width_discount'         => $modulations['width_discount'],
                                'counterweight_discount' => $modulations['counterweight_discount'],
                                'turn_bar_discount'      => $modulations['turn_bar_discount'],
                                'tube_discount'          => $modulations['tube_discount'],
                                'fascia_discount'        => $modulations['fascia_discount'],
                                'join_id'                => null,
                                'type_reg'               => $order['type_reg'],
                            ];
                            // Agregamos la informacin al pedido
                            $order['details'][$key]['counterweight_discount'] = $modulations['counterweight_discount'];
                            $order['details'][$key]['turn_bar_discount'] = $modulations['turn_bar_discount'];
                            $order['details'][$key]['tube_discount'] = $modulations['tube_discount'];
                            $order['details'][$key]['fascia_discount'] = $modulations['fascia_discount'];

                        }
                        if( $item['product_id'] == 4 ) {
                            $requests = $this->requestItems($item,$articles,$materialRequests,$order['details'],$order['nomen']);
                            // $requests = $this->requestItemsGuarantee($item,$articles,$materialRequests,$order);
                            foreach ($requests as $request) {
                                $dataRequestRecord[] = [
                                    'material_request_id'  => $EmaterialRequest->id,
                                    'detail_order_id'      => $item['id'],
                                    // 'item_id'              => $request['item_id'],
                                    // 'article'              => $request['value'],
                                    'article_id'           => $request['article_id'],
                                    'unit_id'              => $request['unit_id'],
                                    'quantity'             => $request['quantity'],
                                    'cost'                 => $request['cost'],
                                    'width_lot'            => $request['width_lot'],
                                    'relation_item'        => null,
                                    'type_reg'             => $order['type_reg'],
                                ];
                            }
                            if((INT)$item['model_id'] === 25) { // LAMBREQUIN
                                // Modulacion para lambrequin
                                $dataModulationRecord[] = [
                                    'modulation_id'          => $EModulation->id,
                                    'detail_order_id'        => $item['id'],
                                    'height_add'             => 0.10,
                                    'width_discount'         => 0.005,
                                    'counterweight_discount' => 0.002,
                                    'turn_bar_discount'      => 0,
                                    'tube_discount'          => 0,
                                    'fascia_discount'        => 0.005,
                                    'join_id'                => null,
                                    'type_reg'               => $order['type_reg'],
                                ];
                                // Agregamos la informacin al pedido
                                $order['details'][$key]['counterweight_discount'] = 0.002;
                                $order['details'][$key]['fascia_discount'] = 0.005;
                            }
                            if((INT)$item['model_id'] === 51) { // CORBATIN
                                // Modulacion para lambrequin
                                $dataModulationRecord[] = [
                                    'modulation_id'          => $EModulation->id,
                                    'detail_order_id'        => $item['id'],
                                    'height_add'             => 0.2,
                                    'width_discount'         => 0,
                                    'counterweight_discount' => 0,
                                    'turn_bar_discount'      => 0,
                                    'tube_discount'          => 0,
                                    'fascia_discount'        => 0,
                                    'join_id'                => null,
                                    'type_reg'               => $order['type_reg'],
                                ];
                            }
                            if((INT)$item['model_id'] === 57) { // FIJO
                                // Modulacion para lambrequin
                                $dataModulationRecord[] = [
                                    'modulation_id'          => $EModulation->id,
                                    'detail_order_id'        => $item['id'],
                                    'height_add'             => 0.2,
                                    'width_discount'         => 0,
                                    'counterweight_discount' => 0,
                                    'turn_bar_discount'      => 0,
                                    'tube_discount'          => 0,
                                    'fascia_discount'        => 0,
                                    'join_id'                => null,
                                    'type_reg'               => $order['type_reg'],
                                ];
                            }
                        }
                        $order['details'][$key]['type_reg'] = $order['type_reg'];
                        $order['details'][$key]['ubica'] = $ubica;
                        $order['details'][$key]['capture_id'] = (INT)$order['type_reg'] === 2 ? $order['capture_id'] : 0;
                        $orderDetails[] = $order['details'][$key];
                    }
                    $ubica++;
                }
            }
            // var_dump($dataRequestRecord);
            // dd();
            DMaterialRequest::insert($dataRequestRecord);
            DModulation::insert($dataModulationRecord);

            // // MODULACION PERFILES
            // $Modulation = new Modulation();
            // $modulations = $Modulation->modulationAlls($orderDetails,$ordersIDsTxt,$guaranteeIDsTxt);
            // // guardamos detalle de las lineas
            // $dataProductionLineInsert = [];
            // foreach ($orderDetails as $orderDetail) {
            //     $tube_id = null;
            //     $set_tube_id = null;
            //     $perfil_color_id = null;
            //     $set_perfil_id = null;
            //     $join_perfil = null;
            //     $counterweight_bar_id = null;
            //     $counterweight_color_id = null;
            //     $set_counterweight_id = null;
            //     $twistbar_id = null;
            //     $twistbar_color_id = null;
            //     $set_twistbar_id = null;
            //     // TUBES
            //     foreach ($modulations['tubes'] as $tube) {
            //         foreach ($tube['items'] as $item) {
            //             foreach ($item['moduled_items'] as $modulateItem) {
            //                 if( ( (INT)$modulateItem['id'] === (INT)$orderDetail['id'] AND (INT)$modulateItem['type_reg'] === (INT)$orderDetail['type_reg'] )  ) {
            //                     $tube_id = $tube['tube_id'];
            //                     $set_tube_id = $item['set_id'];
            //                 }
            //             }
            //         }
            //     }
            //     // PERFILES
            //     foreach ($modulations['perfiles'] as $perfil) {
            //         foreach ($perfil['items'] as $item) {
            //             foreach ($item['moduled_items'] as $modulateItem) {
            //                 if((INT)$modulateItem['detail_order_id_group'] === 0) {
            //                     if((INT)$modulateItem['id'] === (INT)$orderDetail['id'] AND (INT)$modulateItem['type_reg'] === (INT)$orderDetail['type_reg'] ) {
            //                         $perfil_color_id = $perfil['color_id'];
            //                         $set_perfil_id = $item['set_id'];
            //                     }
            //                 } else {
            //                     $explDO = explode(',',$modulateItem['detail_order_id_group'] );
            //                     foreach ($explDO as $do) {
            //                         if((INT)$do
            //                         === (INT)$orderDetail['id']
            //                         AND (INT)$modulateItem['type_reg']
            //                         === (INT)$orderDetail['type_reg'] ) {
            //                             $perfil_color_id = $perfil['color_id'];
            //                             $set_perfil_id = $item['set_id'];
            //                             $join_perfil = $orderDetail['relation_id'];
            //                         }
            //                     };
            //                 }

            //             }
            //         }
            //     }
            //     // COUNTERWEIGHT
            //     foreach ($modulations['counterweight'] as $counterweight) {
            //         foreach ($counterweight['colors'] as $colors) {
            //             foreach ($colors['items'] as $item) {
            //                 foreach ($item['moduled_items'] as $modulateItem) {
            //                     if((INT)$modulateItem['id'] === (INT)$orderDetail['id'] AND (INT)$modulateItem['type_reg'] === (INT)$orderDetail['type_reg'] ) {
            //                         $counterweight_bar_id = $counterweight['counterweight_bar_id'];
            //                         $counterweight_color_id = $colors['color_id'];
            //                         $set_counterweight_id = $item['set_id'];
            //                     }
            //                 }
            //             }
            //         }
            //     }
            //     // TWISTBAR
            //     foreach ($modulations['twistbar'] as $twistbar) {
            //         foreach ($twistbar['colors'] as $colors) {
            //             foreach ($colors['items'] as $item) {
            //                 foreach ($item['moduled_items'] as $modulateItem) {
            //                     if((INT)$modulateItem['id'] === (INT)$orderDetail['id'] AND (INT)$modulateItem['type_reg'] === (INT)$orderDetail['type_reg'] ) {
            //                         $twistbar_id = $twistbar['twistbar_id'];
            //                         $twistbar_color_id = $colors['color_id'];
            //                         $set_twistbar_id = $item['set_id'];
            //                     }
            //                 }
            //             }
            //         }
            //     }
            //     $dataProductionLineInsert[] = [
            //         'detail_order_id'            => $orderDetail['id'],
            //         'production_line_id'         => $EProductionLine->id,
            //         'tube_id'                    => $tube_id,
            //         'set_tube_id'                => $set_tube_id,
            //         'perfil_color_id'            => $perfil_color_id,
            //         'set_perfil_id'              => $set_perfil_id,
            //         'join_perfil'                => $join_perfil,
            //         'counterweight_bar_id'       => $counterweight_bar_id,
            //         'counterweight_color_id'     => $counterweight_color_id,
            //         'set_counterweight_id'       => $set_counterweight_id,
            //         'twistbar_id'                => $twistbar_id,
            //         'twistbar_color_id'          => $twistbar_color_id,
            //         'set_twistbar_id'            => $set_twistbar_id,
            //         'ubica_id'                   => $orderDetail['ubica'],
            //         'type_reg'                   => $orderDetail['type_reg'],
            //     ];
            // }
            // DProductionLine::insert($dataProductionLineInsert);
            //
            // Cambiamos el status de los pedidos
            EOrder::whereIn('id',$ordersIDs)
            ->update(['status_id'=>13]);
            EGuaranty::whereIn('id',$guaranteeIDs)
            ->update([
                'status_id'=>2,
                'material_request_id'  => $EmaterialRequest->id,
            ]);
            // GET INFO
            $EMaterialRequest = EMaterialRequest::select('e_material_requests.id','e_material_requests.user_id','c_erp_info_users.short_name','e_material_requests.is_complete','e_material_requests.check1','e_material_requests.invoice_id','e_material_requests.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_material_requests.user_id')
            ->where('e_material_requests.id',$EmaterialRequest->id)
            ->first();
            $DMaterialRequest = DMaterialRequest::select('d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit', 'd_material_requests.cost',DB::raw('SUM(d_material_requests.quantity) AS quantity, (SUM(d_material_requests.quantity) * d_material_requests.cost ) AS total') )
            ->join('c_articles','c_articles.id','d_material_requests.article_id')
            ->join('c_units','c_units.id','d_material_requests.unit_id')
            ->where('d_material_requests.material_request_id',$EmaterialRequest->id)
            ->groupBy('d_material_requests.material_request_id','d_material_requests.article_id','c_articles.article','d_material_requests.unit_id','c_units.unit')
            ->get();
            $materialRequest = $this->setIndividualMaterialRequests($EMaterialRequest->toArray(),$DMaterialRequest->toArray());
            return response()->json([
                'success'          =>  true ,
                'materialRequest' => $materialRequest ,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }
    public function saveOrderCollect(Request $request,$order_id)
    {
        // try {
            $statusChange = 0;
            if( $request->nomen == 'GLS' OR $request->nomen == 'SLS' ) {
                $detailStatus = EGuaranty::select('id','folio','status_id','delivery_type_id')->where('folio',$request->order_id)->where('nomen',$request->nomen)->first();
            }  else {
                $detailStatus = EOrder::select('status_id','delivery_type_id')->where('id',$request->order_id)->first();
            }
            if( ( (INT)$detailStatus->status_id === 5 AND ( $request->nomen == 'GLS' OR $request->nomen == 'SLS' ) ) OR ( (INT)$detailStatus->status_id === 9 AND $request->nomen == 'LS' ) ) {
                $date_now = Carbon::now();
                // Verificams que tipo de documento es
                if( $request->nomen == 'GLS' || $request->nomen == 'SLS' ) {
                    // Cambiamos el status del pedido a Recolectado
                    $statusChange = 12;
                    EGuaranty::where('id',$detailStatus->id)
                    ->update([
                        'status_id'    => 12,
                        'collect_date' => $date_now,
                    ]);
                    // LOGS
                    $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                    $logs->createMovementLog($request->user_id,'Actualizó status',1,7,23,1,'guarantee_id',$request->order_id,'Se actualizo status de Empacado a Recolectado');
                    // vaciamos las ubicaciones
                    $DGuaranty = DGuaranty::select('production_location_id')->where('guarantee_id',$detailStatus->id)->get();
                    $locationIDS = [];
                    foreach ($DGuaranty as $item) { $locationIDS[] = $item['production_location_id']; }
                    // actualizaciom ubicaciones
                    DProductionLocation::whereIn('id',$locationIDS)
                    ->update([ 'is_occupied' => 0, ]);
                    // quitamos las ubicaciones asignadas
                    DGuaranty::where('guarantee_id',$detailStatus->id)
                    ->update([ 'production_location_id' => null, ]);
                }  else {
                    // Cambiamos el status del pedido a Recolectado
                    $statusChange = 17;
                    EOrder::where('id',$request->order_id)
                    ->update([
                        'status_id'    => 17,
                        'collect_date' => $date_now,
                    ]);
                    // LOGS
                    $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                    $logs->createMovementLog($request->user_id,'Actualizó status de pedido',1,7,23,1,'order_id',$request->order_id,'Se actualizo status de Empacado a Recolectado');
                    // vaciamos las ubicaciones
                    $DOrder = DOrder::select('production_location_id')->where('order_id',$request->order_id)->get()->toArray();
                    $locationIDS = [];
                    foreach ($DOrder as $item) { $locationIDS[] = $item['production_location_id']; }
                    // actualizaciom ubicaciones
                    DProductionLocation::whereIn('id',$locationIDS)
                    ->update([ 'is_occupied' => 0, ]);
                    // quitamos las ubicaciones asignadas
                    DOrder::where('order_id',$request->order_id)
                    ->update([ 'production_location_id' => null, ]);
                }
                // buscamos los usuarios del cambio SOCKET
                $submodule_id = 0;
                $users_socket_ids = [];
                if( (INT)$detailStatus->delivery_type_id === 1 ) { $submodule_id = 12; }
                if( (INT)$detailStatus->delivery_type_id === 2 ) { $submodule_id = 13; }
                if( (INT)$detailStatus->delivery_type_id === 3 ) { $submodule_id = 22; }
                $users_ids = DErpAccessUser::select('user_id as id')
                ->where('module_id', 12)
                ->where('submodule_id', $submodule_id)
                ->get();
                // Guardamos usuarios para el socket
                foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
                $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
                // locations
                $productionLocations = DProductionLocation::select('id','location','row','col')->where('is_active',1)->where('is_occupied',0)->get();

                return response()->json([
                    'success'               =>  true ,
                    'nomen'                 =>  $request->nomen ,
                    'folio'                 =>  ( $request->nomen == 'GLS' || $request->nomen == 'SLS' ) ? $detailStatus->folio : null ,
                    'order_id'              =>  $request->order_id ,
                    'delivery_type_id'      =>  $detailStatus->delivery_type_id ,
                    'statusChange'          =>  $statusChange ,
                    'users_socket'              => $users_socket,
                    'productionLocations'   =>  $productionLocations ,
                ], 200);
            } else {
                return response()->json([
                    'success'       =>  false ,
                    'error_type'    =>  'order_not_status' ,
                ], 400);
            }
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }
    // PRIVATE
    private function requestItems($item,$articles,$materialRequests,$orderDetails,$nomen) {
        $requests = [];
        switch ($item['product_id']) {
            case 1: // Enrollable
                // Tela
                $heightAdd = 0;
                if($item['is_heat_seal'] == 0) { $heightAdd = $this->heightAdd($item['product_id'],$item['operation_id'],$item['motor_id'],$item['tube_id'],$item['relation_cassette']); }
                $requestCloth = ($item['height']+$heightAdd);
                if($item['is_inverted'] == 1) { $requestCloth = $item['width']; }
                $requests[] = [
                    'value'      => $item['article'],
                    'item_id'    => $item['item_id'],
                    'article_id' => $item['article_id'],
                    'quantity'   => $requestCloth,
                    'unit_id'    => 2,
                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                    'width_lot'  => $this->foundWidthLot($articles,$item['article_id']),
                ];

                foreach ($materialRequests as $mr) {
                    // if ($item['operation_id'] == $mr['operation_id'] ) { // Operacion Manual
                        //  CINTA DOBLE CARA
                        if ( $mr['is_general'] == 1  AND is_null($mr['color_id']) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // INSERTO 11 MM
                        if ( $mr['is_general'] == 7 AND is_null($mr['color_id']) AND $mr['counterweight_bar_id'] == $item['counterweight_bar_id'] ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // INSERTO REDONDO DE 6 MM
                        else if( $mr['is_general'] == 7  AND is_null($mr['color_id']) AND is_null($mr['counterweight_bar_id']) AND ($item['counterweight_bar_id'] == 2 OR $item['counterweight_bar_id'] == 4 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // Contrapeso + color // componentes contrapeso
                        if($item['counterweight_bar_id'] == 4) { // baso ovalada cubierta
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND 7 == $mr['color_id'] ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        } else {
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        }
                        // TUBO O ADAPTADOR
                        if ( $item['tube_id'] == $mr['tube_id'] AND $item['operation_id'] == $mr['operation_id']) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // LLEVA CASSETTE
                        if( $item['relation_cassette'] > 0 ) {
                            // PERFIL
                            if ( $mr['is_perfil'] == 1 AND $item['component_color_id'] == $mr['color_id'] AND $item['side_id'] == 1) {
                                if( $nomen == "GLS" ) { $orderID = $item['guarantee_id']; } else { $orderID = $item['order_id']; }
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $this->sumWidthPerfil($item['relation_cassette'],$orderID,$orderDetails,$nomen),
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            // TAPA PERFIL
                            if ( $mr['is_perfil'] == 2 AND $mr['is_general'] == 4  AND $item['side_id'] == 1 AND $item['component_color_id'] == $mr['color_id']) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            // BRACKET DE INSTALACION
                            if ( $mr['is_perfil'] == 2 AND $mr['is_general'] == 5) {
                                $quantityTemp = 2;
                                $totalBrackets = floor($item['width'] / 0.50);
                                if( $totalBrackets > 2 ) {  $quantityTemp =  $totalBrackets; }
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $quantityTemp,
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            // SOPORTES
                            switch ($item['divisions']) {
                                case 1:
                                    if ($mr['is_general'] == 10 AND $item['side_id'] == 1) { // SOPORTE LATERAL
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 2,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                break;
                                case 2:
                                    if ($mr['is_general'] == 10 AND $item['side_id'] == 1) { // SOPORTE LATERAL
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 2,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                    if ($mr['is_general'] == 8 AND $item['side_id'] == 1) { // SOPORTE INTERMEDIO PARA FASCIA
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 1,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                break;
                                case 3:
                                    if ($mr['is_general'] == 10 AND $item['side_id'] == 1) { // SOPORTE LATERAL
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 3,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                    if ($mr['is_general'] == 8 AND $item['side_id'] == 1) { // SOPORTE INTERMEDIO PARA FASCIA
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 1,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                break;
                            }
                            // MOTORIZADAS CON CASSETE
                            if ($mr['is_general'] == 18 AND $item['side_id'] == 1  AND $mr['operation_id'] == $item['operation_id'] ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        } else {
                            // SOPORTES
                            switch ($item['divisions']) {
                                case 1:
                                    // if ($mr['is_general'] == 10 AND $item['side_id'] == 1) { // SOPORTE LATERAL
                                    //     $requests[] = [
                                    //         'value'      => $mr['article'],
                                    //         'item_id'    => $item['item_id'],
                                    //         'article_id' => $mr['article_id'],
                                    //         'quantity'   => 2,
                                    //         'unit_id'    => $mr['unit_id'],
                                    //         'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    //         'width_lot'  => null,
                                    //     ];
                                    // }
                                break;
                                case 2:
                                    // if ($mr['is_general'] == 10 AND $item['side_id'] == 1) { // SOPORTE LATERAL
                                    //     $requests[] = [
                                    //         'value'      => $mr['article'],
                                    //         'item_id'    => $item['item_id'],
                                    //         'article_id' => $mr['article_id'],
                                    //         'quantity'   => 2,
                                    //         'unit_id'    => $mr['unit_id'],
                                    //         'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    //         'width_lot'  => null,
                                    //     ];
                                    // }
                                    if ($mr['is_general'] == 9 AND $item['side_id'] == 1 AND $mr['operation_id'] == $item['operation_id']  ) { // SOPORTE INTERMEDIO ENROLLABLE O CON TRACCION
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 1,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                break;
                            }
                        }
                        // CADENA
                        if ( $mr['is_chain'] == 1 AND $item['chain_id'] == $mr['chain_id'] AND ($item['component_color_id'] == $mr['color_id'] OR is_null($mr['color_id'])) ) {
                            $heightChain = $this->getchainSize($item['height_chain'],$item['height'],$item['mechanism_id'],$item['relation_cassette'],$item['tube_id'],$item['product_id'],$item['if_chain_height']);
                            $heightChainAdd = $this->heightChainAdd($item['relation_heat_seal'],$orderDetails);
                            $heightChain = $heightChain + $heightChainAdd;
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $heightChain,
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // COMPLEMENTOS CADENA
                        if ( $item['chain_id'] == $mr['chain_id'] AND $mr['is_chain'] == 2 AND is_null($mr['color_id']) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        if ( $item['chain_id'] == $mr['chain_id'] AND $mr['is_chain'] == 2 AND $item['component_color_id'] == $mr['color_id'] AND $mr['product_id'] == 1) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // MECANISMO
                        $CCID = $item['component_color_id'];
                        if( (INT)$item['mechanism_id'] === 2 ) { $CCID = 1; }
                        if ( $mr['mechanism_id'] == $item['mechanism_id'] AND $CCID == $mr['color_id'] AND !is_null($item['mechanism_id']) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                    // }
                }

            break;
            case 2: // Sheer elegance
                $heightAdd = $this->heightAdd($item['product_id'],$item['operation_id'],$item['motor_id'],$item['tube_id'],$item['relation_cassette']);
                $requests[] = [
                    'value'      => $item['article'],
                    'item_id'    => $item['item_id'],
                    'article_id' => $item['article_id'],
                    'quantity'   => ($item['height']*2)+$heightAdd,
                    'unit_id'    => 2,
                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                    'width_lot'  => $this->foundWidthLot($articles,$item['article_id']),
                ];
                foreach ($materialRequests as $mr) {
                    // if ($item['operation_id'] == $mr['operation_id'] ) { // Operacion Manual
                        //  CINTA  DOBLE  CARA
                        if ( $mr['is_general'] == 1 AND is_null($mr['color_id']) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : ($item['width']*4),
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // INSERTO 11 MM
                        if ( $mr['is_general'] == 7 AND is_null($mr['color_id']) AND $mr['counterweight_bar_id'] == 1) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // Contrapeso + color // componentes contrapeso
                        if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // BARRA GIRO
                        if ( $mr['is_general'] == 2 AND $item['component_color_id'] == $mr['color_id'] AND $item['counterweight_bar_id'] == 3) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // BARRA GIRO TIPO A
                        if ( $mr['is_general'] == 11 AND $item['component_color_id'] == $mr['color_id'] AND $item['counterweight_bar_id'] == 5) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // TUBO O ADAPTADOR
                        if ( $item['tube_id']  == $mr['tube_id'] AND $item['operation_id'] == $mr['operation_id'] ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // PERFIL
                        if ( $mr['is_perfil'] == 1 AND $item['component_color_id'] == $mr['color_id'] AND $item['side_id'] == 1) {
                            if( $nomen == "GLS" ) { $orderID = $item['guarantee_id']; } else { $orderID = $item['order_id']; }
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $this->sumWidthPerfil($item['relation_cassette'],$orderID,$orderDetails,$nomen),
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }

                        // TAPA PERFIL
                        if ( $mr['is_perfil'] == 2 AND $mr['is_general'] == 4  AND $item['side_id'] == 1 AND $item['component_color_id'] == $mr['color_id']) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // TAPA PERFIL LISA
                        // if ( $mr['is_perfil'] == 2 AND $mr['is_general'] == 3 AND  $item['mechanism_id'] != 1 AND $item['side_id'] == 1 AND $item['component_color_id'] == $mr['color_id'] AND $item['side_id'] == 1 ) {
                        //     $requests[] = [
                        //         'value'      => $mr['article'],
                        //         'article_id' => $mr['article_id'],
                        //         'quantity'   => $mr['quantity'],
                        //         'unit_id'    => $mr['unit_id'],
                        //         'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                        //         'width_lot'  => null,
                        //     ];
                        // }
                        // SOPORTES
                        switch ($item['divisions']) {
                            case 1:
                                if ($mr['is_general'] == 10 AND $item['mechanism_id'] != 1 AND $item['side_id'] == 1) { // SOPORTE LATERAL Unicamente con mecanismo SL8
                                    $requests[] = [
                                        'value'      => $mr['article'],
                                        'item_id'    => $item['item_id'],
                                        'article_id' => $mr['article_id'],
                                        'quantity'   => $mr['quantity'],
                                        'unit_id'    => $mr['unit_id'],
                                        'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                        'width_lot'  => null,
                                    ];
                                }
                            break;
                            case 2:
                                if ($mr['is_general'] == 10 AND $item['side_id'] == 1) { // SOPORTE LATERAL
                                    $requests[] = [
                                        'value'      => $mr['article'],
                                        'item_id'    => $item['item_id'],
                                        'article_id' => $mr['article_id'],
                                        'quantity'   => $mr['quantity'],
                                        'unit_id'    => $mr['unit_id'],
                                        'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                        'width_lot'  => null,
                                    ];
                                }
                                if ($mr['is_general'] == 8 AND $item['side_id'] == 1) { // SOPORTE INTERMEDIO PARA FASCIA
                                    $requests[] = [
                                        'value'      => $mr['article'],
                                        'item_id'    => $item['item_id'],
                                        'article_id' => $mr['article_id'],
                                        'quantity'   => 1,
                                        'unit_id'    => $mr['unit_id'],
                                        'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                        'width_lot'  => null,
                                    ];
                                }
                            break;
                            case 3:
                                if ($mr['is_general'] == 10 AND $item['side_id'] == 1) { // SOPORTE LATERAL
                                    $requests[] = [
                                        'value'      => $mr['article'],
                                        'item_id'    => $item['item_id'],
                                        'article_id' => $mr['article_id'],
                                        'quantity'   => 2,
                                        'unit_id'    => $mr['unit_id'],
                                        'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                        'width_lot'  => null,
                                    ];
                                }
                                if ($mr['is_general'] == 8 AND $item['side_id'] == 1) { // SOPORTE INTERMEDIO PARA FASCIA
                                    $requests[] = [
                                        'value'      => $mr['article'],
                                        'item_id'    => $item['item_id'],
                                        'article_id' => $mr['article_id'],
                                        'quantity'   => 1,
                                        'unit_id'    => $mr['unit_id'],
                                        'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                        'width_lot'  => null,
                                    ];
                                }
                            break;
                        }
                        // MECANISMO
                        $CCID = $item['component_color_id'];
                        if( (INT)$item['mechanism_id'] === 2 ) { $CCID = 1; } // SL8 se  cambia todo a blanco
                        if ( $mr['mechanism_id'] == $item['mechanism_id'] AND $CCID == $mr['color_id'] AND !is_null($item['mechanism_id']) ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                        }
                        // BRACKET DE INSTALACION
                        if ( $mr['is_perfil'] == 2 AND $mr['is_general'] == 5 ) {
                            $quantityTemp = 2;
                            $totalBrackets = floor($item['width'] / 0.50);
                            if( $totalBrackets > 2 ) {  $quantityTemp =  $totalBrackets; }
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $quantityTemp,
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // CADENA
                        if ( $mr['is_chain'] == 1 AND $item['chain_id'] == $mr['chain_id'] AND $item['component_color_id'] == $mr['color_id']) {
                            $heightChain = $this->getchainSize($item['height_chain'],$item['height'],$item['mechanism_id'],$item['relation_cassette'],$item['tube_id'],$item['product_id'],0);
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $heightChain,
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // COMPLEMENTOS CADENA
                        if ( $item['chain_id'] == $mr['chain_id'] AND $mr['is_chain'] == 2 AND is_null($mr['color_id'])) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        if ( $item['chain_id'] == $mr['chain_id'] AND $mr['is_chain'] == 2 AND $item['component_color_id'] == $mr['color_id']) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                    // }
                }
            break;
            case 4: // ACCESORIOS
                if( ( $item['product_id'] == 4 AND $item['relation_accesories'] == 0 AND ( $item['relation_cassette'] < 1 OR is_null($item['relation_cassette']) ) ) AND ( (INT)$item['model_id'] !== 25 AND (INT)$item['model_id'] !== 51 AND (INT)$item['model_id'] !== 57  AND (INT)$item['model_id'] !== 59 ) ) {
                    if((INT)$item['article_id'] === 97) { // CINTA DOBLE CARA
                        $requests[] = [
                            'value'      => $item['article'],
                            'item_id'    => $item['item_id'],
                            'article_id' => $item['article_id'],
                            'quantity'   => $item['quantity']*50,
                            'unit_id'    => 2,
                            'cost'       => $this->foundCostItem($articles,$item['article_id']),
                            'width_lot'  => null,
                        ];
                    } else {
                        $requests[] = [
                            'value'      => $item['article'],
                            'item_id'    => $item['item_id'],
                            'article_id' => $item['article_id'],
                            'quantity'   => (INT)$item['unit_id'] === 2 ? $item['width'] : $item['quantity'],
                            'unit_id'    => $item['unit_id'],
                            'cost'       => $this->foundCostItem($articles,$item['article_id']),
                            'width_lot'  => null,
                        ];
                    }
                }
                if((INT)$item['model_id'] === 51) {
                    $requests[] = [
                        'value'      => 'Corbatin',
                        'item_id'    => $item['item_id'],
                        'article_id' => $item['corbatin_id'],
                        'quantity'   => $item['height'],
                        'unit_id'    => 2,
                        // 'cost'       => $this->foundCostLambrequinItem($articles,$item['lambrequin_id']),
                        'cost'       => 60,
                        'width_lot'  => $this->foundWidthLot($articles,$item['corbatin_id']),
                    ];
                    foreach ($materialRequests as $mr) {
                        // Contrapeso + color // componentes contrapeso
                        if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND ($item['component_color_id'] == $mr['color_id']  OR 7  == $mr['color_id'] ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                    }
                    //  VELCRO
                    $requests[] = [
                        'value'      => 'VELCRO DE 19MM',
                        'item_id'    => $item['item_id'],
                        'article_id' => 281,
                        'quantity'   => $item['width'],
                        'unit_id'    => 2,
                        'cost'       => 5,
                        'width_lot'  => null,
                    ];
                }
                if((INT)$item['model_id'] === 57) {
                    $requests[] = [
                        'value'      => 'Fijo',
                        'item_id'    => $item['item_id'],
                        'article_id' => $item['fijo_id'],
                        'quantity'   => $item['height'],
                        'unit_id'    => 2,
                        // 'cost'       => $this->foundCostLambrequinItem($articles,$item['lambrequin_id']),
                        'cost'       => 60,
                        'width_lot'  => $this->foundWidthLot($articles,$item['fijo_id']),
                    ];
                    //  VELCRO
                    $requests[] = [
                        'value'      => 'VELCRO DE 19MM',
                        'item_id'    => $item['item_id'],
                        'article_id' => 281,
                        'quantity'   => $item['width'],
                        'unit_id'    => 2,
                        'cost'       => 5,
                        'width_lot'  => null,
                    ];

                }
                if((INT)$item['model_id'] === 25) {
                    // lambrequin
                    $requests[] = [
                        'value'      => 'Lambrequin',
                        'item_id'    => $item['item_id'],
                        'article_id' => $item['lambrequin_id'],
                        'quantity'   => $item['height'],
                        'unit_id'    => 2,
                        // 'cost'       => $this->foundCostLambrequinItem($articles,$item['lambrequin_id']),
                        'cost'       => 100,
                        'width_lot'  => $this->foundWidthLot($articles,$item['lambrequin_id']),
                    ];
                    foreach ($materialRequests as $mr) {

                        //  CINTA  DOBLE  CARA
                        if ( $mr['is_general'] == 1 AND is_null($mr['color_id']) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : ($item['width']*4),
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // Contrapeso + color // componentes contrapeso
                        if($item['counterweight_bar_id'] == 4) { // baso ovalada cubierta
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND 7 == $mr['color_id'] ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        } else {
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        }
                        // INSERTO 11 MM
                        if ( $mr['is_general'] == 7 AND is_null($mr['color_id']) AND $mr['counterweight_bar_id'] == $item['counterweight_bar_id'] ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // INSERTO REDONDO DE 6 MM
                        else if( $mr['is_general'] == 7  AND is_null($mr['color_id']) AND is_null($mr['counterweight_bar_id']) AND ($item['counterweight_bar_id'] == 2 OR $item['counterweight_bar_id'] == 4 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // Accesorios lambrequin

                        if( (INT)$item['is_velcro'] === 1) {
                            if( (INT)$item['model_id'] === 25 AND $mr['is_lambrequin'] == 1 AND (INT)$mr['is_general'] === 17 ) { // VELCRO
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        } else {
                            if( (INT)$item['model_id'] === 25 AND $mr['is_lambrequin'] == 1 AND (INT)$mr['is_general'] === 13 ) { // RIEL
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            if( (INT)$item['model_id'] === 25 AND $mr['is_lambrequin'] == 1 AND (INT)$mr['is_general'] === 14 ) { // TAPAS
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            if( (INT)$item['model_id'] === 25 AND $mr['is_lambrequin'] == 1 AND (INT)$mr['is_general'] === 15 ) { // SOPORTE RIEL UNIVERSAL
                                $quantityTemp = 2;
                                $totalBrackets = floor($item['width'] / 0.50);
                                if( $totalBrackets > 2 ) {  $quantityTemp =  $totalBrackets; }
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $quantityTemp,
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            if( (INT)$item['model_id'] === 25 AND $mr['is_lambrequin'] == 1 AND (INT)$mr['is_general'] === 16 ) { // TABLETA PARA RIEL UNIVERSAL
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        }
                    }
                }
                // PERFIL PRIV
                if((INT)$item['model_id'] === 59) {
                    $requests[] = [
                        'value'      => $item['article'],
                        'item_id'    => $item['item_id'],
                        'article_id' => $item['article_id'],
                        'quantity'   => $item['width']*$item['quantity'],
                        'unit_id'    => 2,
                        'cost'       => $this->foundCostItem($articles,$item['article_id']),
                        'width_lot'  => null,
                    ];
                    //  FELPA
                    $requests[] = [
                        'value'      => 'FELPA',
                        'item_id'    => $item['item_id'],
                        'article_id' => 306,
                        'quantity'   => $item['width']*$item['quantity'],
                        'unit_id'    => 2,
                        'cost'       => 1.79,
                        'width_lot'  => null,
                    ];
                }
            break;
            case 5: // Lienzo
                $itemHeatSeal = [];
                foreach ($orderDetails as $key => $od) { if( (INT)$od['relation_heat_seal'] === (INT)$item['relation_heat_seal'] AND (INT)$od['product_id'] === 1 AND (INT)$od['is_heat_seal'] === 1 ) { $itemHeatSeal = $od; } }
                $heightAdd = $this->heightAdd($itemHeatSeal['product_id'],$itemHeatSeal['operation_id'],$itemHeatSeal['motor_id'],$itemHeatSeal['tube_id'],$itemHeatSeal['relation_cassette']);
                $requestCloth = ($item['height']+$heightAdd);
                if($item['is_inverted'] == 1) { $requestCloth = $item['width']; }
                $requests[] = [
                    'value'      => $item['article'],
                    'item_id'    => $item['item_id'],
                    'article_id' => $item['article_id'],
                    'quantity'   => $requestCloth,
                    'unit_id'    => 2,
                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                    'width_lot'  => $this->foundWidthLot($articles,$item['article_id']),
                ];
            break;
        }
        return $requests;
    }

    private function requestItemsGuarantee($item,$articles,$materialRequests,$order) {
        $requests = [];
        // switch material reques indentify
        if($order['nomen'] == 'GLS') {
            if( (INT)$order['capture_id'] === 1 ) {
                $clothIDT = 1; // telas
                $counterweightIDT = 1; // Contrapeso
                $tubeIDT = 1; // tubo
                $componentColorITD = 1; // Color componente
                $bracketITD = 1; // bracket
                $motorITD = 1; // motor
                $chainITD = 1; // cadena
                $heightChainITD = 1; // alto cadena
                $mechanismITD = 1; // mecanismo
                // DAMAGE
                $mechanismMSheerChange = 1;
                $damageFabric = 0;
                $damageTube = 0;
                $damageMechanism = 0;
                $damageCounterweight = 0;
                $damageCounterweightComp = 1;
                $damageTubeComp = 1;
                $damageChain = 0;
                $damagedFascia = 0;
                $damagedMotor = 0;
            } else {
                if( (INT)$item['article_id'] !== (INT)$item['ch_article_id'] ){ $clothIDT = 1; } else { $clothIDT = 0; }// telas
                if( (INT)$item['counterweight_bar_id'] !== (INT)$item['ch_counterweight_bar_id'] ){ $counterweightIDT = 1;} else { $counterweightIDT = 0; } // Contrapeso
                if( (INT)$item['tube_id'] !== (INT)$item['ch_tube_id'] ){ $tubeIDT = 1; } else { $tubeIDT = 0; }// Tubo
                if( (INT)$item['component_color_id'] !== (INT)$item['ch_component_color_id'] ){ $componentColorITD = 1; } else { $componentColorITD = 0; } // Color componente
                $bracketITD = 0; // bracket
                $motorITD = 0; // motor
                if( (INT)$item['chain_id'] !== (INT)$item['ch_chain_id'] AND (INT)$item['operation_id'] === 1 ){ $chainITD = 1; } else { $chainITD = 0; }// cadena
                if( (INT)$item['height_chain'] !== (INT)$item['ch_height_chain'] AND (INT)$item['operation_id'] === 1 ){ $heightChainITD = 1; } else { $heightChainITD = 0; }// alto cadena
                if( (INT)$item['mechanism_id'] !== (INT)$item['ch_mechanism_id'] AND (INT)$item['operation_id'] === 1 ){
                    $mechanismITD = 1;
                    if( (INT)$item['mechanism_id'] === 2 AND (INT)$item['ch_mechanism_id'] === 6 AND (INT)$item['product_id'] === 2 ) {
                        $mechanismMSheerChange = 1;
                    } else {
                        $mechanismMSheerChange = 0;
                    }
                } else {
                    $mechanismITD = 0;
                    $mechanismMSheerChange = 0;
                }// mecanismo
                // DAMAGE
                $damageFabric = (INT)$item['damage_fabric'];
                $damageTube = (INT)$item['damage_tube'];
                $damageMechanism = (INT)$item['damage_mechanism'];
                $damageCounterweight = (INT)$item['damage_counterweight'];
                $damageCounterweightComp = 0;
                $damageTubeComp = 0;
                $damageChain = (INT)$item['damage_chain'];
                $damagedFascia = (INT)$item['damage_fascia'];
                $damagedMotor = (INT)$item['damage_motor'];
            }
        } else {
            $clothIDT = 1; // telas
            $counterweightIDT = 1; // Contrapeso
            $tubeIDT = 1; // tubo
            $componentColorITD = 1; // Color componente
            $bracketITD = 1; // bracket
            $motorITD = 1; // motor
            $chainITD = 1; // cadena
            $heightChainITD = 1; // alto cadena
            $mechanismITD = 1; // mecanismo
            // DAMAGE
            $mechanismMSheerChange = 1;
            $damageFabric = 0;
            $damageTube = 0;
            $damageMechanism = 0;
            $damageCounterweight = 0;
            $damageCounterweightComp = 1;
            $damageTubeComp = 1;
            $damageChain = 0;
            $damagedFascia = 0;
            $damagedMotor = 0;
        }

        switch ($item['product_id']) {
            case 1: // Enrollable
                // Tela
                if( (INT)$clothIDT === 1 || (INT)$damageFabric === 1 ) {
                    $heightAdd = 0;
                    if($item['is_heat_seal'] == 0) { $heightAdd = $this->heightAdd($item['product_id'],$item['operation_id'],$item['motor_id'],$item['tube_id'],$item['relation_cassette']); }
                    $requestCloth = ($item['height']+$heightAdd);
                    if($item['is_inverted'] == 1) { $requestCloth = $item['width']; }
                    $requests[] = [
                        'value'      => $item['article'],
                        'item_id'    => $item['item_id'],
                        'article_id' => $item['article_id'],
                        'quantity'   => $requestCloth,
                        'unit_id'    => 2,
                        'cost'       => $this->foundCostItem($articles,$item['article_id']),
                        'width_lot'  => $this->foundWidthLot($articles,$item['article_id']),
                    ];
                }

                foreach ($materialRequests as $mr) {
                    // if ($item['operation_id'] == $mr['operation_id'] ) { // Operacion Manual
                        //  CINTA DOBLE CARA
                        if ( $mr['is_general'] == 1  AND is_null($mr['color_id'])  AND ( (INT)$counterweightIDT === 1 OR (INT)$clothIDT === 1 OR (INT)$damageFabric === 1 OR (INT)$damageTube === 1 OR (INT)$tubeIDT === 1 OR (INT)$damagedFascia === 1 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // INSERTO 11 MM
                        if ( $mr['is_general'] == 7 AND is_null($mr['color_id']) AND $mr['counterweight_bar_id'] == $item['counterweight_bar_id'] AND ( (INT)$counterweightIDT === 1 OR (INT)$clothIDT === 1 OR (INT)$damageFabric === 1 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // INSERTO REDONDO DE 6 MM
                        else if( $mr['is_general'] == 7  AND is_null($mr['color_id']) AND is_null($mr['counterweight_bar_id']) AND ($item['counterweight_bar_id'] == 2 OR $item['counterweight_bar_id'] == 4 )  AND (INT)$counterweightIDT === 1  ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // Contrapeso + color // componentes contrapeso
                        if( (INT)$item['counterweight_bar_id'] === 4 ) { // baso ovalada cubierta

                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND 7 == $mr['color_id'] AND (INT)$mr['is_general'] === 0 AND ( ( (INT)$counterweightIDT === 1 OR (INT)$componentColorITD === 1 )  OR (INT)$damageCounterweight === 1 ) ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] AND (INT)$mr['is_general'] === 0 AND ( ( (INT)$counterweightIDT === 1 OR (INT)$componentColorITD === 1 )  OR (INT)$damageCounterweight === 1 )) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            // Cmplementos contrapeeso
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND 7 == $mr['color_id'] AND (INT)$mr['is_general'] === 6 AND ( ( (INT)$counterweightIDT === 1 OR (INT)$componentColorITD === 1 ) OR (INT)$damageCounterweightComp === 1 )  ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] AND (INT)$mr['is_general'] === 6 AND ( ( (INT)$counterweightIDT === 1 OR (INT)$componentColorITD === 1 ) OR (INT)$damageCounterweightComp === 1 )  ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        } else {
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] AND (INT)$mr['is_general'] === 0 AND ( ( (INT)$counterweightIDT === 1 OR (INT)$componentColorITD === 1 ) OR (INT)$damageCounterweight === 1 )  ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            // Cmplementos contrapeeso
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] AND (INT)$mr['is_general'] === 6 AND ( ( (INT)$counterweightIDT === 1 OR (INT)$componentColorITD === 1 ) OR (INT)$damageCounterweightComp === 1 )  ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        }
                        // TUBO
                        if ( $item['tube_id'] == $mr['tube_id'] AND $item['operation_id'] == $mr['operation_id'] AND (INT)$mr['is_general'] === 0 AND ( (INT)$tubeIDT === 1 OR (INT)$damageTube === 1 ) ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                        }
                        // TUBO ADAPTADOR
                        if ( $item['tube_id'] == $mr['tube_id'] AND $item['operation_id'] == $mr['operation_id'] AND (INT)$mr['is_general'] === 19 AND (INT)$damageTubeComp === 1 ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                        }
                        // LLEVA CASSETTE
                        if( $item['relation_cassette'] > 0 ) {
                            // PERFIL
                            if ( $mr['is_perfil'] == 1 AND $item['component_color_id'] == $mr['color_id'] AND $item['side_id'] == 1 AND ( (INT)$componentColorITD === 1 || (INT)$damagedFascia === 1 ) ) {
                                if( $order['nomen'] == "GLS" ) { $orderID = $item['guarantee_id']; } else { $orderID = $item['order_id']; }
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $this->sumWidthPerfil($item['relation_cassette'],$orderID,$order['details'],$order['nomen']),
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            // TAPA PERFIL
                            if ( $mr['is_perfil'] == 2 AND $mr['is_general'] == 4  AND $item['side_id'] == 1 AND $item['component_color_id'] == $mr['color_id'] AND ( (INT)$componentColorITD === 1 || (INT)$damagedFascia === 1 ) ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            // BRACKET DE INSTALACION
                            if ( $mr['is_perfil'] == 2 AND $mr['is_general'] == 5 AND (INT)$bracketITD === 1) {
                                $quantityTemp = 2;
                                $totalBrackets = floor($item['width'] / 0.50);
                                if( $totalBrackets > 2 ) {  $quantityTemp =  $totalBrackets; }
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $quantityTemp,
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            // SOPORTES
                            switch ($item['divisions']) {
                                case 1:
                                    if ( $mr['is_general'] == 10 AND $item['side_id'] == 1 AND (INT)$bracketITD === 1 ) { // SOPORTE LATERAL
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 2,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                break;
                                case 2:
                                    if ( $mr['is_general'] == 10 AND $item['side_id'] == 1 AND (INT)$bracketITD === 1 ) { // SOPORTE LATERAL
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 2,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                    if ( $mr['is_general'] == 8 AND $item['side_id'] == 1  AND (INT)$bracketITD === 1 ) { // SOPORTE INTERMEDIO PARA FASCIA
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 1,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                break;
                                case 3:
                                    if ($mr['is_general'] == 10 AND $item['side_id'] == 1  AND (INT)$bracketITD === 1 ) { // SOPORTE LATERAL
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 3,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                    if ($mr['is_general'] == 8 AND $item['side_id'] == 1 AND (INT)$bracketITD === 1 ) { // SOPORTE INTERMEDIO PARA FASCIA
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 1,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                break;
                            }
                            // MOTORIZADAS CON CASSETE
                            if ($mr['is_general'] == 18 AND $item['side_id'] == 1  AND $mr['operation_id'] == $item['operation_id']  AND ( (INT)$motorITD === 1 || (INT)$damagedMotor === 1 ) ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        } else {
                            // SOPORTES
                            switch ($item['divisions']) {
                                case 1:
                                    // if ($mr['is_general'] == 10 AND $item['side_id'] == 1) { // SOPORTE LATERAL
                                    //     $requests[] = [
                                    //         'value'      => $mr['article'],
                                    //         'item_id'    => $item['item_id'],
                                    //         'article_id' => $mr['article_id'],
                                    //         'quantity'   => 2,
                                    //         'unit_id'    => $mr['unit_id'],
                                    //         'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    //         'width_lot'  => null,
                                    //     ];
                                    // }
                                break;
                                case 2:
                                    // if ($mr['is_general'] == 10 AND $item['side_id'] == 1) { // SOPORTE LATERAL
                                    //     $requests[] = [
                                    //         'value'      => $mr['article'],
                                    //         'item_id'    => $item['item_id'],
                                    //         'article_id' => $mr['article_id'],
                                    //         'quantity'   => 2,
                                    //         'unit_id'    => $mr['unit_id'],
                                    //         'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    //         'width_lot'  => null,
                                    //     ];
                                    // }
                                    if ($mr['is_general'] == 9 AND $item['side_id'] == 1 AND $mr['operation_id'] == $item['operation_id']  AND (INT)$bracketITD === 1  ) { // SOPORTE INTERMEDIO ENROLLABLE O CON TRACCION
                                        $requests[] = [
                                            'value'      => $mr['article'],
                                            'item_id'    => $item['item_id'],
                                            'article_id' => $mr['article_id'],
                                            'quantity'   => 1,
                                            'unit_id'    => $mr['unit_id'],
                                            'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                            'width_lot'  => null,
                                        ];
                                    }
                                break;
                            }
                        }
                        // CADENA
                        if ( $mr['is_chain'] == 1 AND $item['chain_id'] == $mr['chain_id'] AND ($item['component_color_id'] == $mr['color_id'] OR is_null($mr['color_id'])) AND
                        ( ( ( (INT)$chainITD === 1 OR (INT)$heightChainITD === 1 ) OR (INT)$componentColorITD === 1 ) OR (INT)$damageChain === 1 ) ) {
                            $heightChain = $this->getchainSize($item['height_chain'],$item['height'],$item['mechanism_id'],$item['relation_cassette'],$item['tube_id'],$item['product_id'],$item['if_chain_height']);
                            $heightChainAdd = $this->heightChainAdd($item['relation_heat_seal'],$order['details']);
                            $heightChain = $heightChain + $heightChainAdd;
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $heightChain,
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // COMPLEMENTOS CADENA
                        if ( $item['chain_id'] == $mr['chain_id'] AND $mr['is_chain'] == 2 AND is_null($mr['color_id']) AND (INT)$chainITD === 1 ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        if ( $item['chain_id'] == $mr['chain_id'] AND $mr['is_chain'] == 2 AND $item['component_color_id'] == $mr['color_id'] AND $mr['product_id'] == 1 AND ( (INT)$chainITD === 1 OR (INT)$componentColorITD === 1 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // MECANISMO
                        $CCID = $item['component_color_id'];
                        if( (INT)$item['mechanism_id'] === 2 ) { $CCID = 1; }
                        if ( $mr['mechanism_id'] == $item['mechanism_id'] AND $CCID == $mr['color_id'] AND !is_null($item['mechanism_id']) AND ( ( (INT)$mechanismITD === 1 OR (INT)$componentColorITD === 1 ) OR (INT)$damageMechanism === 1 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                    // }
                }

            break;
            case 2: // Sheer elegance
                if( (INT)$clothIDT === 1  || (INT)$damageFabric === 1 ) {
                    $heightAdd = $this->heightAdd($item['product_id'],$item['operation_id'],$item['motor_id'],$item['tube_id'],$item['relation_cassette']);
                    $requests[] = [
                        'value'      => $item['article'],
                        'item_id'    => $item['item_id'],
                        'article_id' => $item['article_id'],
                        'quantity'   => ($item['height']*2)+$heightAdd,
                        'unit_id'    => 2,
                        'cost'       => $this->foundCostItem($articles,$item['article_id']),
                        'width_lot'  => $this->foundWidthLot($articles,$item['article_id']),
                    ];
                }
                foreach ($materialRequests as $mr) {
                    // if ($item['operation_id'] == $mr['operation_id'] ) { // Operacion Manual
                        //  CINTA  DOBLE  CARA
                        if ( $mr['is_general'] == 1 AND is_null($mr['color_id']) AND ( (INT)$counterweightIDT === 1 OR (INT)$clothIDT === 1 OR (INT)$damageFabric === 1  OR (INT)$damageTube === 1 OR (INT)$tubeIDT === 1 OR (INT)$damagedFascia === 1 )) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : ($item['width']*4),
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // INSERTO 11 MM
                        if ( $mr['is_general'] == 7 AND is_null($mr['color_id']) AND $mr['counterweight_bar_id'] == 1 AND ( (INT)$counterweightIDT === 1 OR (INT)$clothIDT === 1 OR (INT)$damageFabric === 1 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // Contrapeso + color // componentes contrapeso
                        if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] AND (INT)$mr['is_general'] === 0 AND ( ( (INT)$counterweightIDT === 1 OR (INT)$componentColorITD === 1 ) OR (INT)$damageCounterweight === 1 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // Cmplementos contrapeeso
                        if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] AND (INT)$mr['is_general'] === 6 AND ( ( (INT)$counterweightIDT === 1 OR (INT)$componentColorITD === 1 ) OR (INT)$damageCounterweightComp === 1 )  ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // BARRA GIRO
                        if ( $mr['is_general'] == 2 AND $item['component_color_id'] == $mr['color_id'] AND $item['counterweight_bar_id'] == 3 AND (INT)$counterweightIDT === 1) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // BARRA GIRO TIPO A
                        if ( $mr['is_general'] == 11 AND $item['component_color_id'] == $mr['color_id'] AND $item['counterweight_bar_id'] == 5 AND ((INT)$counterweightIDT === 1 OR (INT)$componentColorITD === 1 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // TUBO
                        if ( $item['tube_id']  == $mr['tube_id'] AND $item['operation_id'] == $mr['operation_id'] AND (INT)$mr['is_general'] === 0 AND ( (INT)$tubeIDT === 1 OR (INT)$damageTube === 1 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }

                        // TUBO ADAPTADOR
                        if ( $item['tube_id']  == $mr['tube_id'] AND $item['operation_id'] == $mr['operation_id'] AND (INT)$mr['is_general'] === 19 AND (INT)$damageTubeComp === 1 ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }

                        // PERFIL
                        if ( $mr['is_perfil'] == 1 AND $item['component_color_id'] == $mr['color_id'] AND $item['side_id'] == 1 AND ( (INT)$componentColorITD === 1 || (INT)$damagedFascia === 1 )) {
                            if( $order['nomen'] == "GLS" ) { $orderID = $item['guarantee_id']; } else { $orderID = $item['order_id']; }
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $this->sumWidthPerfil($item['relation_cassette'],$orderID,$order['details'],$order['nomen']),
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }

                        // TAPA PERFIL
                        if ( $mr['is_perfil'] == 2 AND $mr['is_general'] == 4  AND $item['side_id'] == 1 AND $item['component_color_id'] == $mr['color_id'] AND ( (INT)$componentColorITD === 1 || (INT)$damagedFascia === 1 || (INT)$mechanismMSheerChange === 1 )) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // TAPA PERFIL LISA
                        // if ( $mr['is_perfil'] == 2 AND $mr['is_general'] == 3 AND  $item['mechanism_id'] != 1 AND $item['side_id'] == 1 AND $item['component_color_id'] == $mr['color_id'] AND $item['side_id'] == 1 ) {
                        //     $requests[] = [
                        //         'value'      => $mr['article'],
                        //         'article_id' => $mr['article_id'],
                        //         'quantity'   => $mr['quantity'],
                        //         'unit_id'    => $mr['unit_id'],
                        //         'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                        //         'width_lot'  => null,
                        //     ];
                        // }
                        // SOPORTES
                        switch ($item['divisions']) {
                            case 1:
                                if ($mr['is_general'] == 10 AND $item['mechanism_id'] != 1 AND $item['side_id'] == 1 AND ( (INT)$bracketITD === 1  || (INT)$mechanismMSheerChange === 1 ) ) { // SOPORTE LATERAL Unicamente con mecanismo SL8
                                    $requests[] = [
                                        'value'      => $mr['article'],
                                        'item_id'    => $item['item_id'],
                                        'article_id' => $mr['article_id'],
                                        'quantity'   => $mr['quantity'],
                                        'unit_id'    => $mr['unit_id'],
                                        'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                        'width_lot'  => null,
                                    ];
                                }
                            break;
                            case 2:
                                if ($mr['is_general'] == 10 AND $item['side_id'] == 1 AND ( (INT)$bracketITD === 1  || (INT)$mechanismMSheerChange === 1 ) ) { // SOPORTE LATERAL
                                    $requests[] = [
                                        'value'      => $mr['article'],
                                        'item_id'    => $item['item_id'],
                                        'article_id' => $mr['article_id'],
                                        'quantity'   => $mr['quantity'],
                                        'unit_id'    => $mr['unit_id'],
                                        'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                        'width_lot'  => null,
                                    ];
                                }
                                if ($mr['is_general'] == 8 AND $item['side_id'] == 1 AND (INT)$bracketITD === 1 ) { // SOPORTE INTERMEDIO PARA FASCIA
                                    $requests[] = [
                                        'value'      => $mr['article'],
                                        'item_id'    => $item['item_id'],
                                        'article_id' => $mr['article_id'],
                                        'quantity'   => 1,
                                        'unit_id'    => $mr['unit_id'],
                                        'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                        'width_lot'  => null,
                                    ];
                                }
                            break;
                            case 3:
                                if ($mr['is_general'] == 10 AND $item['side_id'] == 1 AND ( (INT)$bracketITD === 1  || (INT)$mechanismMSheerChange === 1 ) ) { // SOPORTE LATERAL
                                    $requests[] = [
                                        'value'      => $mr['article'],
                                        'item_id'    => $item['item_id'],
                                        'article_id' => $mr['article_id'],
                                        'quantity'   => 2,
                                        'unit_id'    => $mr['unit_id'],
                                        'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                        'width_lot'  => null,
                                    ];
                                }
                                if ($mr['is_general'] == 8 AND $item['side_id'] == 1 AND (INT)$bracketITD === 1 ) { // SOPORTE INTERMEDIO PARA FASCIA
                                    $requests[] = [
                                        'value'      => $mr['article'],
                                        'item_id'    => $item['item_id'],
                                        'article_id' => $mr['article_id'],
                                        'quantity'   => 1,
                                        'unit_id'    => $mr['unit_id'],
                                        'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                        'width_lot'  => null,
                                    ];
                                }
                            break;
                        }
                        // MECANISMO
                        $CCID = $item['component_color_id'];
                        if( (INT)$item['mechanism_id'] === 2 ) { $CCID = 1; } // SL8 se  cambia todo a blanco
                        if ( $mr['mechanism_id'] == $item['mechanism_id'] AND $CCID == $mr['color_id'] AND !is_null($item['mechanism_id']) AND ( ( (INT)$mechanismITD === 1 OR (INT)$componentColorITD === 1 ) OR (INT)$damageMechanism === 1 ) ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                        }
                        // BRACKET DE INSTALACION
                        if ( $mr['is_perfil'] == 2 AND $mr['is_general'] == 5  AND (INT)$bracketITD === 1) {
                            $quantityTemp = 2;
                            $totalBrackets = floor($item['width'] / 0.50);
                            if( $totalBrackets > 2 ) {  $quantityTemp =  $totalBrackets; }
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $quantityTemp,
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // CADENA
                        if ( $mr['is_chain'] == 1 AND $item['chain_id'] == $mr['chain_id'] AND $item['component_color_id'] == $mr['color_id'] AND ( ( ( (INT)$chainITD === 1 OR (INT)$heightChainITD === 1) OR (INT)$componentColorITD === 1 ) OR (INT)$damageChain === 1 ) ) {
                            $heightChain = $this->getchainSize($item['height_chain'],$item['height'],$item['mechanism_id'],$item['relation_cassette'],$item['tube_id'],$item['product_id'],0);
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $heightChain,
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // COMPLEMENTOS CADENA
                        if ( $item['chain_id'] == $mr['chain_id'] AND $mr['is_chain'] == 2 AND is_null($mr['color_id']) AND (INT)$chainITD === 1 ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        if ( $item['chain_id'] == $mr['chain_id'] AND $mr['is_chain'] == 2 AND $item['component_color_id'] == $mr['color_id'] AND ( (INT)$chainITD === 1 OR (INT)$componentColorITD === 1 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                    // }
                }
            break;
            case 4: // ACCESORIOS
                if( $item['product_id'] == 4 AND ($item['relation_accesories'] == 0 OR is_null($item['relation_accesories'])) AND ( (INT)$item['model_id'] !== 25 AND (INT)$item['model_id'] !== 6 AND (INT)$item['model_id'] !== 51 AND (INT)$item['model_id'] !== 57 ) AND ( (INT)$motorITD === 1 || (INT)$damagedMotor === 1 ) )  {

                    if((INT)$item['article_id'] === 97) { // CINTA DOBLE CARA
                        $requests[] = [
                            'value'      => $item['article'],
                            'item_id'    => $item['item_id'],
                            'article_id' => $item['article_id'],
                            'quantity'   => $item['quantity']*50,
                            'unit_id'    => 2,
                            'cost'       => $this->foundCostItem($articles,$item['article_id']),
                            'width_lot'  => null,
                        ];
                    } else {
                        $requests[] = [
                            'value'      => $item['article'],
                            'item_id'    => $item['item_id'],
                            'article_id' => $item['article_id'],
                            'quantity'   => $item['quantity'],
                            'unit_id'    => $item['unit_id'],
                            'cost'       => $this->foundCostItem($articles,$item['article_id']),
                            'width_lot'  => null,
                        ];
                    }
                }
                if((INT)$item['model_id'] === 51 AND $order['nomen'] == 'LS') {
                    $requests[] = [
                        'value'      => 'Corbatin',
                        'item_id'    => $item['item_id'],
                        'article_id' => $item['corbatin_id'],
                        'quantity'   => $item['height'],
                        'unit_id'    => 2,
                        // 'cost'       => $this->foundCostLambrequinItem($articles,$item['lambrequin_id']),
                        'cost'       => 60,
                        'width_lot'  => $this->foundWidthLot($articles,$item['corbatin_id']),
                    ];
                    foreach ($materialRequests as $mr) {
                        // Contrapeso + color // componentes contrapeso
                        if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND ($item['component_color_id'] == $mr['color_id']  OR 7  == $mr['color_id'] ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                    }
                    //  VELCRO
                    $requests[] = [
                        'value'      => 'VELCRO DE 19MM',
                        'item_id'    => $item['item_id'],
                        'article_id' => 281,
                        'quantity'   => $item['width'],
                        'unit_id'    => 2,
                        'cost'       => 5,
                        'width_lot'  => null,
                    ];

                }
                if((INT)$item['model_id'] === 57 AND $order['nomen'] == 'LS') {
                    $requests[] = [
                        'value'      => 'Fijo',
                        'item_id'    => $item['item_id'],
                        'article_id' => $item['fijo_id'],
                        'quantity'   => $item['height'],
                        'unit_id'    => 2,
                        // 'cost'       => $this->foundCostLambrequinItem($articles,$item['lambrequin_id']),
                        'cost'       => 60,
                        'width_lot'  => $this->foundWidthLot($articles,$item['fijo_id']),
                    ];
                    //  VELCRO
                    $requests[] = [
                        'value'      => 'VELCRO DE 19MM',
                        'item_id'    => $item['item_id'],
                        'article_id' => 281,
                        'quantity'   => $item['width'],
                        'unit_id'    => 2,
                        'cost'       => 5,
                        'width_lot'  => null,
                    ];

                }
                if((INT)$item['model_id'] === 25 AND $order['nomen'] == 'LS') {
                    // lambrequin
                    if( (INT)$clothIDT === 1 ) {
                        $requests[] = [
                            'value'      => 'Lambrequin',
                            'item_id'    => $item['item_id'],
                            'article_id' => $item['lambrequin_id'],
                            'quantity'   => $item['height'],
                            'unit_id'    => 2,
                            // 'cost'       => $this->foundCostLambrequinItem($articles,$item['lambrequin_id']),
                            'cost'       => 100,
                            'width_lot'  => $this->foundWidthLot($articles,$item['lambrequin_id']),
                        ];
                    }
                    foreach ($materialRequests as $mr) {

                        //  CINTA  DOBLE  CARA
                        if ( $mr['is_general'] == 1 AND is_null($mr['color_id']) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : ($item['width']*4),
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // Contrapeso + color // componentes contrapeso
                        if($item['counterweight_bar_id'] == 4 AND ((INT)$counterweightIDT === 1 OR (INT)$componentColorITD === 1 ) ) { // baso ovalada cubierta
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND 7 == $mr['color_id'] ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        } else {
                            if ( $item['counterweight_bar_id'] == $mr['counterweight_bar_id'] AND $item['component_color_id'] == $mr['color_id'] AND ((INT)$counterweightIDT === 1 OR (INT)$componentColorITD === 1 ) ) {
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'] != 0 ? $mr['quantity'] : $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        }
                        // INSERTO 11 MM
                        if ( $mr['is_general'] == 7 AND is_null($mr['color_id']) AND $mr['counterweight_bar_id'] == $item['counterweight_bar_id'] ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // INSERTO REDONDO DE 6 MM
                        else if( $mr['is_general'] == 7  AND is_null($mr['color_id']) AND is_null($mr['counterweight_bar_id']) AND ($item['counterweight_bar_id'] == 2 OR $item['counterweight_bar_id'] == 4 ) ) {
                            $requests[] = [
                                'value'      => $mr['article'],
                                'item_id'    => $item['item_id'],
                                'article_id' => $mr['article_id'],
                                'quantity'   => $item['width'],
                                'unit_id'    => $mr['unit_id'],
                                'cost'       => $this->foundCostItem($articles,$mr['article_id']),
                                'width_lot'  => null,
                            ];
                        }
                        // Accesorios lambrequin
                        if( (INT)$item['is_velcro'] === 1) {
                            if( (INT)$item['model_id'] === 25 AND $mr['is_lambrequin'] == 1 AND (INT)$mr['is_general'] === 17 ) { // VELCRO
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        } else {
                            if( (INT)$item['model_id'] === 25 AND $mr['is_lambrequin'] == 1 AND (INT)$mr['is_general'] === 13 ) { // RIEL
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            if( (INT)$item['model_id'] === 25 AND $mr['is_lambrequin'] == 1 AND (INT)$mr['is_general'] === 14 ) { // TAPAS
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $mr['quantity'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            if( (INT)$item['model_id'] === 25 AND $mr['is_lambrequin'] == 1 AND (INT)$mr['is_general'] === 15 ) { // SOPORTE RIEL UNIVERSAL
                                $quantityTemp = 2;
                                $totalBrackets = floor($item['width'] / 0.50);
                                if( $totalBrackets > 2 ) {  $quantityTemp =  $totalBrackets; }
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $quantityTemp,
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                            if( (INT)$item['model_id'] === 25 AND $mr['is_lambrequin'] == 1 AND (INT)$mr['is_general'] === 16 ) { // TABLETA PARA RIEL UNIVERSAL
                                $requests[] = [
                                    'value'      => $mr['article'],
                                    'item_id'    => $item['item_id'],
                                    'article_id' => $mr['article_id'],
                                    'quantity'   => $item['width'],
                                    'unit_id'    => $mr['unit_id'],
                                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                                    'width_lot'  => null,
                                ];
                            }
                        }
                    }
                }
            break;
            case 5: // Lienzo
                $itemHeatSeal = [];
                foreach ($order['details'] as $key => $od) { if( (INT)$od['relation_heat_seal'] === (INT)$item['relation_heat_seal'] AND (INT)$od['product_id'] === 1 AND (INT)$od['is_heat_seal'] === 1 ) { $itemHeatSeal = $od; } }
                $heightAdd = $this->heightAdd($itemHeatSeal['product_id'],$itemHeatSeal['operation_id'],$itemHeatSeal['motor_id'],$itemHeatSeal['tube_id'],$itemHeatSeal['relation_cassette']);
                $requestCloth = ($item['height']+$heightAdd);
                if($item['is_inverted'] == 1) { $requestCloth = $item['width']; }
                $requests[] = [
                    'value'      => $item['article'],
                    'item_id'    => $item['item_id'],
                    'article_id' => $item['article_id'],
                    'quantity'   => $requestCloth,
                    'unit_id'    => 2,
                    'cost'       => $this->foundCostItem($articles,$item['article_id']),
                    'width_lot'  => $this->foundWidthLot($articles,$item['article_id']),
                ];
            break;
        }
        return $requests;
    }


    public function getSuccessOrder($order_id, $client_id)
    {
        try {
            $order = $this->getIndividualOrder($order_id);
            if($order['client_id'] == $client_id) {
                return response()->json([
                    'success'       =>  true ,
                    'order' =>  $order,
                ], 200);
            } else {
                return response()->json([
                    'success'       =>  true ,
                    'order' =>  [],
                ], 200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }


    public function savePackedLocationItem(Request $request)
    {
        // try {
            $date_now = Carbon::now();
            $productionLine = DProductionLocation::select('id','is_occupied')->where('id',$request->production_location_id)->first();
            if( (INT)$productionLine['is_occupied'] === 0) {
                // cambiamos el status del item
                if( $request->nomen == 'GLS' ) {
                    DGuaranty::where('id',$request->order_detail_id)
                    ->update([
                        'status_production_id'      => 5,
                        'production_location_id'    => $productionLine['id'],
                        'packing_date'              => $date_now,
                    ]);
                    // Update loocation
                    DProductionLocation::where('id',$productionLine['id'])
                    ->update([ 'is_occupied' => 1, ]);
                    // verificamos si todos los items ya se encuentran empacados
                    $totalRegs = [];
                    $statementTR = DB::getPdo()->prepare("CALL sp_modulation(13,".$request->order_id.",2,0,0,0.0,0.0,0.0,'','','','','')");
                    $statementTR->execute();
                    do {  $resultsAcc[] = $statementTR->fetchAll(\PDO::FETCH_OBJ); } while ($statementTR->nextRowSet());
                    foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $totalRegs[] = $value; }
                    // buscamos la informacion a cambiar
                    $order_detal = DGuaranty::select('d_guarantee.id','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status')
                    ->where('d_guarantee.id',$request->order_detail_id)
                    ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
                    ->first();
                } else {
                    DOrder::where('id',$request->order_detail_id)
                    ->update([
                        'status_production_id' => 5,
                        'production_location_id'    => $productionLine['id'],
                        'packing_date'         => $date_now,
                    ]);
                    // Update loocation
                    DProductionLocation::where('id',$productionLine['id'])
                    ->update([ 'is_occupied' => 1, ]);
                    // LOGS
                    $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                    $logs->createMovementLog($request->user_id,'Empacado de producto terminado',1,7,23,3,'order_detail_id',$request->order_detail_id,'Se guardo y se le asigno ubicación a una partida');
                    // verificamos si todos los items ya se encuentran empacados
                    $totalRegs = [];
                    $statementTR = DB::getPdo()->prepare("CALL sp_modulation(13,".$request->order_id.",1,0,0,0.0,0.0,0.0,'','','','','')");
                    $statementTR->execute();
                    do {  $resultsAcc[] = $statementTR->fetchAll(\PDO::FETCH_OBJ); } while ($statementTR->nextRowSet());
                    foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $totalRegs[] = $value; }

                    // buscamos la informacion a cambiar
                    $order_detal = DOrder::select('d_orders.id','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status')
                    ->where('d_orders.id',$request->order_detail_id)
                    ->join('c_status_productions','c_status_productions.id','d_orders.status_production_id')
                    ->first();

                }

                // buscamos los usuarios del cambio
                $users_ids = DErpAccessUser::select('user_id as id')
                ->where('module_id', 7)
                ->where('submodule_id', 23)
                ->get();
                // VAMOS SI YA SE COPLETARON LOS ITEMS PARA CAMBIAR EL STATUS DEL PEDIDO
                if((INT)$totalRegs[0]['total_regs'] === (INT)$totalRegs[0]['package_status']) {
                    $typeFile = '';
                    $descTypeFile = '';
                    // Verificams que tipo de documento es
                    if( $request->nomen == 'GLS' ) {
                        $typeFile = 'Garatia';
                        $descTypeFile = 'una garatia';
                        // Cambiamos el status del pedido a empacado
                        EGuaranty::where('id',$request->order_id)
                        ->update([
                            'status_id'    => 5,
                            'packing_date' => $date_now,
                        ]);
                        // LOGS
                        $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                        $logs->createMovementLog($request->user_id,'Actualizó status de garantia',1,7,23,1,'guarantee_id',$request->order_id,'Se actualizo status de pedido a Empacado');
                    }  else {
                        $typeFile = 'Pedido';
                        $descTypeFile = 'un pedido';
                        // Cambiamos el status del pedido a empacado
                        EOrder::where('id',$request->order_id)
                        ->update([
                            'status_id'    => 9,
                            'packing_date' => $date_now,
                        ]);
                        // LOGS
                        $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                        $logs->createMovementLog($request->user_id,'Actualizó status de pedido',1,7,23,1,'order_id',$request->order_id,'Se actualizo status de pedido a Empacado');
                    }
                    // cremos notificacion
                    switch ((INT)$totalRegs[0]['delivery_type_id']) {
                        case 1: //Envío
                            $to = '/shipments/deliveries';
                            $message = [
                                "title"       => $typeFile.' en embarque (Envío)',
                                "description" => 'Tienes '.$descTypeFile.' en embarque con tipo de entrega ENVÍO.',
                                "icon"        => 'mdi-truck-cargo-container',
                                "icon_color"  => '#EE3382',
                            ];
                            $users_not_ids = DErpAccessUser::select('user_id as id')
                            ->where('module_id', 12)
                            ->where('submodule_id', 12)
                            ->get();
                        break;
                        case 2: // Mostrador
                            $to = '/shipments/reception';
                            $message = [
                                "title"       => $typeFile.' en embarque (Mostrador)',
                                "description" => 'Tienes '.$descTypeFile.' en embarque con tipo de entrega MOSTRADOR.',
                                "icon"        => 'mdi-package-variant',
                                "icon_color"  => '#EE3382',
                            ];
                            $users_not_ids = DErpAccessUser::select('user_id as id')
                            ->where('module_id', 12)
                            ->where('submodule_id', 13)
                            ->get();
                        break;
                        case 3: // Ruta
                            $to = '/shipments/route';
                            $message = [
                                "title"       => $typeFile.' en embarque (Ruta)',
                                "description" => 'Tienes '.$descTypeFile.' en embarque con tipo de entrega RUTA.',
                                "icon"        => 'mdi-map-outline',
                                "icon_color"  => '#EE3382',
                            ];
                            $users_not_ids = DErpAccessUser::select('user_id as id')
                            ->where('module_id', 12)
                            ->where('submodule_id', 22)
                            ->get();
                        break;
                    }
                    // Guardamos usuarios para el socket
                    foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
                    $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
                    // Creamos notificacion
                    $notifications = new Notifications;
                    $notification = $notifications->createNewNotification($request->order_id,1,0,$users_not_ids,$message,$to);
                    foreach ($users_not_ids as $value_not) { $users_socket_notifications_ids[] = $value_not['id']; }
                    $users_socket_notification = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_notifications_ids)->where('user_type','ERP')->get();
                    // PEDIDO
                    // Verificams que tipo de documento es
                    if( $request->nomen == 'GLS' ) {
                        $order = $this->getIndividualGuarantee($request->order_id);
                    } else  {
                        $order = $this->getIndividualOrder($request->order_id);
                    }


                    return response()->json([
                        'success'                   =>  true ,
                        'opt'                       => $request->opt,
                        'nomen'                     => $request->nomen,
                        'order_id'                  => $request->order_id,
                        'order_detail'              => $order_detal,
                        'users_socket'              => $users_socket,
                        'users_socket_notification' => $users_socket_notification,
                        'notification'              => $notification,
                        'order'                     => $order,
                        'type_change'               => 'all',
                    ], 200);
                } else {
                    // Guardamos usuarios para el socket
                    foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
                    $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
                    return response()->json([
                        'success'         =>  true ,
                        'users_socket'    => $users_socket,
                        'order_detail'    => $order_detal,
                        'type_change'     => 'only_item',
                    ], 200);
                }
            } else {
                return response()->json([
                    'success'       =>  false ,
                    'type_error'    => 'location_occupied',
                ], 400);
            }
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function saveLocation(Request $request)
    {
        // try {
            $date_now = Carbon::now();
            $productionLine = DProductionLocation::select('id',DB::raw('CASE WHEN id = 257 THEN 0 else is_occupied END AS is_occupied'))->where('location',$request->production_location_id)->first();
            if( (INT)$productionLine['is_occupied'] === 0) {
                if(!$request->editLocation) {
                    // cambiamos el status del item
                    if( $request->nomen == 'GLS' OR  $request->nomen == 'SLS' ) {
                        DGuaranty::where('id',$request->order_detail_id)
                        ->update([
                            'status_production_id'      => 5,
                            'production_location_id'    => $productionLine['id'],
                            'packing_date'              => $date_now,
                        ]);
                        // Update loocation
                        DProductionLocation::where('id',$productionLine['id'])
                        ->update([ 'is_occupied' => 1, ]);
                        // verificamos si todos los items ya se encuentran empacados
                        $totalRegs = [];
                        $statementTR = DB::getPdo()->prepare("CALL sp_modulation(13,".$request->order_id.",2,0,0,0.0,0.0,0.0,'','','','','')");
                        $statementTR->execute();
                        do {  $resultsAcc[] = $statementTR->fetchAll(\PDO::FETCH_OBJ); } while ($statementTR->nextRowSet());
                        foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $totalRegs[] = $value; }
                        // buscamos la informacion a cambiar
                        $order_detal = DGuaranty::select('d_guarantee.id','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status')
                        ->where('d_guarantee.id',$request->order_detail_id)
                        ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
                        ->first();
                    } else {
                        DOrder::where('id',$request->order_detail_id)
                        ->update([
                            'status_production_id'      => 5,
                            'production_location_id'    => $productionLine['id'],
                            'packing_date'              => $date_now,
                        ]);
                        // Update loocation
                        DProductionLocation::where('id',$productionLine['id'])
                        ->update([ 'is_occupied' => 1, ]);
                        // LOGS
                        $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                        $logs->createMovementLog($request->user_id,'Empacado de producto terminado',1,7,23,3,'order_detail_id',$request->order_detail_id,'Se guardo y se le asigno ubicación a una partida');
                        // verificamos si todos los items ya se encuentran empacados
                        $totalRegs = [];
                        $statementTR = DB::getPdo()->prepare("CALL sp_modulation(13,".$request->order_id.",1,0,0,0.0,0.0,0.0,'','','','','')");
                        $statementTR->execute();
                        do {  $resultsAcc[] = $statementTR->fetchAll(\PDO::FETCH_OBJ); } while ($statementTR->nextRowSet());
                        foreach (json_decode(json_encode($resultsAcc[0]), true)  as $value) { $totalRegs[] = $value; }
                        // buscamos la informacion a cambiar
                        $order_detal = DOrder::select('d_orders.id','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status')
                        ->where('d_orders.id',$request->order_detail_id)
                        ->join('c_status_productions','c_status_productions.id','d_orders.status_production_id')
                        ->first();
                    }
                    // buscamos los usuarios del cambio
                    $users_ids = DErpAccessUser::select('user_id as id')
                    ->where('module_id', 7)
                    ->where('submodule_id', 23)
                    ->get();
                    // VAMOS SI YA SE COPLETARON LOS ITEMS PARA CAMBIAR EL STATUS DEL PEDIDO
                    if((INT)$totalRegs[0]['total_regs'] === (INT)$totalRegs[0]['package_status']) {
                        $typeFile = '';
                        $descTypeFile = '';
                        // Verificams que tipo de documento es
                        if( $request->nomen == 'GLS' ) {
                            $typeFile = 'Garatia';
                            $descTypeFile = 'una garatia';
                            // Cambiamos el status del pedido a empacado
                            EGuaranty::where('id',$request->order_id)
                            ->update([
                                'status_id'    => 5,
                                'packing_date' => $date_now,
                            ]);
                            // LOGS
                            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                            $logs->createMovementLog($request->user_id,'Actualizó status de garantia',1,7,23,1,'guarantee_id',$request->order_id,'Se actualizo status de producción a Empacado');
                        }  else if( $request->nomen == 'SLS' ) {
                            $typeFile = 'Servicio';
                            $descTypeFile = 'un servicio';
                            // Cambiamos el status del pedido a empacado
                            EGuaranty::where('id',$request->order_id)
                            ->update([
                                'status_id'    => 5,
                                'packing_date' => $date_now,
                            ]);
                            // LOGS
                            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                            $logs->createMovementLog($request->user_id,'Actualizó status de servicio',1,7,23,1,'guarantee_id',$request->order_id,'Se actualizo status de producción a Empacado');
                        }  else {
                            $typeFile = 'Pedido';
                            $descTypeFile = 'un pedido';
                            // Cambiamos el status del pedido a empacado
                            EOrder::where('id',$request->order_id)
                            ->update([
                                'status_id'    => 9,
                                'packing_date' => $date_now,
                            ]);
                            // LOGS
                            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                            $logs->createMovementLog($request->user_id,'Actualizó status de pedido',1,7,23,1,'order_id',$request->order_id,'Se actualizo status de producción a Empacado');
                        }
                        // cremos notificacion
                        switch ((INT)$totalRegs[0]['delivery_type_id']) {
                            case 1: //Envío
                                $to = '/shipments/deliveries';
                                $message = [
                                    "title"       => $typeFile.' Empacado completo (Envío)',
                                    "description" => 'Tienes '.$descTypeFile.'El pedido ya está empacado, necesitas recolectarlo.',
                                    "icon"        => 'mdi-truck-cargo-container',
                                    "icon_color"  => '#EE3382',
                                ];
                                $users_not_ids = DErpAccessUser::select('user_id as id')
                                ->where('module_id', 12)
                                ->where('submodule_id', 12)
                                ->get();
                            break;
                            case 2: // Mostrador
                                $to = '/shipments/reception';
                                $message = [
                                    "title"       => $typeFile.' Empacado completo (Mostrador)',
                                    "description" => 'Tienes '.$descTypeFile.'El pedido ya está empacado, necesitas recolectarlo.',
                                    "icon"        => 'mdi-package-variant',
                                    "icon_color"  => '#EE3382',
                                ];
                                $users_not_ids = DErpAccessUser::select('user_id as id')
                                ->where('module_id', 12)
                                ->where('submodule_id', 13)
                                ->get();
                            break;
                            case 3: // Ruta
                                $to = '/shipments/route';
                                $message = [
                                    "title"       => $typeFile.' Empacado completo (Ruta)',
                                    "description" => 'Tienes '.$descTypeFile.'El pedido ya está empacado, necesitas recolectarlo.',
                                    "icon"        => 'mdi-map-outline',
                                    "icon_color"  => '#EE3382',
                                ];
                                $users_not_ids = DErpAccessUser::select('user_id as id')
                                ->where('module_id', 12)
                                ->where('submodule_id', 22)
                                ->get();
                            break;
                        }
                        // Guardamos usuarios para el socket
                        foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
                        $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
                        // Creamos notificacion
                        $notifications = new Notifications;
                        $notification = $notifications->createNewNotification($request->order_id,1,0,$users_not_ids,$message,$to);
                        foreach ($users_not_ids as $value_not) { $users_socket_notifications_ids[] = $value_not['id']; }
                        $users_socket_notification = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_notifications_ids)->where('user_type','ERP')->get();
                        // PEDIDO
                        // Verificams que tipo de documento es
                        if( $request->nomen == 'GLS' || $request->nomen == 'SLS'  ) {
                            $order = $this->getIndividualGuarantee($request->order_id);
                        } else  {
                            $order = $this->getIndividualOrder($request->order_id);
                        }

                        return response()->json([
                            'success'                   =>  true ,
                            'opt'                       => $request->opt,
                            'nomen'                     => $request->nomen,
                            'order_id'                  => $request->order_id,
                            'order_detail'              => $order_detal,
                            'users_socket'              => $users_socket,
                            'users_socket_notification' => $users_socket_notification,
                            'notification'              => $notification,
                            'order'                     => $order,
                            'order_detail_id'           => $request->order_detail_id,
                            'production_location_id'    => $productionLine['id'],
                            'type_change'               => 'all',
                        ], 200);
                    } else {
                        // Guardamos usuarios para el socket
                        foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
                        $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
                        return response()->json([
                            'success'                   =>  true ,
                            'users_socket'              => $users_socket,
                            'order_detail'              => $order_detal,
                            'production_location_id'    => $productionLine['id'],
                            'order_detail_id'           => $request->order_detail_id,
                            'type_change'               => 'only_item',
                        ], 200);
                    }
                } else {
                    // buscamos la locacion antigua y actualizamos
                    DProductionLocation::where('location',$request->itemSelected['production_location_id'])
                    ->update([ 'is_occupied' => 0, ]);
                    // cambiamos el status del item
                    if( $request->nomen == 'GLS' || $request->nomen == 'SLS' ) {
                        DGuaranty::where('id',$request->order_detail_id)
                        ->update([
                            'production_location_id' => $productionLine['id'],
                        ]);
                        // buscamos la informacion a cambiar
                        $order_detal = DGuaranty::select('d_guarantee.id','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status')
                        ->where('d_guarantee.id',$request->order_detail_id)
                        ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
                        ->first();
                    } else {
                        DOrder::where('id',$request->order_detail_id)
                        ->update([
                            'production_location_id' => $productionLine['id'],
                        ]);
                        // buscamos la informacion a cambiar
                        $order_detal = DOrder::select('d_orders.id','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status')
                        ->where('d_orders.id',$request->order_detail_id)
                        ->join('c_status_productions','c_status_productions.id','d_orders.status_production_id')
                        ->first();
                    }
                    // Update loocation
                    DProductionLocation::where('id',$productionLine['id'])
                    ->update([ 'is_occupied' => 1, ]);
                    // Guardamos usuarios para el socket
                    $users_ids = DErpAccessUser::select('user_id as id')
                    ->where('module_id', 7)
                    ->where('submodule_id', 23)
                    ->get();
                    foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
                    $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
                    return response()->json([
                        'success'                   =>  true ,
                        'users_socket'              => $users_socket,
                        'order_detail'              => $order_detal,
                        'production_location_id'    => $productionLine['id'],
                        'order_detail_id'           => $request->order_detail_id,
                        'type_change'               => 'only_item',
                    ], 200);
                }
            } else {
                return response()->json([
                    'success'       =>  false ,
                    'type_error'    => 'location_occupied',
                ], 400);
            }
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
    }
    public function getReceptionOrders()
    {
        try {
            $orders = $this->getShipmentOrders([9,17],2);
            $guarantee = $this->getShipmentGuarantee([5,12],2);
            $allData = [];
            foreach ($orders as $key => $order) {
                $orders[$key]['type_reg'] = 1;
                $allData[] = $orders[$key];
            }
            foreach ($guarantee as $key2 => $warranty) {
                $guarantee[$key2]['type_reg'] = 2;
                $allData[] = $guarantee[$key2];
            }
            return response()->json([
                'success' =>  true ,
                'orders'  => $allData,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }

    public function downloadReceptionOrderDetail($order_id,$nomen)
    {
        // try {
            if( $nomen == 'GLS' || $nomen == 'SLS' ) {
                $order = $this->getIndividualShipmentGuarantee($order_id);
            } else  {
                $order = $this->getIndividualShipmentOrder($order_id);
            }
            $pdf = new FPDF(new PDF_Code128("L", "mm", "A4"));
            return $pdf->createReceptionOrderDetail($order);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function downloadReceptionOrderDetailTicket($order_id,$nomen)
    {
        // try {
            if( $nomen == 'GLS' || $nomen == 'SLS' ) {
                $order = $this->getIndividualShipmentGuarantee($order_id);
            } else  {
                $order = $this->getIndividualShipmentOrder($order_id);
            }
            $heightPP = 112;
            $heightPP = (INT)$heightPP + ( (INT)COUNT($order['details']) * 8 ) + 30 ;
            $pdf = new FPDF(new PDF_Code128("P", "mm", [(INT)$heightPP,150] ));
            return $pdf->createReceptionOrderDetailTicket($order);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function getDeliveryOrders()
    {
        try {
            $orders = $this->getShipmentOrders([9,17],1);
            $guarantee = $this->getShipmentGuarantee([5,12],1);
            $allData = [];
            foreach ($orders as $key => $order) {
                $orders[$key]['type_reg'] = 1;
                $allData[] = $orders[$key];
            }
            foreach ($guarantee as $key2 => $warranty) {
                $guarantee[$key2]['type_reg'] = 2;
                $allData[] = $guarantee[$key2];
            }


            return response()->json([
                'success'      =>  true ,
                'orders' => $allData,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }

    public function downloadDeliveryOrderDetail($order_id,$nomen)
    {
        // try {
            if( $nomen == 'GLS' || $nomen == 'SLS' ) {
                $order = $this->getIndividualShipmentGuarantee($order_id);
            } else  {
                $order = $this->getIndividualShipmentOrder($order_id);
            }
            $pdf = new FPDF(new PDF_Code128("L", "mm", "A4"));
            return $pdf->createDeliveryOrderDetail($order);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function downloadDeliveryOrderDetailTicket($order_id,$nomen)
    {
        // try {
            if( $nomen == 'GLS' || $nomen == 'SLS' ) {
                $order = $this->getIndividualShipmentGuarantee($order_id);
            } else  {
                $order = $this->getIndividualShipmentOrder($order_id);
            }
            $heightPP = 112;
            $heightPP = (INT)$heightPP + ( (INT)COUNT($order['details']) * 8 ) + 30 ;
            $pdf = new FPDF(new PDF_Code128("P", "mm", [(INT)$heightPP,150] ));
            return $pdf->createDeliveryOrderDetailTicket($order);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);
        // }
    }

    public function getRouteOrders()
    {
        try {
            $orders = $this->getShipmentOrders([9,15,17],3);
            $guarantee = $this->getShipmentGuarantee([5,7,12],3);
            $allData = [];
            foreach ($orders as $key => $order) {
                $orders[$key]['type_reg'] = 1;
                $allData[] = $orders[$key];
            }
            foreach ($guarantee as $key2 => $warranty) {
                $guarantee[$key2]['type_reg'] = 2;
                $allData[] = $guarantee[$key2];
            }
            return response()->json([
                'success' =>  true ,
                'orders'  => $allData,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }

    public function onRouteOrder(Request $request)
    {
        try {
            if($request->nomen  == 'LS') {
                $date_now = Carbon::now();
                $status_id = 15;
                $name_movement = 'Pedido en ruta';
                $description = 'Se inició el proceso de ruta para el pedido '.$request->order_id.'.';
                $module_id = 12;
                $submodule_id = 22;
                // cambiamos el status del item
                EOrder::where('id',$request->order_id)
                ->update([
                    'status_id'    => $status_id,
                    'on_route_date' => $date_now,
                ]);
                // LOGS
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->user_id,$name_movement,1,$module_id,$submodule_id,3,'order_id',$request->order_id,$description);
            } else {
                $date_now = Carbon::now();
                $status_id = 7;
                $name_movement = 'Garantia en ruta';
                $description = 'Se inició el proceso de ruta para la  garantia '.$request->order_id.'.';
                $module_id = 12;
                $submodule_id = 22;
                // cambiamos el status del item
                EGuaranty::where('id',$request->order_id)
                ->update([
                    'status_id'    => $status_id,
                    'on_route_date' => $date_now,
                ]);
                // LOGS
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->user_id,$name_movement,1,$module_id,$submodule_id,3,'guarantee_id',$request->order_id,$description);
            }
            // buscamos los usuarios del cambio
            $users_ids = DErpAccessUser::select('user_id as id')
            ->where('module_id', $module_id)
            ->where('submodule_id', $submodule_id)
            ->get();
            // Guardamos usuarios para el socket
            foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();

            return response()->json([
                'success'       =>  true ,
                'opt'           => $request->opt,
                'nomen'         => $request->nomen,
                'order_id'      => $request->order_id,
                'users_socket'  => $users_socket,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }

    public function saveFinalizeOrder(Request $request)
    {
        try {
            $date_now = Carbon::now();
            switch ($request->opt) {
                case 'shipmentReception':
                    $fileType = 'Pedido entregado';
                    $status_id = 11;
                    if($request->nomen == 'GLS') {
                        $fileType = 'Garantia entregada';
                        $status_id = 8;
                    }
                    if($request->nomen == 'SLS') {
                        $fileType = 'Servicio entregado';
                        $status_id = 8;
                    }
                    $name_movement = $fileType;
                    $description = $fileType.' a '.$request->name_receives.' / '.$request->comment_receives;
                    $module_id = 12;
                    $submodule_id = 13;
                    $name_receives = $request->name_receives;
                    $comment_receives = $request->comment_receives;
                break;
                case 'shipmentDelivery':
                    $fileType = 'Pedido enviado';
                    $status_id = 10;
                    if($request->nomen == 'GLS') {
                        $fileType = 'Garantia enviada';
                        $status_id = 6;
                    }
                    if($request->nomen == 'SLS') {
                        $fileType = 'Servicio enviado';
                        $status_id = 6;
                    }
                    $name_movement = $fileType;
                    $description = $fileType.' entregado a paquetería. / '.$request->comment_receives;
                    $module_id = 12;
                    $submodule_id = 12;
                    $name_receives = 'Paqueteria';
                    $comment_receives = $request->comment_receives;
                break;
                case 'shipmentRoute':
                    $fileType = 'Pedido entregado';
                    $status_id = 11;
                    if($request->nomen == 'GLS') {
                        $fileType = 'Garantia entregada';
                        $status_id = 8;
                    }
                    if($request->nomen == 'SLS') {
                        $fileType = 'Servicio entregado';
                        $status_id = 8;
                    }
                    $name_movement = $fileType;
                    $description = $fileType.' a '.$request->name_receives.' / '.$request->comment_receives;
                    $module_id = 12;
                    $submodule_id = 22;
                    $name_receives = $request->name_receives;
                    $comment_receives = $request->comment_receives;
                break;
            }
            if($request->nomen == 'GLS' ||$request->nomen == 'SLS' ) {
                // cambiamos el status del item
                EGuaranty::where('id',$request->order_id)
                ->update([
                    'status_id'    => $status_id,
                    'finalize_date' => $date_now,
                    'name_receives' => $name_receives,
                    'comment_receives' => $comment_receives,
                ]);
                // LOGS
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->user_id,$name_movement,1,$module_id,$submodule_id,3,'guarantee_id',$request->order_id,$description);
            } else {
                // cambiamos el status del item
                EOrder::where('id',$request->order_id)
                ->update([
                    'status_id'    => $status_id,
                    'finalize_date' => $date_now,
                    'name_receives' => $name_receives,
                    'comment_receives' => $comment_receives,
                ]);
                // LOGS
                $logs = new Logs(); // user_id , name_movement , type_system_id , module_id , submodule_id , type_log_movement_id  ,identifier_number , description
                $logs->createMovementLog($request->user_id,$name_movement,1,$module_id,$submodule_id,3,'order_id',$request->order_id,$description);
            }
            // buscamos los usuarios del cambio
            $users_ids = DErpAccessUser::select('user_id as id')
            ->where('module_id', $module_id)
            ->where('submodule_id', $submodule_id)
            ->get();
            // Guardamos usuarios para el socket
            foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();

            return response()->json([
                'success'       =>  true ,
                'opt'           => $request->opt,
                'nomen'         => $request->nomen,
                'folio'         => $request->folio,
                'order_id'      => $request->order_id,
                'users_socket'  => $users_socket,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 400);
        }
    }

    public function downloadRouteOrderDetail($order_id,$nomen)
    {
        // try {
            if( $nomen == 'GLS' ) {
                $order = $this->getIndividualShipmentGuarantee($order_id);
            } else  {
                $order = $this->getIndividualShipmentOrder($order_id);
            }
            $pdf = new FPDF(new PDF_Code128("L", "mm", "A4"));
            return $pdf->createRouteOrderDetail($order);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);

        // }
    }

    public function downloadRouteOrderDetailTicket($order_id,$nomen)
    {
        // try {
            if( $nomen == 'GLS' ) {
                $order = $this->getIndividualShipmentGuarantee($order_id);
            } else  {
                $order = $this->getIndividualShipmentOrder($order_id);
            }
            $heightPP = 112;
            $heightPP = (INT)$heightPP + ( (INT)COUNT($order['details']) * 8 ) + 30 ;
            $pdf = new FPDF(new PDF_Code128("P", "mm", [(INT)$heightPP,150] ));
            return $pdf->createRouteOrderDetailTicket($order);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 400);

        // }
    }

    public function setStopOrder(Request $request) {
        // try {
            // CAMBIAMOS STATUS ORDENES
            switch ($request->opt) {
                case 1: // STOP
                    EOrder::where('id',$request->order_id)
                    ->update([ 'status_id' => 14,]);
                    $msj = 'Se actualizo status de pedido a Detenido; Comentario: '.$request->description;
                break;
                case 2: // RETRN
                    EOrder::where('id',$request->order_id)
                    ->update([ 'status_id' => 7,]);
                    $msj = 'Se regresó status de pedido a En producción; Comentario: '.$request->description;
                break;
            }
            // Guardamos en LOGS
            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
            $logs->createMovementLog($request->user_id,'Actualizó status de pedido',1,7,17,3,'order_id',$request->order_id,$msj);
            // SOCKET
            // buscamos los usuarios del cambio
            $users_ids = DErpAccessUser::select('user_id as id')
            ->where('module_id', 7)
            ->where('submodule_id', 17)
            ->get();
            $users_socket_ids = [];
            foreach ($users_ids as $value) { $users_socket_ids[] = $value['id']; }
            $users_socket = DSocketConnection::select('socket_id','user_id','user_type')->whereIn('user_id',$users_socket_ids)->where('user_type','ERP')->get();
            $order = $this->getIndividualOrder($request->order_id);
            return response()->json([
                'success'      =>  true ,
                'order_id'     => $request->order_id,
                'opt'          => $request->opt,
                'order'        => $order,
                'users_socket' => $users_socket,
            ], 200);
        // } catch (\Throwable $th) {
        //         return response()->json([
        //             'success' => false ,
        //             'error'   => $th
        //         ], 200);
        // }
    }

    public function generateKeyEditOrder(Request $request) {
        // try {
            $key_edit_order = Str::random(16);
            // cambiamos el key
            EOrder::where('id',$request->order_id)
            ->update([
                'key_edit_order' => $key_edit_order,
            ]);
            // Guardamos en LOGS
            $msj = 'Inicio edición en pedido. '.$request->order_id;
            $logs = new Logs(); // user_id , name_movement , type_system_id , module_id, submodule_id , type_log_movement_id  ,identifier_number , description
            $logs->createMovementLog($request->user_id,'Edición de pedido',1,4,10,3,'order_id',$request->order_id,$msj);

            return response()->json([
                'success'      =>  true ,
                'key_edit_order'     => $key_edit_order,
            ], 200);
        // } catch (\Throwable $th) {
        //         return response()->json([
        //             'success' => false ,
        //             'error'   => $th
        //         ], 200);
        // }
    }

    public function validKeyEditOrder(Request $request) {
        // try {
            // verificamos que el key este ligado al pedido
            $keyValid = EOrder::select('key_edit_order')
            ->where('id',$request->order_id)
            ->where('key_edit_order',$request->key_edit_order)
            ->first();
            if(is_null($keyValid->key_edit_order)) {
                return response()->json([
                    'success'      =>  false ,
                ], 200);
            } else {
                return response()->json([
                    'success'      =>  true ,
                ], 200);
            }
        // } catch (\Throwable $th) {
        //         return response()->json([
        //             'success' => false ,
        //             'error'   => $th
        //         ], 200);
        // }
    }

    public function getRelationOrders()
    {
        try {
            $notRelationOrders = $this->relationOrders(0);
            $orders = $this->relationOrders(1);
            return response()->json([
                'success'           =>  true ,
                'orders'            => $orders,
                'notRelationOrders' => $notRelationOrders,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 200);
        }
    }

    public function saveRelationInvoiceOrder(Request $request) {
        // try {

            // Actualizamos el invoice ID
            EOrder::where('id',$request->order_id)
            ->update([
                'invoice_id' => $request->invoice_id ,
            ]);
            $order = $this->getIndividualOrder($request->order_id);
            return response()->json([
                'success' =>  true ,
                'order_id' => $request->order_id ,
                'invoice_id' => $request->invoice_id ,
                'order' => $order ,
            ], 200);
        // } catch (\Throwable $th) {
            //     return response()->json([
            //         'success' => false ,
            //         'error'   => $th
            //     ], 200);
        // }
    }

    public function updateInvoiceOrder(Request $request, $order_id) {
        // try {
            // Actualizamos el invoice ID
            EOrder::where('id',$order_id)
            ->update([
                'invoice_id' => $request->invoice_id ,
            ]);
            $order = $this->getIndividualOrder($request->order_id);
            return response()->json([
                'success' =>  true ,
                'order_id' => $order_id ,
                'invoice_id' => $request->invoice_id ,
                'order' => $order ,
            ], 200);
        // } catch (\Throwable $th) {
            //     return response()->json([
            //         'success' => false ,
            //         'error'   => $th
            //     ], 200);
        // }
    }

    public function downloadOrderDetail($order_id)
    {
        // try {

            $order = $this->getIndividualOrder($order_id);
            $pdf = new FPDF(new PDF_Code128("L", "mm", "A4"));
            return $pdf->createOrderDetail($order);

        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    // public function viewOrdersTotal()
    // {
    //     // try {

    //         $orders = $this->allOrders();

    //         foreach ($orders as $key => $order) {

    //             $total =  app(GetTotal::class)->getTotalOrder($order['details']);
    //             $orders[$key]['total'] = number_format($total['total'],2);
    //         }

    //         echo '<table border="1">';

    //         foreach ($orders as $key => $order) {

    //             echo
    //             '<tr><td>'.$order['id'].'</td>
    //             <td>'.$order['total'].'</td></tr>';
    //         }
    //         echo '</table>';

    //     // } catch (\Throwable $th) {
    //     //     return response()->json([
    //     //         'success' => false ,
    //     //         'error'   => $th
    //     //     ], 200);
    //     // }
    // }

    public function viewOrdersDetailTotal()
    {
        // try {

            $ordersDetail = $this->allDetailOrders();
            foreach ($ordersDetail as $key => $orderDetail) {
                $total =  app(GetTotal::class)->getIndividualTotalOrder($orderDetail);
                $ordersDetail[$key]['total'] = number_format($total,2);
            }

            echo '<table border="1">';

            foreach ($ordersDetail as $key => $orderDetail) {

                echo
                '<tr><td>'.$orderDetail['id'].'</td>
                <td>'.$orderDetail['total'].'</td></tr>';
            }
            echo '</table>';

        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    public function getViewLocations()
    {
        // try {

            // scans orders
            $DOrder = DOrder::select('d_orders.order_id',DB::raw("'LS' AS nomen"),'d_orders.item_id','c_articles.article','d_production_locations.location')
                ->join('d_production_locations', function($join) {
                $join->on('d_production_locations.id', '=', 'd_orders.production_location_id');
                $join->on('d_production_locations.is_occupied', '=', DB::raw(1));
            })
            ->join('c_articles','c_articles.id','d_orders.article_id')
            ->get();
            // scans guarantee
            $DGuaranty = DGuaranty::select('d_guarantee.guarantee_id AS order_id','e_guarantee.folio','e_guarantee.nomen','d_guarantee.item_id','c_articles.article','d_production_locations.location')
            ->join('e_guarantee','e_guarantee.id','d_guarantee.guarantee_id')
            ->join('d_production_locations', function($join) {
                $join->on('d_production_locations.id', '=', 'd_guarantee.production_location_id');
                $join->on('d_production_locations.is_occupied', '=', DB::raw(1));
            })
            ->join('c_articles','c_articles.id','d_guarantee.article_id')
            ->get();
            $locationsScan = [];
            foreach ($DOrder as $item) { $locationsScan[] =  $item; }
            foreach ($DGuaranty as $item) { $locationsScan[] =  $item; }
            return response()->json([
                'success' =>  true ,
                'locationsScan' => $locationsScan ,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    public function searchLocationOrders($nomen, $order_id)
    {
        // try {

            $locationsOrder = [];
            switch ($nomen) {
                case 'LS':
                    $orderSearch = EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.status_id','c_status_orders.status','e_orders.packing_date')
                    ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
                    ->where('e_orders.id',$order_id)
                    ->first();
                    // scans orders
                    $locationsOrder = DOrder::select('d_orders.item_id','c_articles.article','c_status_productions.status','d_production_locations.location')
                    ->leftJoin('d_production_locations','d_production_locations.id', 'd_orders.production_location_id')
                    ->join('c_articles','c_articles.id','d_orders.article_id')
                    ->join('c_status_productions','c_status_productions.id','d_orders.status_production_id')
                    ->where('d_orders.order_id',$order_id)
                    ->whereIn('d_orders.product_id',[1,2])
                    ->get();
                break;
                case 'GLS':
                    $orderSearch = EGuaranty::select('e_guarantee.id','e_guarantee.nomen','e_guarantee.folio','e_guarantee.status_id','c_status_guarantee.status','e_guarantee.packing_date')
                    ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
                    ->where('e_guarantee.folio',$order_id)
                    ->where('e_guarantee.nomen',$nomen)
                    ->first();
                    if($orderSearch) {
                        // scans guarantee
                        $locationsOrder = DGuaranty::select('d_guarantee.item_id','c_articles.article','c_status_productions.status','d_production_locations.location')
                        ->leftJoin('d_production_locations','d_production_locations.id', 'd_guarantee.production_location_id')
                        ->join('e_guarantee','e_guarantee.id','d_guarantee.guarantee_id')
                        ->join('c_articles','c_articles.id','d_guarantee.article_id')
                        ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
                        ->where('d_guarantee.guarantee_id',$orderSearch->id)
                        ->whereIn('d_guarantee.product_id',[1,2])
                        ->get();
                    } else { $locationsOrder = []; }
                break;
                case 'SLS':
                    $orderSearch = EGuaranty::select('e_guarantee.id','e_guarantee.nomen','e_guarantee.folio','e_guarantee.status_id','c_status_guarantee.status','e_guarantee.packing_date')
                    ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
                    ->where('e_guarantee.folio',$order_id)
                    ->where('e_guarantee.nomen',$nomen)
                    ->first();
                    if($orderSearch) {
                        // scans guarantee
                        $locationsOrder = DGuaranty::select('d_guarantee.item_id','c_articles.article','c_status_productions.status','d_production_locations.location')
                        ->leftJoin('d_production_locations','d_production_locations.id', 'd_guarantee.production_location_id')
                        ->join('e_guarantee','e_guarantee.id','d_guarantee.guarantee_id')
                        ->join('c_articles','c_articles.id','d_guarantee.article_id')
                        ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
                        ->where('d_guarantee.guarantee_id',$orderSearch->id)
                        ->whereIn('d_guarantee.product_id',[1,2])
                        ->get();
                    } else { $locationsOrder = []; }
                break;
            }
            return response()->json([
                'success'           =>  true ,
                'orderSearch'       => $orderSearch ,
                'locationsOrder'    => $locationsOrder ,
            ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' => false ,
        //         'error'   => $th
        //     ], 200);
        // }
    }

    public function getGuarantee() {

        $EGuaranty = EGuaranty::select('e_guarantee.id','e_guarantee.folio','e_guarantee.nomen',DB::raw("CASE WHEN e_guarantee.capture_id = 1 THEN 'Persiana Nueva' ELSE 'Captura componentes' END AS type_capture"),'e_guarantee.user_id','c_erp_info_users.short_name as agent_name','e_guarantee.order_id','e_orders.client_id',DB::raw('CASE WHEN c_users.short_name IS NOT NULL THEN c_users.short_name ELSE c_users.full_name END AS client_name'),'e_guarantee.warranty_type_id','c_type_guarantee.type_warranty','e_guarantee.guarantee_error_id','c_guarantee_errors.guarantee_error','e_guarantee.guarantee_type_error_id','c_guarantee_type_errors.guarantee_type_error','e_guarantee.description','e_guarantee.status_id','c_status_guarantee.status','c_status_guarantee.color_status','e_guarantee.delivery_type_id','c_delivery_types.delivery',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_guarantee.if_installer_required','e_guarantee.capture_id',DB::raw('CASE e_guarantee.capture_id WHEN 1 THEN "Persiana Nueva" WHEN 2 THEN "Captura componentes" END AS capture '),'e_guarantee.created_at')
        ->join('e_orders','e_orders.id','e_guarantee.order_id')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_guarantee.user_id')
        ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
        ->join('c_type_guarantee','c_type_guarantee.id','e_guarantee.warranty_type_id')
        ->join('c_guarantee_errors','c_guarantee_errors.id','e_guarantee.guarantee_error_id')
        ->join('c_guarantee_type_errors','c_guarantee_type_errors.id','e_guarantee.guarantee_type_error_id')
        ->leftJoin('c_delivery_types','c_delivery_types.id','e_guarantee.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_guarantee.client_address_id')
        ->where('e_guarantee.status_id',1)
        ->get();
        $DGuaranty = DGuaranty::select('d_guarantee.id','d_guarantee.guarantee_id','d_guarantee.item_id','d_guarantee.article_id','d_orders.article_id as ch_article_id',DB::raw('CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN la.lambrequin_price ELSE d_guarantee.price END AS price'),'c_articles.model_id','la.model_id as la_model_id','cb.model_id as cb_model_id','fj.model_id as fj_model_id','d_guarantee.discount1','d_guarantee.discount2','d_guarantee.discount3','d_guarantee.quantity','d_guarantee.width','d_orders.width as ch_width','d_guarantee.height','d_orders.height as ch_height','d_guarantee.product_id','c_products.product','d_guarantee.operation_id','c_operations.operation','d_guarantee.fall','d_guarantee.counterweight_bar_id','d_orders.counterweight_bar_id as ch_counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_guarantee.chain_id','d_orders.chain_id as ch_chain_id','c_chains.chain','d_guarantee.height_chain','d_orders.height_chain as ch_height_chain','d_guarantee.side_id','d_orders.side_id as ch_side_id','d_guarantee.mechanism_side_id','d_orders.mechanism_side_id as ch_mechanism_side_id','c_mechanism_sides.mechanism_side','d_guarantee.unit_id','c_units.unit','d_guarantee.component_color_id','d_orders.component_color_id as ch_component_color_id','c_colors.color as color_component','d_guarantee.commit','d_guarantee.commit_client','d_guarantee.awning_type_id','d_guarantee.area_description','d_guarantee.relation_id','d_guarantee.relation_bracket','d_guarantee.is_inverted','d_guarantee.is_heat_seal','d_guarantee.relation_cassette','d_guarantee.relation_lambrequin','d_guarantee.cassette_id','d_guarantee.relation_motor','d_guarantee.motor_id','d_orders.motor_id as ch_motor_id','d_guarantee.relation_accesories','d_guarantee.relation_heat_seal','d_guarantee.relation_bracket_dn','d_guarantee.relation_control','d_guarantee.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_guarantee.is_tie_stripe','d_guarantee.tube_id','d_orders.tube_id as ch_tube_id','c_tubes.tube','d_guarantee.divisions','d_guarantee.mechanism_id','d_orders.mechanism_id as ch_mechanism_id','c_mechanisms.mechanism','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_guarantee.production_location_id','d_guarantee.lambrequin_id','d_orders.lambrequin_id as ch_lambrequin_id','d_guarantee.fijo_id','d_orders.fijo_id as ch_fijo_id','d_guarantee.corbatin_id','d_orders.corbatin_id as ch_corbatin_id','d_guarantee.is_velcro','d_guarantee.item_detail','d_guarantee.damage_fabric','d_guarantee.damage_tube','d_guarantee.damage_mechanism','d_guarantee.damage_counterweight','d_guarantee.damage_chain','d_guarantee.damage_fascia','d_guarantee.damage_motor','d_guarantee.damage_fabric','d_guarantee.damage_tube','d_guarantee.damage_mechanism','d_guarantee.damage_counterweight','d_guarantee.damage_chain','d_guarantee.damage_fascia','d_guarantee.damage_motor','d_guarantee.item_detail')
        ->join('c_articles','c_articles.id','d_guarantee.article_id')
        ->join('c_products','c_products.id','d_guarantee.product_id')
        ->leftJoin('c_operations','c_operations.id','d_guarantee.operation_id')
        ->join('c_units','c_units.id','d_guarantee.unit_id')
        ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_guarantee.mechanism_side_id')
        ->leftJoin('c_mechanisms','c_mechanisms.id','d_guarantee.mechanism_id')
        ->leftJoin('c_chains','c_chains.id','d_guarantee.chain_id')
        ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_guarantee.counterweight_bar_id')
        ->leftJoin('c_colors','c_colors.id','d_guarantee.component_color_id')
        ->leftJoin('c_config_motors', function($join) {
            $join->on('c_config_motors.article_id', '=', 'd_guarantee.motor_id');
            $join->on('c_config_motors.num_divisions','=','d_guarantee.divisions');
        })
        ->leftJoin('c_articles as c_article_motor','c_article_motor.id','d_guarantee.motor_id')
        ->leftJoin('c_tubes','c_tubes.id','d_guarantee.tube_id')
        ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
        ->leftJoin('c_articles AS la','la.id','d_guarantee.lambrequin_id')
        ->leftJoin('c_articles AS cb','cb.id','d_guarantee.corbatin_id')
        ->leftJoin('c_articles AS fj','fj.id','d_guarantee.fijo_id')
        ->leftJoin('d_orders','d_orders.id','d_guarantee.detail_order_id')
        ->join('e_guarantee', function($join) {
            $join->on('e_guarantee.id', '=', 'd_guarantee.guarantee_id')
            ->where('e_guarantee.status_id',DB::raw(1));
        })
        ->get();
        $guarantee = $this->setGuarantee($EGuaranty->toArray(),$DGuaranty->toArray());
        return $guarantee;
    }

    public function allStatusGuarantee($statusID) {

        $EGuaranty = EGuaranty::select('e_guarantee.id','e_guarantee.folio','e_guarantee.nomen',DB::raw("CASE WHEN e_guarantee.capture_id = 1 THEN 'Persiana Nueva' ELSE 'Captura componentes' END AS type_capture"),'e_guarantee.user_id','c_erp_info_users.short_name as agent_name','e_guarantee.order_id','e_orders.client_id',DB::raw('CASE WHEN c_users.short_name IS NOT NULL THEN c_users.short_name ELSE c_users.full_name END AS client_name'),'e_guarantee.warranty_type_id','c_type_guarantee.type_warranty','e_guarantee.guarantee_error_id','c_guarantee_errors.guarantee_error','e_guarantee.guarantee_type_error_id','c_guarantee_type_errors.guarantee_type_error','e_guarantee.description','e_guarantee.status_id','c_status_guarantee.status','c_status_guarantee.color_status','e_guarantee.material_request_date','e_guarantee.production_date','e_guarantee.deadline_date','e_guarantee.delivery_type_id','c_delivery_types.delivery',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_guarantee.if_installer_required','e_guarantee.created_at')
        ->join('e_orders','e_orders.id','e_guarantee.order_id')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_guarantee.user_id')
        ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
        ->join('c_type_guarantee','c_type_guarantee.id','e_guarantee.warranty_type_id')
        ->join('c_guarantee_errors','c_guarantee_errors.id','e_guarantee.guarantee_error_id')
        ->join('c_guarantee_type_errors','c_guarantee_type_errors.id','e_guarantee.guarantee_type_error_id')
        ->leftJoin('c_delivery_types','c_delivery_types.id','e_guarantee.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_guarantee.client_address_id')
        ->where('e_guarantee.status_id',$statusID)
        ->get();
        $DGuaranty = DGuaranty::select('d_guarantee.id','d_guarantee.guarantee_id','d_guarantee.item_id','d_guarantee.article_id',DB::raw('CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN la.lambrequin_price ELSE d_guarantee.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_guarantee.discount1','d_guarantee.discount2','d_guarantee.discount3','d_guarantee.quantity','d_guarantee.width','d_guarantee.height','d_guarantee.product_id','c_products.product','d_guarantee.operation_id','c_operations.operation','d_guarantee.fall','d_guarantee.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_guarantee.chain_id','c_chains.chain','d_guarantee.height_chain','d_guarantee.side_id','d_guarantee.mechanism_side_id','c_mechanism_sides.mechanism_side','d_guarantee.unit_id','c_units.unit','d_guarantee.component_color_id','c_colors.color as color_component','d_guarantee.commit','d_guarantee.commit_client','d_guarantee.awning_type_id','d_guarantee.area_description','d_guarantee.relation_id','d_guarantee.relation_bracket','d_guarantee.is_inverted','d_guarantee.is_heat_seal','d_guarantee.relation_cassette','d_guarantee.relation_lambrequin','d_guarantee.cassette_id','d_guarantee.relation_motor','d_guarantee.motor_id','d_guarantee.relation_accesories','d_guarantee.relation_heat_seal','d_guarantee.relation_bracket_dn','d_guarantee.relation_control','d_guarantee.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_guarantee.is_tie_stripe','d_guarantee.tube_id','c_tubes.tube','d_guarantee.divisions','d_guarantee.mechanism_id','c_mechanisms.mechanism','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_guarantee.production_location_id','d_guarantee.lambrequin_id','d_guarantee.fijo_id','d_guarantee.corbatin_id','d_guarantee.is_velcro','d_guarantee.damage_fabric','d_guarantee.damage_tube','d_guarantee.damage_mechanism','d_guarantee.damage_counterweight','d_guarantee.damage_chain','d_guarantee.damage_fascia','d_guarantee.damage_motor','d_guarantee.item_detail')
        ->join('c_articles','c_articles.id','d_guarantee.article_id')
        ->join('c_products','c_products.id','d_guarantee.product_id')
        ->leftJoin('c_operations','c_operations.id','d_guarantee.operation_id')
        ->join('c_units','c_units.id','d_guarantee.unit_id')
        ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_guarantee.mechanism_side_id')
        ->leftJoin('c_mechanisms','c_mechanisms.id','d_guarantee.mechanism_id')
        ->leftJoin('c_chains','c_chains.id','d_guarantee.chain_id')
        ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_guarantee.counterweight_bar_id')
        ->leftJoin('c_colors','c_colors.id','d_guarantee.component_color_id')
        ->leftJoin('c_config_motors', function($join) {
            $join->on('c_config_motors.article_id', '=', 'd_guarantee.motor_id');
            $join->on('c_config_motors.num_divisions','=','d_guarantee.divisions');
        })
        ->leftJoin('c_articles as c_article_motor','c_article_motor.id','d_guarantee.motor_id')
        ->leftJoin('c_tubes','c_tubes.id','d_guarantee.tube_id')
        ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
        ->leftJoin('c_articles AS la','la.id','d_guarantee.lambrequin_id')
        ->leftJoin('c_articles AS cb','cb.id','d_guarantee.corbatin_id')
        ->leftJoin('c_articles AS fj','fj.id','d_guarantee.fijo_id')
        ->join('e_guarantee', function($join) use ($statusID){
            $join->on('e_guarantee.id', '=', 'd_guarantee.guarantee_id')
            ->where('e_guarantee.status_id',DB::raw($statusID));
        })
        ->get();
        $guarantee = $this->setGuarantee($EGuaranty->toArray(),$DGuaranty->toArray());
        return $guarantee;
    }

    public function allStatusPLGuarantee($statusID) {

        $EGuaranty = EGuaranty::select('e_guarantee.id','e_guarantee.folio','e_guarantee.nomen',DB::raw("CASE WHEN e_guarantee.capture_id = 1 THEN 'Persiana Nueva' ELSE 'Captura componentes' END AS type_capture"),'e_guarantee.user_id','c_erp_info_users.short_name as agent_name','e_guarantee.order_id','e_orders.client_id',DB::raw('CASE WHEN c_users.short_name IS NOT NULL THEN c_users.short_name ELSE c_users.full_name END AS client_name'),'e_guarantee.warranty_type_id','c_type_guarantee.type_warranty','e_guarantee.guarantee_error_id','c_guarantee_errors.guarantee_error','e_guarantee.guarantee_type_error_id','c_guarantee_type_errors.guarantee_type_error','e_guarantee.description','e_guarantee.status_id','c_status_guarantee.status','c_status_guarantee.color_status','e_guarantee.material_request_date','e_guarantee.production_date','e_guarantee.deadline_date','e_guarantee.delivery_type_id','c_delivery_types.delivery',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_guarantee.if_installer_required','e_guarantee.created_at')
        ->join('e_orders','e_orders.id','e_guarantee.order_id')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_guarantee.user_id')
        ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
        ->join('c_type_guarantee','c_type_guarantee.id','e_guarantee.warranty_type_id')
        ->join('c_guarantee_errors','c_guarantee_errors.id','e_guarantee.guarantee_error_id')
        ->join('c_guarantee_type_errors','c_guarantee_type_errors.id','e_guarantee.guarantee_type_error_id')
        ->leftJoin('c_delivery_types','c_delivery_types.id','e_guarantee.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_guarantee.client_address_id')
        ->where('e_guarantee.status_id',$statusID)
        ->get();
        $DGuaranty = DGuaranty::select('d_guarantee.id','d_guarantee.guarantee_id','d_guarantee.item_id','d_guarantee.article_id',DB::raw('CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN la.lambrequin_price ELSE d_guarantee.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_guarantee.discount1','d_guarantee.discount2','d_guarantee.discount3','d_guarantee.quantity','d_guarantee.width','d_guarantee.height','d_guarantee.product_id','c_products.product','d_guarantee.operation_id','c_operations.operation','d_guarantee.fall','d_guarantee.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_guarantee.chain_id','c_chains.chain','d_guarantee.height_chain','d_guarantee.side_id','d_guarantee.mechanism_side_id','c_mechanism_sides.mechanism_side','d_guarantee.unit_id','c_units.unit','d_guarantee.component_color_id','c_colors.color as color_component','d_guarantee.commit','d_guarantee.commit_client','d_guarantee.awning_type_id','d_guarantee.area_description','d_guarantee.relation_id','d_guarantee.relation_bracket','d_guarantee.is_inverted','d_guarantee.is_heat_seal','d_guarantee.relation_cassette','d_guarantee.relation_lambrequin','d_guarantee.cassette_id','d_guarantee.relation_motor','d_guarantee.motor_id','d_guarantee.relation_accesories','d_guarantee.relation_heat_seal','d_guarantee.relation_bracket_dn','d_guarantee.relation_control','d_guarantee.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_guarantee.is_tie_stripe','d_guarantee.tube_id','c_tubes.tube','d_guarantee.divisions','d_guarantee.mechanism_id','c_mechanisms.mechanism','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_guarantee.production_location_id','d_guarantee.lambrequin_id','d_guarantee.fijo_id','d_guarantee.corbatin_id','d_guarantee.is_velcro','d_guarantee.item_detail','d_guarantee.damage_fabric','d_guarantee.damage_tube','d_guarantee.damage_mechanism','d_guarantee.damage_counterweight','d_guarantee.damage_chain','d_guarantee.damage_fascia','d_guarantee.damage_motor','d_guarantee.item_detail')
        ->join('c_articles','c_articles.id','d_guarantee.article_id')
        ->join('c_products','c_products.id','d_guarantee.product_id')
        ->leftJoin('c_operations','c_operations.id','d_guarantee.operation_id')
        ->join('c_units','c_units.id','d_guarantee.unit_id')
        ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_guarantee.mechanism_side_id')
        ->leftJoin('c_mechanisms','c_mechanisms.id','d_guarantee.mechanism_id')
        ->leftJoin('c_chains','c_chains.id','d_guarantee.chain_id')
        ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_guarantee.counterweight_bar_id')
        ->leftJoin('c_colors','c_colors.id','d_guarantee.component_color_id')
        ->leftJoin('c_config_motors', function($join) {
            $join->on('c_config_motors.article_id', '=', 'd_guarantee.motor_id');
            $join->on('c_config_motors.num_divisions','=','d_guarantee.divisions');
        })
        ->leftJoin('c_articles as c_article_motor','c_article_motor.id','d_guarantee.motor_id')
        ->leftJoin('c_tubes','c_tubes.id','d_guarantee.tube_id')
        ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
        ->leftJoin('c_articles AS la','la.id','d_guarantee.lambrequin_id')
        ->leftJoin('c_articles AS cb','cb.id','d_guarantee.corbatin_id')
        ->leftJoin('c_articles AS fj','fj.id','d_guarantee.fijo_id')
        ->join('e_guarantee', function($join) use ($statusID){
            $join->on('e_guarantee.id', '=', 'd_guarantee.guarantee_id')
            ->where('e_guarantee.status_id',DB::raw($statusID));
        })
        ->whereIn('d_guarantee.product_id',[1,2])
        ->get();
        $guarantee = $this->setGuarantee($EGuaranty->toArray(),$DGuaranty->toArray());
        return $guarantee;
    }


    public function getShipmentGuarantee($statusID,$delivery_type) {

        $EGuaranty = EGuaranty::select('e_guarantee.id','e_guarantee.folio','e_guarantee.nomen',DB::raw("CASE WHEN e_guarantee.capture_id = 1 THEN 'Persiana Nueva' ELSE 'Captura componentes' END AS type_capture"),'e_guarantee.user_id','c_erp_info_users.short_name as agent_name','e_guarantee.order_id','e_orders.client_id',DB::raw('CASE WHEN c_users.short_name IS NOT NULL THEN c_users.short_name ELSE c_users.full_name END AS client_name'),'e_guarantee.warranty_type_id','c_type_guarantee.type_warranty','e_guarantee.guarantee_error_id','c_guarantee_errors.guarantee_error','e_guarantee.guarantee_type_error_id','c_guarantee_type_errors.guarantee_type_error','e_guarantee.description','e_guarantee.status_id','c_status_guarantee.status','c_status_guarantee.color_status','e_guarantee.material_request_date','e_guarantee.production_date','e_guarantee.deadline_date','e_guarantee.packing_date','e_guarantee.delivery_type_id','c_delivery_types.delivery',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_guarantee.if_installer_required','e_guarantee.created_at')
        ->join('e_orders','e_orders.id','e_guarantee.order_id')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_guarantee.user_id')
        ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
        ->join('c_type_guarantee','c_type_guarantee.id','e_guarantee.warranty_type_id')
        ->join('c_guarantee_errors','c_guarantee_errors.id','e_guarantee.guarantee_error_id')
        ->join('c_guarantee_type_errors','c_guarantee_type_errors.id','e_guarantee.guarantee_type_error_id')
        ->leftJoin('c_delivery_types','c_delivery_types.id','e_guarantee.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_guarantee.client_address_id')
        ->whereIn('e_guarantee.status_id',$statusID)
        ->where('e_guarantee.delivery_type_id',DB::raw($delivery_type))
        ->get();
        $DGuaranty = DGuaranty::select('d_guarantee.id','d_guarantee.guarantee_id','d_guarantee.item_id','d_guarantee.article_id',DB::raw('CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN la.lambrequin_price ELSE d_guarantee.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_guarantee.discount1','d_guarantee.discount2','d_guarantee.discount3','d_guarantee.quantity','d_guarantee.width','d_guarantee.height','d_guarantee.product_id','c_products.product','d_guarantee.operation_id','c_operations.operation','d_guarantee.fall','d_guarantee.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_guarantee.chain_id','c_chains.chain','d_guarantee.height_chain','d_guarantee.side_id','d_guarantee.mechanism_side_id','c_mechanism_sides.mechanism_side','d_guarantee.unit_id','c_units.unit','d_guarantee.component_color_id','c_colors.color as color_component','d_guarantee.commit','d_guarantee.commit_client','d_guarantee.awning_type_id','d_guarantee.area_description','d_guarantee.relation_id','d_guarantee.relation_bracket','d_guarantee.is_inverted','d_guarantee.is_heat_seal','d_guarantee.relation_cassette','d_guarantee.relation_lambrequin','d_guarantee.cassette_id','d_guarantee.relation_motor','d_guarantee.motor_id','d_guarantee.relation_accesories','d_guarantee.relation_heat_seal','d_guarantee.relation_bracket_dn','d_guarantee.relation_control','d_guarantee.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_guarantee.is_tie_stripe','d_guarantee.tube_id','c_tubes.tube','d_guarantee.divisions','d_guarantee.mechanism_id','c_mechanisms.mechanism','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_guarantee.production_location_id','d_guarantee.lambrequin_id','d_guarantee.fijo_id','d_guarantee.corbatin_id','d_guarantee.is_velcro','d_guarantee.item_detail','d_guarantee.damage_fabric','d_guarantee.damage_tube','d_guarantee.damage_mechanism','d_guarantee.damage_counterweight','d_guarantee.damage_chain','d_guarantee.damage_fascia','d_guarantee.damage_motor','d_guarantee.item_detail')
        ->join('c_articles','c_articles.id','d_guarantee.article_id')
        ->join('c_products','c_products.id','d_guarantee.product_id')
        ->leftJoin('c_operations','c_operations.id','d_guarantee.operation_id')
        ->join('c_units','c_units.id','d_guarantee.unit_id')
        ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_guarantee.mechanism_side_id')
        ->leftJoin('c_mechanisms','c_mechanisms.id','d_guarantee.mechanism_id')
        ->leftJoin('c_chains','c_chains.id','d_guarantee.chain_id')
        ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_guarantee.counterweight_bar_id')
        ->leftJoin('c_colors','c_colors.id','d_guarantee.component_color_id')
        ->leftJoin('c_config_motors', function($join) {
            $join->on('c_config_motors.article_id', '=', 'd_guarantee.motor_id');
            $join->on('c_config_motors.num_divisions','=','d_guarantee.divisions');
        })
        ->leftJoin('c_articles as c_article_motor','c_article_motor.id','d_guarantee.motor_id')
        ->leftJoin('c_tubes','c_tubes.id','d_guarantee.tube_id')
        ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
        ->leftJoin('c_articles AS la','la.id','d_guarantee.lambrequin_id')
        ->leftJoin('c_articles AS cb','cb.id','d_guarantee.corbatin_id')
        ->leftJoin('c_articles AS fj','fj.id','d_guarantee.fijo_id')
        ->join('e_guarantee', function($join) use ($statusID,$delivery_type){
            $join->on('e_guarantee.id', '=', 'd_guarantee.guarantee_id')
            ->whereIn('e_guarantee.status_id',$statusID)
            ->where('e_guarantee.delivery_type_id',DB::raw($delivery_type));
        })
        ->get();
        $guarantee = $this->setGuarantee($EGuaranty->toArray(),$DGuaranty->toArray());
        return $guarantee;
    }

    public function getIndividualGuarantee($guarenteeID) {
        $EGuaranty = EGuaranty::select('e_guarantee.id','e_guarantee.folio','e_guarantee.nomen',DB::raw("CASE WHEN e_guarantee.capture_id = 1 THEN 'Persiana Nueva' ELSE 'Captura componentes' END AS type_capture"),'e_guarantee.user_id','c_erp_info_users.short_name as agent_name','e_guarantee.order_id','e_orders.client_id',DB::raw('null AS proyect_name,null AS payment_method,null AS payment_option, null AS account_number,CASE WHEN c_users.short_name IS NOT NULL THEN c_users.short_name ELSE c_users.full_name END AS client_name'),'e_guarantee.warranty_type_id','c_type_guarantee.type_warranty','e_guarantee.guarantee_error_id','c_guarantee_errors.guarantee_error','e_guarantee.guarantee_type_error_id','c_guarantee_type_errors.guarantee_type_error','e_guarantee.description','e_guarantee.status_id','c_status_guarantee.status','c_status_guarantee.color_status','e_guarantee.delivery_type_id','c_delivery_types.delivery',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_guarantee.if_installer_required','e_guarantee.created_at')
        ->join('e_orders','e_orders.id','e_guarantee.order_id')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_guarantee.user_id')
        ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
        ->join('c_type_guarantee','c_type_guarantee.id','e_guarantee.warranty_type_id')
        ->join('c_guarantee_errors','c_guarantee_errors.id','e_guarantee.guarantee_error_id')
        ->join('c_guarantee_type_errors','c_guarantee_type_errors.id','e_guarantee.guarantee_type_error_id')
        ->leftJoin('c_delivery_types','c_delivery_types.id','e_guarantee.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_guarantee.client_address_id')
        ->where('e_guarantee.id',$guarenteeID)
        ->first();
        $DGuaranty = DGuaranty::select('d_guarantee.id',DB::raw('d_guarantee.guarantee_id AS order_id'),'d_guarantee.guarantee_id','d_guarantee.item_id','d_guarantee.article_id',DB::raw('CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN la.lambrequin_price ELSE d_guarantee.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_guarantee.discount1','d_guarantee.discount2','d_guarantee.discount3','d_guarantee.quantity','d_guarantee.width','d_guarantee.height','d_guarantee.product_id','c_products.product','d_guarantee.operation_id','c_operations.operation','d_guarantee.fall','d_guarantee.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_guarantee.chain_id','c_chains.chain','d_guarantee.height_chain','d_guarantee.side_id','d_guarantee.mechanism_side_id','c_mechanism_sides.mechanism_side','d_guarantee.unit_id','c_units.unit','d_guarantee.component_color_id','c_colors.color as color_component','d_guarantee.commit','d_guarantee.commit_client','d_guarantee.awning_type_id','d_guarantee.area_description','d_guarantee.relation_id','d_guarantee.relation_bracket','d_guarantee.is_inverted','d_guarantee.is_heat_seal','d_guarantee.relation_cassette','d_guarantee.relation_lambrequin','d_guarantee.cassette_id','d_guarantee.relation_motor','d_guarantee.motor_id','d_guarantee.relation_accesories','d_guarantee.relation_heat_seal','d_guarantee.relation_bracket_dn','d_guarantee.relation_control','d_guarantee.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_guarantee.is_tie_stripe','d_guarantee.tube_id','c_tubes.tube','d_guarantee.divisions','d_guarantee.mechanism_id','c_mechanisms.mechanism','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_guarantee.production_location_id','d_production_locations.location','d_guarantee.lambrequin_id','d_guarantee.fijo_id','d_guarantee.corbatin_id','d_guarantee.is_velcro','d_guarantee.item_detail','d_guarantee.damage_fabric','d_guarantee.damage_tube','d_guarantee.damage_mechanism','d_guarantee.damage_counterweight','d_guarantee.damage_chain','d_guarantee.damage_fascia','d_guarantee.damage_motor','d_guarantee.item_detail')
        ->join('c_articles','c_articles.id','d_guarantee.article_id')
        ->join('c_products','c_products.id','d_guarantee.product_id')
        ->leftJoin('c_operations','c_operations.id','d_guarantee.operation_id')
        ->join('c_units','c_units.id','d_guarantee.unit_id')
        ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_guarantee.mechanism_side_id')
        ->leftJoin('c_mechanisms','c_mechanisms.id','d_guarantee.mechanism_id')
        ->leftJoin('c_chains','c_chains.id','d_guarantee.chain_id')
        ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_guarantee.counterweight_bar_id')
        ->leftJoin('c_colors','c_colors.id','d_guarantee.component_color_id')
        ->leftJoin('c_config_motors', function($join) {
            $join->on('c_config_motors.article_id', '=', 'd_guarantee.motor_id');
            $join->on('c_config_motors.num_divisions','=','d_guarantee.divisions');
        })
        ->leftJoin('c_articles as c_article_motor','c_article_motor.id','d_guarantee.motor_id')
        ->leftJoin('c_tubes','c_tubes.id','d_guarantee.tube_id')
        ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
        ->leftJoin('c_articles AS la','la.id','d_guarantee.lambrequin_id')
        ->leftJoin('c_articles AS cb','cb.id','d_guarantee.corbatin_id')
        ->leftJoin('c_articles AS fj','fj.id','d_guarantee.fijo_id')
        ->leftJoin('d_production_locations','d_production_locations.id','d_guarantee.production_location_id')
        ->where('d_guarantee.guarantee_id',$guarenteeID)
        ->get();
        $guarantee = $this->setIndividualGuarantee($EGuaranty->toArray(),$DGuaranty->toArray());
        return $guarantee;
    }

     // PRIVATE

    private function discountItems($item, $cuts, $orderDetails)  {
        $modulations = [
            'height_add'             => (INT)$item['is_heat_seal'] === 0 ? $this->heightAdd($item['product_id'],$item['operation_id'],$item['motor_id'],$item['tube_id'],$item['relation_cassette']) : 0,
            'width_discount'         => null,
            'counterweight_discount' => null,
            'tube_discount'          => null,
            'turn_bar_discount'      => null,
            'fascia_discount'        => null,
        ];
        if( (INT)$item['product_id'] === 1 OR (INT)$item['product_id'] === 2 ) {
            $is_bracket = 0;
            $is_cassette = 0;
            $tube_id = $item['tube_id'];
            if($item['relation_bracket'] > 0) { $is_bracket = 1; }
            if($item['relation_cassette'] > 0) { $is_cassette = 1; }
            // ESTE CAMBIO ES PARA LA SHEER CON MECANISMO XROLL SHEER CON TAPAS Y TUBO DE 38MM
            if( (INT)$item['mechanism_id'] === 6 AND (INT)$item['product_id'] === 2 AND (INT)$tube_id === 2 ) { $tube_id = 1; }
            $enter = 1;
            foreach ($cuts as $cut) {
                // dd('if('.$cut['product_id'].' == '.$item['product_id'].' AND '.$cut['operation_id'].' == '.$item['operation_id'].' AND '.$cut['divisions'].' == '.$item['divisions'].' AND '.$cut['side_id'].' == '.$item['side_id'].' AND '.( $cut['mechanism_side_id'].' == '.$item['mechanism_side_id'].' OR '.is_null($cut['mechanism_side_id']) ).' AND '.$cut['is_cassette'].' == '.$is_cassette.' AND '.$cut['is_bracket'].' == '.$is_bracket.' AND '.is_null($cut['mm_motor']).' AND '.is_null($cut['motor_model_id']).' AND '.$cut['tube_id'].' == '.$item['tube_id'].' )' );
                if($cut['product_id'] == $item['product_id'] AND $cut['operation_id'] == $item['operation_id'] AND $cut['divisions'] == $item['divisions'] AND $cut['side_id'] == $item['side_id'] AND ( $cut['mechanism_side_id'] == $item['mechanism_side_id'] OR is_null($cut['mechanism_side_id']) ) AND $cut['is_cassette'] == $is_cassette AND $cut['is_bracket'] == $is_bracket AND is_null($cut['mm_motor']) AND ( $cut['motor_model_id'] == $item['model_motor_id'] OR is_null($cut['motor_model_id'])) AND $cut['tube_id'] == $tube_id ) {
                    // echo  $enter,' cut_id ', $cut['id'],'<br>';
                    $modulations['width_discount'] = $cut['width_discount'];
                    $modulations['tube_discount'] = $cut['tube_discount'];
                    $modulations['turn_bar_discount'] = $cut['turn_bar_discount'];
                    $modulations['fascia_discount'] = $cut['fascia_discount'];
                    if($item['is_counterweight_covered'] == 1) {
                        $modulations['counterweight_discount'] = $cut['covered_counterweight_discount'];
                    }  else {
                        $modulations['counterweight_discount'] = $cut['counterweight_discount'];
                    }
                    $enter++;
                }
            }
        }
        if((INT)$item['product_id'] === 5 ) {
            $itemHeatSeal = [];
            foreach ($orderDetails as $key => $od) { if( (INT)$od['relation_heat_seal'] === (INT)$item['relation_heat_seal'] AND (INT)$od['product_id'] === 1 AND (INT)$od['is_heat_seal'] === 1 ) { $itemHeatSeal = $od; } }
            $modulations['height_add'] = $this->heightAdd($itemHeatSeal['product_id'],$itemHeatSeal['operation_id'],$itemHeatSeal['motor_id'],$itemHeatSeal['tube_id'],$itemHeatSeal['relation_cassette']);
            //

            $is_bracket = 0;
            $is_cassette = 0;
            if($itemHeatSeal['relation_bracket'] > 0) { $is_bracket = 1; }
            if($itemHeatSeal['relation_cassette'] > 0) { $is_cassette = 1; }
            $enter = 1;
            foreach ($cuts as $cut) {
                if($cut['product_id'] == $itemHeatSeal['product_id'] AND $cut['operation_id'] == $itemHeatSeal['operation_id'] AND $cut['divisions'] == $itemHeatSeal['divisions'] AND $cut['side_id'] == $itemHeatSeal['side_id'] AND ( $cut['mechanism_side_id'] == $itemHeatSeal['mechanism_side_id'] OR is_null($cut['mechanism_side_id']) ) AND $cut['is_cassette'] == $is_cassette AND $cut['is_bracket'] == $is_bracket AND is_null($cut['mm_motor']) AND ( $cut['motor_model_id'] == $itemHeatSeal['model_motor_id'] OR is_null($cut['motor_model_id'])) AND $cut['tube_id'] == $itemHeatSeal['tube_id'] ) {
                    $modulations['width_discount'] = $cut['width_discount'];
                    $enter++;
                }
            }
        }
        return $modulations;
    }

    private function heightAdd($product_id,$operation_id,$motor_id,$tube_id,$relation_cassette) {
        $add = 0;
        switch ($product_id) {
            case 1: // ENROLLABLE
                switch ($operation_id) {
                    case 1: // MANUAL
                        switch ($tube_id) {
                            case 2: // 38MM
                                $add = 0.3;
                            break;
                            case 3: // 50MM
                                $add = 0.5;
                            break;
                            case 5: // 63MM
                                $add = 0.6;
                            break;
                            case 6: // 70MM
                                $add = 0.7;
                            break;
                        }
                    break;
                    case 2: // MOTORIZDA
                        if((INT)$motor_id === 265 ) {
                            $add = 0.25; } else {
                                if( (INT)$tube_id === 5) {
                                    $add = 0.5;
                                } else if( (INT)$tube_id === 6) {
                                    $add = 0.6;
                                } else {
                                    $add = 0.3;
                                }
                            }
                    break;
                }
                if($relation_cassette > 0) {  $add = $add + 0.10; }
            break;
            case 2: // SHEER
                case 1: // ENROLLABLE
                    switch ($operation_id) {
                        case 1: // MANUAL
                            $add = 0.6;
                        break;
                        case 2: // MOTORIZDA
                            $add = 0.6;
                        break;
                    }
            break;
        }
        return $add;
    }
    private function heightChainAdd($relation_heat_seal,$orderDetail) {
        $add = 0;
        foreach ($orderDetail as $key => $detail) {
            if( (INT)$detail['relation_heat_seal'] === (INT)$relation_heat_seal AND (INT)$detail['product_id'] ===  5) {
                $add = $detail['height'];
            }
        }
        return $add;
    }
    private function foundCostItem($data,$article_id) {
        $cost = 0;
        foreach ($data as $article) { if($article['id'] == $article_id) { $cost = $article['cost']; } }
        return $cost;
    }
    private function foundWidthLot($data,$article_id) {
        $width_lot = 0;
        foreach ($data as $article) { if($article['id'] == $article_id) { $width_lot = $article['width_lot']; } }
        return $width_lot;
    }
    private function foundCostLambrequinItem($data,$article_id) {
        $cost = 0;
        foreach ($data as $article) { if($article['id'] == $article_id) { $cost = 100; } }
        return $cost;
    }
    private function sumWidthPerfil($relation_cassette,$order_id,$details,$nomen) {
        $sumWidth = 0;
        foreach ($details as $item) {
            if( $nomen == "GLS" ) { $orderID = $item['guarantee_id']; } else { $orderID = $item['order_id']; }
            if((INT)$item['relation_cassette'] === $relation_cassette AND (INT)$orderID === $order_id AND ( (INT)$item['product_id'] === 1 OR (INT)$item['product_id'] === 2 )) {
                $sumWidth = $sumWidth + $item['width'];
            }
        }
        return $sumWidth;
    }
    private function foundArticleRelation($articles,$relation_id,$orderDetails,$type) {
        $articleFinal = [];
        switch ($type) {
            case 'LAMBREQUIN':
                $articleFinal = [
                    'counterweight_bar_id' => 'hola',
                    'counterweight_quantity' => 0,
                    'component_color_id' => 0,
                    'product_id' => 0,
                ];
                $totalWidth = 0;
                foreach ($orderDetails as $od) {
                    if( $od['relation_lambrequin'] == $relation_id AND ( (INT)$od['product_id'] === 1 OR (INT)$od['product_id'] === 2 )) {
                        if( (INT)$od['side_id'] === 1 ) {
                            $articleFinal['counterweight_bar_id'] = $od['counterweight_bar_id'];
                            $articleFinal['component_color_id'] = $od['component_color_id'];
                            $articleFinal['product_id'] = $od['product_id'];
                        }
                        $totalWidth = $totalWidth+$od['width'];
                    }
                }
                $articleFinal['counterweight_quantity'] = $totalWidth;
            break;
        }
        return $articleFinal;
    }

    private function allOrders($page,$limit,$search,$isSearch) {
        $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.packing_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        // ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id');
        if( (INT)$isSearch) {
            $EOrder->where( function($query) use ($search) {
                return $query
                ->orWhere('e_orders.id','like','%'.$search.'%')
                ->orWhere('e_orders.user_id','like','%'.$search.'%')
                ->orWhere('e_orders.quotation_id','like','%'.$search.'%')
                ->orWhere('c_erp_info_users.short_name','like','%'.$search.'%')
                ->orWhere('e_orders.client_id','like','%'.$search.'%')
                ->orWhere('c_users.full_name','like','%'.$search.'%')
                ->orWhere('e_orders.proyect_name','like','%'.$search.'%');
            });
        }
        $EOrder = $EOrder->orderBy('e_orders.id','DESC')
        ->offset(($page - 1) * $limit)
        ->take($limit)
        ->get();
        $orderIDs = [];
        foreach ($EOrder as $equo) { $orderIDs[] = $equo['id']; }
        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro','d_orders.relation_perfil_priv','d_orders.relation_tensor','d_orders.if_chain_height')
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
        ->whereIn('d_orders.order_id',$orderIDs)
        ->get();
        // var_dump($DOrder);
        // dd();
        $orders = $this->setAllOrder($EOrder->toArray(),$DOrder->toArray());
        return $orders;
    }

    private function preOrdersAuth($check) {
        $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.packing_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        // ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id');
        if((INT)$check === 1) { $EOrder->where('e_orders.check1',DB::raw(0)); }
        if((INT)$check === 2) { $EOrder->where('e_orders.check2',DB::raw(0)); }
        $EOrder = $EOrder->where('e_orders.status_id',1)
        ->get();

        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro','d_orders.relation_perfil_priv','d_orders.relation_tensor','d_orders.if_chain_height')
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
        ->join('e_orders', function($join) use($check) {
            $join->on('e_orders.id', '=', 'd_orders.order_id');
            $join->on('e_orders.status_id',DB::raw(1));
            if((INT)$check === 1) { $join->on('e_orders.check1',DB::raw(0)); }
            if((INT)$check === 2) { $join->on('e_orders.check2',DB::raw(0)); }
        })
        ->get();
        $orders = $this->setOrder($EOrder->toArray(),$DOrder->toArray());
        return $orders;
    }

    private function allStatusOrders($statusID) {
        $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.packing_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        // ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id')
        ->where('e_orders.status_id',DB::raw($statusID))
        ->get();
        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro','d_orders.relation_perfil_priv','d_orders.relation_tensor','d_orders.if_chain_height')
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
        ->join('e_orders', function($join) use($statusID) {
            $join->on('e_orders.id', '=', 'd_orders.order_id')
            ->where('e_orders.status_id',DB::raw($statusID));
        })
        ->get();
        $orders = $this->setOrder($EOrder->toArray(),$DOrder->toArray());
        return $orders;
    }

    private function allStatusPLOrders($statusID) {
        $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.packing_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        // ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id')
        ->where('e_orders.status_id',DB::raw($statusID))
        ->get();
        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro','d_orders.relation_perfil_priv','d_orders.relation_tensor','d_orders.if_chain_height')
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
        ->join('e_orders', function($join) use($statusID) {
            $join->on('e_orders.id', '=', 'd_orders.order_id')
            ->where('e_orders.status_id',DB::raw($statusID));
        })
        ->whereIn('d_orders.product_id',[1,2])
        ->get();
        $orders = $this->setOrder($EOrder->toArray(),$DOrder->toArray());
        return $orders;
    }

    private function getShipmentOrders($statusID,$delivery_type) {
        $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.packing_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        // ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id')
        ->whereIn('e_orders.status_id',$statusID)
        ->where('e_orders.delivery_type_id',DB::raw($delivery_type))
        ->get();
        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro','d_orders.relation_perfil_priv','d_orders.relation_tensor','d_orders.if_chain_height')
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
        ->join('e_orders', function($join) use($statusID) {
            $join->on('e_orders.id', '=', 'd_orders.order_id')
            ->whereIn('e_orders.status_id',$statusID);
        })
        ->get();
        $orders = $this->setOrder($EOrder->toArray(),$DOrder->toArray());
        return $orders;
    }

    private function getIndividualOrder($orderID) {
        $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.packing_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        // ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id')
        ->where('e_orders.id',DB::raw($orderID))
        ->first();
        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost', DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'), 'd_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_production_locations.location','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro','d_orders.relation_perfil_priv','d_orders.relation_tensor','d_orders.if_chain_height')
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
        ->leftJoin('d_production_locations','d_production_locations.id','d_orders.production_location_id')
        ->where('d_orders.order_id',DB::raw($orderID))
        ->get();
        $orders = $this->setIndividualOrder($EOrder->toArray(),$DOrder->toArray());
        return $orders;
    }

    private function getIndividualShipmentOrder($orderID) {
        $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.packing_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        // ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id')
        ->where('e_orders.id',DB::raw($orderID))
        ->first();
        // PRODUCTO TERMINADO
        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost', DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'), 'd_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_production_locations.location','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro','d_orders.relation_perfil_priv','d_orders.relation_tensor','d_orders.if_chain_height')
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
        ->leftJoin('d_production_locations','d_production_locations.id','d_orders.production_location_id')
        ->where('d_orders.order_id',DB::raw($orderID))
        ->whereIn('d_orders.product_id',[1,2])
        ->get();
        // ACC
        $DOrderAcc = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost', DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'), 'd_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_production_locations.location','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro','d_orders.relation_perfil_priv','d_orders.relation_tensor','d_orders.if_chain_height')
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
        ->leftJoin('d_production_locations','d_production_locations.id','d_orders.production_location_id')
        ->where('d_orders.order_id',DB::raw($orderID))
        ->whereIn('d_orders.product_id',[4])
        ->where( function($query) {
            return $query
            ->where('d_orders.relation_accesories', '<=', DB::raw('0'))
            ->orWhereNull('d_orders.relation_accesories');
        })
        ->where( function($query) {
            return $query
            ->where('d_orders.relation_motor', '<=', DB::raw('0'))
            ->orWhereNull('d_orders.relation_motor');
        })
        ->where( function($query) {
            return $query
            ->where('d_orders.relation_control', '<=', DB::raw('0'))
            ->orWhereNull('d_orders.relation_control');
        })
        ->where( function($query) {
            return $query
            ->where('d_orders.relation_bracket_dn', '<=', DB::raw('0'))
            ->orWhereNull('d_orders.relation_bracket_dn');
        })
        ->where('c_articles.model_id','!=',6)
        ->where( function($query) {
            return $query
            ->where('d_orders.motor_id', '=', DB::raw('0'))
            ->orWhereNull('d_orders.motor_id');
        })
        ->whereNotIn('d_orders.article_id',[282])
        ->where( function($query) {
            return $query
            ->where('d_orders.relation_lambrequin', '=', DB::raw('0'))
            ->orWhereNull('d_orders.lambrequin_id');
        })
        ->get();
        $orders = $this->setIndividualShipmentOrder($EOrder->toArray(),$DOrder->toArray(), !empty($DOrderAcc) ? $DOrderAcc->toArray() : [] );
        return $orders;
    }

    private function getIndividualShipmentGuarantee($guarantee_id) {
        $EGuaranty = EGuaranty::select('e_guarantee.id','e_guarantee.folio','e_guarantee.nomen',DB::raw("CASE WHEN e_guarantee.capture_id = 1 THEN 'Persiana Nueva' ELSE 'Captura componentes' END AS type_capture"),'e_guarantee.user_id','c_erp_info_users.short_name as agent_name','e_guarantee.order_id','e_orders.client_id',DB::raw('null AS proyect_name,null AS payment_method,null AS payment_option, null AS account_number,CASE WHEN c_users.short_name IS NOT NULL THEN c_users.short_name ELSE c_users.full_name END AS client_name'),'e_guarantee.warranty_type_id','c_type_guarantee.type_warranty','e_guarantee.guarantee_error_id','c_guarantee_errors.guarantee_error','e_guarantee.guarantee_type_error_id','c_guarantee_type_errors.guarantee_type_error','e_guarantee.description','e_guarantee.status_id','c_status_guarantee.status','c_status_guarantee.color_status','e_guarantee.delivery_type_id','c_delivery_types.delivery',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_guarantee.if_installer_required','e_guarantee.authorization_date','e_guarantee.packing_date','e_guarantee.created_at')
        ->join('e_orders','e_orders.id','e_guarantee.order_id')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_guarantee.user_id')
        ->join('c_status_guarantee','c_status_guarantee.id','e_guarantee.status_id')
        ->join('c_type_guarantee','c_type_guarantee.id','e_guarantee.warranty_type_id')
        ->join('c_guarantee_errors','c_guarantee_errors.id','e_guarantee.guarantee_error_id')
        ->join('c_guarantee_type_errors','c_guarantee_type_errors.id','e_guarantee.guarantee_type_error_id')
        ->leftJoin('c_delivery_types','c_delivery_types.id','e_guarantee.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_guarantee.client_address_id')
        ->where('e_guarantee.id',DB::raw($guarantee_id))
        ->first();
        // PRODUCTO TERMINADO
        $DGuaranty = DGuaranty::select('d_guarantee.id',DB::raw('d_guarantee.guarantee_id AS order_id'),'d_guarantee.guarantee_id','d_guarantee.item_id','d_guarantee.article_id',DB::raw('CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN la.lambrequin_price ELSE d_guarantee.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_guarantee.discount1','d_guarantee.discount2','d_guarantee.discount3','d_guarantee.quantity','d_guarantee.width','d_guarantee.height','d_guarantee.product_id','c_products.product','d_guarantee.operation_id','c_operations.operation','d_guarantee.fall','d_guarantee.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_guarantee.chain_id','c_chains.chain','d_guarantee.height_chain','d_guarantee.side_id','d_guarantee.mechanism_side_id','c_mechanism_sides.mechanism_side','d_guarantee.unit_id','c_units.unit','d_guarantee.component_color_id','c_colors.color as color_component','d_guarantee.commit','d_guarantee.commit_client','d_guarantee.awning_type_id','d_guarantee.area_description','d_guarantee.relation_id','d_guarantee.relation_bracket','d_guarantee.is_inverted','d_guarantee.is_heat_seal','d_guarantee.relation_cassette','d_guarantee.relation_lambrequin','d_guarantee.cassette_id','d_guarantee.relation_motor','d_guarantee.motor_id','d_guarantee.relation_accesories','d_guarantee.relation_heat_seal','d_guarantee.relation_bracket_dn','d_guarantee.relation_control','d_guarantee.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_guarantee.is_tie_stripe','d_guarantee.tube_id','c_tubes.tube','d_guarantee.divisions','d_guarantee.mechanism_id','c_mechanisms.mechanism','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_guarantee.production_location_id','d_production_locations.location','d_guarantee.lambrequin_id','d_guarantee.fijo_id','d_guarantee.corbatin_id','d_guarantee.is_velcro','d_guarantee.item_detail','d_guarantee.damage_fabric','d_guarantee.damage_tube','d_guarantee.damage_mechanism','d_guarantee.damage_counterweight','d_guarantee.damage_chain','d_guarantee.damage_fascia','d_guarantee.damage_motor','d_guarantee.item_detail')
        ->join('c_articles','c_articles.id','d_guarantee.article_id')
        ->join('c_products','c_products.id','d_guarantee.product_id')
        ->leftJoin('c_operations','c_operations.id','d_guarantee.operation_id')
        ->join('c_units','c_units.id','d_guarantee.unit_id')
        ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_guarantee.mechanism_side_id')
        ->leftJoin('c_mechanisms','c_mechanisms.id','d_guarantee.mechanism_id')
        ->leftJoin('c_chains','c_chains.id','d_guarantee.chain_id')
        ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_guarantee.counterweight_bar_id')
        ->leftJoin('c_colors','c_colors.id','d_guarantee.component_color_id')
        ->leftJoin('c_config_motors', function($join) {
            $join->on('c_config_motors.article_id', '=', 'd_guarantee.motor_id');
            $join->on('c_config_motors.num_divisions','=','d_guarantee.divisions');
        })
        ->leftJoin('c_articles as c_article_motor','c_article_motor.id','d_guarantee.motor_id')
        ->leftJoin('c_tubes','c_tubes.id','d_guarantee.tube_id')
        ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
        ->leftJoin('c_articles AS la','la.id','d_guarantee.lambrequin_id')
        ->leftJoin('c_articles AS cb','cb.id','d_guarantee.corbatin_id')
        ->leftJoin('c_articles AS fj','fj.id','d_guarantee.fijo_id')
        ->leftJoin('d_production_locations','d_production_locations.id','d_guarantee.production_location_id')
        ->where('d_guarantee.guarantee_id',DB::raw($guarantee_id))
        ->whereIn('d_guarantee.product_id',[1,2])
        ->get();
        // ACC
        $DGuarantyAcc = DGuaranty::select('d_guarantee.id',DB::raw('d_guarantee.guarantee_id AS order_id'),'d_guarantee.guarantee_id','d_guarantee.item_id','d_guarantee.article_id',DB::raw('CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_guarantee.corbatin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_guarantee.fijo_id IS NOT NULL AND d_guarantee.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_guarantee.lambrequin_id IS NOT NULL AND d_guarantee.product_id = 4 THEN la.lambrequin_price ELSE d_guarantee.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_guarantee.discount1','d_guarantee.discount2','d_guarantee.discount3','d_guarantee.quantity','d_guarantee.width','d_guarantee.height','d_guarantee.product_id','c_products.product','d_guarantee.operation_id','c_operations.operation','d_guarantee.fall','d_guarantee.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_guarantee.chain_id','c_chains.chain','d_guarantee.height_chain','d_guarantee.side_id','d_guarantee.mechanism_side_id','c_mechanism_sides.mechanism_side','d_guarantee.unit_id','c_units.unit','d_guarantee.component_color_id','c_colors.color as color_component','d_guarantee.commit','d_guarantee.commit_client','d_guarantee.awning_type_id','d_guarantee.area_description','d_guarantee.relation_id','d_guarantee.relation_bracket','d_guarantee.is_inverted','d_guarantee.is_heat_seal','d_guarantee.relation_cassette','d_guarantee.relation_lambrequin','d_guarantee.cassette_id','d_guarantee.relation_motor','d_guarantee.motor_id','d_guarantee.relation_accesories','d_guarantee.relation_heat_seal','d_guarantee.relation_bracket_dn','d_guarantee.relation_control','d_guarantee.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_guarantee.is_tie_stripe','d_guarantee.tube_id','c_tubes.tube','d_guarantee.divisions','d_guarantee.mechanism_id','c_mechanisms.mechanism','d_guarantee.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_guarantee.production_location_id','d_production_locations.location','d_guarantee.lambrequin_id','d_guarantee.fijo_id','d_guarantee.corbatin_id','d_guarantee.is_velcro','d_guarantee.item_detail','d_guarantee.damage_fabric','d_guarantee.damage_tube','d_guarantee.damage_mechanism','d_guarantee.damage_counterweight','d_guarantee.damage_chain','d_guarantee.damage_fascia','d_guarantee.damage_motor','d_guarantee.item_detail')
        ->join('c_articles','c_articles.id','d_guarantee.article_id')
        ->join('c_products','c_products.id','d_guarantee.product_id')
        ->leftJoin('c_operations','c_operations.id','d_guarantee.operation_id')
        ->join('c_units','c_units.id','d_guarantee.unit_id')
        ->leftJoin('c_mechanism_sides','c_mechanism_sides.id','d_guarantee.mechanism_side_id')
        ->leftJoin('c_mechanisms','c_mechanisms.id','d_guarantee.mechanism_id')
        ->leftJoin('c_chains','c_chains.id','d_guarantee.chain_id')
        ->leftJoin('c_counterweight_bars','c_counterweight_bars.id','d_guarantee.counterweight_bar_id')
        ->leftJoin('c_colors','c_colors.id','d_guarantee.component_color_id')
        ->leftJoin('c_config_motors', function($join) {
            $join->on('c_config_motors.article_id', '=', 'd_guarantee.motor_id');
            $join->on('c_config_motors.num_divisions','=','d_guarantee.divisions');
        })
        ->leftJoin('c_articles as c_article_motor','c_article_motor.id','d_guarantee.motor_id')
        ->leftJoin('c_tubes','c_tubes.id','d_guarantee.tube_id')
        ->join('c_status_productions','c_status_productions.id','d_guarantee.status_production_id')
        ->leftJoin('c_articles AS la','la.id','d_guarantee.lambrequin_id')
        ->leftJoin('c_articles AS cb','cb.id','d_guarantee.corbatin_id')
        ->leftJoin('c_articles AS fj','fj.id','d_guarantee.fijo_id')
        ->leftJoin('d_production_locations','d_production_locations.id','d_guarantee.production_location_id')
        ->where('d_guarantee.guarantee_id',DB::raw($guarantee_id))
        ->whereIn('d_guarantee.product_id',[4])
        ->where( function($query) {
            return $query
            ->where('d_guarantee.relation_accesories', '<=', DB::raw('0'))
            ->orWhereNull('d_guarantee.relation_accesories');
        })
        ->where( function($query) {
            return $query
            ->where('d_guarantee.relation_motor', '<=', DB::raw('0'))
            ->orWhereNull('d_guarantee.relation_motor');
        })
        ->where( function($query) {
            return $query
            ->where('d_guarantee.relation_control', '<=', DB::raw('0'))
            ->orWhereNull('d_guarantee.relation_control');
        })
        ->where( function($query) {
            return $query
            ->where('d_guarantee.relation_bracket_dn', '<=', DB::raw('0'))
            ->orWhereNull('d_guarantee.relation_bracket_dn');
        })
        ->where('c_articles.model_id','!=',6)
        ->where( function($query) {
            return $query
            ->where('d_guarantee.motor_id', '=', DB::raw('0'))
            ->orWhereNull('d_guarantee.motor_id');
        })
        ->whereNotIn('d_guarantee.article_id',[282])
        ->where( function($query) {
            return $query
            ->where('d_guarantee.relation_lambrequin', '=', DB::raw('0'))
            ->orWhereNull('d_guarantee.lambrequin_id');
        })
        ->get();
        $guarantee = $this->setIndividualShipmentGuarantee($EGuaranty->toArray(),!empty($DGuaranty) ? $DGuaranty->toArray() : [], !empty($DGuarantyAcc) ? $DGuarantyAcc->toArray() : [] );
        return $guarantee;
    }


    private function allAssignOrders() {
        $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id')
        ->where('e_orders.status_id',12)
        ->get();
        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','d_orders.width','d_orders.height','d_orders.product_id','d_orders.operation_id','d_orders.fall','d_orders.counterweight_bar_id','d_orders.chain_id','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','d_orders.unit_id','d_orders.component_color_id','d_orders.awning_type_id','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','d_orders.is_tie_stripe','d_orders.tube_id','d_orders.divisions','d_orders.mechanism_id','d_modulations.id as modulation_detail_id','d_modulations.modulation_id','d_modulations.height_add','d_modulations.width_discount','d_modulations.counterweight_discount','d_modulations.tube_discount','d_modulations.turn_bar_discount','d_modulations.fascia_discount','d_modulations.join_id','d_modulations.lot')
        ->join('e_orders', function($join) {
            $join->on('e_orders.id', '=', 'd_orders.order_id')
            ->where('e_orders.status_id',12);
        })
        ->join('d_modulations','d_modulations.detail_order_id','d_orders.id')
        ->get();
        $orders = $this->setOrder($EOrder->toArray(),$DOrder->toArray());
        return $orders;
    }

    private function relationOrders($opt) {


        $EOrder =  EOrder::select('e_orders.id',DB::raw("'LS' AS nomen"),'e_orders.user_id','c_erp_info_users.short_name as agent_name','e_orders.client_id','e_orders.quotation_id','e_orders.invoice_id',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_users.discount as client_discount','e_orders.status_id','c_status_orders.status','c_status_orders.color_status','e_orders.proyect_name','e_orders.production_date','e_orders.deadline_date','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.packing_date','e_orders.payment_method_id','c_payment_methods.payment_method','e_orders.payment_option_id','c_payment_options.payment_option','e_orders.account_number','e_orders.delivery_type_id','c_delivery_types.delivery','e_orders.client_address_id',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"),'e_orders.created_at')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        // ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id');
        switch ((INT)$opt) {
            case 0:
                $EOrder->whereNull('e_orders.invoice_id');
            break;
            case 1:
                $EOrder->whereNotNull('e_orders.invoice_id');
            break;
        }
        $EOrder = $EOrder->get();
        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro','d_orders.relation_perfil_priv','d_orders.relation_tensor','d_orders.if_chain_height')
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
        ->leftJoin('c_articles AS fj','fj.id','d_orders.fijo_id');
        switch ((INT)$opt) {
            case 0:
                $DOrder->join('e_orders', function($join) {
                    $join->on('e_orders.id', '=', 'd_orders.order_id')
                    ->whereNull('e_orders.invoice_id');
                });
            break;
            case 1:
                $DOrder->join('e_orders', function($join) {
                    $join->on('e_orders.id', '=', 'd_orders.order_id')
                    ->whereNotNull('e_orders.invoice_id');
                });
            break;
        }
        $DOrder = $DOrder->get();
        $orders = $this->setOrder($EOrder->toArray(),$DOrder->toArray());

        return $orders;
    }

    private function allDetailOrders() {

        $DOrder = DOrder::select('d_orders.id','d_orders.order_id','d_orders.item_id','d_orders.article_id','c_articles.cost',DB::raw('CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("LAMBREQUIN ",la.article) ELSE CASE WHEN d_orders.corbatin_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("CORBATIN ",cb.article) ELSE CASE WHEN d_orders.fijo_id IS NOT NULL AND d_orders.product_id = 4 THEN CONCAT("FIJO ",fj.article) ELSE c_articles.article END END END AS article, CASE WHEN d_orders.lambrequin_id IS NOT NULL AND d_orders.product_id = 4 THEN la.lambrequin_price ELSE d_orders.price END AS price, c_articles.model_id, la.model_id as la_model_id, cb.model_id as cb_model_id, fj.model_id as fj_model_id'),'d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity','d_orders.width','d_orders.height','d_orders.product_id','c_products.product','d_orders.operation_id','c_operations.operation','d_orders.fall','d_orders.counterweight_bar_id','c_counterweight_bars.counterweight_bar','c_counterweight_bars.is_counterweight_covered','d_orders.chain_id','c_chains.chain','d_orders.height_chain','d_orders.side_id','d_orders.mechanism_side_id','c_mechanism_sides.mechanism_side','d_orders.unit_id','c_units.unit','d_orders.component_color_id','c_colors.color as color_component','d_orders.commit','d_orders.commit_client','d_orders.awning_type_id','d_orders.area_description','d_orders.relation_id','d_orders.relation_bracket','d_orders.is_inverted','d_orders.is_heat_seal','d_orders.relation_cassette','d_orders.relation_lambrequin','d_orders.cassette_id','d_orders.relation_motor','d_orders.motor_id','d_orders.relation_accesories','d_orders.relation_heat_seal','d_orders.relation_bracket_dn','d_orders.relation_control','d_orders.channel','c_config_motors.mm as mm_motor','c_article_motor.model_id as model_motor_id','d_orders.is_tie_stripe','d_orders.tube_id','c_tubes.tube','d_orders.divisions','d_orders.mechanism_id','c_mechanisms.mechanism','d_orders.status_production_id','c_status_productions.status','c_status_productions.color_status as production_color_status','d_orders.production_location_id','d_orders.lambrequin_id','d_orders.fijo_id','d_orders.corbatin_id','d_orders.is_velcro','d_orders.relation_perfil_priv','d_orders.relation_tensor','d_orders.if_chain_height')
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
        ->get()
        ->toArray();
        return $DOrder;
    }

    private function setIndividualOrder($Eorder,$DOrder) {
        $ordersDetailIDs = [];
        foreach ($DOrder as  $do) { $ordersDetailIDs[] = $do['id']; }
        $materialRequests = DMaterialRequest::select('d_material_requests.id','d_material_requests.material_request_id','d_material_requests.detail_order_id','d_material_requests.article_id','d_material_requests.unit_id','d_material_requests.quantity',DB::raw(' CASE WHEN c_production_lot_costs.cost IS NULL THEN d_material_requests.cost ELSE c_production_lot_costs.cost END AS cost'),'d_material_requests.cost AS cost_init','c_production_lot_costs.cost AS cost_lot','d_material_requests.provider_id','d_material_requests.width_lot','c_articles.article','c_articles.model_id','c_models.product_id')
        ->join('c_articles','c_articles.id','d_material_requests.article_id')
        ->join('c_models','c_models.id','c_articles.model_id')
        ->LeftJoin('c_production_lot_costs', function($join) {
            $join->on('c_production_lot_costs.article_id','=','d_material_requests.article_id');
            $join->on('c_production_lot_costs.width_lot','=','d_material_requests.width_lot');
        })
        ->whereIn('detail_order_id',$ordersDetailIDs)
        ->get()
        ->toArray();

        $Eorder['details'] = [];
        foreach ($DOrder as $key2 =>  $dorder) {
            $Eorder['details'][] = $DOrder[$key2];
        }

        foreach ($Eorder['details'] as $key3 => $details) {
            $Eorder['details'][$key3]['material_request'] = [];
            foreach ($materialRequests as $key4 => $mr) {
                if((INT)$details['id'] === (INT)$mr['detail_order_id']) {
                    $Eorder['details'][$key3]['material_request'][] = $mr;
                }
            }
        }
        return $Eorder;
    }

    private function setIndividualShipmentOrder($EGuaranty,$DGuarantee,$DGuaranteeAcc) {
        $guarantyDetailIDs = [];
        foreach ($DGuarantee as  $do) { $guarantyDetailIDs[] = $do['id']; }
        $materialRequests = DMaterialRequest::select('d_material_requests.id','d_material_requests.material_request_id','d_material_requests.detail_order_id','d_material_requests.article_id','d_material_requests.unit_id','d_material_requests.quantity',DB::raw(' CASE WHEN c_production_lot_costs.cost IS NULL THEN d_material_requests.cost ELSE c_production_lot_costs.cost END AS cost'),'d_material_requests.cost AS cost_init','c_production_lot_costs.cost AS cost_lot','d_material_requests.provider_id','d_material_requests.width_lot','c_articles.article','c_articles.model_id','c_models.product_id')
        ->join('c_articles','c_articles.id','d_material_requests.article_id')
        ->join('c_models','c_models.id','c_articles.model_id')
        ->LeftJoin('c_production_lot_costs', function($join) {
            $join->on('c_production_lot_costs.article_id','=','d_material_requests.article_id');
            $join->on('c_production_lot_costs.width_lot','=','d_material_requests.width_lot');
        })
        ->whereIn('detail_order_id',$guarantyDetailIDs)
        ->get()
        ->toArray();

        $EGuaranty['details'] = [];
        foreach ($DGuarantee as $key2 =>  $dorder) {
            $EGuaranty['details'][] = $DGuarantee[$key2];
        }

        foreach ($DGuaranteeAcc as $key4 =>  $dorderAcc) {
            $EGuaranty['details'][] = $DGuaranteeAcc[$key4];
        }

        foreach ($EGuaranty['details'] as $key3 => $details) {
            $EGuaranty['details'][$key3]['material_request'] = [];
            foreach ($materialRequests as $key4 => $mr) {
                if((INT)$details['id'] === (INT)$mr['detail_order_id']) {
                    $EGuaranty['details'][$key3]['material_request'][] = $mr;
                }
            }
        }
        return $EGuaranty;
    }

    private function setIndividualShipmentGuarantee($Eorder,$DOrder,$DOrderAcc) {
        $ordersDetailIDs = [];
        foreach ($DOrder as  $do) { $ordersDetailIDs[] = $do['id']; }
        $materialRequests = DMaterialRequest::select('d_material_requests.id','d_material_requests.material_request_id','d_material_requests.detail_order_id','d_material_requests.article_id','d_material_requests.unit_id','d_material_requests.quantity',DB::raw(' CASE WHEN c_production_lot_costs.cost IS NULL THEN d_material_requests.cost ELSE c_production_lot_costs.cost END AS cost'),'d_material_requests.cost AS cost_init','c_production_lot_costs.cost AS cost_lot','d_material_requests.provider_id','d_material_requests.width_lot','c_articles.article','c_articles.model_id','c_models.product_id')
        ->join('c_articles','c_articles.id','d_material_requests.article_id')
        ->join('c_models','c_models.id','c_articles.model_id')
        ->LeftJoin('c_production_lot_costs', function($join) {
            $join->on('c_production_lot_costs.article_id','=','d_material_requests.article_id');
            $join->on('c_production_lot_costs.width_lot','=','d_material_requests.width_lot');
        })
        ->whereIn('detail_order_id',$ordersDetailIDs)
        ->get()
        ->toArray();

        $Eorder['details'] = [];
        foreach ($DOrder as $key2 =>  $dorder) {
            $Eorder['details'][] = $DOrder[$key2];
        }

        foreach ($DOrderAcc as $key4 =>  $dorderAcc) {
            $Eorder['details'][] = $DOrderAcc[$key4];
        }

        foreach ($Eorder['details'] as $key3 => $details) {
            $Eorder['details'][$key3]['material_request'] = [];
            foreach ($materialRequests as $key4 => $mr) {
                if((INT)$details['id'] === (INT)$mr['detail_order_id']) {
                    $Eorder['details'][$key3]['material_request'][] = $mr;
                }
            }
        }
        return $Eorder;
    }

    private function setAllOrder($Eorder,$DOrder) {
        foreach ($Eorder as $key => $order) {
            $Eorder[$key]['details'] = [];
            foreach ($DOrder as $key2 =>  $dorder) {
                if($dorder['order_id'] == $order['id']) {
                    $Eorder[$key]['details'][] = $DOrder[$key2];
                }
            }
        }
        return $Eorder;
    }

    private function setOrder($Eorder,$DOrder) {
        // Obtenemos el costo de las telas
        $ordersDetailIDs = [];
        foreach ($DOrder as  $do) { $ordersDetailIDs[] = $do['id']; }
        $materialRequests = DMaterialRequest::select('d_material_requests.id','d_material_requests.material_request_id','d_material_requests.detail_order_id','d_material_requests.article_id','d_material_requests.unit_id','d_material_requests.quantity',DB::raw(' CASE WHEN c_production_lot_costs.cost IS NULL THEN d_material_requests.cost ELSE c_production_lot_costs.cost END AS cost'),'d_material_requests.cost AS cost_init','c_production_lot_costs.cost AS cost_lot','d_material_requests.provider_id','d_material_requests.width_lot','c_articles.article','c_articles.model_id','c_models.product_id')
        ->join('c_articles','c_articles.id','d_material_requests.article_id')
        ->join('c_models','c_models.id','c_articles.model_id')
        ->LeftJoin('c_production_lot_costs', function($join) {
            $join->on('c_production_lot_costs.article_id','=','d_material_requests.article_id');
            $join->on('c_production_lot_costs.width_lot','=','d_material_requests.width_lot');
        })
        ->whereIn('detail_order_id',$ordersDetailIDs)
        ->get()
        ->toArray();
        foreach ($Eorder as $key => $order) {
            $Eorder[$key]['details'] = [];
            foreach ($DOrder as $key2 =>  $dorder) {
                if($dorder['order_id'] == $order['id']) {
                    $Eorder[$key]['details'][] = $DOrder[$key2];
                }
            }
            foreach ($Eorder[$key]['details'] as $key3 => $details) {
                $Eorder[$key]['details'][$key3]['material_request'] = [];
                foreach ($materialRequests as $key4 => $mr) {
                    if((INT)$details['id'] === (INT)$mr['detail_order_id']) {
                        $Eorder[$key]['details'][$key3]['material_request'][] = $mr;
                    }
                }
            }
        }
        return $Eorder;
    }

    private function setGuarantee($EGuaranty,$DGuaranty) {
        foreach ($EGuaranty as $key => $warranty) {
            $EGuaranty[$key]['details'] = [];
            foreach ($DGuaranty as $key2 =>  $dw) {
                if($dw['guarantee_id'] == $warranty['id']) {
                    $EGuaranty[$key]['details'][] = $DGuaranty[$key2];
                }
            }
        }
        return $EGuaranty;
    }


    private function setIndividualGuarantee($EGuarntee,$DGuarantee) {

        $EGuarntee['details'] = [];
        foreach ($DGuarantee as $key2 =>  $dGuarnty) {
            $EGuarntee['details'][] = $DGuarantee[$key2];
        }
        return $EGuarntee;
    }

    private function setMaterialRequests($EMaterialRequest,$DMaterialRequest) {

        foreach ($EMaterialRequest as $key => $request) {
            $EMaterialRequest[$key]['company_id'] = 1;
            $EMaterialRequest[$key]['company'] = 'Lanson Shades';
            $EMaterialRequest[$key]['details_request'] = [];
            foreach ($DMaterialRequest as $key2 =>  $dRequest) {
                if($dRequest['material_request_id'] == $request['id']) {
                    $EMaterialRequest[$key]['details_request'][] = $DMaterialRequest[$key2];
                }
            }
        }
        return $EMaterialRequest;
    }

    private function setMaterialRequestsWH($EMaterialRequest,$DMaterialRequest) {

        foreach ($EMaterialRequest as $key => $request) {
            $EMaterialRequest[$key]['nomen'] = 'MR';
            $EMaterialRequest[$key]['company_id'] = 1;
            $EMaterialRequest[$key]['company'] = 'Lanson Shades';
            $EMaterialRequest[$key]['details_request'] = [];
            foreach ($DMaterialRequest as $key2 =>  $dRequest) {
                if($dRequest['material_request_id'] == $request['id']) {
                    $EMaterialRequest[$key]['details_request'][] = $DMaterialRequest[$key2];
                }
            }
        }
        return $EMaterialRequest;
    }

    private function setFullMaterialRequests($EMaterialRequest,$DMaterialRequest,$EOrder,$DOrder) {

        foreach ($EMaterialRequest as $keyM => $Ematerial) {
            $EMaterialRequest[$keyM]['orders'] = $EOrder;

            foreach ($EMaterialRequest['orders'] as $key => $order) {
                $EMaterialRequest['orders'][$key]['details'] = [];
                foreach ($DOrder as $key2 => $dOrder) {
                    if($order['id'] == $dOrder['order_id']) {
                        $EMaterialRequest['orders'][$key]['details'][] = $dOrder;
                    }
                }
                foreach ($EMaterialRequest['orders'][$key]['details'] as $key3 => $details) {
                    $EMaterialRequest['orders'][$key]['details'][$key3]['requests'] = [];
                    foreach ($DMaterialRequest as $key4 => $dMaterialRequest) {
                        if($details['id'] == $dMaterialRequest['detail_order_id']) {
                            $EMaterialRequest['orders'][$key]['details'][$key3]['requests'][] = $dMaterialRequest;
                        }
                    }
                }
            }
        }

        return $EMaterialRequest;
    }

    private function setIndividualMaterialRequests($EMaterialRequest,$DMaterialRequest) {
        $EMaterialRequest['details_request'] = [];
        foreach ($DMaterialRequest as $dRequest) {
            $EMaterialRequest['details_request'][] = $dRequest;
        }
        return $EMaterialRequest;
    }

    private function dateDeadlineAssigned() {
        $date_now = Carbon::now();
        $new_date = $date_now->add(1, 'day');
        $day=date('w', strtotime($date_now->toDateTimeString()));
        switch ($day) {
            case 5:
                $new_date = $date_now->add(3, 'day');
                return $new_date->toDateTimeString();
            break;
            default:
                return $new_date->toDateTimeString();
            break;
        }
    }

    private function getchainSize($height_chain,$height,$mechanism_id,$relation_cassette,$tube_id,$product_id,$if_chain_height) {
        $heightChain = '';
        if( (DOUBLE)$height_chain !== (DOUBLE)$height AND (INT)$product_id === 1 ) {
            $heightChain = number_format(($height_chain * 2),3);
        } else {
            if((INT)$relation_cassette > 0 AND (INT)$tube_id === 3 ) {
                $heightChain = number_format(($height_chain * 2),3);
            } else if((INT)$mechanism_id === 2 AND (INT)$relation_cassette > 0 AND (INT)$tube_id !== 3 ) {
                $heightChain = number_format(($height_chain * 1.6),3);
            } else if((INT)$mechanism_id === 3 ) {
                if( (INT)$if_chain_height === 1 ) {
                    $heightChain = number_format(($height_chain * 2),3);
                } else {
                    if((DOUBLE)$height_chain !== (DOUBLE)$height ) {
                        $heightChain = number_format(($height_chain * 2),3);
                    } else {
                        $heightChain = number_format(($height_chain * 1.6),3);
                    }
                }
            } else if((INT)$mechanism_id === 6 ) {
                $heightChain = number_format((($height_chain * 2)*1.1),3);
            } else {
                $heightChain = number_format(($height_chain * 2),3);
            }
        }
        return $heightChain;
    }

    private function setSections($ESection,$DSection) {
        foreach ($ESection as $key => $eSection) {
            $ESection[$key]['details'] = [];
            foreach ($DSection as $key2 => $dSection) {
                if( (INT)$eSection['id'] === (INT)$dSection['section_id'] ) {
                    $ESection[$key]['details'][] = $dSection ;
                }
            }
        }
        return $ESection;
    }

}
