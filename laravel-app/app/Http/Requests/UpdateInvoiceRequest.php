<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
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
            'issued_date' => ['required', 'date'],
            'created_date' => ['required', 'date'],
            'note' => ['required', 'string'],
            'tax' => ['required', 'numeric'],
            'sale_person' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['string'],
            'items.*.item_id' => ['required','string','exists:items,id'],
            'items.*.description' => ['string'],
            'items.*.cost' => ['required', 'numeric'],
            'items.*.hours' => ['required', 'numeric'],
            'customer_id' => ['exists:customers,id'],
            'total' => ['required', 'numeric'],
            'file' => ['required', 'file', 'mimes:pdf'],
            'send_method' => ['required', 'string', Rule::in(['mail', 'web'])],
            'subject' => ['string'],
            'message' => ['string'],
        ];
    }
}
