<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Kota;
use Illuminate\Http\Request;

class KotaController extends Controller
{
    public function listKota(Request $request)
    {   
        $models = Kota::select(['id','nama'])->orderBy('nama');
        if ($request->provinsi_id) {
            $models->where('provinsi_id', $request->provinsi_id);
        }

        $models = $models->get();
        return response()->json(
            $models,
            200
        );
    }

    public function listKotaAll()
    {
        return response()->json(Kota::select(['id','nama'])->orderBy('nama')->get());
    }

    public function show(Kota $kota)
    {
        return response()->json($kota);
    }

    public function listKecamatan($kota)
    {
        # code...
    }

    public function listKelurahan($kec)
    {
        # code...
    }
}
