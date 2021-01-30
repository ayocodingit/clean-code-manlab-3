<?php

namespace App\Imports;

use App\Models\Sampel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HasilPemeriksaanImport implements ToCollection, WithHeadingRow
{
    public $data;
    public $errors;
    public $errors_count = 0;

    public function collection(Collection $rows)
    {
        $this->data = [];
        $this->errors = [];
        DB::beginTransaction();
        try {
            foreach ($rows as $key => $row) {
                if (!$row->get('no')) {
                    continue;
                }
                $sampel = Sampel::where('nomor_sampel', $row->get('no_sample'))->first();
                if (!$sampel) {
                    $this->addError($key, "Nomor sampel '".$row->get('no_sample')."' tidak ditemukan");
                } elseif ($sampel->sampel_status == 'extraction_sample_sent') {
                    $this->addError($key, "Nomor sampel '".$row->get('no_sample')."' belum diterima di Lab PCR. Mohon diterima terlebih dahulu. ");
                } elseif ($sampel->sampel_status != 'pcr_sample_received') {
                    $this->addError($key, "Nomor sampel '".$row->get('no_sample')."' masih pada status ".$sampel->status->deskripsi);
                }

                $interpretasi = strtolower($row->get('interpretasi'));

                if (empty($interpretasi)) {
                    $this->addError($key, 'Interpretasi kosong');
                }

                switch ($interpretasi) {
                    case 'negative':
                        $interpretasi = 'negatif';
                    break;
                    case 'positive':
                        $interpretasi = 'positif';
                    break;
                }

                if (!in_array(strtoupper($interpretasi), HASIL_PEMERIKSAAN)) {
                    $this->addError($key, 'Interpretasi harus POSITIF, NEGATIF, INKONKLUSIF, atau INVALID');
                }

                if (empty($row->get('tanggal_periksa'))) {
                    $this->addError($key, 'Tanggal Periksa kosong');
                }

                if (\DateTime::createFromFormat('Y-m-d', $row->get('tanggal_periksa')) == false) {
                    $this->addError($key, 'Format Tanggal Salah');
                }

                $data = $row->toArray();
                $ct_keys = array_keys($data);
                $data_ct = [];
                foreach ($ct_keys as $ct_key) {
                    if (strpos($ct_key, 'ct_') === 0) {
                        if (!is_numeric($data[$ct_key])) {
                            $this->addError($key, strtoupper($ct_key).' Harus berupa angka');
                        }
                        $data_ct[] = [
                            'target_gen' => strtoupper(substr($ct_key, 3)),
                            'ct_value' => $data[$ct_key],
                        ];
                    }
                }
                if (is_integer($data['tanggal_periksa'])) {
                    $data['tanggal_periksa'] = gmdate('Y-m-d', ($data['tanggal_periksa'] - 25569) * 86400);
                }
                $this->data[] = [
                    'no' => $data['no'],
                    'nomor_sampel' => $data['no_sample'],
                    'sampel_id' => $sampel ? $sampel->id : null,
                    'kesimpulan_pemeriksaan' => $interpretasi,
                    'tanggal_input_hasil' => $data['tanggal_periksa'],
                    'target_gen' => $data_ct,
                ];
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function addError($key, $message)
    {
        if (!isset($this->errors[$key])) {
            $this->errors[$key] = [];
        }
        $this->errors[$key][] = $message;
        ++$this->errors_count;
    }

    public function hasError($key)
    {
        return isset($this->errors[$key]) && count($this->errors[$key]) > 0;
    }
}
