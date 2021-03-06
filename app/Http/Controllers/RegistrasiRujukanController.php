<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pasien;
use App\Models\Register;
use App\Models\PasienRegister;
use App\Models\Sampel;
use App\Models\Fasyankes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Validator;
use Auth;
use App\Exports\RegisRujukanExport;
use Maatwebsite\Excel\Facades\Excel;


class RegistrasiRujukanController extends Controller
{
    public function cekData(Request $request)
    {
        $nomor = $request->get('nomor_sampel');
        $sampel = Sampel::where('nomor_sampel', 'ilike', $nomor)->first();
        if (!$sampel) {
            return response()->json([
                'status' => 400,
                'message' => 'Nomor Sampel Tidak Ditemukan',
                'result' => []
            ]);
        } else {
            if ($sampel->register_id != null) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Sampel sudah memiliki data pasien',
                    'result' => []
                ]);
            } else {
                return response()->json([
                    'status' => 200,
                    'message' => 'Sampel dimukan',
                    'result' => $sampel
                ]);
            }
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $v = Validator::make($request->all(), [
            'reg_kewarganegaraan' => 'nullable',
            'reg_sumberpasien' => 'nullable',
            'reg_nama_pasien' => 'required',
            'reg_nik'  => 'max:16',
            'reg_nohp' => 'nullable|max:15',
            'kota_id' => 'required',
            'provinsi_id' => 'nullable',
            'kecamatan_id' => 'nullable',
            'kelurahan_id' => 'nullable',
            'reg_alamat' => 'required',
            'reg_jk' => 'nullable',
            'reg_fasyankes_pengirim' => 'required',
            'reg_nama_rs' => 'required',
        ], [
            'peg_nama_pasien.required' => 'Nama Pasien tidak boleh kosong',
            'reg_nik.max' => 'NIK maksimal terdiri dari :max karakter',
            'reg_tempatlahir.required' => 'Tempat lahir tidak boleh kosong',
            'reg_tgllahir' => 'Tanggal lahir tidak boleh kosong',
            'reg_nohp' => 'No HP tidak boleh kosong',
            'kota_id' => 'Mohon pilih salah satu kota/kabupaten',
            'reg_kecamatan' => 'Kecamatan tidak boleh ksoong',
            'reg_kelurahan' => 'Kelurahan tidak boleh ksoong',
            'reg_alamat' => 'Alamat tidak boleh kosong',
            'reg_rt' => 'RT tidak boleh kosong',
            'reg_rw' => 'RW tidak boleh kosong',
            'reg_suhu' => 'Suhu tidak boleh kosong',
            'reg_dinkes_pengirim' => 'Form wajib diisi ',
            'reg_fasyankes_pengirim' => 'Form wajib diisi ',
            'reg_nama_rs' => 'Form wajib diisi ',
            'reg_nama_dokter' => 'Form wajib diisi ',
            'reg_telp_fas_pengirim' => 'Form wajib diisi',
            'reg_tanggalkunjungan' => 'Form wajib diisi ',
            'reg_kunke' => 'Form wajib diisi ',
            'reg_rsfasyankes' => 'Form wajib diisi ',
        ]);
        $v->validate();

        DB::beginTransaction();
        try {
            $nomor_register = $request->input('reg_no');
            if (Register::where('nomor_register', $nomor_register)->exists()) {
                $nomor_register = with(new \App\Http\Controllers\V1\RegisterController)->generateNomorRegister(null, 'rujukan');
            }
            $rs = Fasyankes::where('id', $request->get('reg_fasyankes_id'))->first();
            $register = Register::create([
                'nomor_register' => $nomor_register,
                'nomor_rekam_medis' => null,
                'register_uuid' => (string) Str::uuid(),
                'creator_user_id' => $user->id,
                'sumber_pasien' => $request->get('reg_sumberpasien') == "Umum" ? "Umum" : $request->get('reg_sumberpasien_isian'),
                'jenis_registrasi' => 'rujukan',
                'dinkes_pengirim' => null,
                'other_dinas_pengirim' => null,
                'fasyankes_id' => $rs ? $rs->id : $request->get('reg_fasyankes_id'),
                'fasyankes_pengirim' => $request->get('reg_fasyankes_pengirim'),
                'nama_rs' => $rs ? $rs->nama : $request->get('reg_nama_rs'),
                'other_nama_rs' => $request->get('reg_nama_rs_lainnya'),
                'nama_dokter' => $request->get('reg_nama_dokter'),
                'no_telp' => $request->get('reg_telp_fas_pengirim'),
                'tanggal_kunjungan' => $request->get('reg_tanggalkunjungan'),
                'kunjungan_ke' => $request->get('reg_kunke'),
                'rs_kunjungan' => $request->get('reg_rsfasyankes'),
                'hasil_rdt' => null
            ]);

            $pasien = new Pasien;
            $pasien->nama_lengkap = $request->get('reg_nama_pasien');
            $pasien->kewarganegaraan = $request->get('reg_kewarganegaraan');
            $pasien->nik = $request->get('reg_nik');
            $pasien->tempat_lahir = $request->get('reg_tempatlahir');
            $pasien->tanggal_lahir = $request->get('reg_tgllahir');
            $pasien->no_hp = $request->get('reg_nohp');
            $pasien->provinsi_id = $request->get('provinsi_id');
            $pasien->kota_id = $request->get('kota_id');
            $pasien->kecamatan_id = $request->get('kecamatan_id');
            $pasien->kelurahan_id = $request->get('kelurahan_id');
            $pasien->kecamatan = $request->get('reg_kecamatan');
            $pasien->kelurahan = $request->get('reg_kelurahan');
            $pasien->alamat_lengkap = $request->get('reg_alamat');
            $pasien->no_rt = $request->get('reg_rt');
            $pasien->no_rw = $request->get('reg_rw');
            $pasien->suhu = parseDecimal($request->get('reg_suhu'));
            $pasien->jenis_kelamin = $request->get('reg_jk');
            $pasien->keterangan_lain = $request->get('reg_keterangan');
            $pasien->usia_tahun = $request->get('reg_usia_tahun');
            $pasien->usia_bulan = $request->get('reg_usia_bulan');
            $pasien->status = $request->get('status');
            $pasien->pelaporan_id = $request->get('pelaporan_id');
            $pasien->pelaporan_id_case = $request->get('pelaporan_id_case');
            $pasien->save();

            $regis = PasienRegister::create([
                'pasien_id' => $pasien->id,
                'register_id' => $register->id,
            ]);

            foreach ($request->get('samples') as $sm) {
                $sampel = Sampel::where('nomor_sampel', 'ilike', $sm['nomor_sampel'])->first();
                if ($sampel) {
                    $sampel->register_id = $register->id;
                    $sampel->nomor_register = $register->nomor_register;
                    $sampel->save();
                }
            }
            DB::commit();
            return response()->json(['status' => 201, 'message' => 'Proses Registrasi Rujukan Berhasil Ditambahkan', 'result' => []]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function delete($id, $pasien)
    {
        DB::beginTransaction();
        try {
            PasienRegister::where('register_id', $id)->where('pasien_id', $pasien)->delete();
            $sampel = Sampel::where('register_id', $id)->get();
            foreach ($sampel as $sm) {
                $sm->register_id = null;
                $sm->nomor_register = null;
                $sm->save();
            }
            $register = Register::where('id', $id)->delete();
            $pasien = Pasien::where('id', $pasien)->delete();
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Berhasil menghapus data register"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function getById(Request $request, $register_id, $pasien_id)
    {
        $register = Register::where('id', $register_id)->first();
        $pasien   = Pasien::where('pasien.id', $pasien_id)
            ->leftJoin('kota', 'kota.id', 'pasien.kota_id')
            ->leftJoin('provinsi', 'provinsi.id', 'pasien.provinsi_id')
            ->leftJoin('kecamatan', 'kecamatan.id', 'pasien.kecamatan_id')
            ->leftJoin('kelurahan', 'kelurahan.id', 'pasien.kelurahan_id')
            ->select('pasien.*', 'kecamatan.nama as kecamatan', 'provinsi.nama as provinsi', 'kota.nama as kota', 'kelurahan.nama as kelurahan')
            ->first();
        $sampel   = Sampel::where('register_id', $register_id)->get();
        if (!$pasien) {
            return response()->json([
                'status' => 400,
                'message' => 'Data Pasien Tidak Ditemukan',
                'result' => ['sampels' => $sampel]
            ]);
        }
        $smp = [];
        
        foreach ($sampel as $sm) {
            array_push($smp, array(
                'sampel_status' => $sm->sampel_status,
                'nomor_sampel' => $sm->nomor_sampel,
                'id' => $sm->id
            ));
        }
        return response()->json([
            'status' => 200,
            'message' => 'success',
            'result' => [
                'reg_no' =>  $register->nomor_register,
                'reg_kewarganegaraan' => $pasien->kewarganegaraan,
                'reg_sumberpasien' => $register->sumber_pasien,
                "reg_sumberpasien_isian" => $register->sumber_pasien == "Umum" ? null : $register->sumber_pasien,
                'reg_nama_pasien' => $pasien->nama_lengkap,
                'reg_nik' => $pasien->nik,
                'reg_tempatlahir' => $pasien->tempat_lahir,
                'reg_tgllahir' => $pasien->tanggal_lahir ? $pasien->tanggal_lahir->format('Y-m-d') : null,
                'reg_nohp' => $pasien->no_hp,
                'reg_provinsi_id' => $pasien->provinsi_id,
                'reg_provinsi' => $pasien->provinsi,
                'reg_kota_id' => $pasien->kota_id,
                'reg_kota' => $pasien->kota,
                'reg_kecamatan_id' => $pasien->kecamatan_id,
                'reg_kecamatan' => $pasien->kecamatan,
                'reg_kelurahan_id' => $pasien->kelurahan_id,
                'reg_kelurahan' => $pasien->kelurahan,
                'reg_alamat' => $pasien->alamat_lengkap,
                'reg_rt' => $pasien->no_rt,
                'reg_rw' => $pasien->no_rw,
                'reg_suhu' => $pasien->suhu,
                'samples' => $smp,
                'reg_keterangan' => $pasien->keterangan_lain,
                'reg_jk' => $pasien->jenis_kelamin,
                'reg_kunke' => $register->kunjungan_ke,
                'reg_tanggalkunjungan' => $register->tanggal_kunjungan,
                'reg_rs_kunjungan' => $register->rs_kunjungan,
                'reg_fasyankes_pengirim' => $register->fasyankes_pengirim,
                'reg_telp_fas_pengirim' => $register->no_telp,
                'reg_nama_dokter' => $register->nama_dokter,
                'reg_nama_rs' => $register->nama_rs,
                'reg_nama_rs_lainnya' => $register->other_nama_rs,
                'daerahlain' => $register->other_dinas_pengirim,
                'reg_dinkes_pengirim' => $register->dinkes_pengirim,
                'reg_no' => $register->nomor_register,
                'reg_rsfasyankes' => $register->rs_kunjungan,
                'reg_usia_tahun' => $pasien->usia_tahun,
                'reg_usia_bulan' => $pasien->usia_bulan,
                'reg_hasil_rdt' => $register->hasil_rdt,
                'reg_fasyankes_id' => $register->fasyankes_id,
                'nama_rs' => $register->nama_rs,
                'fasyankes_pengirim' => $register->fasyankes_pengirim,
                'status' => $pasien->status,
                'pelaporan_id' => $pasien->pelaporan_id,
                'pelaporan_id_case' => $pasien->pelaporan_id_case,
            ]
        ]);
    }

    public function storeUpdate(Request $request, $register_id, $pasien_id)
    {
        $user = Auth::user();
        $v = Validator::make($request->all(), [
            'reg_nohp' => 'nullable|max:15',
            'reg_kota_id' => 'required',
            'reg_alamat' => 'required',
            'reg_fasyankes_pengirim' => 'required',
            'reg_nama_rs' => 'required',
            'reg_nama_pasien' => 'required',
        ], [
            'reg_nama_pasien.required' => 'Nama Pasien tidak boleh kosong',
            'reg_kota_id' => 'Mohon pilih salah satu kota/kabupaten',
            'reg_alamat' => 'Alamat tidak boleh kosong',
            'reg_fasyankes_pengirim' => 'Form wajib diisi ',
            'reg_nama_rs' => 'Form wajib diisi ',
        ]);
        $v->validate();
        $nomor_register = $request->input('reg_no');
        $rs = Fasyankes::where('id', $request->get('reg_fasyankes_id'))->first();
        $register = Register::where('id', $register_id)->first();
        $registerOrigin = clone $register;
        $register->nomor_register =  $nomor_register;
        $register->fasyankes_id =  $rs ? $request->get('reg_fasyankes_id') : null;
        $register->nomor_rekam_medis =  null;

        $register->nama_dokter =  $request->get('reg_nama_dokter');
        $register->no_telp =  $request->get('reg_telp_fas_pengirim');
        $register->register_uuid =  (string) Str::uuid();
        $register->creator_user_id =  $user->id;
        $register->sumber_pasien =  $request->get('reg_sumberpasien') == "Umum" ? "Umum" : $request->get('reg_sumberpasien_isian');
        $register->jenis_registrasi =  'rujukan';
        $register->dinkes_pengirim =  $request->get('reg_dinkes_pengirim');
        $register->other_dinas_pengirim =  $request->get('daerahlain');
        $register->fasyankes_pengirim =  $request->get('reg_fasyankes_pengirim');
        $register->nama_rs =  $rs ? $rs->nama : $request->get('reg_nama_rs');
        $register->other_nama_rs =  $request->get('reg_nama_rs_lainnya');
        $register->tanggal_kunjungan =  $request->get('reg_tanggalkunjungan');
        $register->kunjungan_ke =  $request->get('reg_kunke');
        $register->rs_kunjungan =  $request->get('reg_rsfasyankes');
        $register->hasil_rdt = null;
        $register->save();

        $pasien = Pasien::where('id', $pasien_id)->first();
        $pasienOrigin = clone $pasien;

        $pasien->nama_lengkap = $request->get('reg_nama_pasien');
        $pasien->kewarganegaraan = $request->get('reg_kewarganegaraan');
        $pasien->nik = $request->get('reg_nik');
        $pasien->tempat_lahir = $request->get('reg_tempatlahir');
        $pasien->tanggal_lahir = $request->get('reg_tgllahir');
        $pasien->no_hp = $request->get('reg_nohp');
        $pasien->provinsi_id = $request->get('reg_provinsi_id');
        $pasien->kota_id = $request->get('reg_kota_id');
        $pasien->kecamatan_id = $request->get('reg_kecamatan_id');
        $pasien->kelurahan_id = $request->get('reg_kelurahan_id');
        $pasien->kecamatan = $request->get('reg_kecamatan');
        $pasien->kelurahan = $request->get('reg_kelurahan');
        $pasien->alamat_lengkap = $request->get('reg_alamat');
        $pasien->no_rt = $request->get('reg_rt');
        $pasien->no_rw = $request->get('reg_rw');
        $pasien->suhu = parseDecimal($request->get('reg_suhu'));
        $pasien->jenis_kelamin = $request->get('reg_jk');
        $pasien->keterangan_lain = $request->get('reg_keterangan');
        $pasien->usia_tahun = $request->get('reg_usia_tahun');
        $pasien->usia_bulan = $request->get('reg_usia_bulan');
        $pasien->status = $request->get('status');
        $pasien->pelaporan_id = $request->get('pelaporan_id');
        $pasien->pelaporan_id_case = $request->get('pelaporan_id_case');
        $pasien->save();

        $registerChanges = $register->getChanges();
        $pasienChanges = $pasien->getChanges();

        $registerLogs = array();
        foreach ($registerChanges as $key => $value) {
            if ($key != "updated_at" && $key != "register_uuid") {
                $registerLogs[$key]["from"] = $registerOrigin[$key];
                $registerLogs[$key]["to"] = $value;
            }
        }

        $pasienLogs = array();
        foreach ($pasienChanges as $key => $value) {
            if ($key != "updated_at") {
                $pasienLogs[$key]["from"] = $pasienOrigin[$key];
                $pasienLogs[$key]["to"] = $value;
            }
        }

        $register->logs()->create([
            "user_id" => $user->id,
            "info" => json_encode(array(
                "register" => $registerLogs,
                "pasien" => $pasienLogs
            ))
        ]);

        return response()->json(['status' => 200, 'message' => 'Proses Registrasi Rujukan Berhasil Diubah', 'result' => []]);
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable', // 'date|date_format:Y-m-d',
            'end_date' => 'nullable', // 'date|date_format:Y-m-d',
        ]);

        $payload = [];

        if ($request->has('start_date')) {
            $payload['startDate'] = parseDate($request->input('start_date'));
        }

        if ($request->has('end_date')) {
            // $payload['endDate'] = parseDate($request->input('end_date'));
            $payload['endDate'] = date('Y-m-d', strtotime($request->input('end_date') . "+1 days"));
        }
        return Excel::download(new RegisRujukanExport($payload), 'registrasi-rujukan-' . time() . '.xlsx');
    }
}
