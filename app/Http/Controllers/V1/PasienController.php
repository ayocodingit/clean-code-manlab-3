<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\PelaporanService;
use App\Models\Pasien;
use Illuminate\Http\Request;

class PasienController extends Controller
{
    public function fetchData(Request $request)
    {
        $pelaporan = new PelaporanService;
        $response = $pelaporan->pendaftar_rdt($request->get('search'), $request->get('limit', 10))->json();
        $dataPelaporan = [];
        if ($response['status_code'] == 200) {
            foreach ($response['data']['content'] as $key => $item) {
                $dataPelaporan[$key]['id'] = null;
                $dataPelaporan[$key]['nama_lengkap'] = $item['name'];
                $dataPelaporan[$key]['nik'] = $item['nik'];
                $dataPelaporan[$key]['tanggal_lahir'] = $item['birth_date'];
                $dataPelaporan[$key]['tempat_lahir'] = null;
                $dataPelaporan[$key]['kewarganegaraan'] = $item['nationality'];
                $dataPelaporan[$key]['no_hp'] = $item['phone_number'];
                $dataPelaporan[$key]['no_telp'] = null;
                $dataPelaporan[$key]['pekerjaan'] = null;
                $dataPelaporan[$key]['jenis_kelamin'] = $item['gender'];
                $dataPelaporan[$key]['kota_id'] = (int)str_replace('.', '', $item['address_district_code']);
                $dataPelaporan[$key]['no_rw'] = null;
                $dataPelaporan[$key]['no_rt'] = null;
                $dataPelaporan[$key]['alamat_lengkap'] = $item['address_detail'];
                $dataPelaporan[$key]['keterangan_lain'] = null;
                $dataPelaporan[$key]['suhu'] = null;
                $dataPelaporan[$key]['created_at'] = null;
                $dataPelaporan[$key]['updated_at'] = null;
                $dataPelaporan[$key]['usia_tahun'] = $item['age'];
                $dataPelaporan[$key]['usia_bulan'] = null;
                $dataPelaporan[$key]['status'] = $this->setStatusCoce($item['status']);
                $dataPelaporan[$key]['kecamatan'] = $item['address_subdistrict_name'];
                $dataPelaporan[$key]['kelurahan'] = $item['address_village_name'];
                $dataPelaporan[$key]['kecamatan_id'] = (int)str_replace('.', '', $item['address_subdistrict_code']);
                $dataPelaporan[$key]['kelurahan_id'] = (int)str_replace('.', '', $item['address_village_code']);
                $dataPelaporan[$key]['provinsi_id'] = 32;
                $dataPelaporan[$key]['pelaporan_id'] = $item['id'];
                $dataPelaporan[$key]['pelaporan_id_case'] = $item['id_case'];
                $dataPelaporan[$key]['status_name'] = $item['status'];
            }
        }

        $search = $request->search;
        $pasien = Pasien::where('nama_lengkap', 'ilike', "%$search%")
            ->orWhere('nik', 'like', "$search%")
            ->orWhere('no_hp', 'like', "$search%")
            ->distinct('nama_lengkap', 'nik')
            ->orderByRaw('nama_lengkap desc, nik desc, updated_at desc')
            ->limit(10)->get()->toArray();
        return response()->json(array_merge($dataPelaporan, $pasien), 200);
    }

    public function setStatusCoce($status)
    {
        switch (strtoupper($status)) {
            case 'SUSPECT':
                $status = 'suspek';
                break;
            case 'CLOSEDCONTACT':
                $status = 'kontak erat';
                break;
            case 'PROBABLE':
                $status = 'probable';
                break;
            case 'CONFIRMATION':
                $status = 'konfirmasi';
                break;
            default:
                $status = 'tanpa kriteria';
                break;
        }

        return array_search($status, array_map('strtolower', Pasien::STATUSES));
    }
}
