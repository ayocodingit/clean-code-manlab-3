<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ekstraksiTerkirim extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nomor_sampel' => $this->nomor_sampel,
            'nomor_register' => $this->nomor_register,
            'is_musnah_ekstraksi' => $this->is_musnah_ekstraksi,
            'jenis_sampel_nama' => $this->jenis_sampel_nama,
            'kondisi_sampel' => $this->kondisi_sampel,
            'lab_pcr_nama' => $this->lab_pcr_nama,
            'waktu_extraction_sample_sent' => $this->waktu_extraction_sample_sent,
            'status' => ['deskripsi' => $this->status->deskripsi],
            'waktu_sample_taken' => $this->waktu_sample_taken,
            'pcr' => $this->pcr,
        ];
    }
}
