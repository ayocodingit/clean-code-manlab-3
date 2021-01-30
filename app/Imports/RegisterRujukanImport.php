<?php

namespace App\Imports;

use App\Models\Fasyankes;
use App\Models\Pasien;
use App\Models\Register;
use App\Models\Sampel;
use App\Rules\ExistsFasyankes;
use App\Rules\ExistsSampel;
use App\Rules\ExistsWilayah;
use App\Rules\UniqueSampel;
use App\Traits\RegisterTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RegisterRujukanImport implements ToCollection, WithHeadingRow
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
        $validator = Validator::make(
            $data,
            [
                //register
                '*.telp_fasyankes' => 'nullable|regex:@^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$@',
                '*.kunjungan' => 'nullable|integer',
                '*.id_fasyankes' => [
                    'required',
                    'integer',
                    'exists:fasyankes,id',
                ],
                '*.kategori' => 'nullable',
                '*.suhu' => 'nullable',
                '*.tanggal_kunjungan' => 'nullable|date|date_format:Y-m-d',
                '*.rs_kunjungan' => 'nullable',

                //pasien
                '*.nik' => 'nullable|digits:16',
                '*.nama_pasien' => 'required|min:3',
                '*.tanggal_lahir' => 'nullable|date|date_format:Y-m-d',
                '*.usia_bulan' => 'nullable|integer',
                '*.usia_tahun' => 'nullable|integer',
                '*.rt' => 'nullable|integer',
                '*.rw' => 'nullable|integer',
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
                '*.suhu' => ['nullable', 'regex:/^[0-9]+(\.[0-9][0-9]?)?$/'],
                '*.alamat' => 'required',
                '*.kewarganegaraan' => 'nullable',
                '*.tempat_lahir' => 'nullable',
                '*.no_hp' => 'nullable',
                '*.jenis_kelamin' => 'nullable',
                '*.kriteria' => Rule::in(array_map('strtolower', Pasien::STATUSES)),
                // sampel
                '*.nomor_sampel' => [
                    'required',
                    'regex:/^' . Sampel::NUMBER_FORMAT_RUJUKAN . '$/',
                    new ExistsSampel(),
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

                $fasyankes = Fasyankes::find($row['id_fasyankes']);
                $registerData = [
                    'sumber_pasien' => $row['kategori'],
                    'register_uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'jenis_registrasi' => 'rujukan',
                    'nomor_register' => $this->generateNomorRegister(null, 'rujukan'),
                    'kunjungan_ke' => $row['kunjungan'],
                    'tanggal_kunjungan' => $row['tanggal_kunjungan'],
                    'rs_kunjungan' => $row['rs_kunjungan'],
                    'fasyankes_id' => $row['id_fasyankes'],
                    'hasil_rdt' => null,
                    'nama_rs' => $fasyankes->nama,
                    'other_nama_rs' => null,
                    'fasyankes_pengirim' => $fasyankes->tipe,
                    'nama_dokter' => $row['dokter'],
                    'no_telp' => $row['telp_fasyankes'],
                ];

                $register = Register::create($registerData);

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
                    'no_hp' => $row['no_hp'],
                    'no_rw' => $row['rw'],
                    'no_rt' => $row['rt'],
                    'alamat_lengkap' => $row['alamat'],
                    'keterangan_lain' => $row['keterangan'],
                    'suhu' => $row['suhu'],
                    'sumber_pasien' => $registerData['sumber_pasien'],
                    'suhu' => $row['suhu'],
                    'sumber_pasien' => $registerData['sumber_pasien'],
                    'usia_tahun' => $row['usia_tahun'],
                    'usia_bulan' => $row['usia_bulan'],
                    'status' => $row['kriteria'] ? array_search($row['kriteria'], array_map('strtolower', Pasien::STATUSES)) : defaultStatus,
                ];

                $pasien = Pasien::create($pasienData);

                $register->pasiens()->attach($pasien);

                $nomor_sampel = $row['nomor_sampel'];

                $sampel = Sampel::where('nomor_sampel', $nomor_sampel)->first();
                $sampel->update([
                    'nomor_register' => $register->nomor_register,
                    'register_Id' => $register->id,
                ]);
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
