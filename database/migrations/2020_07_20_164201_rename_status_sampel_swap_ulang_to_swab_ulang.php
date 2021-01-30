<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\StatusSampel;

class RenameStatusSampelSwapUlangToSwabUlang extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // insert status swab_ulang
        StatusSampel::updateOrCreate(['sampel_status' => 'swab_ulang'], ['sampel_status' => 'swab_ulang', 'deskripsi' => 'swab_ulang', 'chamber' => 'verifikasi']);
        // update data sampel dengan yang status swap_ulang menjadi swab_ulang
        DB::table('sampel_log')->where('sampel_status', 'swap_ulang')->update(['sampel_status' => 'swab_ulang']);
        DB::table('sampel')->where('sampel_status', 'swap_ulang')->update(['sampel_status' => 'swab_ulang']);
        // hapus status swap_ulang
        DB::table('status_sampel')->where('sampel_status', 'swap_ulang')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::table('status_sampel')->where('sampel_status', 'swap_ulang')->delete();
    }
}
