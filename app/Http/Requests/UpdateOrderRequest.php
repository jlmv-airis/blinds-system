<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => ['sometimes', 'required', 'in:pending,confirmed,cancelled'],
            'client_id' => ['sometimes', 'required', 'integer', 'exists:clients,id'],
        ];
    }
}
