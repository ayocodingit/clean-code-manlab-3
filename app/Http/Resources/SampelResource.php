<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SampelResource extends JsonResource
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
            'jenis_sampel_nama' => $this->jenis_sampel_nama,
            'kondisi_sampel' => $this->kondisi_sampel,
            'lab_pcr_nama' => $this->lab_pcr_nama,
            'status' => ['deskripsi' => $this->status->deskripsi],
            'waktu_sample_taken' => $this->waktu_sample_taken,
            'waktu_extraction_sample_extracted' => $this->waktu_extraction_sample_extracted,
            'waktu_pcr_sample_received' => $this->waktu_pcr_sample_received,
            'waktu_pcr_sample_analyzed' => $this->waktu_pcr_sample_analyzed,
            'waktu_extraction_sample_sent' => $this->waktu_extraction_sample_sent,
            'ekstraksi' => [
                'operator_ekstraksi' => $this->ekstraksi->operator_ekstraksi,
                'catatan_pengiriman' => $this->ekstraksi->catatan_pengiriman,
                'catatan_penerimaan' => $this->ekstraksi->catatan_penerimaan,
            ],
            'pcr' => $this->pcr,
        ];
    }
}
