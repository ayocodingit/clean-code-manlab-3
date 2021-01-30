<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TerimaPCRRequest extends FormRequest
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
            'petugas_penerima_sampel_rna' => 'required',
            'operator_real_time_pcr' => 'required',
            'tanggal_mulai_pemeriksaan' => 'required',
            'jam_mulai_pcr' => 'required',
            'jam_selesai_pcr' => 'required',
            'nama_reagen_pcr' => 'required',
            'ct_normal' => 'required',
            'samples' => 'required|array',
            'samples.*.nomor_sampel' => 'required|exists:sampel,nomor_sampel,deleted_at,NULL'
        ];
    }
}
