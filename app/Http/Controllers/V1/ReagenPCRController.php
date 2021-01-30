<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\ReagenPCR;
use Illuminate\Http\Request;

class ReagenPCRController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $models = ReagenPCR::query();
        $search = $request->get('search', false);
        $order = $request->get('order', 'nama');

        if ($search != '') {
            $models = $models->where(function ($q) use ($search) {
                $q->where('nama', 'ilike', '%' . $search . '%')
                    ->orWhere('ct_normal', $search);
            });
        }
        $count = $models->count();

        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 20);
        $order_direction = $request->get('order_direction', 'asc');

        switch ($order) {
            case 'nama':
            case 'ct_normal':
                $models = $models->orderBy($order, $order_direction);
                break;
        }

        $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();

        $result = [
            'data' => $models,
            'count' => $count,
        ];

        return response()->json($result);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'nama' => 'required|unique:reagen_pcr,nama',
            'ct_normal' => 'required|integer',
        ];
        $request->validate($rules);

        $reagenPCR = new ReagenPCR;
        $reagenPCR->nama = $request->get('nama');
        $reagenPCR->ct_normal = $request->get('ct_normal');
        $reagenPCR->save();

        return response()->json(['result' => $reagenPCR]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ReagenPCR $reagenPCR)
    {
        return response()->json(['result' => $reagenPCR]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReagenPCR $reagenPCR)
    {
        $rules = [
            'nama' => 'required|unique:reagen_pcr,nama,' . $reagenPCR->id.',id',
            'ct_normal' => 'required|integer',
        ];
        $request->validate($rules);

        $reagenPCR->nama = $request->get('nama');
        $reagenPCR->ct_normal = $request->get('ct_normal');
        $reagenPCR->save();
        return response()->json(['result' => $reagenPCR]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReagenPCR $reagenPCR)
    {
        $reagenPCR->delete();
        return response()->json(['message' => 'DELETED']);
    }
}
