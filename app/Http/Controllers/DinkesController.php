<?php

namespace App\Http\Controllers;

use App\Models\Fasyankes;
use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class DinkesController extends Controller
{
    public function listDinkes(Request $request)
    {
        $models = Fasyankes::with(['kota']);
        $params = $request->get('params',false);
        $search = $request->get('search',false);
        $order  = $request->get('order','name');

        if ($search != '') {
            $models = $models->where(function($q) use ($search) {
                $q->where('fasyankes.nama','ilike','%'.$search.'%')
                   ->orWhere('tipe','ilike','%'.$search.'%')
                   ->orWhereHas('kota', function($query) use ($search) { 
                        $query->where('kota.nama','ilike','%'.$search.'%');
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
                case 'tipe':
                    $models = $models->orderBy($order,$order_direction);
                    break;
                case 'kota':
                    $models = $models->leftJoin('kota','kota.id','fasyankes.kota_id')
                                ->select('fasyankes.*')
                                ->addSelect('kota.nama as nama_kota')
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

    public function saveDinkes(Request $request)
    {
        $this->validate($request,[
            'nama' => 'required|max:255',
            'tipe' => 'required',
        ]);

        $kota = new Fasyankes;
        $kota->nama = $request->nama;
        $kota->tipe = $request->tipe;
        $kota->kota_id = $request->kota_id;
        $kota->save();
        
        return response()->json(['status'=>201,'message'=>'Berhasil menambahkan Fasyankes','result'=>[]]);
    }

    public function deleteDinkes($id)
    {
        try{
            $dinkes = Fasyankes::find($id);
            if ($dinkes->register()->exists()) {
                return response()->json('Tidak dapat hapus, karena data sedang digunakan', 409);
            } else {
                $dinkes->delete();
                return response()->json(['status' => 200, 'message' => 'Berhasil menghapus data Kota', 'result' => []]);
            }
        }catch(\Exception $ex) {
            return response()->json(['status' => 400, 'message' => 'Gagal menghapus data, terjadi kesalahan server', 'result' => $ex->getMessage()]);
        }
        
    }

    public function updateDinkes(Request $request, $id)
    {
        $this->validate($request,[
            'nama' => 'required|max:255',
            'tipe' => 'required',
        ]);

        // dd($id);
        $kota = Fasyankes::where('id',$id)->first();
        $kota->nama = $request->nama;
        $kota->tipe = $request->tipe;
        $kota->kota_id = $request->kota_id;
        $kota->save();

        return response()->json(['status'=>200,'message'=>'Berhasil mengubah data Fasyankes','result'=>[]]);
    }

    public function showUpdate(Request $request, $id)
    {
        $kota = Fasyankes::with(['kota'])->where('id',$id)->first();
        return response()->json(['status'=>200,'message'=>'success','result'=>$kota]);
    }

}
