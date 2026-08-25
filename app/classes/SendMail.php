<?php
namespace App\classes;

use App\Mail\SendQuotationMailable;
use Illuminate\Support\Facades\Mail;

class SendMail {
    public function sendQuotation($quotation,$request) {
        // try {
            Mail::to($request['from'])
            ->cc($request['cc'])
            ->send(new SendQuotationMailable($quotation));
            return response()->json([ 'success' => true, 'from' => $request['from'] ], 200);
        // } catch (\Throwable $th) {
        //     return response()->json([ 'success' => false, "Error" => $th ], 400);
        // }
    }
}