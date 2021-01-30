<?php

namespace App\Exports;

use App\Models\Sampel;
use App\Models\Register;
use App\Models\PasienRegister;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class RegisMandiriExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $count;

    public function __construct(array $payload = [])
    {
        if (isset($payload['startDate']) && $payload['startDate']) {
            $this->startDate = $payload['startDate'];
        }

        if (isset($payload['endDate']) && $payload['endDate']) {
            $this->endDate = $payload['endDate'];
        }

        if (isset($payload['sampelStatus']) && $payload['sampelStatus']) {
            $this->sampelStatus = $payload['sampelStatus'];
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $cellRange = 'A1:V1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);

                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ];
                $worksheet = $event->sheet->getDelegate();
                $worksheet->getStyle("A1:V$this->count")->applyFromArray($styleArray);
                
                for($i = 2; $i <= $this->count; $i++) {
                    $event->sheet->setCellValue("E$i", STATUSES[$event->sheet->getCell("E$i")->getValue()] ?? null);
                }
            },
        ];
    }

    public function collection()
    {
        $query = PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
                    ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
                    ->leftJoin('sampel','sampel.register_id','register.id')
                    ->leftJoin('provinsi', 'provinsi.id', 'pasien.provinsi_id')
                    ->leftJoin('kota', 'kota.id', 'pasien.kota_id')
                    ->leftJoin('kecamatan', 'kecamatan.id', 'pasien.kecamatan_id')
                    ->leftJoin('kelurahan', 'kelurahan.id', 'pasien.kelurahan_id')
                    ->select([
                        DB::raw('ROW_NUMBER() OVER(order by register.id) AS Row'),
                        'register.nomor_register',
                        'sampel.nomor_sampel',
                        'register.sumber_pasien',
                        'status',
                        'pasien.nama_lengkap',
                        DB::raw("Concat('''',pasien.nik)"),
                        'pasien.usia_tahun',
                        DB::raw("'Tahun' as tahun"),
                        'pasien.tempat_lahir',
                        'pasien.tanggal_lahir',
                        'pasien.jenis_kelamin',
                        'provinsi.nama as provinsi',
                        'kota.nama as kota',
                        'kecamatan.nama as kecamatan',
                        'kelurahan.nama as kelurahan',
                        'alamat_lengkap',
                        'no_rw',
                        'no_rt',
                        'pasien.no_hp',
                        'register.kunjungan_ke',
                        'register.created_at',
                    ]);

        if ($this->startDate) {
            $query->where('register.created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->where('register.created_at', '<=', $this->endDate);
        }
        $query->where('jenis_registrasi', 'mandiri');
        $query->where('pasien.is_from_migration', false);
        $result = $query->get();
        $this->count = count($result) + 1;

        return $result;
    }

    public function headings(): array
    {
        return [
            'No.',
            'No Registrasi',
            'Kode Sampel',
            'Kategori',
            'Status',
            'Nama Pasien',
            'NIK',
            'Usia',
            'Satuan',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Provinsi',
            'Kota',
            'Kecamatan',
            'Kelurahan',
            'Alamat',
            'RT',
            'RW',
            'No. HP',
            'Kunjungan Ke',
            'Tanggal Registrasi',
        ];
    }
}
