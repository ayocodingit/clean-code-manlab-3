<?php

namespace App\Http\Controllers\Temp;

use App\Http\Controllers\Controller;
use App\Jobs\migrasiDataCovid;
use App\Models\Ekstraksi;
use App\Models\Pasien;
use App\Models\PasienRegister;
use App\Models\PemeriksaanSampel;
use App\Models\Register;
use App\Models\Sampel;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MigrasiController extends Controller
{
    public function cleanTable()
    {
        DB::table('sampel_log')->truncate();
        DB::table('sampel')->truncate();
        DB::table('pasien_register')->truncate();
        DB::table('register')->truncate();
        DB::table('pasien')->truncate();
        DB::table('pemeriksaansampel')->truncate();
        \DB::table('ekstraksi')->truncate();
    }

    public function uploadFilehandler(Request $request)
    {
        $filenameWithExt = $request->file('file')->getClientOriginalName();
        //Get just filename
        $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
        // Get just ext
        $extension = $request->file('file')->getClientOriginalExtension();
        // Filename to store
        $fileNameToStore = $filename.'_'.time().'.'.$extension;
        // Upload Image
        $path = $request->file('file')->storeAs('public/migration', $filenameWithExt);

        return $filenameWithExt;
    }

    public function checkDuplicateSampel($nomor_sampel)
    {
        $sampel = Sampel::where('nomor_sampel', $nomor_sampel)->first();

        if ($sampel != null) {
            DB::beginTransaction();
            \DB::table('ekstraksi')->where('sampel_id', $sampel->id)->delete();
            \DB::table('pemeriksaansampel')->where('sampel_id', $sampel->id)->delete();
            \DB::table('sampel_log')->where('sampel_id', $sampel->id)->delete();
            if ($sampel->nomor_register != null) {
                $pasienRegister = PasienRegister::where('register_id', $sampel->register_id)->first();
                \DB::table('pasien')->where('id', $pasienRegister->pasien_id)->delete();
                \DB::table('pasien_register')->where('register_id', $sampel->register_id)->delete();
                \DB::table('sampel')->where('nomor_register', $sampel->nomor_register)->delete();
                \DB::table('register')->where('id', $sampel->register_id)->delete();
            }
            DB::commit();
        }
    }

    public function hitungUmur($tanggal_lahir, $type = 'mandiri')
    {
        if ($type == 'rujukan') {
            return $tanggal_lahir == null ? 0 : date('Y') - substr($tanggal_lahir, 6, 4);
        }

        return $tanggal_lahir == null ? 0 : date('Y') - substr($tanggal_lahir, 6, 4);
    }

    public function validateDate($date, $format = 'd-m-Y')
    {
        $d = \DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    public function queueImportData()
    {
        dispatch(new migrasiDataCovid());
    }

    public function filterTypeSampel($nama_sampel)
    {
        if ($nama_sampel == 'Usap Nasofaring; Serum') {
            return 'Usap Nasofaring & Orofaring';
        } elseif ($nama_sampel = 'Usap Nasofaring&Orofaring') {
            return 'Usap Nasofaring & Orofaring';
        } else {
            return $nama_sampel;
        }
    }

    public function validasiTanggalLahir($tanggal_lahir)
    {
        if ($tanggal_lahir == '' or $tanggal_lahir == '-' or $tanggal_lahir == 'STUKPA SUKABUMI' or $tanggal_lahir == '33/05/1951' or $tanggal_lahir == '8/16/1960' or $tanggal_lahir == '11/17/1978' or $tanggal_lahir == '5/24/1992') {
            return null;
        } else {
            return $tanggal_lahir;
        }
    }

    public function validasiUmur($umur, $tgl_lahir)
    {
        $umur = str_replace([' bln', ' hari'], '', $umur);

        if ($umur == '' and $tgl_lahir == null) {
            return null;
        } elseif ($umur == '' and $tgl_lahir != null) {
            return date('Y') - substr($tgl_lahir, 0, 4);
        } else {
            return $umur;
        }
    }

    public function migrasiMandiri(Request $request)
    {
        set_time_limit(20000);
        $validator = \Validator::make($request->all(), [
            'file' => 'required|',
        ]);

        if ($validator->fails()) {
            $response['success'] = false;
            $response['message'] = $validator->messages();
            $response['data'] = null;
        } else {
            $sourceData = $this->uploadFilehandler($request);
            $readFile = fopen(storage_path('/app/public/migration/'.$sourceData), 'r');
            $rowInserted = 0;
            while (($row = fgetcsv($readFile, 0, ',')) !== false) {
                $kota = \DB::table('kota')->where('nama', strtoupper($row[11]))->first();

                $tgl_lahir = $this->validateDate($row[9], 'Y-m-d') == true ? $row[9] : null;
                $data = [
                    'no_registrasi' => $row[0],
                    'nomor_register' => $row[0],
                    'nomor_sampel' => str_replace(' ', '', $row[1]), // kode sampel
                    'sumber_pasien' => $row[2],
                    'status' => $row[3],
                    'nama_lengkap' => iconv('UTF-8', 'UTF-8//IGNORE', $row[4]),
                    'nik' => substr($row[5], 0, 16),
                    'kota_id' => $kota->id,
                    'satuan' => $row[7],
                    'tempat_lahir' => $row[8],
                    'tanggal_lahir' => $tgl_lahir,
                    'usia' => $this->validasiUmur($row[6], $tgl_lahir),
                    'jenis_kelamin' => trim($row[10]),
                    'domisili' => $row[11],
                    'alamat_lengkap' => iconv('UTF-8', 'UTF-8//IGNORE', $row[12]),
                    'no_hp' => substr($row[13], 0, 25),
                    'type_sampel' => $this->filterTypeSampel($row[14]),
                    'kesimpulan_pemeriksaan' => $row[15],
                    'tanggal_terima' => $row[16],
                    'tanggal_periksa' => $row[17],
                    'kunjungan_ke' => 1,
                    'tanggal_kunjungan' => $row[17],
                    'register_uuid' => Str::uuid(),
                    'jenis_registrasi' => 'mandiri',
                    'status' => null,
                    'is_from_migration' => true,
                ];

                if ($row[6] == '') {
                    $data['usia_tahun'] = null;
                    $data['usia_bulan'] = null;
                } else {
                    if ($row[7] == 'bulan') {
                        $data['usia_bulan'] = $data['usia'];
                        $data['usia_tahun'] = null;
                    } else {
                        $data['usia_bulan'] = null;
                        $data['usia_tahun'] = $data['usia'];
                    }
                }

                $this->checkDuplicateSampel($data['nomor_sampel']);

                if (Register::where('nomor_register', $data['nomor_register'])->count() < 1) {
                    DB::beginTransaction();
                    $pasien = Pasien::Create($data);
                    $register = Register::create($data);
                    $pasienRegister = PasienRegister::create(['pasien_id' => $pasien->id, 'register_id' => $register->id, 'is_from_migration' => true]);
                    $jenis_sampel = \App\Models\JenisSampel::where('nama', $data['type_sampel'])->first();

                    $data['register_id'] = $register->id;
                    $data['validator_id'] = 2;
                    $data['waktu_sample_taken'] = $data['tanggal_terima'];
                    $data['waktu_pcr_sample_analyzed'] = $data['tanggal_periksa'];
                    $data['jenis_sampel_id'] = $jenis_sampel->id;
                    $data['jenis_sampel_nama'] = $jenis_sampel->nama;
                    $data['sampel_status'] = 'sample_valid';
                    $data['waktu_sample_valid'] = $data['tanggal_periksa'];
                    $data['petugas_pengambilan_sampel'] = 'Baik';
                    $sampel = Sampel::create($data);

                    // insert ke pemeriksaansampel
                    $data['sampel_id'] = $sampel->id;
                    $data['user_id'] = 1;
                    $data['petugas_penerima_sampel_rna'] = 'import';
                    $data['operator_real_time_pcr'] = 'import';
                    $data['tanggal_mulai_pemeriksaan'] = $data['tanggal_periksa'];
                    $data['tanggal_penerimaan_sampel'] = $data['tanggal_terima'];
                    PemeriksaanSampel::create($data);

                    $data['petugas_penerima_sampel'] = 'import';
                    $data['tanggal_mulai_ekstraksi'] = $data['tanggal_periksa'];
                    Ekstraksi::create($data);

                    // inser ke log sampel
                    \DB::table('sampel_log')->insert([
                        [
                            'sampel_id' => $sampel->id,
                            'sampel_status' => 'sample_taken',
                            'sampel_status_before' => null,
                            'created_at' => $data['tanggal_terima'],
                            'updated_at' => $data['tanggal_terima'],
                            'is_from_migration' => true,
                        ],
                        [
                            'sampel_id' => $sampel->id,
                            'sampel_status' => 'sample_valid',
                            'sampel_status_before' => null,
                            'createt_at' => $data['tanggal_periksa'],
                            'is_from_migration' => true,
                            'updated_at' => $data['tanggal_periksa'],
                        ],
                    ]);
                    ++$rowInserted;
                    DB::commit();
                }
            }

            \File::deleteDirectory(storage_path('/app/public/migration/'));
            $response['success'] = true;
            $response['message'] = 'upload data mandiri berhasil';
            $response['data'] = $rowInserted.' Berhasil Diproses';
        }

        return response()->json($response, 200);
    }

    public function MigrasiRujukan(Request $request)
    {
        set_time_limit(20000);
        $validator = \Validator::make($request->all(), [
            'file' => 'required|',
        ]);

        if ($validator->fails()) {
            $response['success'] = false;
            $response['message'] = $validator->messages();
            $response['data'] = null;
        } else {
            $sourceData = $this->uploadFilehandler($request);
            $readFile = fopen(storage_path('/app/public/migration/'.$sourceData), 'r');
            $rowInserted = 0;
            while (($row = fgetcsv($readFile, 0, ',')) !== false) {
                $kota = \DB::table('kota')->where('nama', strtoupper($row[11]))->first();

                $fasyankes = \App\Models\Fasyankes::firstOrCreate(
                    ['nama' => $row[15]],
                    ['nama' => $row[15], 'tipe' => $row[14]]
                );

                $tgl_lahir = $this->validateDate($row[9], 'Y-m-d') == true ? $row[9] : null;

                $data = [
                    'no_registrasi' => $row[0],
                    'nomor_register' => $row[0],
                    'nomor_sampel' => str_replace(' ', '', $row[1]), // kode sampel
                    'sumber_pasien' => $row[2],
                    'status' => $row[3],
                    'nama_lengkap' => $row[4],
                    'nik' => substr($row[5], 0, 16),
                    'usia' => $this->validasiUmur($row[6], $tgl_lahir),
                    'kota_id' => $kota->id,
                    'satuan' => $row[7],
                    'tempat_lahir' => $row[8],
                    'tanggal_lahir' => $tgl_lahir,
                    'jenis_kelamin' => trim($row[10]),
                    'domisili' => $row[11],
                    'alamat_lengkap' => $row[12],
                    'no_hp' => substr($row[13], 0, 25),
                    'fasyankes_pengirim' => $row[14], // instansi pengirim
                    'nama_rs' => $row[15], // nama instansi
                    'type_sampel' => $this->filterTypeSampel($row[16]),
                    'kesimpulan_pemeriksaan' => $row[17],
                    'lab_pcr' => $row[18],
                    'tanggal_terima' => $row[19],
                    'tanggal_periksa' => $row[20],
                    'kunjungan_ke' => 1,
                    'tanggal_kunjungan' => $row[20],
                    'register_uuid' => Str::uuid(),
                    'jenis_registrasi' => 'rujukan',
                    'fasyankes_id' => $fasyankes->id,
                    'status' => null,
                    'is_from_migration' => true,
                ];

                if ($row[6] == '') {
                    $data['usia_tahun'] = null;
                    $data['usia_bulan'] = null;
                } else {
                    if ($row[7] == 'bulan') {
                        $data['usia_bulan'] = $data['usia'];
                        $data['usia_tahun'] = null;
                    } else {
                        $data['usia_bulan'] = null;
                        $data['usia_tahun'] = $data['usia'];
                    }
                }

                $this->checkDuplicateSampel($data['nomor_sampel']);

                if (Register::where('nomor_register', $data['nomor_register'])->count() < 1) {
                    DB::beginTransaction();
                    $pasien = Pasien::Create($data);
                    $register = Register::create($data);
                    $pasienRegister = PasienRegister::create(['pasien_id' => $pasien->id, 'register_id' => $register->id, 'is_from_migration' => true]);
                    $jenis_sampel = \App\Models\JenisSampel::where('nama', $data['type_sampel'])->first();

                    $data['register_id'] = $register->id;
                    $data['validator_id'] = 2;
                    $data['waktu_sample_taken'] = $data['tanggal_terima'].' 00:00:00';
                    $data['waktu_pcr_sample_analyzed'] = $data['tanggal_periksa'].' 00:00:00';
                    $data['jenis_sampel_id'] = $jenis_sampel->id;
                    $data['jenis_sampel_nama'] = $jenis_sampel->nama;
                    $data['lab_pcr_id'] = 1;
                    $data['lab_pcr_nama'] = $data['lab_pcr'];
                    $data['sampel_status'] = 'sample_valid';
                    $data['waktu_sample_valid'] = $data['tanggal_periksa'];
                    $data['petugas_pengambilan_sampel'] = 'Baik';
                    $sampel = Sampel::create($data);

                    // insert ke pemeriksaansampel
                    $data['sampel_id'] = $sampel->id;
                    $data['user_id'] = 1;
                    $data['petugas_penerima_sampel_rna'] = 'import';
                    $data['operator_real_time_pcr'] = 'import';
                    $data['tanggal_mulai_pemeriksaan'] = $data['tanggal_periksa'];
                    $data['tanggal_penerimaan_sampel'] = $data['tanggal_terima'];
                    PemeriksaanSampel::create($data);

                    $data['petugas_penerima_sampel'] = 'import';
                    $data['tanggal_mulai_ekstraksi'] = $data['tanggal_periksa'];
                    Ekstraksi::create($data);

                    // inser ke log sampel
                    \DB::table('sampel_log')->insert([
                        [
                            'sampel_id' => $sampel->id,
                            'sampel_status' => 'sample_taken',
                            'sampel_status_before' => null,
                            'created_at' => $data['tanggal_terima'],
                            'updated_at' => $data['tanggal_terima'],
                            'is_from_migration' => true,
                        ],
                        [
                            'sampel_id' => $sampel->id,
                            'sampel_status' => 'sample_valid',
                            'sampel_status_before' => null,
                            'createt_at' => $data['tanggal_periksa'],
                            'is_from_migration' => true,
                            'updated_at' => $data['tanggal_periksa'],
                        ],
                    ]);
                    ++$rowInserted;
                    DB::commit();
                }
            }

            \File::deleteDirectory(storage_path('/app/public/migration/'));
            $response['success'] = true;
            $response['message'] = 'upload data rujukan berhasil';
            $response['data'] = $rowInserted.' Berhasil Diproses';
        }

        return response()->json($response, 200);
    }
}
