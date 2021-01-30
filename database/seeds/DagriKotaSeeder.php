<?php

use Illuminate\Database\Seeder;
use App\Models\Kota;

class DagriKotaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->items() as $item) {
            Kota::updateOrCreate($item[0], $item[1]);
        }
    }

    public function items(): array
    {
        $csv = array_map('str_getcsv', file(base_path() . "/database/seeds/csv/dagri_kota.csv"));
        $subdistricts = [];

        foreach ($csv as $key => $value) {
            if ($key < 1) continue;

            $item[0]['id'] = $value[1];
            $item[1]['nama'] = $value[2];
            $item[1]['provinsi_id'] = $value[0];

            array_push($subdistricts, $item);
        }

        return $subdistricts;
    }
}
