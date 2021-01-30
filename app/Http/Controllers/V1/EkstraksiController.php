<?php

namespace App\Http\Controllers\V1;

use App\Enums\LabPCREnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\EditEkstraksiRequest;
use App\Http\Requests\TerimaEkstraksiRequest;
use Illuminate\Http\Request;
use App\Models\Sampel;
use App\Models\Ekstraksi;
use App\Models\PemeriksaanSampel;
use App\Models\LabPCR;
use Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class EkstraksiController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:isAdmin', ['only' => ['setSwabUlang']]);
    }

    public function getData(Request $request)
    {
        $models = Sampel::query();
        $params = $request->get('params', false);
        $search = $request->get('search', false);
        $order = $request->get('order', 'name');
        $models->where(function ($qr) {
            $qr->whereHas('pcr', function ($q) {
                $q->where('kesimpulan_pemeriksaan', '<>', 'inkonklusif')->orWhereNull('kesimpulan_pemeriksaan');
            })->orWhereDoesntHave('pcr');
        });
        if ($search != '') {
            $models = $models->where(function ($q) use ($search) {
                $q->where('nomor_register', 'ilike', '%'.$search.'%')
                   ->orWhere('nomor_sampel', 'ilike', '%'.$search.'%');
            });
        }
        if ($params) {
            $params = json_decode($params, true);
            foreach ($params as $key => $val) {
                if ($val == '' || is_array($val) && count($val) == 0) {
                    continue;
                }
                switch ($key) {
                    case 'lab_pcr_id':
                        $models->where('lab_pcr_id', $val);
                        if ($val == 999999) {
                            if (isset($params['lab_pcr_nama']) && !empty($params['lab_pcr_nama'])) {
                                $models->where('lab_pcr_nama', 'ilike', '%'.$params['lab_pcr_nama'].'%');
                            }
                        }
                        break;
                    case 'sampel_status':
                        if ($val == 'extraction_sent') {
                            $models->whereIn('sampel_status', [
                                'extraction_sample_sent',
                                'pcr_sample_received',
                                'pcr_sample_analyzed',
                                'sample_verified',
                                'sample_valid',
                            ]);
                        } else {
                            $models->where('sampel_status', $val);
                        }
                        $models->with(['ekstraksi', 'pcr', 'status']);
                        break;
                    case 'is_musnah_ekstraksi':
                        $models->where('is_musnah_ekstraksi', $val == 'true' ? true : false);
                        break;
                    case 'waktu_extraction_sample_sent':
                        $tgl = date('Y-m-d', strtotime($val));
                        $models->whereBetween('waktu_extraction_sample_sent', [$tgl.' 00:00:00', $tgl.' 23:59:59']);
                        break;
                    case 'kesimpulan_pemeriksaan':
                        $models->whereHas('pcr', function ($q) use ($val) {
                            $q->where('kesimpulan_pemeriksaan', $val);
                        });
                        break;
                    case 'no_sampel_start':
                        if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                            $str = $val;
                            $n = 1;
                            $start = $n - strlen($str);
                            $str1 = substr($str, $start);
                            $str2 = substr($str, 0, $n);
                            $models->whereRaw("nomor_sampel ilike '%$str2%'");
                            $models->whereRaw("right(nomor_sampel,-1)::int >=  $str1");
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
                            $models->whereRaw("right(nomor_sampel,-1)::int <=  $str1");
                        } else {
                            $models->whereNull('nomor_sampel');
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        $models = $models->where('is_from_migration', false);
        $count = $models->count();

        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 500);

        if ($order) {
            $order_direction = $request->get('order_direction', 'asc');
            if (empty($order_direction)) {
                $order_direction = 'asc';
            }

            switch ($order) {
                case 'nomor_register':
                case 'nomor_sampel':
                case 'waktu_sample_taken':
                case 'waktu_extraction_sample_extracted':
                case 'waktu_extraction_sample_sent':
                case 'waktu_extraction_sample_reextract':
                    $models = $models->orderBy($order, $order_direction);
                    // no break
                default:
                    break;
            }
        }
        $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();

        // format data
        foreach ($models as &$model) {
            $check = $model->logs->where('sampel_status', 'extraction_sample_reextract')->count();
            $model['re_ekstraksi'] = $check > 0 ? 're-ekstraksi' : null;
        }

        $result = [
            'data' => $models,
            'count' => $count,
        ];

        return response()->json($result);
    }

    public function detail(Request $request, $id)
    {
        $model = Sampel::with(['ekstraksi', 'status'])->find($id);
        $model->log_ekstraksi = $model->logs()
            ->whereIn('sampel_status', ['extraction_sample_extracted', 'extraction_sample_sent', 'extraction_sample_reextract'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['status' => 200, 'message' => 'success', 'data' => $model]);
    }

    public function edit(EditEkstraksiRequest $request, Sampel $sampel)
    {
        $user = $request->user();
        $lab_pcr = LabPCR::find($request->lab_pcr_id);
        $ekstraksi = Ekstraksi::firstOrNew(['sampel_id' => $sampel->id]);
        $ekstraksi->user_id = $ekstraksi->user_id ?? $user->id;
        $ekstraksi->sampel_id = $sampel->id;
        $ekstraksi->fill($request->validated());
        $ekstraksi->save();
        if ($sampel->sampel_status == 'extraction_sample_extracted') {
            $sampel->lab_pcr_id = $request->lab_pcr_id;
            $sampel->lab_pcr_nama = $lab_pcr->id == LabPCREnum::lainnya()->getIndex() ? $request->lab_pcr_nama : $lab_pcr->nama;
        }

        $tanggalEkstraksi = Carbon::parse($ekstraksi->tanggal_mulai_ekstraksi)->format('Y-m-d');
        $sampel->waktu_extraction_sample_extracted = $tanggalEkstraksi .' '. $ekstraksi->jam_mulai_ekstraksi;

        if ($ekstraksi->tanggal_pengiriman_rna) {
            $tanggalPengiriman = Carbon::parse($ekstraksi->tanggal_pengiriman_rna)->format('Y-m-d');
            $sampel->waktu_extraction_sample_sent = $tanggalPengiriman .' '. $ekstraksi->jam_pengiriman_rna;
        }

        $sampel->save();

        return response()->json(['message' => 'Perubahan berhasil disimpan']);
    }

    /**
     * terima sampel ekstraksi
     *
     * @param  mixed $request
     * @return void
     */
    public function terima(TerimaEkstraksiRequest $request)
    {
        $user = $request->user();

        $samples = Sampel::whereIn('nomor_sampel', Arr::pluck($request->samples, 'nomor_sampel'))->get();

        foreach ($samples as $sampel) {
            $ekstraksi = Ekstraksi::firstOrNew(['sampel_id' => $sampel->id]);
            $ekstraksi->user_id = $ekstraksi->user_id ?? $user->id;
            $ekstraksi->sampel_id = $sampel->id;
            $ekstraksi->fill($request->validated());
            $ekstraksi->save();

            $tanggalEkstraksi = Carbon::parse($ekstraksi->tanggal_mulai_ekstraksi)->format('Y-m-d');

            $sampel->waktu_extraction_sample_extracted = $tanggalEkstraksi .' '. $ekstraksi->jam_mulai_ekstraksi;
            $sampel->updateState('extraction_sample_extracted', [
                'user_id' => $user->id,
                'metadata' => $ekstraksi,
                'description' => 'Sampel diekstraksi',
            ]);
        }

        return response()->json(['message' => 'Penerimaan sampel berhasil dicatat']);
    }

    public function setInvalid(Request $request, $id)
    {
        $user = $request->user();
        $sampel = Sampel::with(['status'])->find($id);
        if (!$sampel) {
            return response()->json(['success' => false, 'code' => 422, 'message' => 'Sampel tidak ditemukan'], 422);
        }
        if ($sampel->sampel_status != 'extraction_sample_reextract' && $sampel->sampel_status != 'sample_taken') {
            return response()->json(['success' => false, 'code' => 422, 'message' => 'Status sampel sudah pada tahap '.$sampel->status->deskripsi.', sehingga tidak dapat ditandai sebagai invalid'], 422);
        }
        $ekstraksi = $sampel->ekstraksi;
        if (!$ekstraksi) {
            $ekstraksi = new Ekstraksi();
            $ekstraksi->sampel_id = $sampel->id;
            $ekstraksi->user_id = $user->id;
        }
        $ekstraksi->catatan_pengiriman = $request->alasan;
        $ekstraksi->save();

        $sampel->updateState('sample_invalid', [
            'user_id' => $user->id,
            'metadata' => $ekstraksi,
            'description' => 'Sample marked as invalid',
        ]);

        return response()->json(['success' => true, 'code' => 201, 'message' => 'Sampel berhasil ditandai sebagai invalid']);
    }

    public function setProses(Request $request, $id)
    {
        $user = $request->user();
        $sampel = Sampel::with(['status'])->find($id);
        if (!$sampel) {
            return response()->json(['success' => false, 'code' => 422, 'message' => 'Sampel tidak ditemukan'], 422);
        }
        if ($sampel->sampel_status != 'extraction_sample_reextract') {
            return response()->json(['success' => false, 'code' => 422, 'message' => 'Status sampel sudah pada tahap '.$sampel->status->deskripsi.', sehingga tidak dapat ditandai sebagai proses'], 422);
        }
        $ekstraksi = $sampel->ekstraksi;
        if (!$ekstraksi) {
            $ekstraksi = new Ekstraksi();
            $ekstraksi->sampel_id = $sampel->id;
            $ekstraksi->user_id = $user->id;
        }
        $ekstraksi->catatan_penerimaan = $request->alasan;
        $ekstraksi->save();

        $sampel->updateState('extraction_sample_extracted', [
            'user_id' => $user->id,
            'metadata' => $ekstraksi,
            'description' => 'Sampel diekstraksi',
        ]);

        return response()->json(['success' => true, 'code' => 201, 'message' => 'Sampel berhasil ditandai sebagai sampel proses']);
    }

    public function kirim(Request $request)
    {
        $user = $request->user();
        $v = Validator::make($request->all(), [
            'tanggal_pengiriman_rna' => 'required',
            'jam_pengiriman_rna' => 'required',
            'nama_pengirim_rna' => 'required',
            'lab_pcr_id' => 'required',
            'lab_pcr_nama' => 'required_if:lab_pcr_id,999999',
        ]);
        $samples = Sampel::whereIn('nomor_sampel', Arr::pluck($request->samples, 'nomor_sampel'))->get()->keyBy('nomor_sampel');
        $lab_pcr = LabPCR::find($request->lab_pcr_id);

        foreach ($request->samples as $key => $item) {
            if (!isset($item['nomor_sampel']) || !isset($samples[$item['nomor_sampel']])) {
                $v->after(function ($validator) {
                    $validator->errors()->add('samples', 'Ada sampel yang tidak valid');
                });
            }
        }
        if (!$lab_pcr) {
            $v->after(function ($validator) {
                $validator->errors()->add('samples', 'Lab PCR Tidak ditemukan');
            });
        }

        $v->validate();

        foreach ($samples as $nomor_sampel => $sampel) {
            $ekstraksi = $sampel->ekstraksi;
            if (!$ekstraksi) {
                $ekstraksi = new Ekstraksi();
                $ekstraksi->sampel_id = $sampel->id;
                $ekstraksi->user_id = $user->id;
            }
            $ekstraksi->tanggal_pengiriman_rna = parseDate($request->tanggal_pengiriman_rna);
            $ekstraksi->jam_pengiriman_rna = parseTime($request->jam_pengiriman_rna);
            $ekstraksi->nama_pengirim_rna = $request->nama_pengirim_rna;
            $ekstraksi->catatan_pengiriman = $request->catatan_pengiriman;
            $ekstraksi->save();

            $sampel->lab_pcr_id = $request->lab_pcr_id;
            $sampel->lab_pcr_nama = $lab_pcr->id == 999999 ? $request->lab_pcr_nama : $lab_pcr->nama;

            $tanggalKirim = Carbon::parse($ekstraksi->tanggal_pengiriman_rna)->format('Y-m-d');

            $sampel->waktu_extraction_sample_sent = Carbon::parse($tanggalKirim.' '.$ekstraksi->jam_pengiriman_rna)->format('Y-m-d H:i:s');
            // $sampel->waktu_extraction_sample_sent = date('Y-m-d H:i:s', strtotime($ekstraksi->tanggal_pengiriman_rna . ' ' .$ekstraksi->jam_pengiriman_rna));

            $sampel->updateState('extraction_sample_sent', [
                'user_id' => $user->id,
                'metadata' => $ekstraksi,
                'description' => 'RNA dikirim ke lab PCR',
            ]);
        }

        return response()->json(['status' => 201, 'message' => 'Pengiriman sampel berhasil dicatat']);
    }

    public function kirimUlang(Request $request)
    {
        $user = $request->user();
        $v = Validator::make($request->all(), [
            'operator_ekstraksi' => 'required',
            'tanggal_mulai_ekstraksi' => 'required',
            'jam_mulai_ekstraksi' => 'required',
            'metode_ekstraksi' => 'required',
            'nama_kit_ekstraksi' => 'required',
            'tanggal_pengiriman_rna' => 'required',
            'jam_pengiriman_rna' => 'required',
            'nama_pengirim_rna' => 'required',
            'catatan_pengiriman' => 'nullable',
            'lab_pcr_id' => 'required',
            'lab_pcr_nama' => 'required_if:lab_pcr_id,999999',
        ]);
        $samples = Sampel::whereIn('nomor_sampel', Arr::pluck($request->samples, 'nomor_sampel'))->get()->keyBy('nomor_sampel');
        $lab_pcr = LabPCR::find($request->lab_pcr_id);

        foreach ($request->samples as $key => $item) {
            if (!isset($item['nomor_sampel']) || !isset($samples[$item['nomor_sampel']])) {
                $v->after(function ($validator) {
                    $validator->errors()->add('samples', 'Ada sampel yang tidak valid');
                });
            }
        }
        if (!$lab_pcr) {
            $v->after(function ($validator) {
                $validator->errors()->add('samples', 'Lab PCR Tidak ditemukan');
            });
        }

        $v->validate();

        foreach ($samples as $nomor_sampel => $sampel) {
            $ekstraksi = $sampel->ekstraksi;
            if (!$ekstraksi) {
                $ekstraksi = new Ekstraksi();
                $ekstraksi->sampel_id = $sampel->id;
                $ekstraksi->user_id = $user->id;
            }
            $ekstraksi->operator_ekstraksi = $request->operator_ekstraksi;
            $ekstraksi->tanggal_mulai_ekstraksi = parseDate($request->tanggal_mulai_ekstraksi);
            $ekstraksi->jam_mulai_ekstraksi = parseTime($request->jam_mulai_ekstraksi);
            $ekstraksi->metode_ekstraksi = $request->metode_ekstraksi;
            $ekstraksi->nama_kit_ekstraksi = $request->nama_kit_ekstraksi;
            $ekstraksi->tanggal_pengiriman_rna = parseDate($request->tanggal_pengiriman_rna);
            $ekstraksi->jam_pengiriman_rna = parseTime($request->jam_pengiriman_rna);
            $ekstraksi->nama_pengirim_rna = $request->nama_pengirim_rna;
            $ekstraksi->catatan_pengiriman = $request->catatan_pengiriman;
            $ekstraksi->save();

            $sampel->lab_pcr_id = $request->lab_pcr_id;
            $sampel->lab_pcr_nama = $lab_pcr->id == 999999 ? $request->lab_pcr_nama : $lab_pcr->nama;
            $sampel->waktu_extraction_sample_sent = date('Y-m-d H:i:s', strtotime($ekstraksi->tanggal_pengiriman_rna.' '.$ekstraksi->jam_pengiriman_rna));
            $sampel->updateState('extraction_sample_sent', [
                'user_id' => $user->id,
                'metadata' => $ekstraksi,
                'description' => 'RNA dikirim ke lab PCR',
            ]);
        }

        return response()->json(['status' => 201, 'message' => 'Pengiriman ulang sampel berhasil dicatat']);
    }

    public function musnahkan(Request $request, $id)
    {
        $user = $request->user();
        $sampel = Sampel::with(['status'])->find($id);
        if (!$sampel) {
            return response()->json(['success' => false, 'code' => 422, 'message' => 'Sampel tidak ditemukan'], 422);
        }
        $ekstraksi = $sampel->ekstraksi;
        if (!$ekstraksi) {
            $ekstraksi = new Ekstraksi();
            $ekstraksi->sampel_id = $sampel->id;
            $ekstraksi->user_id = $user->id;
        }
        $sampel->is_musnah_ekstraksi = true;
        $sampel->save();

        $sampel->addLog([
            'user_id' => $user->id,
            'metadata' => $ekstraksi,
            'description' => 'Sample marked as destroyed at extraction chamber',
        ]);

        return response()->json(['success' => true, 'code' => 201, 'message' => 'Sampel berhasil ditandai telah dimusnahkan']);
    }

    public function setKurang(Request $request, $id)
    {
        $user = $request->user();
        $sampel = Sampel::with(['status', 'pcr'])->find($id);
        if (!$sampel) {
            return response()->json(['success' => false, 'code' => 422, 'message' => 'Sampel tidak ditemukan'], 422);
        }
        if ($sampel->sampel_status != 'sample_invalid') {
            return response()->json(['success' => false, 'code' => 422, 'message' => 'Status sampel sudah pada tahap '.$sampel->status->deskripsi.', sehingga tidak dapat ditandai sebagai sampel kurang'], 422);
        }
        $pcr = $sampel->pcr;
        if (!$pcr) {
            $pcr = new PemeriksaanSampel();
            $pcr->sampel_id = $sampel->id;
            $pcr->user_id = $user->id;
        }
        $pcr->kesimpulan_pemeriksaan = 'sampel kurang';
        $pcr->save();

        $sampel->updateState('pcr_sample_analyzed', [
            'user_id' => $user->id,
            'metadata' => $pcr,
            'description' => 'Sample marked as insufficient',
        ]);

        return response()->json(['success' => true, 'code' => 201, 'message' => 'Sampel berhasil ditandai sebagai sampel kurang']);
    }

    public function setSwabUlang(Request $request, $id)
    {
        $user = $request->user();
        $sampel = Sampel::with(['status', 'pcr'])->find($id);
        if (!$sampel) {
            return response()->json(['success' => false, 'code' => 422, 'message' => 'Sampel tidak ditemukan'], 422);
        }
        $pcr = $sampel->pcr;
        if (!$pcr) {
            $pcr = new PemeriksaanSampel();
            $pcr->sampel_id = $sampel->id;
            $pcr->user_id = $user->id;
        }
        $pcr->kesimpulan_pemeriksaan = 'swab_ulang';
        $pcr->catatan_pemeriksaan = $request->alasan;
        $pcr->save();

        $sampel->updateState('swab_ulang', [
            'user_id' => $user->id,
            'metadata' => $pcr,
            'description' => 'Sample marked as need to be re-swab',
        ]);

        return response()->json(['success' => true, 'code' => 201, 'message' => 'Sampel berhasil ditandai sebagai sampel yang perlu swab ulang']);
    }
}
