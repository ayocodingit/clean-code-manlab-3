<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PelacakanSampel extends JsonResource
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
            'kondisi_sampel' => $this->kondisi_sampel,
            'sampel_status' => $this->sampel_status,
            'pasien' => [
                'nama_lengkap' => $this->register->pasienRegister->pasien->nama_lengkap,
                'nik' => $this->register->pasienRegister->pasien->nik,
                'usia_tahun' => $this->register->pasienRegister->pasien->usia_tahun,
                'tanggal_lahir' => $this->register->pasienRegister->pasien->tanggal_lahir,
                'kota' => ['nama' => $this->register->pasienRegister->pasien->kota->nama],
            ],
            'register' => [
                'sumber_pasien' => $this->register->sumber_pasien,
                'kunjungan_ke' => $this->register->kunjungan_ke,
            ],
            'pemeriksaanSampel' => ['kesimpulan_pemeriksaan' => $this->pemeriksaanSampel[0]->kesimpulan_pemeriksaan],
            'validator' => $this->validator,
        ];
    }
}
