<?php

namespace App\Http\Controllers;

use App\Models\CErpInfoUser;
use App\Models\CRejectionReason;
use App\Models\CStatusLead;
use App\Models\CTypeNote;
use App\Models\CTypeSchedule;
use App\Models\CUser;
use App\Models\DClientsNote;
use App\Models\DLeadsNote;
use App\Models\ELead;
use App\Models\ELeadSchedule;
use App\Models\ESchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ELeadController extends Controller
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
            $leads = ELead::select('e_leads.id','e_leads.agent_id','c_erp_info_users.short_name AS agent_name','c_erp_info_users.email AS agent_email','c_erp_info_users.user_img AS agent_img','c_erp_info_users.color AS agent_color','e_leads.contact','e_leads.company','e_leads.company_details','e_leads.title','e_leads.expected_closing_date','e_leads.status_lead_id','c_status_leads.status','c_status_leads.color_status','e_leads.estimated_value','e_leads.phone','e_leads.email','e_leads.phone2','e_leads.email2')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_leads.agent_id')
            ->join('c_status_leads','c_status_leads.id','e_leads.status_lead_id');
            if( (INT)$userLider->is_leader !== 1 ) { $leads->where('e_leads.agent_id',$request->agent_id); }
            $leads = $leads->where('e_leads.status_lead_id',1)
            ->where('e_leads.finish_lead',0)
            ->get();
            $contacsLeads = ELead::select('e_leads.id','e_leads.agent_id','c_erp_info_users.short_name AS agent_name','c_erp_info_users.email AS agent_email','c_erp_info_users.user_img AS agent_img','c_erp_info_users.color AS agent_color','e_leads.contact','e_leads.company','e_leads.company_details','e_leads.title','e_leads.expected_closing_date','e_leads.status_lead_id','c_status_leads.status','c_status_leads.color_status','e_leads.estimated_value','e_leads.phone','e_leads.email','e_leads.phone2','e_leads.email2')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_leads.agent_id')
            ->join('c_status_leads','c_status_leads.id','e_leads.status_lead_id');
            if( (INT)$userLider->is_leader !== 1 ) { $contacsLeads->where('e_leads.agent_id',$request->agent_id); }
            $contacsLeads = $contacsLeads->where('e_leads.status_lead_id',2)
            ->where('e_leads.finish_lead',0)
            ->get();
            $quotationLeads = ELead::select('e_leads.id','e_leads.agent_id','c_erp_info_users.short_name AS agent_name','c_erp_info_users.email AS agent_email','c_erp_info_users.user_img AS agent_img','c_erp_info_users.color AS agent_color','e_leads.contact','e_leads.company','e_leads.company_details','e_leads.title','e_leads.expected_closing_date','e_leads.status_lead_id','c_status_leads.status','c_status_leads.color_status','e_leads.estimated_value','e_leads.phone','e_leads.email','e_leads.phone2','e_leads.email2')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_leads.agent_id')
            ->join('c_status_leads','c_status_leads.id','e_leads.status_lead_id');
            if( (INT)$userLider->is_leader !== 1 ) { $quotationLeads->where('e_leads.agent_id',$request->agent_id); }
            $quotationLeads = $quotationLeads->where('e_leads.status_lead_id',3)
            ->where('e_leads.finish_lead',0)
            ->get();
            $approvedLeads = ELead::select('e_leads.id','e_leads.agent_id','c_erp_info_users.short_name AS agent_name','c_erp_info_users.email AS agent_email','c_erp_info_users.user_img AS agent_img','c_erp_info_users.color AS agent_color','e_leads.contact','e_leads.company','e_leads.company_details','e_leads.title','e_leads.expected_closing_date','e_leads.status_lead_id','c_status_leads.status','c_status_leads.color_status','e_leads.estimated_value','e_leads.phone','e_leads.email','e_leads.phone2','e_leads.email2')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_leads.agent_id')
            ->join('c_status_leads','c_status_leads.id','e_leads.status_lead_id');
            if( (INT)$userLider->is_leader !== 1 ) { $approvedLeads->where('e_leads.agent_id',$request->agent_id); }
            $approvedLeads = $approvedLeads->where('e_leads.status_lead_id',4)
            ->where('e_leads.finish_lead',0)
            ->get();
            $rejectedLeads = ELead::select('e_leads.id','e_leads.agent_id','c_erp_info_users.short_name AS agent_name','c_erp_info_users.email AS agent_email','c_erp_info_users.user_img AS agent_img','c_erp_info_users.color AS agent_color','e_leads.contact','e_leads.company','e_leads.company_details','e_leads.title','e_leads.expected_closing_date','e_leads.status_lead_id','c_status_leads.status','c_status_leads.color_status','e_leads.estimated_value','e_leads.phone','e_leads.email','e_leads.phone2','e_leads.email2')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_leads.agent_id')
            ->join('c_status_leads','c_status_leads.id','e_leads.status_lead_id');
            if( (INT)$userLider->is_leader !== 1 ) { $rejectedLeads->where('e_leads.agent_id',$request->agent_id); }
            $rejectedLeads = $rejectedLeads->where('e_leads.status_lead_id',5)
            ->where('e_leads.finish_lead',0)
            ->get();
            $statusLeads = CStatusLead::select('id','status','color_status')->get();
            $typeSchedules = CTypeSchedule::select('id','type_schedule','type_schedule_icon','type_schedule_color','is_reschedule')->where('is_active',1)->get();
            $rejectionReasons = CRejectionReason::where('is_active',1)->get();
            $typeNotes = CTypeNote::select('id','type_note','color_type_note')->where('is_active',1)->get();
            return response()->json([
                'success'   => true,
                'leads' => $leads,
                'contacsLeads' => $contacsLeads,
                'quotationLeads' => $quotationLeads,
                'approvedLeads' => $approvedLeads,
                'rejectedLeads' => $rejectedLeads,
                'statusLeads' => $statusLeads,
                'typeSchedules' => $typeSchedules,
                'rejectionReasons' => $rejectionReasons,
                'typeNotes' => $typeNotes,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 400);
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
            // SAVE LEAD
            $ELead = new ELead;
            $ELead->agent_id = $request->agent_id ;
            $ELead->contact = $request->contact ;
            $ELead->company = $request->company ;
            $ELead->title = $request->title ;
            $ELead->expected_closing_date = $request->expected_closing_date ;
            $ELead->estimated_value = $request->estimated_value ;
            $ELead->status_lead_id = $request->status_lead_id ;
            $ELead->phone = $request->phone ;
            $ELead->email = $request->email ;
            $ELead->phone2 = $request->phone2 ;
            $ELead->email2 = $request->email2 ;
            $ELead->save();

            $lead = ELead::select('e_leads.id','e_leads.agent_id','c_erp_info_users.short_name AS agent_name','c_erp_info_users.email AS agent_email','c_erp_info_users.user_img AS agent_img','c_erp_info_users.color AS agent_color','e_leads.contact','e_leads.company','e_leads.company_details','e_leads.title','e_leads.expected_closing_date','e_leads.status_lead_id','c_status_leads.status','c_status_leads.color_status','e_leads.estimated_value','e_leads.phone','e_leads.email','e_leads.phone2','e_leads.email2')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_leads.agent_id')
            ->join('c_status_leads','c_status_leads.id','e_leads.status_lead_id')
            ->where('e_leads.id',$ELead->id)
            ->first();

            return response()->json([
                'success'   => true,
                'lead'      => $lead,
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
     * @param  \App\Models\ELead  $eLead
     * @return \Illuminate\Http\Response
     */
    public function show(ELead $eLead, $lead_id, $user_id)
    {
        // try {
            $lead = ELead::select('e_leads.id','e_leads.agent_id','c_erp_info_users.short_name AS agent_name','c_erp_info_users.email AS agent_email','c_erp_info_users.user_img AS agent_img','c_erp_info_users.color AS agent_color','e_leads.contact','e_leads.company','e_leads.company_details','e_leads.title','e_leads.expected_closing_date','e_leads.status_lead_id','c_status_leads.status','c_status_leads.color_status','e_leads.estimated_value','e_leads.phone','e_leads.email','e_leads.phone2','e_leads.email2')
            ->join('c_erp_info_users','c_erp_info_users.user_id','e_leads.agent_id')
            ->join('c_status_leads','c_status_leads.id','e_leads.status_lead_id')
            ->where('e_leads.id',$lead_id)
            ->first();
            // NOTES
            $leadsNotes = DClientsNote::select('d_clients_notes.id','d_clients_notes.note', 'd_clients_notes.created_at', 'd_clients_notes.is_active', 'd_clients_notes.lead_id', 'd_clients_notes.updated_at', 'd_clients_notes.user_id', 'c_erp_info_users.short_name', 'c_erp_info_users.email as user_email', 'c_erp_info_users.user_img', 'c_erp_info_users.color as color_user')
            ->join('c_erp_info_users','c_erp_info_users.user_id','d_clients_notes.user_id')
            ->where('d_clients_notes.lead_id', $lead_id)
            ->where('d_clients_notes.is_lead', 1)
            ->orderBy('d_clients_notes.id', 'DESC')
            ->get();

            $schedules = $this->getSchedulLeads($lead_id,$user_id);

            return response()->json([
                'success'   => true,
                'lead' => $lead,
                'leadsNotes' => $leadsNotes,
                'schedules' => $schedules,
            ], 200 );
        // } catch (\Throwable $th) {
        //     return response()->json([
        //         'success' =>  false ,
        //         'error'   =>  $th,
        //     ], 400);
        // }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ELead  $eLead
     * @return \Illuminate\Http\Response
     */
    public function edit(ELead $eLead, Request $request, $id)
    {
        try {
            // buscamos
            $sendTrue = true;
            $error = '';
            if( (INT)$request->lead['status_lead_id'] === 4 ) {
                $cient = CUser::where('id',$request->client_id)->first();
                if(!$cient) {
                    $sendTrue = false;
                    $error = 'Aún no das de alta al cliente en ASPEL';
                }
            }
            if($sendTrue) {
                ELead::where('id',$id)
                ->update([
                    'client_id'             => $request->client_id,
                    'rejection_reason_id'   => $request->rejection_reason_id,
                    'description_reject'    => $request->description_reject,
                    'finish_lead'           => 1
                ]);
                return response()->json([
                    'success'           => true,
                    'id'                => $id,
                    'status_lead_id'    => $request->lead['status_lead_id'],
                ], 200 );
            } else {
                return response()->json([
                    'success' =>  false ,
                    'error'   =>  $error,
                ], 400);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ELead  $eLead
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try {
            ELead::where('id',$request->lead_id)
            ->update(['status_lead_id'=>$request->status_lead_id]);
            return response()->json([
                'success'        => true,
                'lead_id'        => $request->lead_id,
                'status_lead_id' => $request->status_lead_id,
                'status_lead_id_old' => $request->status_lead_id_old,
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
     * @param  \App\Models\ELead  $eLead
     * @return \Illuminate\Http\Response
     */
    public function destroy(ELead $eLead)
    {
        //
    }

    public function saveOptionLead(Request $request)
    {
        try {
            switch ($request->opt) {
                case 1: // COMENT
                    ELead::where('id',$request->lead['id'])
                    ->update([
                        'company_details'=>$request->lead['company_details'],
                    ]);
                break;
                case 2: // INFO LEAD
                    ELead::where('id',$request->lead['id'])
                    ->update([
                        'email'=>$request->lead['email'],
                        'phone'=>$request->lead['phone'],
                    ]);
                break;
            }
            return response()->json([
                'success'        => true,
            ], 200 );
        } catch (\Throwable $th) {
            return response()->json([
                'success' =>  false ,
                'error'   =>  $th,
            ], 400);
        }
    }

    // PRIVATE

    private function getSchedulLeads($lead_id,$user_id)
    {
        $dt = Carbon::now();
        $dt = $dt->toDateString().' 00:00:00';
        $ELeadSchedule = ESchedule::select('e_schedules.id','e_schedules.client_id',DB::raw(' CASE WHEN e_schedules.is_lead = 1 THEN aglead.user_id ELSE ag.user_id END AS agent_id , CASE WHEN e_schedules.is_lead = 1 THEN aglead.short_name ELSE ag.short_name END AS agent_name'),DB::raw("CASE WHEN c_users.short_name IS NULL OR c_users.short_name = '' THEN c_users.full_name ELSE c_users.short_name END AS client_name"),'e_schedules.name_activity AS name','c_erp_info_users.id as created_agent_id','c_erp_info_users.short_name as name_create','e_schedules.type_schedules_id','c_type_schedules.type_schedule','c_type_schedules.type_schedule_icon',DB::raw(' CASE WHEN e_schedules.lead_id = '.$lead_id.' THEN c_type_schedules.type_schedule_color ELSE "#c5c5c5" END AS color'),'e_schedules.detail',DB::raw('CASE WHEN e_schedules.start IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.start END AS start, CASE WHEN e_schedules.end IS NULL THEN "1986-07-28 03:45:00" ELSE e_schedules.end END AS end'),'e_schedules.user_address_id','e_schedules.schedule_status_id', 'c_status_schedules.schedule_status','c_status_schedules.schedule_status_color','e_schedules.lead_id','e_leads.contact','e_leads.company','e_schedules.is_lead','e_schedules.created_at','e_schedules.cancellation_description')
        ->leftJoin('c_users','c_users.id','e_schedules.client_id')
        ->join('c_status_schedules','c_status_schedules.id','e_schedules.schedule_status_id')
        ->leftJoin('c_user_addresses','c_user_addresses.id','e_schedules.user_address_id')
        ->join('c_type_schedules','c_type_schedules.id','e_schedules.type_schedules_id')
        ->leftJoin('c_erp_info_users','c_erp_info_users.user_id','e_schedules.user_id')
        ->leftJoin('c_erp_info_users AS ag','ag.user_id','c_users.agent_id')
        ->leftJoin('e_leads','e_leads.id','e_schedules.lead_id')
        ->leftJoin('c_erp_info_users AS aglead','ag.user_id','e_leads.agent_id')
        ->where('e_schedules.is_active','1')
        ->where('e_schedules.user_id',$user_id)
        ->where('e_schedules.start','>=',$dt)
        ->orderBy('start', 'asc')
        ->get();

        return $ELeadSchedule;
    }

}
