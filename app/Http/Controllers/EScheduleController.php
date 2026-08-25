<?php

namespace App\Http\Controllers;

use App\Models\CErpInfoUser;
use App\Models\CTypeNote;
use App\Models\CTypeSchedule;
use App\Models\CUser;
use App\Models\DClientsNote;
use App\Models\ESchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // try {
            // verificamos si es lider
            $userLider = CErpInfoUser::select('is_leader')->where('user_id',[$request->user_id])->first();
            //
            $clients = CUser::select('c_users.id','c_users.short_name','c_users.agent_id','c_erp_info_users.short_name as agent_name','c_users.full_name','c_users.user_email','c_users.user_img','c_users.company','c_users.lada','c_users.phone','c_users.details')
            ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','c_users.agent_id');
            if( (INT)$userLider->is_leader !== 1 ) { $clients->where('agent_id',$request->user_id); }
            $clients = $clients->get();
            //
            $schedules = $this->getSchedulAgents($request->user_id,$userLider->is_leader);
            //
            $typeSchedules = CTypeSchedule::select('id','type_schedule','type_schedule_icon','type_schedule_color','is_reschedule')->where('is_active',1)->get();
            return response()->json([
                'success'       => true,
                'clients'       => $clients,
                'schedules'     => $schedules,
                'typeSchedules' => $typeSchedules,
                'is_leader'     => $userLider->is_leader,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'message' =>  'Error en sistema CDG-001-236',
        //         'error'   =>  $th,
        //     ], 403);
        // }
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
        // try {
            $ESchedule = new ESchedule;
            $ESchedule->user_id             = $request->user_id;
            $ESchedule->client_id           = $request->client_id;
            $ESchedule->name_activity       = $request->name_activity;
            $ESchedule->type_schedules_id   = $request->type_schedules_id;
            $ESchedule->start               = $request->start;
            $ESchedule->end                 = $request->end;
            $ESchedule->detail              = $request->detail;
            if( (INT)$request->is_lead === 1) {
                $ESchedule->is_lead         = $request->is_lead;
                $ESchedule->lead_id         = $request->lead_id;
            }
            $ESchedule->save();
            if( (INT)$request->is_lead === 1) { $schedule = $this->getIndividualScheduleLead($ESchedule->id); } else { $schedule = $this->getIndividualSchedule($request->client_id,$ESchedule->id); }
            return response()->json([
                'success'   => true,
                'schedule'  =>  $schedule,
                'opt'  =>  $request->opt,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 400);
        // }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ESchedule  $eSchedule
     * @return \Illuminate\Http\Response
     */
    public function show(ESchedule $eSchedule,$client_id)
    {
        // try {
            // CLIENT
            $client = CUser::select('id',DB::raw("CASE WHEN c_users.short_name IS NULL OR c_users.short_name = '' THEN c_users.full_name ELSE c_users.short_name END AS client_name"),'agent_id','user_email','user_img','company','lada','phone','details')
            ->where('id',$client_id)
            ->first();
            // NOTES
            $clientNotes = DClientsNote::select('d_clients_notes.id','d_clients_notes.note', 'd_clients_notes.created_at', 'd_clients_notes.is_active', 'd_clients_notes.client_id', 'd_clients_notes.updated_at','d_clients_notes.type_note_id','c_type_notes.type_note','c_type_notes.color_type_note', 'd_clients_notes.user_id', 'c_erp_info_users.short_name', 'c_erp_info_users.email as user_email', 'c_erp_info_users.user_img', 'c_erp_info_users.color as color_user')
            ->join('c_erp_info_users','c_erp_info_users.user_id','d_clients_notes.user_id')
            ->join('c_type_notes','c_type_notes.id','d_clients_notes.type_note_id')
            ->where('d_clients_notes.client_id', $client_id)
            ->orderBy('d_clients_notes.id', 'DESC')
            ->get();

            // 3 LAST NOTES
            $client3LastNotes = DClientsNote::select('d_clients_notes.id','d_clients_notes.note', 'd_clients_notes.created_at', 'd_clients_notes.is_active', 'd_clients_notes.client_id', 'd_clients_notes.updated_at','d_clients_notes.type_note_id','c_type_notes.type_note','c_type_notes.color_type_note', 'd_clients_notes.user_id', 'c_erp_info_users.short_name', 'c_erp_info_users.email as user_email', 'c_erp_info_users.user_img', 'c_erp_info_users.color as color_user')
            ->join('c_erp_info_users','c_erp_info_users.user_id','d_clients_notes.user_id')
            ->join('c_type_notes','c_type_notes.id','d_clients_notes.type_note_id')
            ->where('d_clients_notes.client_id', $client_id)
            ->orderBy('d_clients_notes.id', 'DESC')
            ->limit(3)
            ->get();
            $beforeActivity = $this->getScheduleTime($client_id,2);
            //
            $typeNotes = CTypeNote::select('id','type_note','color_type_note')->where('is_active',1)->get();
            return response()->json([
                'success'               => true,
                'client3LastNotes'      => $client3LastNotes,
                'beforeActivity'        => $beforeActivity,
                'client'                => $client,
                'clientNotes'           => $clientNotes,
                'typeNotes'             => $typeNotes,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'message' =>  'Error en sistema CDG-001-236',
        //         'error'   =>  $th,
        //     ], 403);
        // }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ESchedule  $eSchedule
     * @return \Illuminate\Http\Response
     */
    public function edit(ESchedule $eSchedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESchedule  $eSchedule
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESchedule $eSchedule, $id, $is_lead)
    {

        try {
            $eSchedule::where('id',$id)
            ->update([
                "name_activity"     => $request->name_activity,
                "type_schedules_id" => $request->type_schedules_id,
                "start"             => $request->start,
                "end"               => $request->end,
                "detail"            => $request->detail,
            ]);
            if( (INT)$request->is_lead === 1) { $schedule = $this->getIndividualScheduleLead($id); } else {  $schedule = $this->getIndividualSchedule($request->client_id,$id); }
            return response()->json([
                'success'   => true,
                'schedule'  =>  $schedule,
                'id'        =>  $id,
                'opt'       =>  $request->opt,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }

    public function cancel(Request $request, ESchedule $eSchedule, $id)
    {
        try {
            $eSchedule::where('id',$id)
            ->update([
                "cancellation_description"  => $request->cancellation_description,
                'is_active'                 => 0,
            ]);
            return response()->json([
                'success'   => true,
                'id'        =>  $id,
                'opt'       =>  $request->opt,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ESchedule  $eSchedule
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESchedule $eSchedule)
    {
        //
    }

    public function saveOptionClient(Request $request)
    {
        try {
            CUser::where('id',$request->client['id'])
            ->update([
                "user_email"    => $request->client['user_email'],
                'phone'         => $request->client['phone'],
                'details'       => $request->client['details'],
            ]);
            return response()->json([
                'success'   => true,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }

    // PRIVATE

    private function getSchedule($client_id)
    {
        $ESchedule = ESchedule::select('e_schedules.id','e_schedules.client_id','c_users.agent_id',DB::raw("CASE WHEN c_users.short_name IS NULL OR c_users.short_name = '' THEN c_users.full_name ELSE c_users.short_name END AS client_name"),'e_schedules.name_activity AS name','c_erp_info_users.id as agent_id','c_erp_info_users.short_name as name_agent','e_schedules.type_schedules_id','c_type_schedules.type_schedule','c_type_schedules.type_schedule_icon','c_type_schedules.type_schedule_color AS color','e_schedules.detail',DB::raw('CASE WHEN e_schedules.start IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.start END AS start, CASE WHEN e_schedules.end IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.end END AS end'),'e_schedules.user_address_id','e_schedules.schedule_status_id', 'c_status_schedules.schedule_status','c_status_schedules.schedule_status_color','e_schedules.lead_id','e_leads.contact','e_leads.company','e_schedules.is_lead','e_schedules.created_at','e_schedules.cancellation_description')
        ->leftJoin('c_users','c_users.id','e_schedules.client_id')
        ->join('c_status_schedules','c_status_schedules.id','e_schedules.schedule_status_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_schedules.user_address_id')
        ->join('c_type_schedules','c_type_schedules.id','e_schedules.type_schedules_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_schedules.user_id')
        ->leftJoin('e_leads','e_leads.id','e_lead_schedules.lead_id')
        ->where('e_schedules.is_active','1')
        ->orderBy('start', 'asc')
        ->where('client_id',$client_id)
        ->get();

        return $ESchedule;
    }

    // PRIVATE
    private function getIndividualScheduleLead($id)
    {

        $ELeadSchedule = ESchedule::select('e_schedules.id','e_schedules.lead_id','e_leads.agent_id','e_leads.contact','e_schedules.name_activity AS name','c_erp_info_users.id as agent_id','c_erp_info_users.short_name as name_agent','e_schedules.type_schedules_id','c_type_schedules.type_schedule','c_type_schedules.type_schedule_icon','c_type_schedules.type_schedule_color AS color','e_schedules.detail',DB::raw('CASE WHEN e_schedules.start IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.start END AS start, CASE WHEN e_schedules.end IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.end END AS end'),'e_schedules.schedule_status_id', 'c_status_schedules.schedule_status','c_status_schedules.schedule_status_color','e_schedules.lead_id','e_leads.contact','e_leads.company','e_schedules.is_lead','e_schedules.created_at','e_schedules.cancellation_description')
        ->join('e_leads','e_leads.id','e_schedules.lead_id')
        ->join('c_status_schedules','c_status_schedules.id','e_schedules.schedule_status_id')
        ->join('c_type_schedules','c_type_schedules.id','e_schedules.type_schedules_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_schedules.user_id')
        ->where('e_schedules.id',$id)
        ->orderBy('start', 'asc')
        ->first();

        return $ELeadSchedule;
    }

    private function getSchedulAgents($user_id,$is_leader)
    {
        $ESchedule = ESchedule::select('e_schedules.id','e_schedules.client_id',DB::raw(' CASE WHEN e_schedules.is_lead = 1 THEN aglead.user_id ELSE ag.user_id END AS agent_id , CASE WHEN e_schedules.is_lead = 1 THEN aglead.short_name ELSE ag.short_name END AS agent_name'),DB::raw("CASE WHEN c_users.short_name IS NULL OR c_users.short_name = '' THEN c_users.full_name ELSE c_users.short_name END AS client_name"),'e_schedules.name_activity AS name','c_erp_info_users.id as created_agent_id','c_erp_info_users.short_name as name_create','e_schedules.type_schedules_id','c_type_schedules.type_schedule','c_type_schedules.type_schedule_icon','c_type_schedules.type_schedule_color AS color','e_schedules.detail',DB::raw('CASE WHEN e_schedules.start IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.start END AS start, CASE WHEN e_schedules.end IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.end END AS end'),'e_schedules.user_address_id','e_schedules.schedule_status_id', 'c_status_schedules.schedule_status','c_status_schedules.schedule_status_color','e_schedules.lead_id','e_leads.contact','e_leads.company','e_schedules.is_lead','e_schedules.created_at','e_schedules.cancellation_description')
        ->leftJoin('c_users','c_users.id','e_schedules.client_id')
        ->join('c_status_schedules','c_status_schedules.id','e_schedules.schedule_status_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_schedules.user_address_id')
        ->join('c_type_schedules','c_type_schedules.id','e_schedules.type_schedules_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_schedules.user_id')
        ->leftJoin('c_erp_info_users AS ag','ag.user_id','c_users.agent_id')
        ->leftJoin('e_leads','e_leads.id','e_schedules.lead_id')
        ->leftJoin('c_erp_info_users AS aglead','ag.user_id','e_leads.agent_id')
        ->where('e_schedules.is_active','1')
        ->orderBy('start', 'asc');
        if( (INT)$is_leader !== 1 ) { $ESchedule->where('e_schedules.user_id',$user_id); }
        $ESchedule = $ESchedule->get();

        return $ESchedule;
    }

    private function getIndividualSchedule($client_id,$id)
    {

        $ESchedule = ESchedule::select('e_schedules.id','e_schedules.client_id','c_users.agent_id',DB::raw("CASE WHEN c_users.short_name IS NULL OR c_users.short_name = '' THEN c_users.full_name ELSE c_users.short_name END AS client_name"),'e_schedules.name_activity AS name','c_erp_info_users.id as agent_id','c_erp_info_users.short_name as name_agent','e_schedules.type_schedules_id','c_type_schedules.type_schedule','c_type_schedules.type_schedule_icon',DB::raw(' CASE WHEN e_schedules.client_id = '.$client_id.' THEN c_type_schedules.type_schedule_color ELSE "#c5c5c5" END AS color'),'e_schedules.detail',DB::raw('CASE WHEN e_schedules.start IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.start END AS start, CASE WHEN e_schedules.end IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.end END AS end'),'e_schedules.user_address_id','e_schedules.schedule_status_id', 'c_status_schedules.schedule_status','c_status_schedules.schedule_status_color','e_schedules.lead_id','e_schedules.is_lead','e_schedules.created_at','e_schedules.cancellation_description')
        ->leftJoin('c_users','c_users.id','e_schedules.client_id')
        ->join('c_status_schedules','c_status_schedules.id','e_schedules.schedule_status_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_schedules.user_address_id')
        ->join('c_type_schedules','c_type_schedules.id','e_schedules.type_schedules_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_schedules.user_id')
        ->where('e_schedules.id',$id)
        ->orderBy('start', 'asc')
        ->first();

        return $ESchedule;
    }

    private function getScheduleTime($client_id,$opt)
    {
        $dateNow = Carbon::now()->toDateTimeString();
        $ESchedule = ESchedule::select('e_schedules.id','e_schedules.client_id','c_users.agent_id',DB::raw("CASE WHEN c_users.short_name IS NULL OR c_users.short_name = '' THEN c_users.full_name ELSE c_users.short_name END AS client_name"),'e_schedules.name_activity AS name','c_erp_info_users.id as agent_id','c_erp_info_users.short_name as name_agent','e_schedules.type_schedules_id','c_type_schedules.type_schedule','c_type_schedules.type_schedule_icon',DB::raw(' CASE WHEN e_schedules.client_id = '.$client_id.' THEN c_type_schedules.type_schedule_color ELSE "#c5c5c5" END AS color'),'e_schedules.detail',DB::raw('CASE WHEN e_schedules.start IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.start END AS start, CASE WHEN e_schedules.end IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.end END AS end'),'e_schedules.user_address_id','e_schedules.schedule_status_id', 'c_status_schedules.schedule_status','c_status_schedules.schedule_status_color','e_schedules.lead_id','e_leads.contact','e_leads.company','e_schedules.created_at','e_schedules.cancellation_description')
        ->leftJoin('c_users','c_users.id','e_schedules.client_id')
        ->join('c_status_schedules','c_status_schedules.id','e_schedules.schedule_status_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_schedules.user_address_id')
        ->join('c_type_schedules','c_type_schedules.id','e_schedules.type_schedules_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_schedules.user_id')
        ->leftJoin('e_leads','e_leads.id','e_schedules.lead_id');
        if( (INT)$opt === 1) {
            $ESchedule = $ESchedule->where('e_schedules.is_active','1')
            ->where('e_schedules.end','<=',$dateNow)
            ->where('e_schedules.client_id',$client_id)
            ->orderBy('end', 'asc')
            ->limit(3)
            ->get();
        } else {
            $ESchedule = $ESchedule->where('e_schedules.is_active','1')
            ->where('e_schedules.start','>=',$dateNow)
            ->where('e_schedules.client_id',$client_id)
            ->orderBy('start', 'asc')
            ->limit(3)
            ->get();
        }

        return $ESchedule;
    }

}
