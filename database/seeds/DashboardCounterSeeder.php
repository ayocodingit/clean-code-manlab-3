<?php

use App\Models\DashboardCounter;
use Illuminate\Database\Seeder;

class DashboardCounterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->items() as $item) {
            DashboardCounter::updateOrCreate(
                \Illuminate\Support\Arr::only($item, ['nama']),
                $item
            );
        }
    }

    public function items(): array
    {
        return [
            [
                "nama" => "tracking_progress_registration",
                "total" => 0,
            ],
            [
                "nama" => "tracking_progress_sampel",
                "total" => 0,
            ],
            [
                "nama" => "tracking_progress_ekstraksi",
                "total" => 0,
            ],
            [
                "nama" => "tracking_progress_rtpcr",
                "total" => 0,
            ],
            [
                "nama" => "tracking_progress_verifikasi",
                "total" => 0,
            ],
            [
                "nama" => "tracking_progress_validasi",
                "total" => 0,
            ],
            [
                "nama" => "pasien_negatif",
                "total" => 0,
            ],
            [
                "nama" => "pasien_positif",
                "total" => 0,
            ],
            [
                "nama" => "pasien_diperiksa_1",
                "total" => 0,
            ],
            [
                "nama" => "pasien_diperiksa_2",
                "total" => 0,
            ],
            [
                "nama" => "pasien_diperiksa_3",
                "total" => 0,
            ],
            [
                "nama" => "pasien_diperiksa_4",
                "total" => 0,
            ],
            [
                "nama" => "pasien_diperiksa_5",
                "total" => 0,
            ],
            [
                "nama" => "registrasi_mandiri",
                "total" => 0,
            ],
            [
                "nama" => "registrasi_rujukan",
                "total" => 0,
            ],
            [
                "nama" => "registrasi_total",
                "total" => 0,
            ],
            [
                "nama" => "registrasi_jumlah_perhari_mandiri",
                "total" => 0,
            ],
            [
                "nama" => "registrasi_jumlah_perhari_rujukan",
                "total" => 0,
            ],
            [
                "nama" => "registrasi_data_belum_lengkap_mandiri",
                "total" => 0,
            ],
            [
                "nama" => "registrasi_data_belum_lengkap_rujukan",
                "total" => 0,
            ],
            [
                "nama" => "registrasi_pemeriksaan_selesai_mandiri",
                "total" => 0,
            ],
            [
                "nama" => "registrasi_pemeriksaan_selesai_rujukan",
                "total" => 0,
            ],
            [
                "nama" => "registrasi_belum_input_rujukan",
                "total" => 0,
            ],
            [
                "nama" => "admin_sampel_jumlah_perhari_sampel",
                "total" => 0,
            ],
            [
                "nama" => "admin_sampel_sampel_register_mandiri",
                "total" => 0,
            ],
            [
                "nama" => "admin_sampel_total_sampel",
                "total" => 0,
            ],
            [
                "nama" => "ekstraksi_jumlah_perhari_ektraksi",
                "total" => 0,
            ],
            [
                "nama" => "ekstraksi_sampel_baru",
                "total" => 0,
            ],
            [
                "nama" => "ekstraksi_ekstraksi",
                "total" => 0,
            ],
            [
                "nama" => "ekstraksi_kirim",
                "total" => 0,
            ],
            [
                "nama" => "ekstraksi_sampel_invalid",
                "total" => 0,
            ],
            [
                "nama" => "pcr_sampel_baru",
                "total" => 0,
            ],
            [
                "nama" => "pcr_jumlah_perhari_pcr",
                "total" => 0,
            ],
            [
                "nama" => "pcr_hasil_pemeriksaan",
                "total" => 0,
            ],
            [
                "nama" => "pcr_re_pcr",
                "total" => 0,
            ],
            [
                "nama" => "verifikasi_belum_diverifikasi",
                "total" => 0,
            ],
            [
                "nama" => "verifikasi_terverifikasi",
                "total" => 0,
            ],
            [
                "nama" => "validasi_belum_divalidasi",
                "total" => 0,
            ],
            [
                "nama" => "validasi_tervalidasi",
                "total" => 0,
            ],
        ];
    }
}
