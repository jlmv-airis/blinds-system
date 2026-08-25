<?php

namespace App\Http\Controllers\BI;

use App\Http\Controllers\Controller;
use App\Models\DOrder;
use App\Models\EOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\classes\GetTotal;
use App\Exports\dashboar5Export;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class Dashboard_5Controller extends Controller
{
    public function getDataDashboard5(Request $request) {
        // try {
            // ORDERS
            $EOrders = EOrder::select('e_orders.id','c_erp_info_users.short_name as agent_name',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_status_orders.status',DB::raw('0 AS total, COUNT(*) AS parts'),'e_orders.proyect_name','e_orders.created_at','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.production_date','e_orders.packing_date','e_orders.finalize_date','c_payment_methods.payment_method','c_payment_options.payment_option','c_delivery_types.delivery',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"))
            ->join('d_orders','d_orders.order_id','e_orders.id')
            ->join('c_users','c_users.id','e_orders.client_id')
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
            ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
            ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
            ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
            ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
            ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id');
            switch ($request->filter) {
                case 2: // Dates
                    $dateInit = $request->dateInit.' 00:00:00';
                    $dateEnd = $request->dateEnd.' 23:59:59';
                    $EOrders->whereBetween('e_orders.created_at', [$dateInit, $dateEnd]);
                break;
                case 3: // OrderID
                    $EOrders->where('e_orders.id', $request->order_id);
                break;
            }
            $EOrders = $EOrders->orderBy('id')
            ->groupBy('e_orders.id')
            ->get();
            $ordersIDs = [];
            foreach ($EOrders as $order) { $ordersIDs[] = $order['id']; }
            // DETAILS ORDER
            $DOrder = DOrder::select('d_orders.order_id','c_erp_info_users.short_name as agent_name',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_status_orders.status',DB::raw('0 AS total, 1 AS parts'),'e_orders.authorization_date','e_orders.packing_date','e_orders.finalize_date','e_orders.finalize_date','c_articles.article',DB::raw('ROUND((d_orders.width*d_orders.height),3) AS m2'),'c_products.product','d_orders.product_id','d_orders.width','d_orders.height','d_orders.unit_id','d_orders.price','d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity')
            ->join('e_orders','e_orders.id','d_orders.order_id')
            ->join('c_users','c_users.id','e_orders.client_id')
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
            ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
            ->join('c_articles','c_articles.id','d_orders.article_id')
            ->join('c_products','c_products.id','d_orders.product_id')
            ->whereIn('d_orders.order_id', $ordersIDs)
            ->whereIn('d_orders.product_id', [1,2])
            ->orderBy('d_orders.order_id')
            ->orderBy('d_orders.item_id')
            ->get();
            $orders = $this->setOrder($EOrders->toArray(),$DOrder->toArray());
            $detailOrders = $this->setDetailOrders($DOrder->toArray());

            return response()->json([
                "success" => true,
                "filter" => $request->filter,
                "orders" => $orders,
                "detailOrders" => $detailOrders,
            ],200);
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         "success" => false,
        //         "error" => $th->getMessage(),
        //     ],400);
        // }
    }

    public function downloadExcelDashboard5(Request $request) {

        // ORDERS
        $EOrders = EOrder::select('e_orders.id','c_erp_info_users.short_name as agent_name',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_status_orders.status',DB::raw('0 AS total, COUNT(*) AS parts'),'e_orders.proyect_name','e_orders.created_at','e_orders.authorization_date','e_orders.material_assortment_date','e_orders.material_request_date','e_orders.validate_material_date','e_orders.production_date','e_orders.packing_date','e_orders.finalize_date','c_payment_methods.payment_method','c_payment_options.payment_option','c_delivery_types.delivery',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"))
        ->join('d_orders','d_orders.order_id','e_orders.id')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_payment_methods','c_payment_methods.id','e_orders.payment_method_id')
        ->join('c_payment_options','c_payment_options.id','e_orders.payment_option_id')
        ->join('c_delivery_types','c_delivery_types.id','e_orders.delivery_type_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_orders.client_address_id');
        switch ($request->filter) {
            case 2: // Dates
                $dateInit = $request->dateInit.' 00:00:00';
                $dateEnd = $request->dateEnd.' 23:59:59';
                $EOrders->whereBetween('e_orders.created_at', [$dateInit, $dateEnd]);
            break;
            case 3: // OrderID
                $EOrders->where('e_orders.id', $request->order_id);
            break;
        }
        $EOrders = $EOrders->orderBy('id')
        ->groupBy('e_orders.id')
        ->get();
        $ordersIDs = [];
        foreach ($EOrders as $order) { $ordersIDs[] = $order['id']; }
        // DETAILS ORDER
        $DOrder = DOrder::select('d_orders.order_id','c_erp_info_users.short_name as agent_name',DB::raw(' CASE WHEN c_users.short_name IS NULL THEN c_users.full_name ELSE c_users.short_name END AS client_name'),'c_status_orders.status',DB::raw('0 AS total, 1 AS parts'),'e_orders.authorization_date','e_orders.packing_date','e_orders.finalize_date','e_orders.finalize_date','c_articles.article',DB::raw('ROUND((d_orders.width*d_orders.height),3) AS m2'),'c_products.product','d_orders.product_id','d_orders.width','d_orders.height','d_orders.unit_id','d_orders.price','d_orders.discount1','d_orders.discount2','d_orders.discount3','d_orders.quantity')
        ->join('e_orders','e_orders.id','d_orders.order_id')
        ->join('c_users','c_users.id','e_orders.client_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_orders.user_id')
        ->join('c_status_orders','c_status_orders.id','e_orders.status_id')
        ->join('c_articles','c_articles.id','d_orders.article_id')
        ->join('c_products','c_products.id','d_orders.product_id')
        ->whereIn('d_orders.order_id', $ordersIDs)
        ->whereIn('d_orders.product_id', [1,2])
        ->orderBy('d_orders.order_id')
        ->orderBy('d_orders.item_id')
        ->get();
        $ordersData = $this->setOrder($EOrders->toArray(),$DOrder->toArray());
        $detailOrdersData = $this->setDetailOrders($DOrder->toArray());

        $orders = [];
        foreach ($ordersData as $key => $order) {
            $orders[] = [
                'id' => $order['id'],
                'agent_name' => $order['agent_name'],
                'client_name' => $order['client_name'],
                'status' => $order['status'],
                'total' => $order['total'],
                'parts' => $order['parts'],
                'proyect_name' => $order['proyect_name'],
                'created_at' => $order['created_at'],
                'authorization_date' => $order['authorization_date'],
                'material_request_date' => $order['material_request_date'],
                'material_assortment_date' => $order['material_assortment_date'],
                'validate_material_date' => $order['validate_material_date'],
                'production_date' => $order['production_date'],
                'packing_date' => $order['packing_date'],
                'finalize_date' => $order['finalize_date'],
                'payment_method' => $order['payment_method'],
                'payment_option' => $order['payment_option'],
                'delivery' => $order['delivery'],
                'address' => $order['address']
            ];
        }
        $detailOrders = [];
        foreach ($detailOrdersData as $key => $do) {
            $detailOrders[] = [
                'order_id' => $do['order_id'],
                'agent_name' => $do['agent_name'],
                'client_name' => $do['client_name'],
                'status' => $do['status'],
                'total' => $do['total'],
                'parts' => $do['parts'],
                'authorization_date' => $do['authorization_date'],
                'packing_date' => $do['packing_date'],
                'finalize_date' => $do['finalize_date'],
                'delivery_date' => $do['finalize_date'],
                'article' => $do['article'],
                'm2' => $do['m2'],
                'product' => $do['product']
            ];
        }
        $rowData = [
            'orders' => $orders,
            'detailOrders' => $detailOrders,
        ];

        $file = Excel::raw(new dashboar5Export($rowData), \Maatwebsite\Excel\Excel::XLSX);
        return [
            "name" => "dsahsboard_orders_".Carbon::now()->toDateTimeString().".xlsx",
            "file" => base64_encode($file),
        ];
    }

    private function setOrder($Eorder,$DOrder) {
        // Obtenemos el costo de las telas
        foreach ($Eorder as $key => $order) {
            $Eorder[$key]['details'] = [];
            foreach ($DOrder as $key2 =>  $dorder) {
                if($dorder['order_id'] == $order['id']) {
                    $totalDetail =  app(GetTotal::class)->getIndividualTotalOrder($DOrder[$key2]);
                    $DOrder[$key2]['total'] = number_format($totalDetail,2);
                    $Eorder[$key]['details'][] = $DOrder[$key2];
                }
            }

            $total =  app(GetTotal::class)->getTotalOrder($Eorder[$key]['details']);
            $Eorder[$key]['total'] = number_format($total['total'],2);
        }
        return $Eorder;
    }

    private function setDetailOrders($DOrder) {
        foreach ($DOrder as $key2 =>  $dorder) {
            $totalDetail =  app(GetTotal::class)->getIndividualTotalOrder($DOrder[$key2]);
            $DOrder[$key2]['total'] = number_format($totalDetail,2);
        }
        return $DOrder;
    }
}
