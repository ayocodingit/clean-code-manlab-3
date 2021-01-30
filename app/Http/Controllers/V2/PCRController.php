<?php
namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sampel;
use App\Http\Resources\pcrListPCR;
use App\Http\Resources\pcrListRNA;
use App\Http\Resources\pcrListHasilPemeriksaan;

class PCRController extends Controller
{
    public function getData(Request $request)
    {
        $sampel = Sampel::leftJoin('ekstraksi', 'ekstraksi.sampel_id', '=', 'sampel.id')
        ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
        ->leftJoin('status_sampel', 'status_sampel.sampel_status', 'sampel.sampel_status')
        ->where('sampel.is_from_migration', false)
        ->select('sampel.sampel_status', 'sampel.id', 'sampel.nomor_sampel', 'sampel.nomor_register', 'sampel.jenis_sampel_nama', 'sampel.waktu_extraction_sample_sent', 'ekstraksi.operator_ekstraksi', 'sampel.waktu_pcr_sample_analyzed', 'ekstraksi.catatan_pengiriman', 'ekstraksi.catatan_penerimaan', 'sampel.waktu_pcr_sample_received', 'sampel.waktu_extraction_sample_sent', 'pemeriksaansampel.kesimpulan_pemeriksaan', 'status_sampel.deskripsi', 'sampel.is_musnah_pcr');
        $order = $request->get('order', 'nomor_sampel');
        $order_direction = $request->get('order_direction', 'asc');

        $params = $request->get('params', false);

        // order handler
        switch ($order) {
          case 'catatan_pengiriman':
            $sampel->with(['ekstraksi' => function ($query) use ($order_direction) {
                $query->orderBy('catatan_pengiriman', $order_direction);
            }]);
          break;
          default:
          $sampel->orderBy($order, $order_direction);
        }

        // filter handler

        if ($params) {
            $params = json_decode($params, true);
            foreach ($params as $key => $val) {
                if ($val !== false && ($val == '' || is_array($val) && count($val) == 0)) {
                    continue;
                }
                switch ($key) {
                  case 'tanggal_mulai_pemeriksaan':
                    $tgl = date('Y-m-d', strtotime($val));
                    $sampel->whereDate('waktu_pcr_sample_analyzed', $tgl);
                      break;
                  case 'is_musnah_pcr':
                    $sampel->where('is_musnah_pcr', $val);
                      break;
                  case 'kesimpulan_pemeriksaan':
                    $value = strtolower($val);
                    $sampel->whereHas('pcr', function ($q) use ($value) {
                        if ($value != 'semua') {
                            $q->whereRaw("lower(kesimpulan_pemeriksaan)='$value'");
                        }
                    });
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
                        case 'status_pemeriksaan':
                          if ($val != 'semua') {
                              $sampel->where('sampel.sampel_status', '=', $val);
                          }
                          break;
                        case 'sampel_status':
                          switch ($val) {
                            case 'extraction_sample_sent':
                                $sampel->where('sampel.sampel_status', 'extraction_sample_sent');
                                $sampel->where('ekstraksi.is_from_migration', false);
                              break;
                              case 'pcr_sample_received':
                                $sampel->where('sampel.sampel_status', 'pcr_sample_received');
                                $sampel->where('ekstraksi.is_from_migration', false);
                                $sampel->where('pemeriksaansampel.is_from_migration', false);
                              break;
                              case 'analyzed':
                                if ($params['filter_inconclusive'] == true) {
                                    // halaman repcr
                                    $sampel->whereIn('sampel.sampel_status', ['pcr_sample_analyzed', 'sample_verified', 'sample_invalid']);
                                    $sampel->whereHas('pcr', function ($query) {
                                        $query->where('kesimpulan_pemeriksaan', 'invalid');
                                    });
                                    $sampel->where('ekstraksi.is_from_migration', false);
                                    $sampel->where('pemeriksaansampel.is_from_migration', false);
                                } else {
                                    // halaman list hasil
                                    $sampel->whereIn('sampel.sampel_status', ['pcr_sample_analyzed', 'sample_verified', 'sample_valid', 'inkonklusif']);
                                    $sampel->where('pemeriksaansampel.kesimpulan_pemeriksaan', '!=', 'swab_ulang');
                                    $sampel->where('pemeriksaansampel.kesimpulan_pemeriksaan', '!=', 'invalid');
                                    $sampel->where('ekstraksi.is_from_migration', false);
                                    $sampel->where('pemeriksaansampel.is_from_migration', false);
                                }
                                // no break
                            default:
                          }
                        break;
                  default:
                      break;
              }
            }
        }
        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 500);

        $count = $sampel->count();
        $sampel = $sampel->skip(($page - 1) * $perpage)->take($perpage)->get();
        $response['count'] = $count;
        $response['data'] = pcrListPCR::collection($sampel);

        return response()->json($response);
    }
}
