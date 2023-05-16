<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
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
            'name' => 'string|max:255',
            'company' => 'string|max:255',
            'email' => 'email|max:255',
            'country' => 'string|max:255',
            'address' => 'string|max:255',
            'contact_number'            => 'phone',
            'contact_number_country'    => 'required_with:contact_number',
        ];
    }
}
