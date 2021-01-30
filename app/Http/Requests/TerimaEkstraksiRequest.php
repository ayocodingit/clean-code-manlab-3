<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TerimaEkstraksiRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'tanggal_penerimaan_sampel' => 'required',
            'jam_penerimaan_sampel' => 'required',
            'petugas_penerima_sampel' => 'required',
            'operator_ekstraksi' => 'required',
            'tanggal_mulai_ekstraksi' => 'required',
            'jam_mulai_ekstraksi' => 'required',
            'metode_ekstraksi' => 'required',
            'nama_kit_ekstraksi' => 'required_if:metode_ekstraksi,Manual',
            'alat_ekstraksi' => 'required_if:metode_ekstraksi,Otomatis',
            'samples' => 'required|array',
            'samples.*.nomor_sampel' => 'required|exists:sampel,nomor_sampel,deleted_at,NULL'
        ];
    }
}
