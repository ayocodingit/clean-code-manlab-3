<?php

namespace App\Http\Requests;

use App\Enums\LabPCREnum;
use Illuminate\Foundation\Http\FormRequest;

class EditEkstraksiRequest extends FormRequest
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
        $rules = [
            'tanggal_penerimaan_sampel' => 'required',
            'jam_penerimaan_sampel' => 'required',
            'petugas_penerima_sampel' => 'required',
            'catatan_pengiriman' => 'nullable',
            'operator_ekstraksi' => 'required',
            'tanggal_mulai_ekstraksi' => 'required',
            'jam_mulai_ekstraksi' => 'required',
            'metode_ekstraksi' => 'required',
            'nama_kit_ekstraksi' => 'required_if:metode_ekstraksi,Manual',
            'alat_ekstraksi' => 'required_if:metode_ekstraksi,Otomatis',
        ];
        if ($this->sampel->sampel_status != 'extraction_sample_extracted') {
            $rules['tanggal_pengiriman_rna'] = 'required';
            $rules['jam_pengiriman_rna'] = 'required';
            $rules['nama_pengirim_rna'] = 'required';
            $rules['lab_pcr_id'] = 'required|exists:lab_pcr,id';
            $rules['lab_pcr_nama'] = 'required_if:lab_pcr_id,999999';
        }

        return $rules;
    }
}
