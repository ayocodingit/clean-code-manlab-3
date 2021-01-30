<?php

namespace App\Imports;

use App\Models\Pasien;
use App\Models\Register;
use App\Models\Sampel;
use App\Rules\ExistsWilayah;
use App\Rules\UniqueSampel;
use App\Traits\RegisterTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RegisterMandiriImport implements ToCollection, WithHeadingRow
{
    use RegisterTrait;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        $rows = (array)json_decode($rows);
        $data = [];
        foreach ($rows as $key => $row) {
            if (empty($rows[$key]->no)) {
                continue;
            }
            $rows[$key]->kriteria = strtolower($row->kriteria);
            $rows[$key]->nomor_sampel = trim(strtoupper($row->nomor_sampel));
            $data[] =(array) $rows[$key];
        }
        $validator = Validator::make($data,
            [
                '*.tanggal_kunjungan' => 'nullable|date|date_format:Y-m-d',
                '*.nik' => 'nullable|digits:16',
                '*.nama_pasien' => 'required|min:3',
                '*.kategori' => 'nullable',
                '*.alamat' => 'required',
                '*.tanggal_lahir' => 'nullable|date|date_format:Y-m-d',
                '*.provinsi_id' => [
                    'nullable',
                    'exists:provinsi,id',
                ],
                '*.kota_id' => [
                    'required',
                    'exists:kota,id',
                ],
                '*.kecamatan_id' => [
                    'nullable',
                    'exists:kecamatan,id',
                ],
                '*.kelurahan_id' => [
                    'nullable',
                    'exists:kelurahan,id',
                ],
                '*.kategori' => 'nullable',
                '*.kewarganegaraan' => 'nullable',
                '*.jenis_kelamin' => 'nullable|in:L,P',
                '*.suhu' => ['nullable', 'regex:/^[0-9]+(\.[0-9]->-?)?$/'],
                '*.kriteria' => Rule::in(array_map('strtolower', Pasien::STATUSES)),
                '*.nomor_sampel' => [
                    'required',
                    'regex:/^' . Sampel::NUMBER_FORMAT_MANDIRI . '$/',
                    new UniqueSampel(),
                    'distinct',

                ],
            ]
        );
        if($validator->fails()){
            $messages = [];
            foreach($validator->errors()->messages() as $key => $message){
                $attribute = explode(".", $key);
                $messages[$data[$attribute[0]]['no']][] = $message; 
            }   
            abort(response()->json(['error' => $messages, 'code' => 422], 422));
        }
        try {
            foreach ($data as $key => $row) {
                //create register
                $registerData = [
                    'sumber_pasien' => $row['kategori'],
                    'register_uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'jenis_registrasi' => 'mandiri',
                    'nomor_register' => $this->generateNomorRegister(),
                    'creator_user_id' => auth()->user()->id,
                    'hasil_rdt' => $row['hasil_rdt'],
                    'kunjungan_ke' => $row['kunjungan'],
                    'tanggal_kunjungan' => $row['tanggal_kunjungan'],
                    'rs_kunjungan' => $row['rs_kunjungan'],
                ];

                $register = Register::create($registerData);

                //create pasien
                $pasienData = [
                    'nik' => $this->parseNIK($row['nik']),
                    'nama_lengkap' => $row['nama_pasien'],
                    'kewarganegaraan' => $row['kewarganegaraan'],
                    'jenis_kelamin' => $row['jenis_kelamin'],
                    'tanggal_lahir' => $row['tanggal_lahir'],
                    'tempat_lahir' => $row['tempat_lahir'],
                    'provinsi_id' => getConvertCodeDagri($row['provinsi_id']),
                    'kota_id' => getConvertCodeDagri($row['kota_id']),
                    'kecamatan_id' => getConvertCodeDagri($row['kecamatan_id']),
                    'kelurahan_id' => getConvertCodeDagri($row['kelurahan_id']),
                    'no_rw' => $row['rw'],
                    'no_rt' => $row['rt'],
                    'no_hp' => $row['no_hp'],
                    'alamat_lengkap' => $row['alamat'],
                    'keterangan_lain' => $row['keterangan'],
                    'suhu' => $row['suhu'],
                    'sumber_pasien' => $registerData['sumber_pasien'],
                    'usia_tahun' => $row['usia_tahun'],
                    'usia_bulan' => $row['usia_bulan'],
                    'status' => $row['kriteria'] ? array_search($row['kriteria'], array_map('strtolower', Pasien::STATUSES)) : defaultStatus,
                ];

                $pasien = Pasien::create($pasienData);

                //attach pasien to register
                $register->pasiens()->attach($pasien);


                $sampelData = [
                    'nomor_sampel' => $row['nomor_sampel'],
                    'nomor_register' => $register->getAttribute('nomor_register'),
                    'jenis_sampel_id',
                    'sampel_status' => 'waiting_sample',
                    'creator_user_id' => auth()->user()->id,
                    'waktu_waiting_sample' => now(),
                ];

                $sampel = Sampel::create($sampelData);

                $register->sampel()->save($sampel);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    private function parseNIK($nik)
    {
        if (!$nik) {
            return null;
        }

        if ($separated = explode("'", $nik)) {
            return count($separated) > 1 ? $separated[1] : (string)$nik;
        }

        return (string)$nik;
    }
}
