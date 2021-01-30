<?php

namespace App\Http\Controllers;

use App\Exports\AjaxTableExport;
use App\Exports\RegisMandiriExport;
use App\Models\PasienRegister;
use App\Models\Sampel;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RegistrasiMandiri extends Controller
{
    public function getData(Request $request, $isData = false)
    {
        $models = PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
            ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
            ->leftJoin('fasyankes', 'fasyankes.id', 'register.fasyankes_id')
            ->leftJoin('kota', 'kota.id', 'pasien.kota_id')
            ->leftJoin('provinsi', 'pasien.provinsi_id', 'provinsi.id')
            ->leftJoin('kecamatan', 'pasien.kecamatan_id', 'kecamatan.id')
            ->leftJoin('kelurahan', 'pasien.kelurahan_id', 'kelurahan.id')
            ->leftJoin('sampel', 'sampel.register_id', 'register.id')
            ->where('sampel.is_from_migration', false)
            ->whereNull('sampel.deleted_at')
            ->where('pasien.is_from_migration', false)
            ->where('pasien_register.is_from_migration', false)
            ->whereNull('register.deleted_at');

        $models = $models->where('register.is_from_migration', false);
        $params = $request->get('params', false);
        $search = $request->get('search', false);
        $order = $request->get('order', 'name');

        if ($params) {
            foreach (json_decode($params) as $key => $val) {
                if ($val == '') {
                    continue;
                }

                switch ($key) {
                    case "nama_pasien":
                        $models = $models->where('pasien.nama_lengkap', 'ilike', '%' . $val . '%');
                        break;
                    case "nomor_register":
                        $models = $models->where('register.nomor_register', 'ilike', '%' . $val . '%');
                        break;
                    case "nomor_sampel":
                        $sampel = Sampel::where('nomor_sampel', 'ilike', $val)->pluck('register_id');
                        $models = $models->whereIn('register.id', $sampel);
                        break;
                    case "start_date":
                        $models = $models->where('register.created_at', '>=', substr($val, 0, 10) . ' 00:00:00');
                        break;
                    case "end_date":
                        $models = $models->where('register.created_at', '<=', substr($val, 0, 10) . ' 23:59:59');
                        break;
                    case "sumber_pasien":
                        $models = $models->where('register.sumber_pasien', $val);
                        break;
                    case "sumber_sampel":
                        $models = $models->where('register.nama_rs', $val);
                        break;
                    case "nama_rs":
                        $models = $models->where("register.nama_rs", 'ilike', '%' . $val . '%');
                        break;
                    case "nama_rs_id":
                        $models = $models->where("register.fasyankes_id", $val);
                        break;
                    case "nama_rs_lainnya":
                        $models = $models->where("register.other_nama_rs", 'ilike', '%' . $val . '%');
                        break;
                    case "kota":
                        $models = $models->where('kota.id', $val);
                        break;
                    case "kategori":
                        if ($val != 'isian') {
                            $models = $models->where('register.sumber_pasien', 'ilike', "%$val%");
                        }
                        break;
                    case "kategori_isian":
                        $models = $models->where('register.sumber_pasien', 'ilike', "%$val%");
                        break;
                    case "start_nomor_sampel":
                        if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                            $str = $val;
                            $n = 1;
                            $start = $n - strlen($str);
                            $str1 = substr($str, $start);
                            $str2 = substr($str, 0, $n);
                            $models->whereRaw("sampel.nomor_sampel ilike '%$str2%'");
                            $models->whereRaw("right(sampel.nomor_sampel,-1)::bigint >=  $str1");
                        } else {
                            $models->whereNull('sampel.nomor_sampel');
                        }
                        break;
                    case "end_nomor_sampel":
                        if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                            $str = $val;
                            $n = 1;
                            $start = $n - strlen($str);
                            $str1 = substr($str, $start);
                            $str2 = substr($str, 0, $n);
                            $models->whereRaw("sampel.nomor_sampel ilike '%$str2%'");
                            $models->whereRaw("right(sampel.nomor_sampel,-1)::bigint <=  $str1");
                        } else {
                            $models->whereNull('sampel.nomor_sampel');
                        }
                        break;
                    case "reg_fasyankes_pengirim":
                        $models = $models->where('register.fasyankes_pengirim', 'ilike', $val);
                        break;
                    case "reg_fasyankes_id":
                        break;
                    default:
                        $models = $models->where($key, $val);
                        break;
                }
            }
        }

        if ($order) {
            $order_direction = $request->get('order_direction', 'asc');
            if (empty($order_direction)) {
                $order_direction = 'desc';
            }

            switch ($order) {
                case 'nama_lengkap':
                case 'nama_pasien':
                    $models = $models->orderBy('pasien.nama_lengkap', $order_direction);
                    break;
                case 'created_at':
                case 'tgl_input':
                    $models = $models->orderBy('register.created_at', $order_direction);
                    break;
                case 'nomor_register':
                    $models = $models->orderBy('register.nomor_register', $order_direction);
                    break;
                case 'nama_kota':
                    $models = $models->orderBy('kota.nama', $order_direction);
                    break;
                case 'sumber_pasien':
                    $models = $models->orderBy('register.sumber_pasien', $order_direction);
                    break;
                case 'nama_rs':
                    $models = $models->orderBy('register.nama_rs', $order_direction);
                    break;
                case 'no_sampel':
                    $models = $models->orderBy('sampel.nomor_sampel', $order_direction);
                    break;
                case 'status':
                    $models = $models->orderBy('pasien.status', $order_direction);
                    break;
                default:
                    break;
            }
        }
        if (!$isData) {
            $models = $models->select(
                'register.nomor_register',
                'pasien_register.register_id',
                'pasien_register.pasien_id',
                'pasien.nama_lengkap',
                'pasien.nik',
                'pasien.status',
                'pasien.usia_bulan',
                'pasien.usia_tahun',
                'kota.nama as nama_kota',
                'register.created_at as tgl_input',
                'register.sumber_pasien',
                'register.jenis_registrasi',
                'register.dinkes_pengirim',
                'register.sumber_pasien',
                'register.nama_rs',
                'register.other_nama_rs',
                'sampel.nomor_sampel',
                'sampel.sampel_status')->distinct();
        } else {
            $models = $models->select(
                'register.nomor_register',
                'sampel.nomor_sampel',
                'register.sumber_pasien',
                'pasien.status',
                'pasien.nama_lengkap',
                'pasien.nik',
                'pasien.usia_tahun',
                'pasien.tanggal_lahir',
                'pasien.tempat_lahir',
                'pasien.jenis_kelamin',
                'pasien.alamat_lengkap',
                'kota.nama as nama_kota',
                'provinsi.nama as provinsi',
                'kecamatan.nama as kecamatan',
                'kelurahan.nama as kelurahan',
                'pasien.no_rt',
                'pasien.no_rw',
                'pasien.no_hp',
                'register.no_telp',
                'register.fasyankes_pengirim',
                'register.nama_rs',
                'register.nama_dokter',
                'register.kunjungan_ke',
                'register.created_at')->distinct();
        }

        $count = $models->count('register.nomor_register');

        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 500);

        $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();

        $result = [
            'data' => $models,
            'count' => $count,
        ];

        return !$isData ? response()->json($result) : $models;
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

        return Excel::download(new RegisMandiriExport($payload), 'registrasi-mandiri-' . time() . '.xlsx');
    }

    public function exportMandiri(Request $request)
    {
        ini_set('max_execution_time', '0');

        $models = $this->getData($request, true);
        $no = (int)($request->get('page', 1) - 1) * $request->get('perpage', 500) + 1;
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
            'Provinsi',
            'Kota',
            'Kecamatan',
            'Kelurahan',
            'Alamat',
            'RT',
            'RW',
            'No. HP',
            'Kunjungan Ke',
            'Tanggal Registrasi',
        ];
        $mapping = function ($model) {
            return [
                $model->no,
                $model->nomor_register,
                $model->nomor_sampel,
                $model->sumber_pasien,
                $model->status ? STATUSES[$model->status] : null,
                $model->nama_lengkap,
                "'" . $model->nik,
                usiaPasien($model->tanggal_lahir, $model->usia_tahun),
                'Tahun',
                $model->tempat_lahir,
                parseDate($model->tanggal_lahir),
                $model->jenis_kelamin,
                $model->provinsi,
                $model->nama_kota,
                $model->kecamatan,
                $model->kelurahan,
                $model->alamat_lengkap,
                $model->no_rt,
                $model->no_rw,
                $model->no_hp ?? $model->no_telp,
                $model->kunjungan_ke,
                parseDate($model->created_at),
            ];
        };
        $column_format = [
        ];

        return Excel::download(new AjaxTableExport($models, $header, $mapping, $column_format, 'Registrasi Mandiri', 'V', $models->count()), 'Registrasi-Mandiri-' . time() . '.xlsx');
    }

    public function exportRujukan(Request $request)
    {
        ini_set('max_execution_time', '0');
        $models = $this->getData($request, true);
        $no = (int)($request->get('page', 1) - 1) * $request->get('perpage', 500) + 1;
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
            'Provinsi',
            'Kota',
            'Kecamatan',
            'Kelurahan',
            'Alamat',
            'RT',
            'RW',
            'No. HP',
            'Instansi Pengirim',
            'Nama Fasyankes/Dinkes',
            'Dokter',
            'Telp Fasyankes',
            'Kunjungan Ke',
            'Tanggal Registrasi',
        ];
        $mapping = function ($model) {
            return [
                $model->no,
                $model->nomor_register,
                $model->nomor_sampel,
                $model->sumber_pasien,
                $model->status ? STATUSES[$model->status] : null,
                $model->nama_lengkap,
                "'" . $model->nik,
                usiaPasien($model->tanggal_lahir, $model->usia_tahun),
                'Tahun',
                $model->tempat_lahir,
                parseDate($model->tanggal_lahir),
                $model->jenis_kelamin,
                $model->provinsi,
                $model->nama_kota,
                $model->kecamatan,
                $model->kelurahan,
                $model->alamat_lengkap,
                $model->no_rt,
                $model->no_rw,
                $model->no_hp ?? $model->no_telp,
                str_replace("_", " ", $model->fasyankes_pengirim),
                $model->nama_rs,
                $model->nama_dokter,
                $model->no_telp,
                $model->kunjungan_ke,
                parseDate($model->created_at),
            ];
        };
        $column_format = [
        ];

        return Excel::download(new AjaxTableExport($models, $header, $mapping, $column_format, 'Registrasi Rujukan', 'Z', $models->count()), 'Registrasi-Rujukan-' . time() . '.xlsx');
    }

}
