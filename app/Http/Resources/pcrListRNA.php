<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class pcrListRNA extends JsonResource
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
            'waktu_extraction_sample_sent' => $this->waktu_extraction_sample_sent,
            'ekstraksi' => [
                'operator_ekstraksi' => $this->ekstraksi->operator_ekstraksi,
                'catatan_pengiriman' => $this->ekstraksi->catatan_pengiriman,
                'catatan_penerimaan' => $this->ekstraksi->catatan_penerimaan,
            ],
        ];
    }
}
