<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Sampel;
use Illuminate\Http\Request;

class EkstraksiController extends Controller
{
    public function getData(Request $request)
    {
        $sampel = Sampel::leftjoin('jenis_sampel', 'sampel.jenis_sampel_id', 'jenis_sampel.id')
            ->leftJoin('status_sampel', 'status_sampel.sampel_status', 'sampel.sampel_status')
            ->leftJoin('ekstraksi', 'ekstraksi.sampel_id', 'sampel.id')
            ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
            ->leftJoin('lab_pcr', 'lab_pcr.id', 'sampel.lab_pcr_id')
            ->where('sampel.is_from_migration', false);

        $sampel->select(
            'sampel.id',
            'sampel.nomor_sampel',
            'sampel.sampel_status',
            'sampel.nomor_register',
            'sampel.jenis_sampel_nama',
            'sampel.waktu_sample_taken',
            'sampel.waktu_extraction_sample_extracted',
            'status_sampel.deskripsi',
            'sampel.petugas_pengambilan_sampel',
            'ekstraksi.catatan_pengiriman',
            'ekstraksi.catatan_penerimaan',
            'ekstraksi.operator_ekstraksi',
            'sampel.waktu_extraction_sample_sent',
            'sampel.waktu_extraction_sample_reextract',
            'sampel.is_musnah_ekstraksi',
            'pemeriksaansampel.kesimpulan_pemeriksaan',
            'lab_pcr.nama as lab_pcr_nama'
        );

        // sort handler
        $order = $request->get('order', 'nomor_sampel');
        $order_direction = $request->get('order_direction', 'asc');
        $sampel->orderBy($order, $order_direction);
        $params = json_decode($request->get('params', false));

        // filter handler
        if ($params) {
            $params = json_decode($request->get('params'), true);
            foreach ($params as $key => $val) {
                if ($val == '' || is_array($val) && count($val) == 0) {
                    continue;
                }
                switch ($key) {
                    case 'kesimpulan_pemeriksaan':
                        $sampel->where('pemeriksaansampel.kesimpulan_pemeriksaan', '=', $val);
                        break;
                    case 'status_pemeriksaan':
                        if ($val != 'extraction_sample_sent' && $val != 'extraction_sent') {
                            $sampel->where('sampel.sampel_status', $val);
                        }
                        break;
                    case 'is_musnah_ekstraksi':
                        $sampel->where('sampel.is_musnah_ekstraksi', '=', $val);
                        break;
                    case 'waktu_extraction_sample_sent':
                        $tgl = date('Y-m-d', strtotime($val));
                        $sampel->whereDate('waktu_extraction_sample_sent', $tgl);
                        break;
                    case 'no_sampel_start':
                        if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                            $str = $val;
                            $n = 1;
                            $start = $n - strlen($str);
                            $str1 = substr($str, $start);
                            $str2 = substr($str, 0, $n);
                            $sampel->whereRaw("nomor_sampel ilike '%$str2%'");
                            $sampel->whereRaw("right(nomor_sampel,-1)::bigint >=  $str1");
                        } else {
                            $sampel->whereNull('nomor_sampel');
                        }
                        break;
                    case 'no_sampel_end':
                        if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                            $str = $val;
                            $n = 1;
                            $start = $n - strlen($str);
                            $str1 = substr($str, $start);
                            $str2 = substr($str, 0, $n);
                            $sampel->whereRaw("nomor_sampel ilike '%$str2%'");
                            $sampel->whereRaw("right(nomor_sampel,-1)::bigint <=  $str1");
                        } else {
                            $sampel->whereNull('nomor_sampel');
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        // filter sample status by menu handler
        $params = json_decode($request->get('params', false));
        switch ($params->sampel_status) {
            case 'sample_taken':
                // halaman pengambilan sampel
                $sampel->whereIn('sampel.sampel_status', ['sample_taken']);
                break;
            case 'extraction_sample_extracted':
                // halaman sampel ekstraksi
                $sampel->whereIn('sampel.sampel_status', ['extraction_sample_extracted']);
                break;
            case 'extraction_sample_reextract':
                // halaman re ektraksi
                $sampel->where('sampel.waktu_extraction_sample_reextract', '!=', null);
                break;
            case 'extraction_sent':
                // halaman sampel terkirim
                $sampel->whereNotNull('waktu_extraction_sample_extracted')->whereNotNull('waktu_extraction_sample_sent');
            // no break
            default:
        }

        // set response data
        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 500);

        $count = $sampel->count();
        $sampel = $sampel->skip(($page - 1) * $perpage)->take($perpage)->get();

        return response()->json(['count' => $count, 'data' => $sampel]);
    }
}
