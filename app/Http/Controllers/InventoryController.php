<?php

namespace App\Http\Controllers;

use App\classes\WebService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    protected $webService;
    public function __construct(WebService $webService)
    {
        $this->webService = $webService;
    }
}
