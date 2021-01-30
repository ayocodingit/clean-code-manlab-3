<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\PelaporanService;
use App\Models\Pasien;
use Illuminate\Http\Request;

class PelaporanController extends Controller
{
    public function fetchData(Request $request)
    {
        $pelaporan = new PelaporanService;
        $response = $pelaporan->pendaftar_rdt($request->get('search'), $request->get('limit', 10))->json();
        if ($response['status_code'] == 200) {
            foreach ($response['data']['content'] as $key => $item) {
                $response['data']['content'][$key]['status_code'] = $this->setStatusCoce($item['status']);
                $response['data']['content'][$key]['address_subdistrict_code'] = (int)str_replace('.', '', $item['address_subdistrict_code']);
                $response['data']['content'][$key]['address_district_code'] = (int)str_replace('.', '', $item['address_district_code']);
                $response['data']['content'][$key]['address_village_code'] = (int)str_replace('.', '', $item['address_village_code']);
                $response['data']['content'][$key]['address_province_code'] = PROVINSI_JAWABARAT;    
                $response['data']['content'][$key]['no_rt'] = null;
                $response['data']['content'][$key]['no_rw'] = null;
                $response['data']['content'][$key]['usia_bulan'] = null;
                $response['data']['content'][$key]['usia_tahun'] = null;
                $response['data']['content'][$key]['suhu'] = null;
            }
        }
        $key = $response['status_code'] == 200 ? count($response['data']['content']) : 0;
        unset($response['status_code']);
        unset($response['message']);
        
        $search = $request->search;
        $pasien = Pasien::where('nama_lengkap', 'ilike', "%$search%")
            ->orWhere('nik', 'like', "$search%")
            ->orWhere('no_hp', 'like', "$search%")
            ->distinct('nama_lengkap', 'nik')
            ->orderByRaw('nama_lengkap desc, nik desc, updated_at desc')
            ->limit(10 - $key)->get();
        foreach ($pasien as $item) {
            $response['data']['content'][$key]['id'] = null;
            $response['data']['content'][$key]['id_case'] = null;
            $response['data']['content'][$key]['nik'] = $item->nik;
            $response['data']['content'][$key]['name'] = $item->nama_lengkap;
            $response['data']['content'][$key]['birth_date'] = $item->tanggal_lahir;
            $response['data']['content'][$key]['age'] = $item->usia_tahun;
            $response['data']['content'][$key]['gender'] = $item->jenis_kelamin;
            $response['data']['content'][$key]['address_detail'] = $item->alamat_lengkap;
            $response['data']['content'][$key]['address_district_code'] = $item->kota_id;
            $response['data']['content'][$key]['address_district_name'] = optional($item->kota)->nama;
            $response['data']['content'][$key]['address_subdistrict_code'] = $item->kecamatan_id;
            $response['data']['content'][$key]['address_subdistrict_name'] = optional($item->kecamatan)->nama;
            $response['data']['content'][$key]['address_village_code'] = $item->kelurahan_id;
            $response['data']['content'][$key]['address_village_name'] = optional($item->kelurahan)->nama;
            $response['data']['content'][$key]['nationality'] = $item->kewarganegaraan;
            $response['data']['content'][$key]['nationality_name'] = null;
            $response['data']['content'][$key]['final_result'] = null;
            $response['data']['content'][$key]['test_location_type'] = null;
            $response['data']['content'][$key]['test_location'] = null;
            $response['data']['content'][$key]['status'] = $item->status;
            $response['data']['content'][$key]['test_date'] = null;
            $response['data']['content'][$key]['id_match'] = null;
            $response['data']['content'][$key]['status_code'] = $item->status;
            $response['data']['content'][$key]['phone_number'] = $item->no_hp;
            $response['data']['content'][$key]['no_rt'] = $item->no_rt;
            $response['data']['content'][$key]['no_rw'] = $item->no_rw;
            $response['data']['content'][$key]['usia_bulan'] = $item->usia_bulan;
            $response['data']['content'][$key]['usia_tahun'] = $item->usia_tahun;
            $response['data']['content'][$key]['suhu'] = $item->suhu;
            $key++;
        }

        return response()->json($response, 200);
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
