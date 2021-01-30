<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class pcrListHasilPemeriksaan extends JsonResource
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
            'waktu_pcr_sample_received' => $this->waktu_pcr_sample_received,
            'tanggal_input_hasil' => $this->pcr->tanggal_input_hasil,
            'is_musnah_pcr' => $this->is_musnah_pcr,
            'status' => ['deskripsi' => $this->status->deskripsi],
            'ekstraksi' => ['catatan_pengiriman' => $this->ekstraksi->catatan_pengiriman],
            'pcr' => ['kesimpulan_pemeriksaan' => $this->pcr->kesimpulan_pemeriksaan, 'catatan_pemeriksaan' => $this->pcr->catatan_pemeriksaan],
        ];
    }
}
