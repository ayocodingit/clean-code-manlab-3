<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkTesMasifRequest;
use App\Models\Pasien;
use App\Models\PasienRegister;
use App\Models\PengambilanSampel;
use App\Models\Register;
use App\Models\Sampel;
use App\Models\TesMasif;
use App\Rules\ExistsWilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Validator;

class TesMasifController extends Controller
{
    public function index(Request $request)
    {
        $models = TesMasif::with('kota');

        $params = $request->get('params', false);
        $order = $request->get('order', 'nama_pasien');
        $order_direction = $request->get('order_direction', 'asc');
        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 20);
        $status = $request->get('status', 'waiting');
        switch ($status) {
            case "taken":
                $models = $models->where('available', false);
                break;
            case "waiting":
                $models = $models->where('available', true);
                break;
        }
        if ($params) {
            foreach (json_decode($params) as $param => $value) {
                if ($value == '') {
                    continue;
                }
                switch ($param) {
                    case 'nama_nik':
                        $models = $models->where(function ($q) use ($value) {
                            $q->where('nama_pasien', 'ilike', '%' . $value . '%')
                                ->orWhere('nik', 'ilike', '%' . $value . '%');
                        });
                        break;
                    case 'tanggal_kunjungan_mulai':
                        $models = $models->where('tanggal_kunjungan', '>=', $value);
                        break;
                    case 'tanggal_kunjungan_akhir':
                        $models = $models->where('tanggal_kunjungan', '<=', $value);
                        break;
                    case 'kategori':
                        $models = $models->where('kategori', 'ilike', '%' . $value . '%');
                        break;
                }
            }
        }
        $count = $models->count();

        switch ($order) {
            default:
                $models = $models->orderBy($order, $order_direction);
                break;
        }

        $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();

        $result = [
            'data' => $models,
            'count' => $count,
        ];

        return response()->json($result);
    }

    public function bulkTesMasif(BulkTesMasifRequest $request)
    {
        $data = $request->get('data');
        $result['berhasil'] = [];
        $result['gagal'] = [];
        foreach ($data as $row) {
            DB::beginTransaction();
            try {
                $nomor_sampel = $row['nomor_sampel'];
                $row['kriteria'] = strtolower($row['kriteria']);
                $validator = Validator::make($row, $this->rules($nomor_sampel));
                if ($validator->fails()) {
                    $result['gagal'][] = [
                        'nomor_sampel' => $nomor_sampel,
                        'message' => "errors",
                        'result' => $validator->messages()->get('*'),
                    ];
                    continue;
                }

                $tesMasif = new TesMasif;
                $tesMasif->fill($row);
                $tesMasif->save();
                $result['berhasil'][] = $nomor_sampel;
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                $result['gagal'][] = [
                    'nomor_sampel' => $nomor_sampel,
                    'message' => $th,
                    'result' => [],
                ];
            }
        }
        if (count($result['gagal']) >= count($data)) {
            return response()->json(['status' => 500, 'message' => 'Tes Masif Gagal Ditambahkan', 'result' => $result], 500);
        }
        return response()->json(['status' => 200, 'message' => 'Tes Masif Berhasil Ditambahkan', 'result' => $result]);
    }

    public function bulk(Request $request)
    {
        DB::beginTransaction();
        $user = $request->user();
        try {
            $registerIds = $request->get('id');
            $dataTesMasif = TesMasif::whereIn('id', $registerIds)->where('available', true);
            abort_if($dataTesMasif->doesntExist(), 500, 'data tidak ditemukan');
            foreach ($dataTesMasif->get() as $tes_masif) {
                $tes_masif['nama_lengkap'] = $tes_masif['nama_pasien'];
                $tes_masif['nomor_register'] = $tes_masif['registration_code'];
                $tes_masif['status'] = $tes_masif['kriteria'];
                $tes_masif['catatan'] = $tes_masif['keterangan'];
                $tes_masif['waktu_sample_taken'] = $tes_masif['created_at'];
                $tes_masif['creator_user_id'] = $user->id;
                $tes_masif['sampel_status'] = 'sample_taken';
                $tes_masif['register_uuid'] = Str::uuid();
                $register = new Register;
                $register->fill($tes_masif->toArray());
                $register->save();

                $pasien = new Pasien;
                $pasien->fill($tes_masif->toArray());
                $pasien->save();

                PasienRegister::create([
                    'pasien_id' => $pasien->id,
                    'register_id' => $register->id,
                ]);

                $pengambilan_sampel = new PengambilanSampel;
                $pengambilan_sampel->fill($tes_masif->toArray());
                $pengambilan_sampel->save();

                $tes_masif['register_id'] = $register->id;
                $tes_masif['nomor_register'] = $register->nomor_register;
                $tes_masif['pengambilan_sampel_id'] = $pengambilan_sampel->id;

                $sampel = new Sampel();
                $sampel->fill($tes_masif->toArray());
                $sampel->save();

                $sampel->updateState($sampel->sampel_status, [
                    'user_id' => $user->id,
                    'metadata' => $sampel,
                    'description' => 'Data Sampel Teregistrasi',
                ]);
                $tes_masif = TesMasif::find($tes_masif->id);
                $tes_masif->available = false;
                $tes_masif->save();
            }
            DB::commit();
            return response()->json(['status' => 200, 'message' => 'success']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['status' => 500, 'message' => $th->getMessage()], 500);
        }
    }

    public function rules($nomor_sampel): array
    {
        return [
            'registration_code' => [
                'required',
                'unique:tes_masif,registration_code',
                'unique:register,nomor_register,NULL,id,deleted_at,NULL'
            ],
            'nama_pasien' => 'required|min:3',
            'jenis_registrasi' => 'required|in:mandiri,tes-masif',
            'nomor_sampel' => [
                'required',
                'regex:/^' . Sampel::NUMBER_FORMAT_TES_MASIF . '$/',
                Rule::unique('tes_masif')->where(function ($query) use ($nomor_sampel) {
                    return $query->where('nomor_sampel', 'ilike', $nomor_sampel);
                }),
                Rule::unique('sampel')->where(function ($query) use ($nomor_sampel) {
                    return $query->where('nomor_sampel', 'ilike', $nomor_sampel);
                }),
            ],
            'fasyankes_id' => 'required|integer|exists:fasyankes,id',
            'kewarganegaraan' => 'nullable',
            'kategori' => 'nullable',
            'kriteria' => Rule::in(array_map('strtolower', Pasien::STATUSES)),
            'nik' => 'nullable|digits:16',
            'tempat_lahir' => 'nullable',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable',
            'provinsi_id' => [
                'nullable',
                new ExistsWilayah,
            ],
            'kota_id' => [
                'nullable',
                new ExistsWilayah,
            ],
            'kecamatan_id' => [
                'nullable',
                new ExistsWilayah,
            ],
            'kelurahan_id' => [
                'nullable',
                new ExistsWilayah,
            ],
            'alamat' => 'required',
            'rt' => 'nullable',
            'rw' => 'nullable',
            'no_hp' => 'nullable',
            'suhu' => ['nullable', 'regex:/^[0-9]+(\.[0-9][0-9]?)?$/'],
            'keterangan' => "nullable",
            'hasil_rdt' => "nullable",
            'usia_tahun' => 'nullable|integer',
            'usia_bulan' => 'nullable|integer',
            'dokter' => 'nullable',
            'telp_fasyankes' => 'nullable|regex:@^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$@',
            'kunjungan' => 'nullable|integer',
            'tanggal_kunjungan' => 'required|date',
            'rs_kunjungan' => 'nullable',
        ];
    }
}
