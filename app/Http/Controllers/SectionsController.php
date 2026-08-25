<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\classes\WebService;
use App\Models\DSection;
use App\Models\ESection;
use Illuminate\Support\Facades\DB;

class SectionsController extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }

    public function getSectionsData(Request $request)
    {
        //
        try {
            $company_id = $request->company_id;
            $response = [];
            if( (INT)$request->company_id === 2) { // INDIGOFF
                $response = $this->webService->getInventoryRT();
            }
            if( (INT)$request->company_id === 4) { // INDIGOFF
                $response = $this->webService->getInventoryINDF();
            }
            if( (INT)$request->company_id === 5) { // INDIGOFF
                $response = $this->webService->getInventoryWRKS();
            }
            $DSection = DSection::select('d_sections.id','e_sections.status_id','d_sections.sku','c_inventory_products.product','d_sections.section','d_sections.projected',DB::raw('CASE WHEN d_add_section_requests.quantity != "" OR d_add_section_requests.quantity IS NOT NULL THEN d_add_section_requests.quantity ELSE 0 END AS add_quantity'))
            ->leftJoin('c_inventory_products', function($join) use($company_id){
                $join->on('c_inventory_products.sku', '=', 'd_sections.sku')
                    ->where('c_inventory_products.company_id', '=', $company_id);
            })
            ->leftJoin('d_add_section_requests','d_add_section_requests.detail_section_id', 'd_sections.id')
            ->join('e_sections','e_sections.id','d_sections.section_id')
            ->whereIn('e_sections.status_id',[1,2,3,4])
            ->where('e_sections.company_id',$company_id)
            ->get()
            ->toArray();
            $inventory = $this->discountSection($response->items,$DSection);
            $ESection = ESection::select('e_sections.id','e_sections.user_id','e_sections.project','c_erp_info_users.short_name as user_name','e_sections.status_id','c_status_sections.status','c_status_sections.color_status','e_sections.detail','e_sections.quotation_id','e_sections.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_sections.user_id')
            ->join('c_status_sections','c_status_sections.id','e_sections.status_id')
            ->where('e_sections.company_id',$company_id)
            ->get();
            //
            return response()->json([
                'success'   => true,
                'inventory' => $inventory,
                'sections'  => $ESection,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }


    public function getRequestSectionsOrders($company_id)
    {
        //
        // try {
            $sectionsNew = $this->getSectionPerStatus(3,$company_id);
            $sectionsRequest = $this->getSectionPerStatus(4,$company_id);
            //
            return response()->json([
                'success'           => true,
                'sectionsNew'       => $sectionsNew,
                'sectionsRequest'   => $sectionsRequest,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 400);
        // }

    }

    public function getDetailsSection($id,$company_id)
    {
        //
        // try {

            $response = [];
            if( (INT)$company_id === 2) { // INDIGOFF
                $response = $this->webService->getInventoryRT();
            }
            if( (INT)$company_id === 4) { // INDIGOFF
                $response = $this->webService->getInventoryINDF();
            }
            if( (INT)$company_id === 5) { // INDIGOFF
                $response = $this->webService->getInventoryWRKS();
            }
            $DSection = DSection::select('d_sections.id','e_sections.status_id','d_sections.sku','c_inventory_products.product','d_sections.section','d_sections.projected',DB::raw('CASE WHEN d_add_section_requests.quantity != "" OR d_add_section_requests.quantity IS NOT NULL THEN d_add_section_requests.quantity ELSE 0 END AS add_quantity'))
            ->leftJoin('c_inventory_products', function($join) use($company_id){
                $join->on('c_inventory_products.sku', '=', 'd_sections.sku')
                    ->where('c_inventory_products.company_id', '=', $company_id);
            })
            ->leftJoin('d_add_section_requests','d_add_section_requests.detail_section_id', 'd_sections.id')
            ->join('e_sections','e_sections.id','d_sections.section_id')
            ->whereIn('e_sections.status_id',[1,2,3,4])
            ->where('e_sections.company_id',$company_id)
            ->get()
            ->toArray();
            $inventory = $this->discountSection($response->items,$DSection);
            //
            $ESection = ESection::select('e_sections.id','e_sections.user_id','e_sections.project','c_erp_info_users.short_name as user_name','e_sections.status_id','c_status_sections.status','c_status_sections.color_status','e_sections.detail','e_sections.quotation_id','e_sections.created_at')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_sections.user_id')
            ->join('c_status_sections','c_status_sections.id','e_sections.status_id')
            ->where('e_sections.id',$id)
            ->first()
            ->toArray();
            $DSection = DSection::select('d_sections.id','d_sections.sku','c_inventory_products.product',DB::raw('CASE WHEN d_add_section_requests.quantity != "" OR d_add_section_requests.quantity IS NOT NULL THEN d_add_section_requests.quantity + d_sections.section ELSE d_sections.section END AS section'),'d_sections.projected')
            ->leftJoin('c_inventory_products', function($join) use($company_id){
                $join->on('c_inventory_products.sku', '=', 'd_sections.sku')
                    ->where('c_inventory_products.company_id', '=', $company_id);
            })
            ->leftJoin('d_add_section_requests', 'd_add_section_requests.detail_section_id', 'd_sections.id')
            ->where('d_sections.section_id',$id)
            ->get()
            ->toArray();
            $section = $this->setIndividualSectionInv($ESection,$DSection,$inventory,$id);
            //
            return response()->json([
                'success'   => true,
                'section'   => $section,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 400);
        // }
    }

    // PRIVATE

    private function getSectionPerStatus($status_id,$company_id)
    {
        $ESection = ESection::select('e_sections.id','e_sections.user_id','e_sections.project','e_sections.company_id','c_companies.company','c_erp_info_users.short_name as user_name','e_sections.status_id','c_status_sections.status','c_status_sections.color_status','e_sections.detail','e_sections.quotation_id','e_sections.request_date','e_sections.material_request_date','e_sections.created_at')
        ->join('c_erp_info_users','c_erp_info_users.user_id','e_sections.user_id')
        ->join('c_status_sections','c_status_sections.id','e_sections.status_id')
        ->join('c_companies','c_companies.id','e_sections.company_id')
        ->where('e_sections.status_id',$status_id)
        ->get()
        ->toArray();
        $DSection = DSection::select('d_sections.id','d_sections.section_id','d_sections.sku','c_inventory_products.product','d_sections.section','d_sections.projected')
        ->leftJoin('c_inventory_products', function($join) use($company_id){
            $join->on('c_inventory_products.sku', '=', 'd_sections.sku')
            ->where('c_inventory_products.company_id', '=', $company_id);
        })
        ->join('e_sections','e_sections.id','d_sections.section_id')
        ->where('e_sections.status_id',$status_id)
        ->get()
        ->toArray();
        $sections = $this->setSections($ESection,$DSection);
        //
        return $sections;
    }

    private function discountSection($items,$sections) {
        foreach ($items as $key => $item) {
            $items[$key]->SECTION = 0;
            $items[$key]->PROJ = 0;
            $items[$key]->STOCK = (DOUBLE)$items[$key]->EXIST;
            foreach ($sections as $key2 => $section) {
                if( $item->CVE_ART == $section['sku'] ){
                    $items[$key]->STOCK = (DOUBLE)$items[$key]->STOCK - ((DOUBLE)$section['section']+(DOUBLE)$section['add_quantity']);
                    $items[$key]->SECTION = (DOUBLE)$items[$key]->SECTION + ((DOUBLE)$section['section']+(DOUBLE)$section['add_quantity']);
                    if( (INT)$section['status_id'] === 1 ) { $items[$key]->PROJ = (DOUBLE)$items[$key]->PROJ + (DOUBLE)$section['projected']; }
                }
            }
        }
        return $items;
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

    private function setIndividualSection($ESection,$DSection) {
        $ESection['details'] = [];
        foreach ($DSection as $key2 => $dSection) {
            $ESection['details'][] = $dSection;
        }
        return $ESection;
    }

    private function setIndividualSectionInv($ESection,$DSection,$inventory,$id) {
        $ESection['details'] = [];
        foreach ($DSection as $key => $dSection) {
            $DSection[$key]['EXIST'] = null;
            $DSection[$key]['STOCK'] = null;
            $DSection[$key]['SECTION'] = null;

            $found_key = array_search($dSection['sku'], array_column($inventory, 'CVE_ART'));

            $DSection[$key]['EXIST'] = (DOUBLE)$inventory[$found_key]->EXIST;
            $DSection[$key]['STOCK'] = (DOUBLE)$inventory[$found_key]->STOCK;
            $DSection[$key]['SECTION'] = (DOUBLE)$inventory[$found_key]->SECTION;
            $ESection['details'][] = $DSection[$key];
        }
        return $ESection;
    }
}
