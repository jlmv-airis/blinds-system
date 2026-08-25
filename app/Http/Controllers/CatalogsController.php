<?php

namespace App\Http\Controllers;

use App\Models\CArticle;
use App\Models\CChain;
use App\Models\CColor;
use App\Models\CConfigMotors;
use App\Models\CCounterweightBar;
use App\Models\CDeliveryType;
use App\Models\CErpInfoUser;
use App\Models\CGuaranteeError;
use App\Models\CGuaranteeTypeError;
use App\Models\CMechanism;
use App\Models\CMechanismSide;
use App\Models\COperation;
use App\Models\CPaymentMethod;
use App\Models\CPaymentOption;
use App\Models\CProduct;
use App\Models\CTube;
use App\Models\CTypeGuarantee;
use App\Models\CTypeMotor;
use App\Models\CUnit;
use App\Models\CUser;
use App\Models\DImagesArticle;
use App\Models\DQuotationDiscount;
use Illuminate\Http\Request;

class CatalogsController extends Controller
{
    //
    public function getCatalogsQuotation(Request $request) {
        try {
            $clients = CUser::get();
            $isLeader = CErpInfoUser::select('is_leader')->where('user_id',$request->user_id)->first();
            $paymentMethods = CPaymentMethod::where('is_active',1)->get();
            $paymentOptions = CPaymentOption::where('is_active',1)->get();
            $deliveryTypes = CDeliveryType::where('is_active',1)->get();
            $mechanismSides = CMechanismSide::where('is_active',1)->where('is_panel',1)->get();
            return response()->json([
                'success'        => true,
                'clients'        => $clients,
                'paymentMethods' => $paymentMethods,
                'paymentOptions' => $paymentOptions,
                'deliveryTypes'  => $deliveryTypes,
                'isLeader'       => $isLeader->is_leader,
                'mechanismSides'    => $mechanismSides,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'message' =>  'Error en sistema CDG-001-236',
                'error'   =>  $th,
            ], 400);
        }
    }

    public function getCatalogsRecordsQuotation() {
        try {
            $products = CProduct::where('is_active',1)->where('is_view',1)->get();
            $colors = CColor::where('is_active',1)->get();
            $operations = COperation::where('is_active',1)->get();
            $chains = CChain::where('is_active',1)->get();
            $typeMotors = CTypeMotor::select('c_type_motors.*','c_tubes.inches')->join('c_tubes','c_tubes.id','c_type_motors.tube_id')->where('c_type_motors.is_active',1)->get();
            $configMotors = CConfigMotors::select('id','article_id','type_motor_id')->where('is_active',1)->get();
            $mechanismSides = CMechanismSide::where('is_active',1)->where('is_panel',1)->get();
            $counterweightBars = CCounterweightBar::where('is_active',1)->get();
            $tubes = CTube::where('is_active',1)->get();
            $mechanisms = CMechanism::where('is_active',1)->get();
            $CArticle = CArticle::select('c_articles.id','c_articles.article','c_articles.sku','c_articles.erp_id','c_articles.model_id','c_models.model','c_models.product_id','c_articles.price','c_articles.cost','c_articles.unit_id','c_units.unit','c_articles.color_id')
            ->where('c_articles.is_active',1)->where('c_articles.is_cassette',1)
            ->join('c_models','c_models.id','c_articles.model_id')
            ->join('c_units','c_units.id','c_articles.unit_id')
            ->get();
            $cassettes = self::setDiscount($CArticle);
            $CArticleMotors = CArticle::select('c_articles.id','c_articles.article','c_articles.sku','c_articles.erp_id','c_articles.model_id','c_models.model','c_models.product_id','c_articles.price','c_articles.cost','c_articles.unit_id','c_units.unit','c_articles.color_id','c_config_motors.type_motor_id','c_config_motors.num_divisions','c_config_motors.width_min','c_config_motors.width_max','c_config_motors.height_max','c_config_motors.se_width_min','c_config_motors.se_width_max','c_config_motors.se_height_max','c_config_motors.mm','c_config_motors.newtons','c_config_motors.is_cassette')
            ->where('c_articles.is_active',1)
            ->where('c_articles.is_motor',1)
            ->join('c_models','c_models.id','c_articles.model_id')
            ->join('c_units','c_units.id','c_articles.unit_id')
            ->join('c_config_motors','c_config_motors.article_id','c_articles.id')
            ->get();
            $motors = self::setDiscount($CArticleMotors);
            $CArticleLamberquin = CArticle::select('c_articles.id','c_articles.article','c_articles.sku','c_articles.erp_id','c_articles.model_id','c_models.model','c_models.product_id','c_articles.cost','c_articles.unit_id','c_units.unit','c_articles.color_id')
            ->where('c_articles.is_active',1)->where('c_articles.is_lamberquin',1)
            ->join('c_models','c_models.id','c_articles.model_id')
            ->join('c_units','c_units.id','c_articles.unit_id')
            ->first()
            ->toArray();
            $lamberquin = self::setIndividualDiscount($CArticleLamberquin);
            $units = CUnit::where('is_active',1)->get();
            return response()->json([
                'success'           => true,
                'products'          => $products,
                'colors'            => $colors,
                'operations'        => $operations,
                'chains'            => $chains,
                'typeMotors'        => $typeMotors,
                'configMotors'      => $configMotors,
                'mechanismSides'    => $mechanismSides,
                'counterweightBars' => $counterweightBars,
                'cassettes'         => $cassettes,
                'motors'            => $motors,
                'lamberquin'        => $lamberquin,
                'tubes'             => $tubes,
                'mechanisms'        => $mechanisms,
                'units'             => $units,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'message' =>  'Error en sistema CDG-001-236',
                'error'   =>  $th,
            ], 400);
        }
    }


    public function getCatalogsGuarantees(Request $request) {
        // try {
            $clients = CUser::get();
            $typeGuarantee = CTypeGuarantee::where('is_active',1)->get();
            $guaranteeErrors = CGuaranteeError::where('is_active',1)->get();
            $guaranteeTypeErrors = CGuaranteeTypeError::where('is_active',1)->get();
            $isLeader = CErpInfoUser::select('is_leader')->where('user_id',$request->user_id)->first();
            $deliveryTypes = CDeliveryType::where('is_active',1)->get();
            // ARTICLES
            $articlesIDs = [];
            //Buscamos los atticulos
            $CArticle = CArticle::select('c_articles.id','c_articles.article','c_articles.sku','c_articles.erp_id','c_articles.model_id','c_models.model','c_models.product_id','c_articles.price','c_articles.cost', 'width_min', 'width_max','c_articles.height_min','c_articles.height_max','c_articles.cloth_discount','c_articles.width_inverted', 'height_inverted','c_articles.stock_lot','c_articles.unit_id','c_units.unit','c_articles.color_id','c_articles.thumbnail','c_articles.is_inverted','c_articles.is_warranty_inverted','c_articles.is_heat_seal','c_articles.lambrequin_price','c_articles.only_counterweight_id','c_articles.is_partner','c_articles.is_active','c_articles.created_at')
            ->join('c_models','c_models.id','c_articles.model_id')
            ->join('c_units','c_units.id','c_articles.unit_id')
            ->where('is_view',1)
            ->get()
            ->toArray();
            // guardamos el id del articulo en un array
            foreach ($CArticle as $article) { $articlesIDs[] = $article['id']; }
            $DImagesArticle = DImagesArticle::whereIn('article_id',$articlesIDs)->get();
            $articles = self::setArticles($CArticle,$DImagesArticle->toArray());
            return response()->json([
                'success'              => true,
                'clients'              => $clients,
                'typeGuarantee'        => $typeGuarantee,
                'guaranteeErrors'      => $guaranteeErrors,
                'guaranteeTypeErrors'  => $guaranteeTypeErrors,
                'isLeader'             => $isLeader['is_leader'],
                'deliveryTypes'        => $deliveryTypes,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 400);
        // }
    }

    private function setDiscount($CArticle) {
        foreach ($CArticle as $key => $article) {
            // Obtener descuento del articulo mediante $discountData temporalmente le agregamos 0 descuento
            $CArticle[$key]['discount'] = 0;
        }
        return $CArticle;
    }

    private function setIndividualDiscount($CArticle) {
        // Obtener descuento del articulo mediante $discountData temporalmente le agregamos 0 descuento
        $CArticle['discount'] = 0;
        return $CArticle;
    }

    private function setArticles($CArticle,$DImagesArticle) {
        foreach ($CArticle as $key => $article) {
            if(is_null($article['thumbnail'])) { $CArticle[$key]['thumbnail'] = 'not-found.png'; }
            $CArticle[$key]['imagen_articles'] = [];
            foreach ($DImagesArticle as $key => $imgArticle) {
                if($article['id'] == $imgArticle['article_id']) {
                    // Obtener descuento de la partida mediante $discountData temporalmente le agregamos 0 descuento
                    $CArticle[$key]['imagen_articles'][] = $imgArticle;
                }
            }
        }
        return $CArticle;
    }


}
