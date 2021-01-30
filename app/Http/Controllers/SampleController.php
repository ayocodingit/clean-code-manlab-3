<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sampel;
use App\Models\PengambilanSampel;
use DB;
use Validator;
use App\Rules\UniqueSampel;


class SampleController extends Controller
{
    public function getData(Request $request)
    {
        $models = Sampel::query();
        $params = $request->get('params',false);
        $search = $request->get('search',false);
        $order  = $request->get('order' ,'name');

        if ($search != '') {
            $models = $models->where(function($q) use ($search) {
                $q->where('nomor_sampel','ilike','%'.$search.'%')
                   ->orWhere('nomor_register','ilike','%'.$search.'%');
            });
        }

        $page = $request->get('page',1);
        $perpage = $request->get('perpage',999999);

        if ($params) {
            foreach (json_decode($params) as $key => $val) {
                if ($val == '') continue;
                switch($key) {
                    case 'sampel_status':
                        if ($val == 'sample_sent') {
                            $models->whereIn('sampel_status', [
                                'sample_taken',
                                'extraction_sample_extracted',
                                'extraction_sample_reextract',
                                'extraction_sample_sent',
                                'pcr_sample_received',
                                'pcr_sample_analyzed',
                                'sample_verified',
                                'sample_valid',
                                'sample_invalid',
                                'swab_ulang'
                            ]);
                        } else {
                            $models->where('sampel_status', $val);
                        }
                        break;
                    case 'waktu_sample_taken':
                        $tgl = date('Y-m-d', strtotime($val));
                        $models->whereBetween('waktu_sample_taken', [$tgl.' 00:00:00',$tgl.' 23:59:59']);
                        break;
                    case 'petugas_pengambil':
                        $models->where('petugas_pengambilan_sampel','ilike',"%$val%");
                        break;
                    case 'jenis_sampel_nama':
                        if (is_string($val)) {
                            $models->where('jenis_sampel_nama','ilike',"%$val%");
                        } else {
                            $models->where('jenis_sampel_id',$val);
                        }
                        break;
                    case 'start_nomor_sampel':
                        if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                            $str = $val;
                            $n = 1;
                            $start = $n - strlen($str);
                            $str1 = substr($str, $start);
                            $str2 = substr($str, 0, $n);
                            $models->whereRaw("nomor_sampel ilike '%$str2%'");
                            $models->whereRaw("right(nomor_sampel,-1)::bigint >=  $str1");
                        } else {
                            $models->whereNull('nomor_sampel');
                        }
                        break;
                    case 'end_nomor_sampel':
                        if (preg_match('{^'.Sampel::NUMBER_FORMAT.'$}', $val)) {
                            $str = $val;
                            $n = 1;
                            $start = $n - strlen($str);
                            $str1 = substr($str, $start);
                            $str2 = substr($str, 0, $n);
                            $models->whereRaw("nomor_sampel ilike '%$str2%'");
                            $models->whereRaw("right(nomor_sampel,-1)::bigint <=  $str1");
                        } else {
                            $models->whereNull('nomor_sampel');
                        }
                        break;
                    default:
                        $models = $models->where($key,'ilike',$val);
                        break;
                }
            }
        }
        $models = $models->where('is_from_migration',false);
        $count = $models->count();

        if ($order) {
            $order_direction = $request->get('order_direction','asc');
            if (empty($order_direction)) $order_direction = 'asc';

            switch ($order) {
                case 'waktu_sample_taken':
                    $models = $models->orderBy('waktu_sample_taken',$order_direction);
                break;
                case 'petugas_pengambil':
                    $models = $models->orderBy('petugas_pengambilan_sampel', $order_direction);
                    break;
                case 'vtm':
                    $models = $models->orderBy('jenis_vtm', $order_direction);
                    break;
                default:
                    $models = $models->orderBy($order, $order_direction);
                    break;
            }
        }

        $models = $models->select(
            'sampel.id',
            'sampel.nomor_sampel',
            'sampel.nomor_register',
            'sampel.register_id',
            'sampel.jenis_sampel_nama',
            'sampel.waktu_waiting_sample',
            'sampel.waktu_sample_taken',
            'sampel.petugas_pengambilan_sampel',
            'sampel.jenis_vtm as nama_vtm'
        );

        $models = $models->skip(($page-1) * $perpage)->take($perpage)->get();

        // format data
        foreach ($models as &$model) {
            $model->sam_barcodenomor_sampel = rand(10000,99999);
        }

        $result = [
            'data' => $models,
            'count' => $count
        ];

        return response()->json($result);
    }

    public function add(Request $request)
    {
        DB::beginTransaction();
        try {
            $pen_sampel_sumber = $request->get('pen_sampel_sumber');
            $regexRule = 'regex:/^'.Sampel::NUMBER_FORMAT_RUJUKAN.'$/';
            if(strtolower($pen_sampel_sumber) == 'mandiri'){
                $regexRule = 'regex:/^'.Sampel::NUMBER_FORMAT_MANDIRI.'$/';
            }
            $v = Validator::make($request->all(),[
                'pen_sampel_sumber' => 'required',
                'sam_jenis_sampel' => 'required|integer|min:1|max:999999',
                'nomorsampel' => ['required', $regexRule, new UniqueSampel()],
                'petugas_pengambil' => 'required',
                'vtm' => 'required'
            ], [
                'pen_sampel_sumber.required' => 'Sumber sampel wajib diisi',
                'nomorsampel.required' => 'Nomor sampel wajib diisi',
                'nomorsampel.regex' => 'Format nomor sampel tidak sesuai',
                'sam_jenis_sampel.required'=> 'Jenis sampel wajib diisi.',
                'sam_jenis_sampel.integer'=> 'Tipe data tidak valid',
                'sam_jenis_sampel.min'=> 'Jumlah karakter minimal :min dijit.',
                'sam_jenis_sampel.max'=> 'Jumlah karakter maksimal :max dijit.',
                'petugas_pengambil.required' => 'Petugas Pengambil tidak boleh kosong'
            ]);
            if (isset($request->sam_jenis_sampel) && $request->sam_jenis_sampel == 999999) {
                $v->after(function ($validator) use ($item, $key) {
                    if (empty($request->sam_namadiluarjenis)) {
                        $validator->errors()->add("sam_namadiluarjenis", 'Jenis sampel belum diisi');
                    }
                });
            }
            $v->validate();
            $model = new PengambilanSampel;
            $model->sumber_sampel = $pen_sampel_sumber;
            $model->penerima_sampel = $request->get('pen_penerima_sampel');
            $model->catatan = $request->get('pen_catatan');
            $model->sampel_diterima = true;
            $model->save();


            //sampel proccess
            $nomor_sampel = strtoupper($request->nomorsampel);
            $sampel = new Sampel;
            $sm = Sampel::where('nomor_sampel','ilike',$nomor_sampel)->first();

            $jenis = DB::table('jenis_sampel')->where('id',$request->sam_jenis_sampel)->first();
            $nama_jenis_sampel = $jenis->nama;
            if($request->sam_jenis_sampel == 999999) {
                $nama_jenis_sampel = $request->sam_namadiluarjenis;
            }
            $sampel->nomor_sampel = $nomor_sampel;
            $sampel->jenis_sampel_id = $request->sam_jenis_sampel;
            $sampel->jenis_sampel_nama = $nama_jenis_sampel;
            $sampel->tanggal_pengambilan_sampel = parseDate($request->tanggalsampel);
            $sampel->jam_pengambilan_sampel = parseTime($request->pukulsampel);
            $sampel->petugas_pengambilan_sampel = $request->petugas_pengambil;
            $sampel->pengambilan_sampel_id = $model->id;
            $sampel->waktu_sample_taken = $request->tanggalsampel ? date('Y-m-d H:i:s', strtotime(parseDate($request->tanggalsampel) . ' ' .parseTime($request->pukulsampel))) : null;
            $sampel->waktu_waiting_sample = date('Y-m-d H:i:s');
            $sampel->sampel_status = 'sample_taken';
            $sampel->jenis_vtm = $request->get('vtm');
            $sampel->save();
            $sampel->updateState('sample_taken', [
                'user_id' => $request->user()->id,
                'metadata' => $sampel,
                'description' => 'Data Sampel Teregistrasi',
            ]);

            //all complete
            DB::commit();
            return response()->json(['status'=>201,'message'=>'Berhasil menambahkan sampel','result'=>[]]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    public function getUpdate(Request $request, $id)
    {
        $model = Sampel::where('nomor_sampel','ilike',$id)
                ->leftJoin('register','sampel.register_id','=','register.id')
                ->leftJoin('pengambilan_sampel', 'pengambilan_sampel.id','=','sampel.pengambilan_sampel_id')
                ->select(
                        'sampel.id as sampel_id',
                        'pengambilan_sampel.id as pengambilan_sampel_id',
                        'register_id',
                        'sampel.sampel_status',
                        'jenis_registrasi',
                        'pengambilan_sampel.sumber_sampel as sumber_sampel',
                        'pengambilan_sampel.penerima_sampel as penerima_sampel',
                        'pengambilan_sampel.catatan',
                        'pengambilan_sampel_id',
                        'petugas_pengambilan_sampel as petugas_pengambil',
                        'jam_pengambilan_sampel as pukulsampel',
                        'jenis_sampel_id as sam_jenis_sampel' ,
                        'jenis_sampel_nama as sam_namadiluarjenis',
                        'tanggal_pengambilan_sampel as tanggalsampel',
                        'sampel.jenis_vtm',
                        'nomor_sampel as nomorsampel')
                ->first();


        return response()->json(['status'=>200,'message'=>'success','result'=>$model]);
    }

    public function getById(Request $request, $id)
    {
        $model = Sampel::where('nomor_sampel','ilike',$id)->first();
        return response()->json(['status'=>200,'message'=>'success','result'=>$model]);
    }

    public function delete(Request $request, $id)
    {
        try{
            $model = Sampel::where('id',$id)->first();
            $model->delete();
            return response()->json(['status'=>200,'message'=>'Berhasil menghapus data ','result'=>[]]);
        }catch(\Exception $ex) {
            return response()->json(['status'=>400,'message'=>'Gagal menghapus data, terjadi kesalahan server','result'=>$ex->getMessage()]);
        }
    }

    public function storeUpdate(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $pen_sampel_sumber = $request->get('pen_sampel_sumber');
            $regexRule = 'regex:/^'.Sampel::NUMBER_FORMAT_RUJUKAN.'$/';
            if(strtolower($pen_sampel_sumber) == 'mandiri'){
                $regexRule = 'regex:/^'.Sampel::NUMBER_FORMAT_MANDIRI.'$/';
            }
            $v = Validator::make($request->all(),[
                'sam_jenis_sampel' => 'required|integer|min:1|max:999999',
                'nomorsampel' => ['required', $regexRule],
                'pen_sampel_sumber' => 'required',
                'petugas_pengambil' => 'required',
                'vtm' => 'required'
            ], [
                'pen_sampel_sumber.required' => 'Sumber sampel wajib diisi',
                'sam_jenis_sampel.required'=> 'Jenis sampel wajib diisi.',
                'sam_jenis_sampel.integer'=> 'Tipe data tidak valid',
                'nomorsampel.regex' => 'Format nomor sampel tidak sesuai',
                'sam_jenis_sampel.min'=> 'Jumlah karakter minimal :min dijit.',
                'sam_jenis_sampel.max'=> 'Jumlah karakter maksimal :max dijit.',
                'nomorsampel.required' => 'Nomor sampel wajib diisi.',
                'petugas_pengambil.required' => 'Petugas Pengambil tidak boleh kosong'
            ]);

            if (isset($request->sam_jenis_sampel) && $request->sam_jenis_sampel == 999999) {
                $v->after(function ($validator) {
                    if (empty($request->sam_namadiluarjenis)) {
                        $validator->errors()->add("sam_namadiluarjenis", 'Jenis sampel belum diisi');
                    }
                });
            }

            $v->validate();
            $model = new PengambilanSampel;

            if ($id != 'take') {
                $model = PengambilanSampel::where('id',$id)->first();
            }
            $model->sumber_sampel = $request->pen_sampel_sumber;
            $model->penerima_sampel = $request->pen_penerima_sampel;
            $model->catatan = $request->pen_catatan;
            $model->sampel_diterima = true;
            $model->save();

            $nomor_sampel = strtoupper($request->nomorsampel);

            $sm = Sampel::where('id',$request->sampel_id)->first();

            if(!$sm) {
                $sm = new Sampel;
            }
            $jenis = DB::table('jenis_sampel')->where('id',$request->sam_jenis_sampel)->first();
            $nama_jenis_sampel = $jenis->nama;
            if($request->sam_jenis_sampel == 999999) {
                $nama_jenis_sampel = $request->sam_namadiluarjenis;
            }

            $sm->nomor_sampel = $nomor_sampel;
            $sm->jenis_sampel_id = $request->sam_jenis_sampel;
            $sm->jenis_sampel_nama = $nama_jenis_sampel;
            $sm->tanggal_pengambilan_sampel = parseDate($request->tanggalsampel);
            $sm->jam_pengambilan_sampel = parseTime($request->pukulsampel);
            $sm->petugas_pengambilan_sampel = $request->petugas_pengambil        ;
            $sm->waktu_sample_taken = $request->tanggalsampel ? date('Y-m-d H:i:s', strtotime(parseDate($request->tanggalsampel) . ' ' .parseTime($request->pukulsampel))) : null;
            $sm->pengambilan_sampel_id = $model->id;
            $sm->jenis_vtm = $request->get('vtm');
            $sm->save();
            if ($sm->sampel_status == 'waiting_sample') {
                $sm->updateState('sample_taken', [
                    'user_id' => $request->user()->id,
                    'metadata' => $sm,
                    'description' => 'Data Sampel Teregistrasi',
                ]);
            }

            //all complete
            DB::commit();
            return response()->json(['status'=>201,'message'=>'Berhasil menambahkan sampel','result'=>[]]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function getSamples(Request $request, $nomor)
    {
        $sm = Sampel::where('nomor_sampel','ilike',$nomor)->first();
        $sms = Sampel::where('pengambilan_sampel_id',$sm->pengambilan_sampel_id)->select('nomor_sampel','sampel.id')->get();
        return response()->json($sms);
    }
}
