<?php

namespace App\Console\Commands;

use App\Models\DashboardChart;
use App\Models\PasienRegister;
use App\Models\Sampel;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardChartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:dashboard_chart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command Dashboard Chart untuk counter dashboard chart';

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
            foreach ($this->itemRegister() as $item) {
                $chart = $this->chartRegistrasi($item['tipe'], $item['where']);
                $data = array_merge($item, [
                    'label' => json_encode($chart['label']),
                    'data' => json_encode($chart['data']),
                ]);
                DashboardChart::updateOrCreate($item, $data);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
        }

        DB::beginTransaction();
        try {
            foreach ($this->itemSampel() as $item) {
                $chart = $this->chartSampel($item['tipe']);
                $data = array_merge($item, [
                    'label' => json_encode($chart['label']),
                    'data' => json_encode($chart['data']),
                ]);
                DashboardChart::updateOrCreate($item, $data);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
        }

        DB::beginTransaction();
        try {
            foreach ($this->itemEkstraksi() as $item) {
                $chart = $this->chartEkstraksi($item['tipe']);
                $data = array_merge($item, [
                    'label' => json_encode($chart['label']),
                    'data' => json_encode($chart['data']),
                ]);
                DashboardChart::updateOrCreate($item, $data);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
        }
        DB::beginTransaction();
        try {
            foreach ($this->itemPcr() as $item) {
                $chart = $this->chartPcr($item['tipe']);
                $data = array_merge($item, [
                    'label' => json_encode($chart['label']),
                    'data' => json_encode($chart['data']),
                ]);
                DashboardChart::updateOrCreate($item, $data);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
        }

        DB::beginTransaction();
        try {
            foreach ($this->itemPositifNegatif() as $item) {
                $chart = $this->chartPositifNegatif($item['tipe'], $item['where']);
                $data = array_merge($item, [
                    'label' => json_encode($chart['label']),
                    'data' => json_encode($chart['data']),
                ]);
                DashboardChart::updateOrCreate($item, $data);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
        }
    }

    public function itemRegister(): array
    {
        return [
            [
                'nama' => 'registrasi',
                'where' => 'mandiri',
                'tipe' => 'Daily',
            ],
            [
                'nama' => 'registrasi',
                'where' => 'rujukan',
                'tipe' => 'Daily',
            ],
            [
                'nama' => 'registrasi',
                'where' => 'mandiri',
                'tipe' => 'Monthly',
            ],
            [
                'nama' => 'registrasi',
                'where' => 'rujukan',
                'tipe' => 'Monthly',
            ],
        ];
    }
    public function itemSampel(): array
    {
        return [
            [
                'nama' => 'sampel',
                'tipe' => 'Daily',
            ],
            [
                'nama' => 'sampel',
                'tipe' => 'Monthly',
            ],
        ];
    }
    public function itemEkstraksi(): array
    {
        return [
            [
                'nama' => 'ekstraksi',
                'tipe' => 'Daily',
            ],
            [
                'nama' => 'ekstraksi',
                'tipe' => 'Monthly',
            ],
        ];
    }
    public function itemPcr(): array
    {
        return [
            [
                'nama' => 'pcr',
                'tipe' => 'Daily',
            ],
            [
                'nama' => 'pcr',
                'tipe' => 'Monthly',
            ],
        ];
    }
    public function itemPositifNegatif(): array
    {
        return [
            [
                'nama' => 'positif_negatif',
                'where' => 'positif',
                'tipe' => 'Daily',
            ],
            [
                'nama' => 'positif_negatif',
                'where' => 'positif',
                'tipe' => 'Monthly',
            ],
            [
                'nama' => 'positif_negatif',
                'where' => 'negatif',
                'tipe' => 'Daily',
            ],
            [
                'nama' => 'positif_negatif',
                'where' => 'negatif',
                'tipe' => 'Monthly',
            ],
        ];
    }

    public function chartRegistrasi($tipe, $jenisRegistrasi)
    {
        $label = [];
        $value = [];
        switch ($tipe) {
            case "Daily":
                $now = Carbon::now();
                $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i');
                $weekEndDate = $now->endOfWeek()->format('Y-m-d H:i');
                $period = CarbonPeriod::create($weekStartDate, $weekEndDate);
                foreach ($period as $date) {
                    array_push($label, $date->format('D'));
                    array_push($value, $this->queryRegistrasi('Daily', $jenisRegistrasi, $date->format('Y-m-d')));
                }
                break;
            case "Monthly":
                $periode = DB::select("SELECT DISTINCT(TO_CHAR(created_at,'YYYY MON')) as label,TO_CHAR(created_at,'MM') as month FROM register ORDER BY month");
                foreach ($periode as $row) {
                    array_push($label, $row->label);
                    array_push($value, $this->queryRegistrasi('Monthly', $jenisRegistrasi, $row->month));
                }
                break;
        }

        return [
            'label' => $label,
            'data' => $value,
        ];
    }

    public function queryRegistrasi($tipe, $jenisRegistrasi, $date)
    {
        $models = PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
            ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
            ->leftJoin('sampel', 'sampel.register_id', 'register.id')
            ->where('register.jenis_registrasi', $jenisRegistrasi)
            ->where('sampel.is_from_migration', false)
            ->whereNull('sampel.deleted_at')
            ->where('pasien.is_from_migration', false)
            ->where('pasien_register.is_from_migration', false)
            ->where('register.is_from_migration', false)
            ->whereNull('register.deleted_at');
        switch ($tipe) {
            case 'Daily':
                $models->whereDate('register.created_at', $date);
                break;
            case 'Monthly':
                $models->whereMonth('register.created_at', $date);
                break;
        }
        return $models->count();
    }

    public function chartSampel($tipe)
    {
        $now = Carbon::now();
        $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i');
        $weekEndDate = $now->endOfWeek()->format('Y-m-d H:i');
        $period = CarbonPeriod::create($weekStartDate, $weekEndDate);
        $label = [];
        $value = [];
        switch ($tipe) {
            case "Daily":
                foreach ($period as $date) {
                    array_push($label, $date->format('D'));
                    array_push($value, $this->querySampel('Daily', $date->format('Y-m-d')));
                }
                break;
            case "Monthly":
                $periode = \DB::select("SELECT DISTINCT(TO_CHAR(created_at,'YYYY MON')) as label,TO_CHAR(created_at,'MM') as month FROM sampel ORDER BY month");
                foreach ($periode as $row) {
                    array_push($label, $row->label);
                    array_push($value, $this->querySampel('Monthly', $row->month));
                }
                break;
        }

        return [
            'label' => $label,
            'data' => $value,
        ];
    }

    public function querySampel($tipe, $date)
    {
        $models = Sampel::where('sampel.is_from_migration', false)
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
            ]);
        switch ($tipe) {
            case 'Daily':
                $models->whereDate('waktu_sample_taken', $date);
                break;
            case 'Monthly':
                $models->whereMonth('waktu_sample_taken', $date);
                break;
        }
        return $models->count();
    }

    public function chartEkstraksi($tipe)
    {
        $now = Carbon::now();
        $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i');
        $weekEndDate = $now->endOfWeek()->format('Y-m-d H:i');
        $period = CarbonPeriod::create($weekStartDate, $weekEndDate);
        $label = [];
        $value = [];
        switch ($tipe) {
            case "Daily":
                foreach ($period as $date) {
                    array_push($label, $date->format('D'));
                    array_push($value, $this->queryEkstraksi('Daily', $date->format('Y-m-d')));
                }
                break;
            case "Monthly":
                $periode = \DB::select("SELECT DISTINCT(TO_CHAR(created_at,'YYYY MON')) as label,TO_CHAR(created_at,'MM') as month FROM sampel ORDER BY month");
                foreach ($periode as $row) {
                    array_push($label, $row->label);
                    array_push($value, $this->queryEkstraksi('Monthly', $row->month));
                }
                break;
        }

        return [
            'label' => $label,
            'data' => $value,
        ];
    }

    public function queryEkstraksi($tipe, $date)
    {
        $models = Sampel::leftjoin('jenis_sampel', 'sampel.jenis_sampel_id', 'jenis_sampel.id')
            ->leftJoin('ekstraksi', 'ekstraksi.sampel_id', 'sampel.id')
            ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
            ->whereNotNull('waktu_extraction_sample_extracted')
            ->whereNotNull('waktu_extraction_sample_sent')
            ->where('sampel.is_from_migration', false);
        switch ($tipe) {
            case 'Daily':
                $models->whereDate('waktu_extraction_sample_extracted', $date);
                break;
            case 'Monthly':
                $models->whereMonth('waktu_extraction_sample_extracted', $date);
                break;
        }
        return $models->count();
    }

    public function chartPcr($tipe)
    {
        $now = Carbon::now();
        $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i');
        $weekEndDate = $now->endOfWeek()->format('Y-m-d H:i');
        $period = CarbonPeriod::create($weekStartDate, $weekEndDate);
        $label = [];
        $value = [];
        switch ($tipe) {
            case "Daily":
                foreach ($period as $date) {
                    array_push($label, $date->format('D'));
                    array_push($value, $this->queryPcr('Daily', $date->format('Y-m-d')));
                }
                break;
            case "Monthly":
                $periode = \DB::select("SELECT DISTINCT(TO_CHAR(created_at,'YYYY MON')) as label,TO_CHAR(created_at,'MM') as month FROM sampel ORDER BY month");
                foreach ($periode as $row) {
                    array_push($label, $row->label);
                    array_push($value, $this->queryPcr('Monthly', $row->month));
                }
                break;
        }

        return [
            'label' => $label,
            'data' => $value,
        ];
    }

    public function queryPcr($tipe, $date)
    {
        $models = Sampel::leftJoin('ekstraksi', 'ekstraksi.sampel_id', '=', 'sampel.id')
            ->leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
            ->whereIn('sampel_status', ['pcr_sample_analyzed', 'sample_verified', 'sample_valid', 'inkonklusif'])
            ->where('pemeriksaansampel.kesimpulan_pemeriksaan', '!=', 'swab_ulang')
            ->where('pemeriksaansampel.kesimpulan_pemeriksaan', '!=', 'invalid')
            ->where('sampel.is_from_migration', false)
            ->where('ekstraksi.is_from_migration', false)
            ->where('pemeriksaansampel.is_from_migration', false);
        switch ($tipe) {
            case 'Daily':
                $models->whereDate('waktu_pcr_sample_analyzed', $date);
                break;
            case 'Monthly':
                $models->whereMonth('waktu_pcr_sample_analyzed', $date);
                break;
        }
        return $models->count();
    }

    public function chartPositifNegatif($tipe, $hasilPemeriksaan)
    {
        $now = Carbon::now();
        $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i');
        $weekEndDate = $now->endOfWeek()->format('Y-m-d H:i');
        $period = CarbonPeriod::create($weekStartDate, $weekEndDate);
        $label = [];
        $value = [];
        switch ($tipe) {
            case "Daily":
                foreach ($period as $date) {
                    array_push($label, $date->format('D'));
                    array_push($value, $this->queryPositifNegatif('Daily', $hasilPemeriksaan, $date->format('Y-m-d')));
                }
                break;
            case "Monthly":
                $periode = \DB::select("SELECT DISTINCT(TO_CHAR(created_at,'YYYY MON')) as label,TO_CHAR(created_at,'MM') as month FROM sampel ORDER BY month");
                foreach ($periode as $row) {
                    array_push($label, $row->label);
                    array_push($value, $this->queryPositifNegatif('Monthly', $hasilPemeriksaan, $row->month));
                }
                break;
        }

        return [
            'label' => $label,
            'data' => $value,
        ];
    }

    public function queryPositifNegatif($tipe, $jenisPemeriksaan, $date)
    {
        $models = Sampel::leftJoin('pemeriksaansampel', 'pemeriksaansampel.sampel_id', 'sampel.id')
            ->leftJoin('register', 'register.id', 'sampel.register_id')
            ->leftJoin('pasien_register', 'pasien_register.register_id', 'sampel.register_id')
            ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
            ->leftJoin('ekstraksi', 'sampel.id', 'ekstraksi.sampel_id')
            ->whereIn('sampel_status', ['sample_verified', 'sample_valid'])
            ->where('pemeriksaansampel.kesimpulan_pemeriksaan', $jenisPemeriksaan)
            ->where('sampel.is_from_migration', false)
            ->where('ekstraksi.is_from_migration', false)
            ->where('pemeriksaansampel.is_from_migration', false)
            ->where('register.is_from_migration', false)
            ->where('pasien.is_from_migration', false)
            ->where('pasien_register.is_from_migration', false)
            ->whereNull('register.deleted_at');
        switch ($tipe) {
            case 'Daily':
                $models->whereDate('waktu_sample_verified', $date);
                break;
            case 'Monthly':
                $models->whereMonth('waktu_sample_verified', $date);
                break;
        }
        return $models->count();
    }

}
