<?php

namespace App\Http\Controllers\V1;

use App\Events\SampelValidatedEvent;
use App\Exports\AjaxTableExport;
use App\Http\Controllers\Controller;
use App\Models\PemeriksaanSampel;
use App\Models\PengambilanSampel;
use App\Models\Sampel;
use App\Models\Validator;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ValidasiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
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
            ->where('sampel_status', 'sample_verified');

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
                case 'jenis_kelamin':
                    $models = $models->orderBy($order, $order_direction);
                    break;
                default:
                    break;
            }
        }

        if (!$isData) {
            $models = $models->select(
                'nomor_sampel',
                'sampel.id as id',
                'nama_lengkap',
                'nik',
                'jenis_kelamin',
                'register.sumber_pasien',
                'jenis_registrasi',
                'nama_rs',
                'other_nama_rs',
                'dinkes_pengirim',
                'other_dinas_pengirim',
                'kota.nama as nama_kota',
                'hasil_deteksi',
                'kesimpulan_pemeriksaan',
                'tanggal_lahir',
                'usia_tahun',
                'register.nomor_register as nomor_register',
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
                'waktu_sample_valid'
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
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function indexValidated(Request $request, $isData = false)
    {
        $models = Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
            ->leftJoin('register', 'register.id', 'sampel.register_id')
            ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
            ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
            ->leftJoin('kota', 'pasien.kota_id', 'kota.id')
            ->leftJoin('provinsi', 'pasien.provinsi_id', 'provinsi.id')
            ->leftJoin('kecamatan', 'pasien.kecamatan_id', 'kecamatan.id')
            ->leftJoin('kelurahan', 'pasien.kelurahan_id', 'kelurahan.id')
            ->where('sampel_status', 'sample_valid')
            ->where('sampel.is_from_migration', false); // 'pcr_sample_analyzed'

        $models->where('pemeriksaansampel.is_from_migration', false);
        $models->where('register.is_from_migration', false);
        $models->where('pasien.is_from_migration', false);
        $models->where('pasien_register.is_from_migration', false);
        $models->whereNull('register.deleted_at');

        $params = $request->get('params', false);
        $search = $request->get('search', false);
        $order = $request->get('order', 'tanggal_divalidasi');

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
                    case 'tanggal_validasi_start':
                        $models->whereDate('waktu_sample_valid', '>=', date('Y-m-d', strtotime($val)));
                        break;
                    case 'tanggal_validasi_end':
                        $models->whereDate('waktu_sample_valid', '<=', date('Y-m-d', strtotime($val)));
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
            $order_direction = $request->get('order_direction', 'desc');
            if (empty($order_direction)) {
                $order_direction = 'desc';
            }

            switch ($order) {
                case 'tanggal_divalidasi':
                    $models = $models->orderBy('waktu_sample_valid', $order_direction);
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
                case 'jenis_kelamin':
                    $models = $models->orderBy($order, $order_direction);
                    break;
                default:
                    break;
            }
        }

        if (!$isData) {
            $models = $models->select(
                'nomor_sampel',
                'sampel.id as id',
                'nama_lengkap',
                'nik',
                'jenis_kelamin',
                'register.sumber_pasien',
                'jenis_registrasi',
                'nama_rs',
                'other_nama_rs',
                'dinkes_pengirim',
                'other_dinas_pengirim',
                'kota.nama as nama_kota',
                'hasil_deteksi',
                'kesimpulan_pemeriksaan',
                'tanggal_lahir',
                'usia_tahun',
                'register.nomor_register as nomor_register',
                'counter_print_hasil',
                'waktu_sample_valid',
                'waktu_sample_print',
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
                'waktu_sample_valid'
            );
            $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();
        }

        return !$isData ? response()->json([
            'data' => $models,
            'count' => $count,
        ]) : $models;
    }

    public function exportValidasi(Request $request)
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
            'Tanggal Validasi',
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
                parseDate($model->waktu_sample_valid),
            ];
        };
        $column_format = [
        ];

        return Excel::download(new AjaxTableExport($models, $header, $mapping, $column_format, 'Validasi', 'AA', $models->count()), 'Validasi-'.time().'.xlsx');
    }

    public function exportTervalidasi(Request $request)
    {
        ini_set('max_execution_time', '0');

        $models = $this->indexValidated($request, true);
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
            'Tanggal Validasi',
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
                parseDate($model->waktu_sample_valid),
            ];
        };
        $column_format = [
        ];

        return Excel::download(new AjaxTableExport($models, $header, $mapping, $column_format, 'Tervalidasi', 'AA', $models->count()), 'Tervalidasi-'.time().'.xlsx');
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
        $result = $sampel->load(['pemeriksaanSampel', 'status', 'register', 'validator', 'ekstraksi', 'logs'])->toArray();
        $pasien = $sampel->register ? optional($sampel->register->pasiens()->with(['kota', 'kecamatan', 'kelurahan', 'provinsi']))->first() : null;
        $fasyankes = $sampel->register ? $sampel->register->fasyankes : null;
        $pengambilanSampel = PengambilanSampel::find($sampel->getAttribute('pengambilan_sampel_id'));

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

    public function getValidator()
    {
        return response()->json([
            'status' => 200,
            'message' => 'success',
            'data' => Validator::whereIsActive(true)->get(),
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
    public function updateToValidate(Request $request, Sampel $sampel)
    {
        $request->validate([
            'validator' => 'required|exists:validator,id',
            'catatan_pemeriksaan' => 'nullable|max:255',
            'last_pemeriksaan_id' => 'required|exists:pemeriksaansampel,id',
        ], $request->only(['validator', 'catatan_pemeriksaan', 'last_pemeriksaan_id']));

        DB::beginTransaction();
        try {
            PemeriksaanSampel::find($request->input('last_pemeriksaan_id'))->update([
                'catatan_pemeriksaan' => $request->input('catatan_pemeriksaan'),
            ]);

            if (!$sampel->nomor_register) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Nomor registrasi belum terdaftar'
                ], 400);
            }

            $user = $request->user();
            $pcr = PemeriksaanSampel::find($request->input('last_pemeriksaan_id'));
            $sampel->addLog([
                'user_id' => $user->id,
                'metadata' => $pcr,
                'description' => 'Hasil pemeriksaan tervalidasi',
            ], 'sample_valid');

            $sampel->update([
                'validator_id' => $request->input('validator'),
                'sampel_status' => 'sample_valid',
                'waktu_sample_valid' => now(),
            ]);

            DB::commit();

            event(new SampelValidatedEvent($sampel));

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
     *
     * @return \Illuminate\Http\Response
     */
    public function bulkValidate(Request $request)
    {
        $request->validate([
            'sampels' => 'required|array',
            'validator' => 'required|exists:validator,id',
        ], $request->all());

        DB::beginTransaction();
        try {
            $uniqueSampelIds = collect($request->input('sampels'))->unique();

            $samples = Sampel::whereIn('id', $uniqueSampelIds)->get();

            foreach ($samples as $sample) {
                if ($sample->nomor_register == "" || $sample->nomor_register == null || !$sample->nomor_register) {
                    return response()->json([
                        'valid' => false,
                        'error' => 'Sampel '.$sample->nomor_sampel.' belum memiliki Nomor register',
                    ], 400);
                }
                $user = $request->user();
                $sample->addLog([
                    'user_id' => $user->id,
                    'metadata' => $sample,
                    'description' => 'Hasil pemeriksaan tervalidasi',
                ], 'sample_valid');

                $sample->update([
                    'validator_id' => $request->input('validator'),
                    'sampel_status' => 'sample_valid',
                    'waktu_sample_valid' => now(),
                ]);
            }

            DB::commit();
            return response()->json([
                'data' => null,
                'status' => 200,
                'message' => 'success',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    /**
     * Regenerate PDF Hasil Pemeriksaan after Validation.
     */
    public function regeneratePdfHasil(Sampel $sampel)
    {
        event(new SampelValidatedEvent($sampel));

        return response()->json([
            'data' => null,
            'status' => 200,
            'message' => 'success',
        ]);
    }

    // tolak sample hasil verivikasi
    public function rejectSample(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->id;
            $sampel = Sampel::find($id);
            $sampel->updateState('pcr_sample_analyzed', [
                'user_id' => Auth::user()->id,
                'metadata' => $sampel,
                'description' => 'Verifikasi ditolak (mohon ditinjau kembali)',
            ]);
            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'success',
                'data' => $sampel,
            ]);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 500,
                'message' => 'failed',
                'data' => $e,
            ]);
        }
    }
}
