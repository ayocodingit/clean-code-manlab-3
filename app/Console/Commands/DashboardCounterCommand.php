<?php

namespace App\Console\Commands;

use App\Models\DashboardCounter;
use App\Models\PasienRegister;
use App\Models\Register;
use App\Models\Sampel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DashboardCounterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:dashboard_counter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            foreach ($this->items() as $item) {
                DashboardCounter::updateOrCreate(
                    \Illuminate\Support\Arr::only($item, ['nama']),
                    $item
                );
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function items(): array
    {
        $data = [
            [
                "nama" => "tracking_progress_registration",
                "total" => PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
                    ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
                    ->leftJoin('sampel', 'sampel.register_id', 'register.id')
                    ->where('sampel.is_from_migration', false)
                    ->whereNull('sampel.deleted_at')
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->count(),
            ],
            [
                "nama" => "tracking_progress_sampel",
                "total" => Sampel::where('is_from_migration', false)->whereIn('sampel_status', [
                    'sample_taken',
                    'extraction_sample_extracted',
                    'extraction_sample_reextract',
                    'extraction_sample_sent',
                    'pcr_sample_received',
                    'pcr_sample_analyzed',
                    'sample_verified',
                    'sample_valid',
                    'sample_invalid',
                    'swab_ulang',
                ])->count() + Sampel::where('is_from_migration', false)
                    ->where('sampel_status', 'waiting_sample')
                    ->count(),
            ],
            [
                "nama" => "tracking_progress_ekstraksi",
                "total" => Sampel::leftjoin('jenis_sampel', 'sampel.jenis_sampel_id', 'jenis_sampel.id')
                    ->leftJoin('ekstraksi', 'ekstraksi.sampel_id', 'sampel.id')
                    ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->whereNotNull('waktu_extraction_sample_extracted')
                    ->whereNotNull('waktu_extraction_sample_sent')
                    ->where('sampel.is_from_migration', false)
                    ->count(),
            ],
            [
                "nama" => "tracking_progress_rtpcr",
                "total" => Sampel::leftJoin('ekstraksi', 'ekstraksi.sampel_id', '=', 'sampel.id')
                    ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->whereIn('sampel_status', ['pcr_sample_analyzed', 'sample_verified', 'sample_valid', 'inkonklusif'])
                    ->where('pemeriksaansampel.kesimpulan_pemeriksaan', '!=', 'swab_ulang')
                    ->where('pemeriksaansampel.kesimpulan_pemeriksaan', '!=', 'invalid')
                    ->where('sampel.is_from_migration', false)
                    ->where('ekstraksi.is_from_migration', false)
                    ->where('pemeriksaansampel.is_from_migration', false)
                    ->count(),
            ],
            [
                "nama" => "tracking_progress_verifikasi",
                "total" => Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->leftJoin('register', 'register.id', 'sampel.register_id')
                    ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
                    ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
                    ->leftJoin('ekstraksi', 'sampel.id', 'ekstraksi.sampel_id')
                    ->whereIn('sampel_status', ['sample_verified', 'sample_valid'])
                    ->where('sampel.is_from_migration', false)
                    ->where('ekstraksi.is_from_migration', false)
                    ->where('pemeriksaansampel.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->count(),
            ],
            [
                "nama" => "tracking_progress_validasi",
                "total" => Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->leftJoin('register', 'register.id', 'sampel.register_id')
                    ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
                    ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
                    ->where('sampel_status', 'sample_valid')
                    ->where('sampel.is_from_migration', false)
                    ->where('pemeriksaansampel.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->count(),
            ],
            [
                "nama" => "pasien_negatif",
                "total" => Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->leftJoin('register', 'register.id', 'sampel.register_id')
                    ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
                    ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
                    ->leftJoin('ekstraksi', 'sampel.id', 'ekstraksi.sampel_id')
                    ->whereIn('sampel_status', ['sample_verified', 'sample_valid'])
                    ->where('pemeriksaansampel.kesimpulan_pemeriksaan', 'negatif')
                    ->where('sampel.is_from_migration', false)
                    ->where('ekstraksi.is_from_migration', false)
                    ->where('pemeriksaansampel.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->count(),
            ],
            [
                "nama" => "pasien_positif",
                "total" => Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->leftJoin('register', 'register.id', 'sampel.register_id')
                    ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
                    ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
                    ->leftJoin('ekstraksi', 'sampel.id', 'ekstraksi.sampel_id')
                    ->whereIn('sampel_status', ['sample_verified', 'sample_valid'])
                    ->where('pemeriksaansampel.kesimpulan_pemeriksaan', 'positif')
                    ->where('sampel.is_from_migration', false)
                    ->where('ekstraksi.is_from_migration', false)
                    ->where('pemeriksaansampel.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->count(),
            ],
            [
                "nama" => "registrasi_mandiri",
                "total" => PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
                    ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
                    ->leftJoin('sampel', 'sampel.register_id', 'register.id')
                    ->where('register.jenis_registrasi', 'mandiri')
                    ->where('sampel.is_from_migration', false)
                    ->whereNull('sampel.deleted_at')
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->count(),
            ],
            [
                "nama" => "registrasi_rujukan",
                "total" => PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
                    ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
                    ->leftJoin('sampel', 'sampel.register_id', 'register.id')
                    ->where('register.jenis_registrasi', 'rujukan')
                    ->where('sampel.is_from_migration', false)
                    ->whereNull('sampel.deleted_at')
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->count(),
            ],
            [
                "nama" => "registrasi_total",
                "total" => PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
                    ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
                    ->leftJoin('sampel', 'sampel.register_id', 'register.id')
                    ->whereIn('register.jenis_registrasi', ['rujukan', 'mandiri'])
                    ->where('sampel.is_from_migration', false)
                    ->whereNull('sampel.deleted_at')
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->count(),
            ],
            [
                "nama" => "registrasi_jumlah_perhari_mandiri",
                "total" => Register::where('is_from_migration', false)->where('jenis_registrasi', 'mandiri')->whereDate('created_at', date('Y-m-d'))->count(),
            ],
            [
                "nama" => "registrasi_jumlah_perhari_rujukan",
                "total" => Register::where('is_from_migration', false)->where('jenis_registrasi', 'rujukan')->whereDate('created_at', date('Y-m-d'))->count(),
            ],
            [
                "nama" => "registrasi_data_belum_lengkap_mandiri",
                "total" => PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
                    ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
                    ->leftJoin('sampel', 'sampel.register_id', 'register.id')
                    ->where('register.jenis_registrasi', 'mandiri')
                    ->where('sampel.is_from_migration', false)
                    ->whereNull('sampel.deleted_at')
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->whereNull('nik')
                    ->orWhereNull('nama_lengkap')
                    ->count(),
            ],
            [
                "nama" => "registrasi_data_belum_lengkap_rujukan",
                "total" => PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
                    ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
                    ->leftJoin('sampel', 'sampel.register_id', 'register.id')
                    ->where('register.jenis_registrasi', 'rujukan')
                    ->where('sampel.is_from_migration', false)
                    ->whereNull('sampel.deleted_at')
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->whereNull('nik')
                    ->orWhereNull('nama_lengkap')
                    ->count(),
            ],
            [
                "nama" => "registrasi_pemeriksaan_selesai_mandiri",
                "total" => PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
                    ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
                    ->leftJoin('sampel', 'sampel.register_id', 'register.id')
                    ->where('register.jenis_registrasi', 'mandiri')
                    ->where('sampel.is_from_migration', false)
                    ->whereNull('sampel.deleted_at')
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->whereIn('sampel_status', ['sample_verified', 'sample_valid'])
                    ->count(),
            ],
            [
                "nama" => "registrasi_pemeriksaan_selesai_rujukan",
                "total" => PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
                    ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
                    ->leftJoin('sampel', 'sampel.register_id', 'register.id')
                    ->where('register.jenis_registrasi', 'rujukan')
                    ->where('sampel.is_from_migration', false)
                    ->whereNull('sampel.deleted_at')
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->whereIn('sampel_status', ['sample_verified', 'sample_valid'])
                    ->count(),
            ],
            [
                "nama" => "registrasi_belum_input_rujukan",
                "total" => Sampel::where('is_from_migration', false)
                    ->whereNull('sampel.nomor_register')
                    ->whereRaw('left(nomor_sampel, 1) ilike (?)', 'R%')
                    ->count(),
            ],
            [
                "nama" => "admin_sampel_jumlah_perhari_sampel",
                "total" => Sampel::where('is_from_migration', false)
                    ->whereDate('waktu_sample_taken', date('Y-m-d'))
                    ->orWhereDate('waktu_waiting_sample', date('Y-m-d'))
                    ->whereIn('sampel_status', ['waiting_sample', 'sample_taken'])
                    ->count(),
            ],
            [
                "nama" => "admin_sampel_sampel_register_mandiri",
                "total" => Sampel::where('is_from_migration', false)
                    ->where('sampel_status', 'waiting_sample')
                    ->count(),
            ],
            [
                "nama" => "admin_sampel_total_sampel",
                "total" => Sampel::where('sampel.is_from_migration', false)
                    ->whereIn('sampel_status', [
                        'sample_taken',
                        'extraction_sample_extracted',
                        'extraction_sample_reextract',
                        'extraction_sample_sent',
                        'pcr_sample_received',
                        'pcr_sample_analyzed',
                        'sample_verified',
                        'sample_valid',
                        'sample_invalid',
                        'swab_ulang',
                    ])->count(),
            ],
            [
                "nama" => "ekstraksi_jumlah_perhari_ektraksi",
                "total" => Sampel::where('is_from_migration', false)->whereDate('waktu_extraction_sample_extracted', date('Y-m-d'))->count(),
            ],
            [
                "nama" => "ekstraksi_sampel_baru",
                "total" => Sampel::leftjoin('jenis_sampel', 'sampel.jenis_sampel_id', 'jenis_sampel.id')
                    ->leftJoin('ekstraksi', 'ekstraksi.sampel_id', 'sampel.id')
                    ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->where('sampel_status', 'sample_taken')
                    ->where('sampel.is_from_migration', false)
                    ->count(),
            ],
            [
                "nama" => "ekstraksi_ekstraksi",
                "total" => Sampel::leftjoin('jenis_sampel', 'sampel.jenis_sampel_id', 'jenis_sampel.id')
                    ->leftJoin('ekstraksi', 'ekstraksi.sampel_id', 'sampel.id')
                    ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->where('sampel_status', 'extraction_sample_extracted')
                    ->where('sampel.is_from_migration', false)
                    ->count() + Sampel::leftjoin('jenis_sampel', 'sampel.jenis_sampel_id', 'jenis_sampel.id')
                    ->leftJoin('ekstraksi', 'ekstraksi.sampel_id', 'sampel.id')
                    ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->whereNotNull('waktu_extraction_sample_extracted')
                    ->whereNotNull('waktu_extraction_sample_sent')
                    ->where('sampel.is_from_migration', false)
                    ->count(),
            ],
            [
                "nama" => "ekstraksi_kirim",
                "total" => Sampel::leftjoin('jenis_sampel', 'sampel.jenis_sampel_id', 'jenis_sampel.id')
                    ->leftJoin('ekstraksi', 'ekstraksi.sampel_id', 'sampel.id')
                    ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->whereNotNull('waktu_extraction_sample_extracted')
                    ->whereNotNull('waktu_extraction_sample_sent')
                    ->where('sampel.is_from_migration', false)
                    ->count(),
            ],
            [
                "nama" => "ekstraksi_sampel_invalid",
                "total" => Sampel::leftjoin('jenis_sampel', 'sampel.jenis_sampel_id', 'jenis_sampel.id')
                    ->leftJoin('ekstraksi', 'ekstraksi.sampel_id', 'sampel.id')
                    ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->where('sampel_status', 'extraction_sample_reextract')
                    ->where('sampel.is_from_migration', false)
                    ->count(),
            ],
            [
                "nama" => "pcr_sampel_baru",
                "total" => Sampel::where('is_from_migration', false)->where('sampel_status', 'extraction_sample_sent')->count(),
            ],
            [
                "nama" => "pcr_jumlah_perhari_pcr",
                "total" => Sampel::where('sampel.is_from_migration', false)->whereDate('waktu_pcr_sample_received', date('Y-m-d'))->count(),
            ],
            [
                "nama" => "pcr_hasil_pemeriksaan",
                "total" => Sampel::leftJoin('ekstraksi', 'ekstraksi.sampel_id', '=', 'sampel.id')
                    ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->whereIn('sampel_status', ['pcr_sample_analyzed', 'sample_verified', 'sample_valid', 'inkonklusif'])
                    ->where('pemeriksaansampel.kesimpulan_pemeriksaan', '!=', 'swab_ulang')
                    ->where('pemeriksaansampel.kesimpulan_pemeriksaan', '!=', 'invalid')
                    ->where('sampel.is_from_migration', false)
                    ->where('ekstraksi.is_from_migration', false)
                    ->where('pemeriksaansampel.is_from_migration', false)
                    ->count(),
            ],
            [
                "nama" => "pcr_re_pcr",
                "total" => Sampel::leftJoin('ekstraksi', 'ekstraksi.sampel_id', '=', 'sampel.id')
                    ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->whereIn('sampel_status', ['pcr_sample_analyzed', 'sample_verified', 'sample_invalid'])
                    ->where('pemeriksaansampel.kesimpulan_pemeriksaan', 'invalid')
                    ->where('sampel.is_from_migration', false)
                    ->where('ekstraksi.is_from_migration', false)
                    ->where('pemeriksaansampel.is_from_migration', false)
                    ->count(),
            ],
            [
                "nama" => "verifikasi_belum_diverifikasi",
                "total" => Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->leftJoin('register', 'register.id', 'sampel.register_id')
                    ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
                    ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
                    ->leftJoin('ekstraksi', 'sampel.id', 'ekstraksi.sampel_id')
                    ->whereIn('sampel_status', ['pcr_sample_analyzed', 'inkonklusif', 'swab_ulang'])
                    ->where('pemeriksaansampel.kesimpulan_pemeriksaan', '!=', 'invalid')
                    ->where('sampel.is_from_migration', false)
                    ->where('pemeriksaansampel.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->count(),
            ],
            [
                "nama" => "verifikasi_terverifikasi",
                "total" => Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->leftJoin('register', 'register.id', 'sampel.register_id')
                    ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
                    ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
                    ->leftJoin('ekstraksi', 'sampel.id', 'ekstraksi.sampel_id')
                    ->whereIn('sampel_status', ['sample_verified', 'sample_valid'])
                    ->where('sampel.is_from_migration', false)
                    ->where('ekstraksi.is_from_migration', false)
                    ->where('pemeriksaansampel.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->count(),
            ],
            [
                "nama" => "validasi_belum_divalidasi",
                "total" => Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->leftJoin('register', 'register.id', 'sampel.register_id')
                    ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
                    ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
                    ->where('sampel.is_from_migration', false)
                    ->where('pemeriksaansampel.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->where('sampel_status', 'sample_verified')
                    ->count(),
            ],
            [
                "nama" => "validasi_tervalidasi",
                "total" => Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
                    ->leftJoin('register', 'register.id', 'sampel.register_id')
                    ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
                    ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
                    ->where('sampel.is_from_migration', false)
                    ->where('pemeriksaansampel.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->where('sampel_status', 'sample_valid')
                    ->count(),
            ],
        ];
        foreach (STATUSES as $key => $value) {
            $data[] = [
                "nama" => "pasien_diperiksa_" . $key,
                "total" => PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
                    ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
                    ->leftJoin('sampel', 'sampel.register_id', 'register.id')
                    ->whereIn('register.jenis_registrasi', ['rujukan', 'mandiri'])
                    ->where('sampel.is_from_migration', false)
                    ->whereNull('sampel.deleted_at')
                    ->where('pasien.is_from_migration', false)
                    ->where('pasien_register.is_from_migration', false)
                    ->where('register.is_from_migration', false)
                    ->whereNull('register.deleted_at')
                    ->where('pasien.status', $key)
                    ->count(),
            ];
        }
        return $data;
    }
}
