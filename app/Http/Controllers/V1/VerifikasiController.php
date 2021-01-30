<?php

namespace App\Http\Controllers\V1;

use App\Exports\AjaxTableExport;
use App\Http\Controllers\Controller;
use App\Models\PemeriksaanSampel;
use App\Models\PengambilanSampel;
use App\Models\Register;
use App\Models\Sampel;
use App\Models\StatusSampel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

class VerifikasiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $isData = false)
    {
        $models = Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
            ->leftJoin('register', 'register.id', 'sampel.register_id')
            ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
            ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
            ->leftJoin('kota', 'pasien.kota_id', 'kota.id')
            ->leftJoin('provinsi', 'pasien.provinsi_id', 'provinsi.id')
            ->leftJoin('kecamatan', 'pasien.kecamatan_id', 'kecamatan.id')
            ->leftJoin('kelurahan', 'pasien.kelurahan_id', 'kelurahan.id')
            ->leftJoin('ekstraksi', 'sampel.id', 'ekstraksi.sampel_id')
            ->whereIn('sampel_status', ['pcr_sample_analyzed', 'inkonklusif', 'swab_ulang'])
            ->where('pemeriksaansampel.kesimpulan_pemeriksaan', '!=', 'invalid');

        $models->where('sampel.is_from_migration', false);
        $models->where('pemeriksaansampel.is_from_migration', false);
        $models->where('register.is_from_migration', false);
        $models->where('pasien.is_from_migration', false);
        $models->where('pasien_register.is_from_migration', false);
        $models->whereNull('register.deleted_at');

        $params = $request->get('params', false);
        $search = $request->get('search', false);
        $order = $request->get('order', 'waktu_sample_verified');

        if ($params) {
            $params = json_decode($params, true);
            foreach ($params as $key => $val) {
                if ($val !== false && ($val == '' || is_array($val) && count($val) == 0)) {
                    continue;
                }
                switch ($key) {
                    case 'kesimpulan_pemeriksaan':
                        $models->where('kesimpulan_pemeriksaan', $val);
                        break;
                    case 'kota_domisili':
                        $models->where('pasien.kota_id', $val);
                        break;
                    case 'fasyankes':
                        $models->where('fasyankes_id', $val);
                        break;
                    case 'kategori':
                        $models->where('register.sumber_pasien', 'ilike', '%'.$val.'%');
                        break;
                    case 'tanggal_verifikasi_start':
                        $models->whereDate('waktu_sample_verified', '>=', date('Y-m-d', strtotime($val)));
                        break;
                    case 'tanggal_verifikasi_end':
                        $models->whereDate('waktu_sample_verified', '<=', date('Y-m-d', strtotime($val)));
                        break;
                    case 'no_sampel_start':
                        if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                            $str = $val;
                            $n = 1;
                            $start = $n - strlen($str);
                            $str1 = substr($str, $start);
                            $str2 = substr($str, 0, $n);
                            $models->whereRaw("nomor_sampel ilike '%$str2%'");
                            $models->whereRaw("right(nomor_sampel,-1)::bigint >=  $str1");
                        } else {
                            $models->whereNull('nomor_sampel');
                        }
                        break;
                    case 'no_sampel_end':
                        if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                            $str = $val;
                            $n = 1;
                            $start = $n - strlen($str);
                            $str1 = substr($str, $start);
                            $str2 = substr($str, 0, $n);
                            $models->whereRaw("nomor_sampel ilike '%$str2%'");
                            $models->whereRaw("right(nomor_sampel,-1)::bigint <=  $str1");
                        } else {
                            $models->whereNull('nomor_sampel');
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        $count = $models->count();

        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 500);

        if ($order) {
            $order_direction = $request->get('order_direction', 'asc');
            if (empty($order_direction)) {
                $order_direction = 'desc';
            }

            switch ($order) {
                case 'created_at':
                    $models = $models->orderBy('waktu_sample_verified', $order_direction);
                    break;
                case 'nomor_register':
                    $models = $models->orderBy($order, $order_direction);
                    break;
                case 'pasien_nama':
                    $models = $models->orderBy('nama_lengkap', $order_direction);
                    break;
                case 'nomor_sampel':
                    $models = $models->orderBy($order, $order_direction);
                    break;
                default:
                    break;
            }
        }

        if (!$isData) {
            $models = $models->select(
                'sampel.nomor_register',
                'nama_lengkap',
                'nik',
                'tanggal_lahir',
                'usia_tahun',
                'kota.nama as nama_kota',
                'register.sumber_pasien',
                'nomor_sampel',
                'hasil_deteksi',
                'kesimpulan_pemeriksaan',
                'sampel_status',
                'sampel.id as id',
                'status',
                'petugas_pengambilan_sampel'
            );
            $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();
        } else {
            $models = $models->select(
                'register.nomor_register',
                'nomor_sampel',
                'register.sumber_pasien',
                'status',
                'nama_lengkap',
                'nik',
                'tanggal_lahir',
                'usia_tahun',
                'tempat_lahir',
                'jenis_kelamin',
                'kota.nama as nama_kota',
                'alamat_lengkap',
                'no_rt',
                'no_rw',
                'pasien.no_telp',
                'pasien.no_hp',
                'nama_rs',
                'other_nama_rs',
                'dinkes_pengirim',
                'other_dinas_pengirim',
                'fasyankes_pengirim',
                'jenis_sampel_nama',
                'hasil_deteksi',
                'kesimpulan_pemeriksaan',
                'register.created_at',
                'waktu_sample_taken',
                'tanggal_input_hasil',
                'provinsi.nama as provinsi',
                'kecamatan.nama as kecamatan',
                'kelurahan.nama as kelurahan',
                'tanggal_mulai_ekstraksi'
            );
            $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();
        }

        return !$isData ? response()->json([
            'data' => $models,
            'count' => $count,
        ]) : $models;
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request
     *
     * @return \Illuminate\Http\Response
     */
    public function indexVerified(Request $request, $isData = false)
    {
        $models = Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
            ->leftJoin('register', 'register.id', 'sampel.register_id')
            ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
            ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
            ->leftJoin('kota', 'pasien.kota_id', 'kota.id')
            ->leftJoin('provinsi', 'pasien.provinsi_id', 'provinsi.id')
            ->leftJoin('kecamatan', 'pasien.kecamatan_id', 'kecamatan.id')
            ->leftJoin('kelurahan', 'pasien.kelurahan_id', 'kelurahan.id')
            ->leftJoin('ekstraksi', 'sampel.id', 'ekstraksi.sampel_id')
            ->whereIn('sampel_status', ['sample_verified', 'sample_valid'])
            ->where('sampel.is_from_migration', false); // 'pcr_sample_analyzed'

        $models->where('ekstraksi.is_from_migration', false);
        $models->where('pemeriksaansampel.is_from_migration', false);
        $models->where('register.is_from_migration', false);
        $models->where('pasien.is_from_migration', false);
        $models->where('pasien_register.is_from_migration', false);
        $models->whereNull('register.deleted_at');

        $params = $request->get('params', false);
        $search = $request->get('search', false);

        $order = $request->get('order', 'waktu_sample_verified');

        if ($params) {
            $params = json_decode($params, true);
            foreach ($params as $key => $val) {
                if ($val !== false && ($val == '' || is_array($val) && count($val) == 0)) {
                    continue;
                }
                switch ($key) {
                    case 'kesimpulan_pemeriksaan':
                        $models->where('kesimpulan_pemeriksaan', $val);
                        break;
                    case 'kota_domisili':
                        $models->where('pasien.kota_id', $val);
                        break;
                    case 'fasyankes':
                        $models->where('fasyankes_id', $val);
                        break;
                    case 'kategori':
                        $models->where('register.sumber_pasien', 'ilike', '%'.$val.'%');
                        break;
                    case 'tanggal_verifikasi_start':
                        $models->whereDate('waktu_sample_verified', '>=', date('Y-m-d', strtotime($val)));
                        break;
                    case 'tanggal_verifikasi_end':
                        $models->whereDate('waktu_sample_verified', '<=', date('Y-m-d', strtotime($val)));
                        break;
                    case 'no_sampel_start':
                        if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                            $str = $val;
                            $n = 1;
                            $start = $n - strlen($str);
                            $str1 = substr($str, $start);
                            $str2 = substr($str, 0, $n);
                            $models->whereRaw("nomor_sampel ilike '%$str2%'");
                            $models->whereRaw("right(nomor_sampel,-1)::bigint >=  $str1");
                        } else {
                            $models->whereNull('nomor_sampel');
                        }
                        break;
                    case 'no_sampel_end':
                        if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                            $str = $val;
                            $n = 1;
                            $start = $n - strlen($str);
                            $str1 = substr($str, $start);
                            $str2 = substr($str, 0, $n);
                            $models->whereRaw("nomor_sampel ilike '%$str2%'");
                            $models->whereRaw("right(nomor_sampel,-1)::bigint <=  $str1");
                        } else {
                            $models->whereNull('nomor_sampel');
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        $count = $models->count();

        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 500);

        if ($order) {
            $order_direction = $request->get('order_direction', 'asc');
            if (empty($order_direction)) {
                $order_direction = 'desc';
            }

            switch ($order) {
                case 'created_at':
                    $models = $models->orderBy('waktu_sample_verified', $order_direction);
                    break;
                case 'nomor_register':
                    $models = $models->orderBy($order, $order_direction);
                    break;
                case 'pasien_nama':
                    $models = $models->orderBy('nama_lengkap', $order_direction);
                    break;
                case 'nomor_sampel':
                    $models = $models->orderBy($order, $order_direction);
                    break;
                default:
                    break;
            }
        }

        if (!$isData) {
            $models = $models->select(
                'sampel.nomor_register',
                'nama_lengkap',
                'nik',
                'tanggal_lahir',
                'usia_tahun',
                'kota.nama as nama_kota',
                'register.sumber_pasien',
                'nomor_sampel',
                'hasil_deteksi',
                'kesimpulan_pemeriksaan',
                'sampel_status',
                'waktu_sample_verified',
                'sampel.id as id',
                'pasien.status',
                'petugas_pengambilan_sampel'
            );
            $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();
        } else {
            $models = $models->select(
                'register.nomor_register',
                'nomor_sampel',
                'register.sumber_pasien',
                'status',
                'nama_lengkap',
                'nik',
                'tanggal_lahir',
                'usia_tahun',
                'tempat_lahir',
                'jenis_kelamin',
                'kota.nama as nama_kota',
                'alamat_lengkap',
                'no_rt',
                'no_rw',
                'pasien.no_telp',
                'pasien.no_hp',
                'nama_rs',
                'other_nama_rs',
                'dinkes_pengirim',
                'other_dinas_pengirim',
                'fasyankes_pengirim',
                'jenis_sampel_nama',
                'hasil_deteksi',
                'kesimpulan_pemeriksaan',
                'register.created_at',
                'waktu_sample_taken',
                'tanggal_input_hasil',
                'provinsi.nama as provinsi',
                'kecamatan.nama as kecamatan',
                'kelurahan.nama as kelurahan',
                'tanggal_mulai_ekstraksi',
                'waktu_sample_valid'
            );
            $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();
        }

        return !$isData ? response()->json([
            'data' => $models,
            'count' => $count,
        ]) : $models;
    }

    public function exportVerifikasi(Request $request)
    {
        ini_set('max_execution_time', '0');

        $models = $this->index($request, true);
        $no = (int) ($request->get('page', 1) - 1) * $request->get('perpage', 500) + 1;
        foreach ($models as $idx => &$model) {
            $model->no = $no++;
        }
        $header = [
            'No',
            'No Registrasi',
            'Kode Sampel',
            'Kategori',
            'Status',
            'Nama Pasien',
            'NIK',
            'Usia',
            'Satuan',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Domisili',
            'Alamat',
            'RT',
            'RW',
            'No Telp',
            'Instansi Pengirim',
            'Nama Fasyankes/Dinkes',
            'Tipe Sampel',
            'Parameter Lab',
            'CT Hasil',
            'Hasil',
            'Tanggal Registrasi',
            'Tanggal Terima Sampel',
            'Tanggal Pemeriksaan',
            'Tanggal Mulai Ekstraksi',
        ];
        $mapping = function ($model) {
            return [
                $model->no,
                $model->nomor_register,
                $model->nomor_sampel,
                $model->sumber_pasien,
                $model->status ? STATUSES[$model->status] : null,
                $model->nama_lengkap,
                "'".$model->nik,
                usiaPasien($model->tanggal_lahir, $model->usia_tahun),
                'Tahun',
                $model->tempat_lahir,
                parseDate($model->tanggal_lahir),
                $model->jenis_kelamin,
                $model->nama_kota,
                alamatLengkap($model->alamat_lengkap, $model->provinsi, $model->nama_kota, $model->kecamatan, $model->kelurahan),
                $model->no_rt,
                $model->no_rw,
                $model->no_hp ?? $model->no_telp,
                removeUnderscore($model->fasyankes_pengirim),
                $model->nama_rs,
                $model->jenis_sampel_nama,
                $model->hasil_deteksi != null ?
                hasil_deteksi(json_decode($model->hasil_deteksi)) : null,
                $model->hasil_deteksi != null ?
                getHasilDeteksiTerkecil($model->hasil_deteksi) : null,
                removeUnderscore($model->kesimpulan_pemeriksaan),
                parseDate($model->created_at),
                parseDate($model->waktu_sample_taken),
                parseDate($model->tanggal_input_hasil),
                parseDate($model->tanggal_mulai_ekstraksi),
            ];
        };
        $column_format = [
        ];

        return Excel::download(new AjaxTableExport($models, $header, $mapping, $column_format, 'Verifikasi', 'AB', $models->count()), 'Verifikasi-'.time().'.xlsx');
    }

    public function exportTerverifikasi(Request $request)
    {
        ini_set('max_execution_time', '0');

        $models = $this->indexVerified($request, true);
        $no = (int) ($request->get('page', 1) - 1) * $request->get('perpage', 500) + 1;
        foreach ($models as $idx => &$model) {
            $model->no = $no++;
        }
        $header = [
            'No',
            'No Registrasi',
            'Kode Sampel',
            'Kategori',
            'Status',
            'Nama Pasien',
            'NIK',
            'Usia',
            'Satuan',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Domisili',
            'Alamat',
            'RT',
            'RW',
            'No Telp',
            'Instansi Pengirim',
            'Nama Fasyankes/Dinkes',
            'Tipe Sampel',
            'Parameter Lab',
            'CT Hasil',
            'Hasil',
            'Tanggal Registrasi',
            'Tanggal Terima Sampel',
            'Tanggal Pemeriksaan',
            'Tanggal Mulai Ekstraksi',
            'Tanggal Validasi'
        ];
        $mapping = function ($model) {
            return [
                $model->no,
                $model->nomor_register,
                $model->nomor_sampel,
                $model->sumber_pasien,
                $model->status ? STATUSES[$model->status] : null,
                $model->nama_lengkap,
                "'".$model->nik,
                usiaPasien($model->tanggal_lahir, $model->usia_tahun),
                'Tahun',
                $model->tempat_lahir,
                parseDate($model->tanggal_lahir),
                $model->jenis_kelamin,
                $model->nama_kota,
                alamatLengkap($model->alamat_lengkap, $model->provinsi, $model->nama_kota, $model->kecamatan, $model->kelurahan),
                $model->no_rt,
                $model->no_rw,
                $model->no_hp ?? $model->no_telp,
                removeUnderscore($model->fasyankes_pengirim),
                $model->nama_rs,
                $model->jenis_sampel_nama,
                $model->hasil_deteksi != null ?
                hasil_deteksi(json_decode($model->hasil_deteksi)) : null,
                $model->hasil_deteksi != null ?
                getHasilDeteksiTerkecil($model->hasil_deteksi) : null,
                removeUnderscore($model->kesimpulan_pemeriksaan),
                parseDate($model->created_at),
                parseDate($model->waktu_sample_taken),
                parseDate($model->tanggal_input_hasil),
                parseDate($model->tanggal_mulai_ekstraksi),
                parseDate($model->waktu_sample_valid),
            ];
        };
        $column_format = [
        ];

        return Excel::download(new AjaxTableExport($models, $header, $mapping, $column_format, 'Terverifikasi', 'AB', $models->count()), 'Terverifikasi-'.time().'.xlsx');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Sampel $sampel
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Sampel $sampel)
    {
        $result = $sampel->load(['pemeriksaanSampel', 'status', 'register', 'ekstraksi', 'logs'])->toArray();
        $pasien = $sampel->register ? optional($sampel->register->pasiens()->with(['kota', 'kecamatan', 'kelurahan', 'provinsi']))->first() : null;
        $fasyankes = $sampel->register ? $sampel->register->fasyankes : null;
        $pengambilanSampel = PengambilanSampel::find($sampel->getAttribute('pengambilan_sampel_id'));

        //add logs with status re_pcr and re_exctration
        $receive_pcr_count = 1;
        $receive_extraction_count = 1;
        foreach ($result['logs'] as $key => $log) {
            if ($log['description'] == 'Receive PCR') {
                $receive_pcr_count++;
                if ($receive_pcr_count >= 2) {
                    // $log['re_pcr'] = 're-pcr';
                    $result['logs'][$key]['re_pcr'] = 're-pcr';
                }
            }
            if ($log['description'] == 'Receive Excraction') {
                $receive_extraction_count++;
                if ($receive_extraction_count >= 2) {
                    $result['logs'][$key]['re_extraction'] = 're-extraction';
                    // $log['re_exctraction'] = 're-extraction';
                }
            }
        }
        //end 'add logs with status re_pcr and re_exctration'

        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => $result + [
                'pasien' => optional($pasien)->toArray(),
                'last_pemeriksaan_sampel' => $sampel->pemeriksaanSampel()->orderBy('tanggal_input_hasil', 'desc')->first(),
                'fasyankes' => $fasyankes,
                'pengambilanSampel' => $pengambilanSampel,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function sampelStatusList()
    {
        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => StatusSampel::where('sampel_status', '!=', 'sample_verified')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Sampel       $sampel
     *
     * @return \Illuminate\Http\Response
     */
    public function updateToVerified(Request $request, Sampel $sampel)
    {
        $v = Validator::make($request->all(), [
            'kesimpulan_pemeriksaan' => 'required|in:positif,negatif,invalid,inkonklusif',
            'catatan_pemeriksaan' => 'nullable|max:255',
            'last_pemeriksaan_id' => 'required|exists:pemeriksaansampel,id',
            'hasil_deteksi.*.target_gen' => 'required',
            'hasil_deteksi.*.ct_value' => 'required',
            // 'grafik' => 'required',
        ]);
        if (count($request->hasil_deteksi) < 1) {
            $v->after(function ($validator) {
                $validator->errors()->add('samples', 'Minimal 1 hasil deteksi CT Value');
            });
        }
        $v->validate();

        DB::beginTransaction();
        try {
            PemeriksaanSampel::find($request->input('last_pemeriksaan_id'))->update([
                'kesimpulan_pemeriksaan' => $request->input('kesimpulan_pemeriksaan'),
                'catatan_pemeriksaan' => $request->input('catatan_pemeriksaan'),
                'hasil_deteksi' => $this->__parseHasilDeteksi($request->hasil_deteksi),
            ]);

            $user = $request->user();
            $pcr = PemeriksaanSampel::find($request->input('last_pemeriksaan_id'));
            $sampel->addLog([
                'user_id' => $user->id,
                'metadata' => $pcr,
                'description' => 'Data pasien dan sampel terverifikasi',
            ], 'sample_verified');

            $sampel->update([
                'sampel_status' => 'sample_verified',
                'waktu_sample_verified' => now(),
            ]);



            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'success',
                'data' => Sampel::find($sampel->id),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Sampel       $sampel
     *
     * @return \Illuminate\Http\Response
     */
    public function verifiedSingleSampel(Request $request, Sampel $sampel)
    {
        $user = $request->user();
        $sampel->addLog([
            'user_id' => $user->id,
            'metadata' => $sampel,
            'description' => 'Data pasien dan sampel terverifikasi',
        ], 'sample_verified');

        $sampel->update([
            'sampel_status' => 'sample_verified',
            'waktu_sample_verified' => now(),
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => Sampel::find($sampel->id),
        ]);
    }

    public function listKategori()
    {
        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => Register::select('sumber_pasien')
                ->whereNotNull('sumber_pasien')
                ->groupBy('sumber_pasien')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }

    private function __parseHasilDeteksi($hasilDeteksi)
    {
        if (!$hasilDeteksi || empty($hasilDeteksi)) {
            return null;
        }

        return collect($hasilDeteksi)->map(function ($hasilCT) {
            $values = explode(',', $hasilCT['ct_value']);

            if ($hasilCT['ct_value'] && count($values) > 1) {
                $hasilCT['ct_value'] = (float) implode('.', $values);
            }

            return $hasilCT;
        });
    }
}
