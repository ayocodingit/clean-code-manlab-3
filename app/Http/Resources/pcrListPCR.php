<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Sampel;

class pcrListPCR extends JsonResource
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
        $sampel = Sampel::find($this->id);

        return [
            'id' => $this->id,
            'nomor_sampel' => $this->nomor_sampel,
            'sampel_status' => $this->sampel_status,
            'nomor_register' => $this->nomor_register,
            'is_musnah_pcr' => $this->is_musnah_pcr,
            'jenis_sampel_nama' => $this->jenis_sampel_nama,
            'catatan_pengiriman'=>$this->catatan_pengiriman,
            'waktu_pcr_sample_analyzed'=>$this->waktu_pcr_sample_analyzed,
            'kesimpulan_pemeriksaan'=>$this->kesimpulan_pemeriksaan,
            'waktu_pcr_sample_received' => $this->waktu_pcr_sample_received,
            'waktu_extraction_sample_sent' => $this->waktu_extraction_sample_sent,
            'deskripsi' => $this->deskripsi,
            're_pcr' => $sampel->logs->where('sampel_status', 'pcr_sample_received')->count() >= 2 ? 're-PCR' : null,
        ];
    }
}
