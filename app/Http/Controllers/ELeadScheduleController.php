<?php

namespace App\Http\Controllers;

use App\Models\ELeadSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ELeadScheduleController extends Controller
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
            $ELeadSchedule = new ELeadSchedule;
            $ELeadSchedule->user_id             = $request->user_id;
            $ELeadSchedule->lead_id             = $request->lead_id;
            $ELeadSchedule->name_activity       = $request->name_activity;
            $ELeadSchedule->type_schedules_id   = $request->type_schedules_id;
            $ELeadSchedule->start               = $request->start;
            $ELeadSchedule->end                 = $request->end;
            $ELeadSchedule->detail              = $request->detail;
            $ELeadSchedule->save();
            $schedule = $this->getIndividualSchedule($ELeadSchedule->id);
            return response()->json([
                'success'   => true,
                'schedule'  =>  $schedule,
                'opt'  =>  $request->opt,
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
     * @param  \App\Models\ELeadSchedule  $eLeadSchedule
     * @return \Illuminate\Http\Response
     */
    public function show(ELeadSchedule $eLeadSchedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ELeadSchedule  $eLeadSchedule
     * @return \Illuminate\Http\Response
     */
    public function edit(ELeadSchedule $eLeadSchedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ELeadSchedule  $eLeadSchedule
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ELeadSchedule $eLeadSchedule, $id)
    {
        try {
            $eLeadSchedule::where('id',$id)
            ->update([
                "name_activity"     => $request->name_activity,
                "type_schedules_id" => $request->type_schedules_id,
                "start"             => $request->start,
                "end"               => $request->end,
                "detail"            => $request->detail,
            ]);
            $schedule = $this->getIndividualSchedule($id);
            return response()->json([
                'success'   => true,
                'schedule'  =>  $schedule,
                'id'        =>  $id,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }

    public function cancel(Request $request, ELeadSchedule $ELeadSchedule, $id)
    {
        try {
            $ELeadSchedule::where('id',$id)
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
     * @param  \App\Models\ELeadSchedule  $eLeadSchedule
     * @return \Illuminate\Http\Response
     */
    public function destroy(ELeadSchedule $eLeadSchedule)
    {
        //
    }

    // PRIVATE

    private function getIndividualSchedule($id)
    {

        $ELeadSchedule = ELeadSchedule::select('e_lead_schedules.id','e_lead_schedules.lead_id','e_leads.agent_id','e_leads.contact','e_lead_schedules.name_activity AS name','c_erp_info_users.id as agent_id','c_erp_info_users.short_name as name_agent','e_lead_schedules.type_schedules_id','c_type_schedules.type_schedule','c_type_schedules.type_schedule_icon','c_type_schedules.type_schedule_color AS color','e_lead_schedules.detail',DB::raw('CASE WHEN e_lead_schedules.start IS NULL THEN "1986-07-28 03:45:00" ELSE e_lead_schedules.start END AS start, CASE WHEN e_lead_schedules.end IS NULL THEN "1986-07-28 03:45:00" ELSE e_lead_schedules.end END AS end'),'e_lead_schedules.schedule_status_id', 'c_status_schedules.schedule_status','c_status_schedules.schedule_status_color','e_lead_schedules.created_at','e_lead_schedules.cancellation_description')
        ->join('e_leads','e_leads.id','e_lead_schedules.lead_id')
        ->join('c_status_schedules','c_status_schedules.id','e_lead_schedules.schedule_status_id')
        ->join('c_type_schedules','c_type_schedules.id','e_lead_schedules.type_schedules_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_lead_schedules.user_id')
        ->where('e_lead_schedules.id',$id)
        ->orderBy('start', 'asc')
        ->first();

        return $ELeadSchedule;
    }
}
