<?php

use Illuminate\Database\Seeder;

class AreaDagriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(DagriProvinsiSeeder::class);
        $this->call(DagriKotaSeeder::class);
        $this->call(KecamatanSeeder::class);
        $this->call(KelurahanSeeder::class);
        $this->call(RemoveUnusedProvinsi::class);
    }
}
