<?php

namespace App\Http\Controllers;

use App\Models\CUserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CUserAddressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        try {
            $CUserAddress = new CUserAddress();
            $CUserAddress->user_id        = $request->client_id;
            $CUserAddress->cp             = $request->cp;
            $CUserAddress->street         = $request->street;
            $CUserAddress->ext            = $request->noExt;
            $CUserAddress->int            = $request->noInt;
            $CUserAddress->suburb         = $request->suburb;
            $CUserAddress->city           = $request->city;
            $CUserAddress->state          = $request->state;
            $CUserAddress->lat            = $request->lat;
            $CUserAddress->lng            = $request->lng;
            $CUserAddress->reference      = $request->reference;
            $CUserAddress->address_google = $request->address_google;
            $CUserAddress->save();
            $clientAddress = CUserAddress::select('*',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"))->where('id',$CUserAddress->id)->first();
            return response()->json([
                'success'       =>  true ,
                'clientAddress' =>  $clientAddress,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false ,
                'error'   => $th
            ], 200);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CUserAddress  $cUserAddress
     * @return \Illuminate\Http\Response
     */
    public function show(CUserAddress $cUserAddress, $client_id)
    {
        
        try {
            $clientAddresses = CUserAddress::select('*',DB::raw("CONCAT(c_user_addresses.street,' #',c_user_addresses.ext, CASE WHEN c_user_addresses.`int` IS NOT NULL THEN CONCAT(' int. ',c_user_addresses.`int`) ELSE '' END,' CP. ',c_user_addresses.cp,', ',c_user_addresses.suburb,', ',c_user_addresses.city,', ',c_user_addresses.state) AS address"))->where('user_id',$client_id)->get();
            return response()->json([
                'success'  => true,
                'clientAddresses'  => $clientAddresses,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CUserAddress  $cUserAddress
     * @return \Illuminate\Http\Response
     */
    public function edit(CUserAddress $cUserAddress)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CUserAddress  $cUserAddress
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CUserAddress $cUserAddress)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CUserAddress  $cUserAddress
     * @return \Illuminate\Http\Response
     */
    public function destroy(CUserAddress $cUserAddress)
    {
        //
    }
}
