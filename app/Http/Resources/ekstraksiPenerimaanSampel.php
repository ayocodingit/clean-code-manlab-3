<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ekstraksiPenerimaanSampel extends JsonResource
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
        ];
    }
}
