<?php

namespace App\Listeners;

use App\Events\SampelValidatedEvent;
use App\Models\File;
use App\Models\Pasien;
use App\Models\PemeriksaanSampel;
use App\Models\Sampel;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreateSuratHasilListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param SampelValidatedEvent $event
     */
    public function handle(SampelValidatedEvent $event)
    {
        $sampel = $event->sampel;

        $pdfFile = $this->createPDF($sampel);

        $this->putToStorage($sampel, $pdfFile);
    }

    public function putToStorage(Sampel $sampel, $file)
    {
        $timestamp = now()->timestamp;

        $storagePath = 'public/surat_hasil';
        $fileName = "SURAT_HASIL_{$sampel->nomor_sampel}_{$timestamp}.pdf";
        $filePath = $storagePath.DIRECTORY_SEPARATOR.$fileName;

        DB::beginTransaction();
        try {
            if (!Storage::exists($storagePath)) {
                Storage::makeDirectory($storagePath);
            }

            $fileStored = Storage::put($filePath, $file);
            $newFile = null;

            if ($fileStored) {
                $dataFile['mime_type'] = 'application/pdf';
                $dataFile['extension'] = 'pdf';
                $dataFile['original_name'] = $fileName;

                // $newFile = $sampel->validFile()->create($dataFile);
                if ($newFile = File::create($dataFile)) {
                    $sampel->update([
                        'valid_file_id' => $newFile->id,
                    ]);
                }
            }

            DB::commit();

            return;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function createPDF(Sampel $sampel)
    {
        $data['sampel'] = $sampel;
        $data['pasien'] = $sampel->register ? optional($sampel->register->pasiens())->first() : null;
        $data['pemeriksaan_sampel'] = $sampel->pemeriksaanSampel;
        $data['validator'] = $sampel->validator;
        $data['last_pemeriksaan_sampel'] = $sampel->pemeriksaanSampel()->orderBy('tanggal_input_hasil', 'desc')->first();
        $data['kop_surat'] = $this->getKopSurat();
        $data['tanggal_validasi'] = $this->__formatTanggalValid($sampel);
        $data['tanggal_lahir_pasien'] = $data['pasien'] ? $this->__getTanggalLahir($data['pasien']) : null;
        $data['umur_pasien'] = $this->hitungUmur($data['pasien']);
        $data['last_pemeriksaan_sampel']['hasil_deteksi_terkecil'] = $data['last_pemeriksaan_sampel'] ? $this->__getHasilDeteksiTerkecil($data['last_pemeriksaan_sampel']) : null;
        $data['register'] = $sampel->register ?? null;
        $data['tanggal_registrasi'] = $this->formatTanggalIndo($sampel->waktu_sample_taken);
        $data['tanggal_periksa'] = $sampel->pcr ? $this->__formatTanggalKunjungan($sampel->pcr) : '-';
        $pdf = PDF::loadView('pdf_templates.print_validasi', $data);
        $pdf->setPaper([0, 0, 609.4488, 935.433]);

        return $pdf->download()->getOriginalContent();
    }

    public function hitungUmur($pasien)
    {
        if ($pasien) {
            if ($pasien->usia_tahun) {
                return $pasien->usia_tahun;
            } elseif ($pasien->tanggal_lahir) {
                return $pasien->tanggal_lahir->diff(Carbon::now())->format('%y');
            }
        }
        return '-';
    }

    public function getKopSurat()
    {
        $pathDirectory = 'kop_surat/kop-surat-labkesda.png';

        $image = public_path($pathDirectory);

        abort_if(!file_exists($image), 500, 'File not exists!');

        $imageContent = file_get_contents($image);

        return 'data:image/png;base64, '.base64_encode($imageContent);
    }

    public function formatTanggalIndo($date)
    {
        return date('d', strtotime($date)).' '.$this->getNamaBulan((int) date('m', strtotime($date))).' '.date('Y', strtotime($date));
    }

    private function __formatTanggalValid(Sampel $sampel)
    {
        if ($sampel->getAttribute('waktu_sample_valid')) {
            $tanggal = now();

            return date('d').' '.
            $this->getNamaBulan((int) date('m')).' '.
            date('Y');
        }

        return date('d', strtotime($sampel->waktu_sample_valid)).' '.
        $this->getNamaBulan((int) date('m', strtotime($sampel->waktu_sample_valid))).' '.
        date('Y', strtotime($sampel->waktu_sample_valid));
    }

    private function __getTanggalLahir(Pasien $pasien)
    {
        return date('d', strtotime($pasien->tanggal_lahir)).' '.
        $this->getNamaBulan((int) date('m', strtotime($pasien->tanggal_lahir))).' '.
        date('Y', strtotime($pasien->tanggal_lahir));
    }

    public static function getNamaBulan(int $bulanKe)
    {
        $arrayNamaBulan = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
            'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        return $arrayNamaBulan[$bulanKe - 1];
    }

    /**
     * The job failed to process.
     *
     * @param SampelValidatedEvent $event
     * @param Exception            $exception
     */
    public function failed(SampelValidatedEvent $event, $exception)
    {
        $event->sampel->update([
            'sampel_status' => 'sample_verified',
            'valid_file_id' => null,
        ]);
    }

    private function __getHasilDeteksiTerkecil(PemeriksaanSampel $hasil)
    {
        return collect($hasil['hasil_deteksi_parsed'])
            ->whereNotNull('ct_value')
            ->where('target_gen', '!=', 'IC')
            ->where('ct_value', '!=', '-')
            ->sortBy('ct_value')->first();
    }

    private function __getUmurPasien(Carbon $tanggalLahir)
    {
        return $tanggalLahir->diff(Carbon::now())->format('%y Thn %m Bln %d Hari');
    }

    private function __formatTanggalKunjungan(PemeriksaanSampel $pcr)
    {
        if (!$pcr->getAttribute('tanggal_mulai_pemeriksaan')) {
            $tanggal = now();

            return '-';
        }

        return date('d', strtotime($pcr->tanggal_mulai_pemeriksaan)).' '.
        $this->getNamaBulan((int) date('m', strtotime($pcr->tanggal_mulai_pemeriksaan))).' '.
        date('Y', strtotime($pcr->tanggal_mulai_pemeriksaan));
    }
}
