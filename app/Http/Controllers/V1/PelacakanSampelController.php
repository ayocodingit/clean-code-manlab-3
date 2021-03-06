<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\PengambilanSampel;
use App\Models\Sampel;
use Illuminate\Http\Request;

class PelacakanSampelController extends Controller
{
    public function index(Request $request)
    {
        $models = Sampel::query(); // 'pcr_sample_analyzed'
        $models->where('sampel.is_from_migration', false);
        $params = $request->get('params', false);
        $search = $request->get('search', false);
        $order = $request->get('order', 'updated_at');

        if ($params) {
            $search = json_decode($params, true);

            if ($search['nomor_register']) {
                $models = $models->where('nomor_register', $search['nomor_register']);
            }

            if ($search['nomor_sampel']) {
                $models = $models->where('nomor_sampel', $search['nomor_sampel']);
            }

            if ($search['tanggal_start']) {
                $models = $models->whereDate('tanggal_pengambilan_sampel', '>=', date('Y-m-d', strtotime($search['tanggal_start'])));
            }

            if ($search['tanggal_end']) {
                $models = $models->whereDate('tanggal_pengambilan_sampel', '<=', date('Y-m-d', strtotime($search['tanggal_end'])));
            }

            if ($search['no_sampel_start']) {
                $val = $search['no_sampel_start'];
                if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                    $str = $val;
                    $n = 1;
                    $start = $n - strlen($str);
                    $str1 = substr($str, $start);
                    $str2 = substr($str, 0, $n);
                    $models = $models->whereRaw("nomor_sampel ilike '%$str2%'");
                    $models = $models->whereRaw("right(nomor_sampel,-1)::int >=  $str1");
                } else {
                    $models = $models->whereNull('nomor_sampel');
                }
            }
            if ($search['no_sampel_end']) {
                $val = $search['no_sampel_end'];
                if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                    $str = $val;
                    $n = 1;
                    $start = $n - strlen($str);
                    $str1 = substr($str, $start);
                    $str2 = substr($str, 0, $n);
                    $models = $models->whereRaw("nomor_sampel ilike '%$str2%'");
                    $models = $models->whereRaw("right(nomor_sampel,-1)::int <=  $str1");
                } else {
                    $models = $models->whereNull('nomor_sampel');
                }
            }

            if ($search['nama_pasien']) {
                $models = $models->with(['register'])->whereHas('register', function ($query) use ($search) {
                    $query->whereHas('pasiens', function ($query) use ($search) {
                        $query->where('nama_lengkap', 'ilike', '%'.$search['nama_pasien'].'%');
                    });
                });
            }

            if ($search['nik']) {
                $models = $models->with(['register'])->whereHas('register', function ($query) use ($search) {
                    $query->whereHas('pasiens', function ($query) use ($search) {
                        $query->where('nik', $search['nik']);
                    });
                });
            }

            if ($search['kategori']) {
                if ($search['kategori'] != 'isian') {
                    $val = $search['kategori'];
                    $models = $models->with(['register'])->whereHas('register', function ($query) use ($val) {
                        $query->where('register.sumber_pasien', 'ilike', "%$val%");
                    });
                }
            }

            if ($search['kategori_isian']) {
                $val = $search['kategori_isian'];
                $models = $models->with(['register'])->whereHas('register', function ($query) use ($val) {
                    $query->where('register.sumber_pasien', 'ilike', "%$val%");
                });
            }

            if ($search['fasyankes']) {
                $val = $search['fasyankes'];
                $models = $models->with(['register'])->whereHas('register', function ($query) use ($val) {
                    $query->where('register.fasyankes_id', $val);
                });
            }

            $searchCollection = collect($search)->filter(function ($item) {
                return $item !== '';
            });

            if (!$searchCollection->count()) {
                $models = $models->where('id', null);
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
                case 'updated_at':
                    $models = $models->orderBy($order, $order_direction);
                    break;
                case 'nomor_register':
                    $models = $models->orderBy($order, $order_direction);
                    break;
                case 'pasien_nama':
                    $models = $models->leftJoin('register', 'sampel.register_id', '=', 'register.id')
                        ->leftJoin('pasien_register', 'register.id', '=', 'pasien_register.register_id')
                        ->leftJoin('pasien', 'pasien_register.pasien_id', '=', 'pasien.id')
                        ->select('sampel.*')
                        ->addSelect('pasien.nama_lengkap')
                        ->distinct()
                        ->orderBy('nama_lengkap', $order_direction);
                    break;
                case 'nomor_sampel':
                    $models = $models->orderBy($order, $order_direction);
                    break;
                case 'sumber_pasien':
                    $models = $models->leftJoin('register', 'sampel.register_id', '=', 'register.id')
                    ->leftJoin('pasien_register', 'register.id', '=', 'pasien_register.register_id')
                    ->leftJoin('pasien', 'pasien_register.pasien_id', '=', 'pasien.id')
                    ->select('sampel.*')
                    ->addSelect('pasien.sumber_pasien')
                    ->distinct()
                    ->orderBy('sumber_pasien', $order_direction);
                        break;
                default:
                    $models = $models->orderBy('updated_at', $order_direction);
                    break;
            }
        }

        $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();

        // format data
        foreach ($models as &$model) {
            $model->register = $model->register ?? null;
            $model->pasien = $model->register ? optional($model->register)->pasiens()->with(['kota'])->first() : null;
            $model->pemeriksaanSampel = $model->pemeriksaanSampel()->orderBy('tanggal_input_hasil', 'desc')->first() ?? null;
            $model->validator = $model->validator ?? null;
        }

        return response()->json([
            'data' => $models,
            'count' => $count,
        ]);
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
        if ($sampel->nomor_register==null) {
            $pasien = null;
            $fasyankes = null;
        } else {
            $pasien = optional($sampel->register->pasiens()->with(['kota', 'kecamatan', 'kelurahan', 'provinsi']))->first();
            $fasyankes = $sampel->register->fasyankes;
        }
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
}
