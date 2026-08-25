<?php

namespace App\Http\Controllers;

use App\Models\CArticle;
use App\Models\DImagesArticle;
use App\Models\DQuotationDiscount;
use Illuminate\Http\Request;
use App\classes\WebService;
use Illuminate\Support\Facades\DB;

class CArticleController extends Controller
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
    public function index()
    {
        $articles = CArticle::select(
            'c_articles.*',
            'c_models.model',
            'c_units.unit',
            'c_products.product as categoria'
        )
        ->leftJoin('c_models','c_models.id','=','c_articles.model_id')
        ->leftJoin('c_units','c_units.id','=','c_articles.unit_id')
        ->leftJoin('c_products','c_products.id','=','c_models.product_id')
        ->where('c_articles.is_active', 1)
        ->get();

        return response()->json([
            "success" => true,
            "data" => $articles,
        ], 200);
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
        $article = new CArticle();
        $article->article = $request->article;
        $article->sku = $request->sku;
        $article->price = $request->price;
        $article->price_list_2 = $request->price_list_2;
        $article->cost = $request->cost;
        $article->unit_id = $request->unit_id;
        $article->model_id = $request->model_id;
        $article->is_active = 1;
        $article->save();

        return response()->json([
            "success" => true,
            "message" => "Artículo creado correctamente",
            "data" => $article
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CArticle  $cArticle
     * @return \Illuminate\Http\Response
     */
    public function show(CArticle $cArticle, $client_id = null)
    {
        $articlesIDs = [];
        $hasValidClient = !empty($client_id) && $client_id !== 'undefined' && is_numeric($client_id);

        $priceExpr = $hasValidClient
            ? DB::raw('CASE c_users.price_list_id WHEN 1 THEN c_articles.price WHEN 2 THEN c_articles.price_list_2 ELSE c_articles.price END AS price')
            : DB::raw('c_articles.price AS price');

        $query = CArticle::select(
            'c_articles.id','c_articles.article','c_articles.sku','c_articles.erp_id',
            'c_articles.model_id','c_models.model','c_models.product_id',
            'c_products.product as categoria',
            $priceExpr,
            'c_articles.price_list_2',
            'c_articles.cost', 'c_articles.width_min', 'c_articles.width_max',
            'c_articles.height_min','c_articles.height_max',
            'c_articles.cloth_discount','c_articles.width_inverted', 'c_articles.height_inverted',
            'c_articles.stock_lot','c_articles.unit_id','c_units.unit','c_articles.color_id',
            'c_articles.thumbnail','c_articles.is_inverted','c_articles.is_warranty_inverted',
            'c_articles.is_heat_seal','c_articles.lambrequin_price','c_articles.only_counterweight_id',
            'c_articles.is_control','c_articles.channels','c_articles.is_partner',
            'c_articles.is_active','c_articles.created_at'
        )
        ->join('c_models','c_models.id','=','c_articles.model_id')
        ->join('c_units','c_units.id','=','c_articles.unit_id')
        ->leftJoin('c_products','c_products.id','=','c_models.product_id');

        if ($hasValidClient) {
            $query->leftJoin('c_users','c_users.id','=',DB::raw($client_id));
        }

        $CArticle = $query->where('c_articles.is_view',1)
            ->get()
            ->toArray();

        foreach ($CArticle as $key => $article) {
            $CArticle[$key]['inventory'] = [];
        }

        foreach ($CArticle as $article) { $articlesIDs[] = $article['id']; }
        $DImagesArticle = DImagesArticle::whereIn('article_id',$articlesIDs)->get();
        $discountData = self::getDiscountData();
        $articles = self::setArticles($CArticle,$DImagesArticle->toArray(),$discountData);
        return response()->json([
            "success" => true,
            "articles" => $articles,
        ],200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CArticle  $cArticle
     * @return \Illuminate\Http\Response
     */
    public function edit(CArticle $cArticle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CArticle  $cArticle
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $id = $request->id;
        $article = CArticle::find($id);
        if (!$article) {
            return response()->json(["success" => false, "message" => "Artículo no encontrado"], 404);
        }
        $article->article = $request->article;
        $article->sku = $request->sku;
        $article->price = $request->price;
        $article->price_list_2 = $request->price_list_2;
        $article->cost = $request->cost;
        $article->unit_id = $request->unit_id;
        $article->model_id = $request->model_id;
        $article->save();

        return response()->json([
            "success" => true,
            "message" => "Artículo actualizado correctamente"
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $id = $request->id;
        $article = CArticle::find($id);
        if (!$article) {
            return response()->json(["success" => false, "message" => "Artículo no encontrado"], 404);
        }
        $article->is_active = 0;
        $article->save();

        return response()->json([
            "success" => true,
            "message" => "Artículo eliminado correctamente"
        ], 200);
    }

    public function getCatalogs()
    {
        $models = DB::table('c_models')->where('is_active', 1)->get();
        $units = DB::table('c_units')->where('is_active', 1)->get();
        $products = DB::table('c_products')->where('is_active', 1)->get();

        return response()->json([
            "success" => true,
            "models" => $models,
            "units" => $units,
            "products" => $products,
        ], 200);
    }

    private function setArticles($CArticle,$DImagesArticle,$discountData) {
        foreach ($CArticle as $key => $article) {
            if(is_null($article['thumbnail'])) { $CArticle[$key]['thumbnail'] = 'not-found.png'; }
            $CArticle[$key]['imagen_articles'] = [];
            $CArticle[$key]['discount'] = 0;

            $CArticle[$key]['articulo_id'] = $article['id'];
            $CArticle[$key]['articulo'] = $article['article'];
            $CArticle[$key]['modelo'] = $article['model'];
            $CArticle[$key]['product'] = $article['categoria'] ?? '';
            $CArticle[$key]['clave'] = $article['sku'];
            $CArticle[$key]['clave_almacen'] = $article['erp_id'] ?? '';
            $CArticle[$key]['precio'] = $article['price'];
            $CArticle[$key]['precio_lista_2'] = $article['price_list_2'];
            $CArticle[$key]['price_list_2'] = $article['price_list_2'];
            $CArticle[$key]['costo'] = $article['cost'];
            $CArticle[$key]['producto_id'] = $article['product_id'];
            $CArticle[$key]['ancho_min'] = $article['width_min'];
            $CArticle[$key]['ancho_max'] = $article['width_max'];
            $CArticle[$key]['alto_min'] = $article['height_min'];
            $CArticle[$key]['alto_max'] = $article['height_max'];
            $CArticle[$key]['descuento_tela'] = $article['cloth_discount'];
            $CArticle[$key]['invertido'] = $article['is_inverted'];
            $CArticle[$key]['invertido_ancho'] = $article['width_inverted'];
            $CArticle[$key]['invertido_alto'] = $article['height_inverted'];
            $CArticle[$key]['gar_invertido'] = $article['is_warranty_inverted'];
            $CArticle[$key]['termosellado'] = $article['is_heat_seal'];
            $CArticle[$key]['precio_galera'] = $article['lambrequin_price'];

            foreach ($DImagesArticle as $imgArticle) {
                if($article['id'] == $imgArticle['article_id']) {
                    $CArticle[$key]['imagen_articles'][] = $imgArticle;
                }
            }
        }
        return $CArticle;
    }

    private function getDiscountData() {
        $discountData = DQuotationDiscount::select()->get();
        return $discountData;
    }
}
