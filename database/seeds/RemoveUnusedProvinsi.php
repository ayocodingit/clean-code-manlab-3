<?php

use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Database\Seeder;

class RemoveUnusedProvinsi extends Seeder
{
    protected $_provinsi_ids = [ 94, 99 ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach($this->_provinsi_ids as $id)
        {
            Kota::where('provinsi_id', $id)->delete();
            Provinsi::where('id', $id)->delete();
        }
    }


}
