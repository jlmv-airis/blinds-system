<?php

namespace App\Http\Controllers;

use App\Models\DLeadsNote;
use Illuminate\Http\Request;

class DLeadsNoteController extends Controller
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
            $DLeadsNote = new DLeadsNote();
            $DLeadsNote->lead_id = $request->lead_id;
            $DLeadsNote->user_id = $request->user_id;
            $DLeadsNote->note    = $request->entryNote;
            $DLeadsNote->save();
            $leadsNote = DLeadsNote::select('d_leads_notes.id','d_leads_notes.note', 'd_leads_notes.created_at', 'd_leads_notes.is_active', 'd_leads_notes.lead_id', 'd_leads_notes.updated_at', 'd_leads_notes.user_id', 'c_erp_info_users.short_name', 'c_erp_info_users.email as user_email', 'c_erp_info_users.user_img', 'c_erp_info_users.color as color_user')
            ->join('c_erp_info_users','c_erp_info_users.user_id','d_leads_notes.user_id')
            ->where('d_leads_notes.id', $DLeadsNote->id)
            ->first();
            return response()->json([
                'success'  => true,
                'leadsNote'  => $leadsNote,
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
     * @param  \App\Models\DLeadsNote  $dLeadsNote
     * @return \Illuminate\Http\Response
     */
    public function show(DLeadsNote $dLeadsNote)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DLeadsNote  $dLeadsNote
     * @return \Illuminate\Http\Response
     */
    public function edit(DLeadsNote $dLeadsNote)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DLeadsNote  $dLeadsNote
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DLeadsNote $dLeadsNote)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DLeadsNote  $dLeadsNote
     * @return \Illuminate\Http\Response
     */
    public function destroy(DLeadsNote $dLeadsNote, $id)
    {
        try {
            $dLeadsNote::where('id',$id)->delete();
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
