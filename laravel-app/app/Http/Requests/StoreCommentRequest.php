<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'number' => 'required|integer',
            'invoice_id' => 'required|uuid|exists:invoices,id',
            'message' => 'required|string',
            'pin.xRatio' => 'required|numeric',
            'pin.yRatio' => 'required|numeric',
        ];
    }
}
