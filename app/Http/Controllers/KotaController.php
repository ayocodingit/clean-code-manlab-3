<?php

namespace App\Http\Controllers;

use App\Models\Kota;
use App\Models\Provinsi;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class KotaController extends Controller
{
    public function listKota(Request $request)
    {
        $models = Kota::with(['provinsi']);
        $params = $request->get('params',false);
        $search = $request->get('search',false);
        $order  = $request->get('order','nama');

        if ($search != '') {
            $models = $models->where(function($q) use ($search) {
                $q->where('kota.nama','ilike','%'.$search.'%')
                   ->orWhereHas('provinsi', function($query) use ($search) { 
                        $query->where('provinsi.nama','ilike','%'.$search.'%');
                   });
            });
        }
        $count = $models->count();

        $page = $request->get('page',1);
        $perpage = $request->get('perpage',999999);

         if ($order) {
            $order_direction = $request->get('order_direction','asc');
            if (empty($order_direction)) $order_direction = 'asc';

            switch ($order) {
                case 'nama':
                    $models = $models->orderBy($order,$order_direction);
                    break;
                case 'provinsi':
                    $models = $models->leftJoin('provinsi','provinsi.id','kota.provinsi_id')
                                ->select('kota.*')
                                ->addSelect('provinsi.nama as nama_kota')
                                ->distinct()
                                ->orderBy('nama_kota',$order_direction);
                    break;
                default:
                    $models = $models->orderBy($order,$order_direction);
                    break;
            }
        }
        $models = $models->skip(($page-1) * $perpage)->take($perpage)->get();

        $result = [
            'data' => $models,
            'count' => $count
        ];

        return response()->json($result);
    }

    public function saveKota(Request $request)
    {
        $this->validate($request,[
            'id' => 'required|unique:kota,id',
            'nama' => 'required|max:255',
            'provinsi_id' => 'required',
        ]);

        $kota = new Kota;
        $kota->id = $request->id;
        $kota->nama = strtoupper($request->nama);
        $kota->provinsi_id = $request->provinsi_id;
        $kota->save();
        
        return response()->json(['status'=>201,'message'=>'Berhasil menambahkan Kota','result'=>[]]);
    }

    public function deleteKota(Request $request,$id)
    {
        try{
            $user = Kota::where('id',$id)->first();
            $user->delete();
            return response()->json(['status'=>200,'message'=>'Berhasil menghapus data Kota','result'=>[]]);
        }catch(\Exception $ex) {
            return response()->json(['status'=>400,'message'=>'Gagal menghapus data, terjadi kesalahan server','result'=>$ex->getMessage()]);
        }
        
    }

    public function updateKota(Request $request, $id)
    {
        $this->validate($request,[
            'id' => 'required|unique:kota,id,'.$id,
            'nama' => 'required|max:255',
            'provinsi_id' => 'required',
        ]);

        // dd($id);
        $kota = Kota::where('id',(int) $id)->first();
        $kota->id = (int) $request->get('id');
        $kota->nama = strtoupper($request->nama);
        $kota->provinsi_id =  $request->get('provinsi_id');
        $kota->save();

        return response()->json(['status'=>200,'message'=>'Berhasil mengubah data kota','result'=>[]]);
    }

    public function showUpdate(Request $request, $id)
    {
        $kota = Kota::with(['provinsi'])->where('id',(int) $id)->first();
        return response()->json(['status'=>200,'message'=>'success','result'=>$kota]);
    }

    public function listProvinsi()
    {
        return response()->json(Provinsi::select('id','nama')->orderBy('nama')->get());
    }

    public function listKecamatan(Request $request)
    {
        $kecamatan = Kecamatan::orderby('nama', 'asc');

        if ($request->kota_id) {
            $kecamatan->where('kota_id', $request->kota_id);
        }

        $kecamatan = $kecamatan->get();

        return response()->json(
            $kecamatan,
            200
        );
    }

    public function listKelurahan(Request $request)
    {
        $kelurahan = Kelurahan::orderby('nama', 'asc');

        if ($request->kecamatan_id) {
            $kelurahan->where('kecamatan_id', $request->kecamatan_id);
        }

        $kelurahan = $kelurahan->get();

        return response()->json(
            $kelurahan,
            200
        );
    }
}
