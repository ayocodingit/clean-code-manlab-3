<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Sampel;
use Illuminate\Http\Request;

class PelacakanSampelController extends Controller
{
    public function index(Request $request, $isData = false)
    {
        $models = Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
            ->leftJoin('register', 'register.id', 'sampel.register_id')
            ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
            ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
            ->leftJoin('kota', 'pasien.kota_id', 'kota.id')
            ->leftJoin('validator', 'sampel.validator_id', 'validator.id')
            ->where('sampel.is_from_migration', false);

        $models->whereNull('register.deleted_at');

        $params = $request->get('params', false);
        $order = $request->get('order', 'nomor_sampel');
        $paramsIsNull = true;
        if ($params) {
            $params = json_decode($params, true);
            foreach ($params as $key => $val) {
                if ($val !== false && ($val == '' || is_array($val) && count($val) == 0)) {
                    continue;
                }
                $paramsIsNull = false;
                switch ($key) {
                    case 'kesimpulan_pemeriksaan':
                        $models->where('kesimpulan_pemeriksaan', $val);
                        break;
                    case 'kota_domisili':
                        $models->where('pasien.kota_id', $val);
                        break;
                    case 'nik':
                        $models->where('nik', $val);
                        break;
                    case 'nomor_register':
                        $models->where('register.nomor_register', $val);
                    break;
                    case 'nama_pasien':
                        $models->where('nama_lengkap', 'ilike', "%$val%");
                        break;
                    case 'kategori':
                        $models->where('register.sumber_pasien', 'ilike', '%'.$val.'%');
                        break;
                    case 'reg_fasyankes_id':
                        $models->where('register.fasyankes_id', $val);
                    break;
                    case 'tanggal_start':
                        $models->whereDate('waktu_sample_taken', '>=', date('Y-m-d', strtotime($val)));
                        break;
                    case 'tanggal_end':
                        $models->whereDate('waktu_sample_taken', '<=', date('Y-m-d', strtotime($val)));
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

        $count = !$paramsIsNull ? $models->count() : 0;

        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 500);

        if ($order) {
            $order_direction = $request->get('order_direction', 'asc');
            if (empty($order_direction)) {
                $order_direction = 'desc';
            }

            switch ($order) {
                case 'status_sampel':
                    $models = $models->orderBy('sampel_status', $order_direction);
                    break;
                case 'nomor_register':
                    $models = $models->orderBy($order, $order_direction);
                    break;
                case 'pasien_nama':
                    $models = $models->orderBy('nama_lengkap', $order_direction);
                    break;
                case 'nomor_sampel':
                    $models = $models->orderBy('sampel.nomor_register', $order_direction);
                    break;
                case 'sumber_pasien':
                    $models = $models->orderBy('register.sumber_pasien', $order_direction);
                    break;
                case 'kesimpulan_pemeriksaan':
                    $models = $models->orderBy('kesimpulan_pemeriksaan', $order_direction);
                    break;
                case 'validator':
                    $models = $models->orderBy('validator.nama', $order_direction);
                    break;
                case 'kota_nama':
                    $models = $models->orderBy('kota.nama', $order_direction);
                    break;
                case 'kunjungan_ke':
                    $models = $models->orderBy('kunjungan_ke', $order_direction);
                break;
                case 'fasyankes':
                    $models = $models->orderBy('nama_rs', $order_direction);
                break;
                default:
                    break;
            }
        }

        if (!$isData) {
            $models = $models->select(
                'nomor_sampel',
                'sampel_status',
                'sampel.id as id',
                'nama_lengkap',
                'usia_tahun',
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
                'petugas_pengambilan_sampel',
                'kunjungan_ke',
                'validator.nama as nama_validator'
            );
            $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();
        } else {
            $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();
        }

        return !$isData ? response()->json([
            'data' => !$paramsIsNull ? $models : [],
            'count' => $count,
        ]) : $models;
    }
}
