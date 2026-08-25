<?php

namespace App\Http\Controllers;

use App\Models\DClientsNote;
use Illuminate\Http\Request;

class DClientsNoteController extends Controller
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
            $DClientsNote = new DClientsNote();
            $DClientsNote->client_id = $request->client_id;
            $DClientsNote->user_id = $request->user_id;
            $DClientsNote->type_note_id = $request->type_note_id;
            $DClientsNote->note    = $request->entryNote;
            if( (INT)$request->is_lead === 1) {
                $DClientsNote->is_lead         = $request->is_lead;
                $DClientsNote->lead_id         = $request->lead_id;
            }
            $DClientsNote->save();
            $clientNote = DClientsNote::select('d_clients_notes.id','d_clients_notes.note', 'd_clients_notes.created_at', 'd_clients_notes.is_active', 'd_clients_notes.client_id', 'd_clients_notes.updated_at','d_clients_notes.type_note_id','c_type_notes.type_note','c_type_notes.color_type_note', 'd_clients_notes.user_id', 'c_erp_info_users.short_name', 'c_erp_info_users.email as user_email', 'c_erp_info_users.user_img', 'c_erp_info_users.color as color_user', 'd_clients_notes.is_lead', 'd_clients_notes.lead_id')
            ->join('c_erp_info_users','c_erp_info_users.user_id','d_clients_notes.user_id')
            ->join('c_type_notes','c_type_notes.id','d_clients_notes.type_note_id')
            ->where('d_clients_notes.id', $DClientsNote->id)
            ->first();
            return response()->json([
                'success'  => true,
                'clientNote'  => $clientNote,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DClientsNote  $dClientsNote
     * @return \Illuminate\Http\Response
     */
    public function show(DClientsNote $dClientsNote)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DClientsNote  $dClientsNote
     * @return \Illuminate\Http\Response
     */
    public function edit(DClientsNote $dClientsNote)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DClientsNote  $dClientsNote
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DClientsNote $dClientsNote)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DClientsNote  $dClientsNote
     * @return \Illuminate\Http\Response
     */
    public function destroy(DClientsNote $dClientsNote, $id)
    {
        try {
            $dClientsNote::where('id',$id)->delete();
            return response()->json([
                'success'   => true,
                'id'        => $id,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }
}
