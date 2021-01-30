<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\DashboardChart;
use App\Models\DashboardCounter;
use App\Models\PasienRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function trackingProgress()
    {
        $data['registration'] = DashboardCounter::where('nama', 'tracking_progress_registration')->first()->total;
        $data['sampel'] = DashboardCounter::where('nama', 'tracking_progress_sampel')->first()->total;
        $data['ekstraksi'] = DashboardCounter::where('nama', 'tracking_progress_ekstraksi')->first()->total;
        $data['rtpcr'] = DashboardCounter::where('nama', 'tracking_progress_rtpcr')->first()->total;
        $data['verifikasi'] = DashboardCounter::where('nama', 'tracking_progress_verifikasi')->first()->total;
        $data['validasi'] = DashboardCounter::where('nama', 'tracking_progress_validasi')->first()->total;

        return response()->json([
            'result' => $data,
            'status' => 200,
        ]);
    }

    public function positifNegatif()
    {
        $data['positif'] = DashboardCounter::where('nama', 'pasien_positif')->first()->total;
        $data['negatif'] = DashboardCounter::where('nama', 'pasien_negatif')->first()->total;

        return response()->json([
            'result' => $data,
            'status' => 200,
        ]);
    }

    public function pasienDiperiksa()
    {
        $data = [];
        foreach (STATUSES as $key => $value) {
            $data[str_replace(' ', '_', strtolower($value))] = DashboardCounter::where('nama', 'pasien_diperiksa_' . $key)->first()->total;
        }

        return response()->json([
            'result' => $data,
            'status' => 200,
        ]);
    }

    public function registrasi()
    {
        $data['mandiri'] = DashboardCounter::where('nama', 'registrasi_mandiri')->first()->total;
        $data['rujukan'] = DashboardCounter::where('nama', 'registrasi_rujukan')->first()->total;
        $data['total'] = DashboardCounter::where('nama', 'registrasi_total')->first()->total;
        $data['jumlah_perhari_mandiri'] = DashboardCounter::where('nama', 'registrasi_jumlah_perhari_mandiri')->first()->total;
        $data['jumlah_perhari_rujukan'] = DashboardCounter::where('nama', 'registrasi_jumlah_perhari_rujukan')->first()->total;
        $data['data_belum_lengkap_mandiri'] = DashboardCounter::where('nama', 'registrasi_data_belum_lengkap_mandiri')->first()->total;
        $data['data_belum_lengkap_rujukan'] = DashboardCounter::where('nama', 'registrasi_data_belum_lengkap_rujukan')->first()->total;
        $data['pemeriksaan_selesai_mandiri'] = DashboardCounter::where('nama', 'registrasi_pemeriksaan_selesai_mandiri')->first()->total;
        $data['pemeriksaan_selesai_rujukan'] = DashboardCounter::where('nama', 'registrasi_pemeriksaan_selesai_rujukan')->first()->total;
        $data['belum_input_rujukan'] = DashboardCounter::where('nama', 'registrasi_belum_input_rujukan')->first()->total;
        return response()->json([
            'result' => $data,
            'status' => 200,
        ]);
    }

    public function adminSampel()
    {
        $data['jumlah_perhari_sampel'] = DashboardCounter::where('nama', 'admin_sampel_jumlah_perhari_sampel')->first()->total;
        $data['sampel_register_mandiri'] = DashboardCounter::where('nama', 'admin_sampel_sampel_register_mandiri')->first()->total;
        $data['total_sampel'] = DashboardCounter::where('nama', 'admin_sampel_total_sampel')->first()->total;
        return response()->json([
            'result' => $data,
            'status' => 200,
        ]);
    }

    public function ekstraksi()
    {
        $data['jumlah_perhari_ektraksi'] = DashboardCounter::where('nama', 'ekstraksi_jumlah_perhari_ektraksi')->first()->total;
        //sampel baru
        $data['sampel_baru'] = DashboardCounter::where('nama', 'ekstraksi_sampel_baru')->first()->total;
        $data['ekstraksi'] = DashboardCounter::where('nama', 'ekstraksi_ekstraksi')->first()->total;
        $data['kirim'] = DashboardCounter::where('nama', 'ekstraksi_kirim')->first()->total;
        $data['sampel_invalid'] = DashboardCounter::where('nama', 'ekstraksi_sampel_invalid')->first()->total;
        return response()->json([
            'result' => $data,
            'status' => 200,
        ]);
    }

    public function pcr()
    {
        $data['sampel_baru'] = DashboardCounter::where('nama', 'pcr_sampel_baru')->first()->total;
        $data['jumlah_perhari_pcr'] = DashboardCounter::where('nama', 'pcr_jumlah_perhari_pcr')->first()->total;
        //hasil pemeriksaan
        $data['hasil_pemeriksaan'] = DashboardCounter::where('nama', 'pcr_hasil_pemeriksaan')->first()->total;
        $data['re_pcr'] = DashboardCounter::where('nama', 'pcr_re_pcr')->first()->total;
        return response()->json([
            'result' => $data,
            'status' => 200,
        ]);
    }

    public function verifikasi()
    {
        $data['belum_diverifikasi'] = DashboardCounter::where('nama', 'verifikasi_belum_diverifikasi')->first()->total;
        $data['terverifikasi'] = DashboardCounter::where('nama', 'verifikasi_terverifikasi')->first()->total;
        return response()->json([
            'result' => $data,
            'status' => 200,
        ]);
    }

    public function validasi()
    {
        $data['belum_divalidasi'] = DashboardCounter::where('nama', 'validasi_belum_divalidasi')->first()->total;
        $data['tervalidasi'] = DashboardCounter::where('nama', 'validasi_tervalidasi')->first()->total;
        return response()->json([
            'result' => $data,
            'status' => 200,
        ]);
    }

    public function chartRegistrasi(Request $request)
    {
        $tipe = $request->get('tipe', 'Daily');
        $jenisRegistrasi = $request->get('jenis', 'mandiri');

        $models = DashboardChart::where('nama', 'registrasi')->where('tipe', $tipe)->where('where', $jenisRegistrasi)->first();

        return response()->json([
            'label' => json_decode($models->label),
            'value' => json_decode($models->data),
        ]);
    }

    public function chartSampel(Request $request)
    {
        $tipe = $request->get('tipe', 'Daily');

        $models = DashboardChart::where('nama', 'sampel')->where('tipe', $tipe)->first();

        return response()->json([
            'label' => json_decode($models->label),
            'value' => json_decode($models->data),
        ]);
    }

    public function chartEkstraksi(Request $request)
    {
        $tipe = $request->get('tipe', 'Daily');

        $models = DashboardChart::where('nama', 'ekstraksi')->where('tipe', $tipe)->first();

        return response()->json([
            'label' => json_decode($models->label),
            'value' => json_decode($models->data),
        ]);
    }

    public function chartPcr(Request $request)
    {
        $tipe = $request->get('tipe', 'Daily');

        $models = DashboardChart::where('nama', 'pcr')->where('tipe', $tipe)->first();

        return response()->json([
            'label' => json_decode($models->label),
            'value' => json_decode($models->data),
        ]);
    }

    public function chartPositifNegatif(Request $request)
    {
        $tipe = $request->get('tipe', 'Daily');
        $hasilPemeriksaan = $request->get('jenis', 'positif');

        $models = DashboardChart::where('nama', 'positif_negatif')->where('tipe', $tipe)->where('where', $hasilPemeriksaan)->first();

        return response()->json([
            'label' => json_decode($models->label),
            'value' => json_decode($models->data),
        ]);
    }

    public function sampelPerdomisili(Request $request)
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
            ->whereNull('register.deleted_at')
            ->where('register.is_from_migration', false)
            ->select(DB::raw('upper(kota.nama) as domisili'), DB::raw('count(*) as jumlah'))
            ->groupBy('kota.nama');

        $order = $request->get('order', 'domisili');
        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 500);

        if ($order) {
            $order_direction = $request->get('order_direction', 'asc');
            $models = $models->orderBy($order, $order_direction);
        }

        $count = clone $models;
        $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();
        $count = count($count->get());

        return response()->json([
            'data' => $models,
            'count' => $count,
        ]);
    }
}
