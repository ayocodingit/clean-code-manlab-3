<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;

class BulkTesMasifRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Config::get('services.tes_masif.api_key') == $this->api_key;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'data' => 'required|array',
        ];
    }

    public function messages()
    {
        return [
            'data.required' => ':attribute wajib diisi.',
            'data.array' => ':attribute harus berupa array.',
            'data.*.nomor_sampel' => 'required|distinct',
        ];
    }
}
