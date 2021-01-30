<?php

use Illuminate\Database\Seeder;
use App\Models\Provinsi;

class DagriProvinsiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->items() as $item) {
            Provinsi::updateOrCreate($item[0], $item[1]);
        }
    }

    public function items(): array
    {
        $csv = array_map('str_getcsv', file(base_path() . "/database/seeds/csv/dagri_provinsi.csv"));
        $provinces = [];

        foreach ($csv as $key => $value) {
            if ($key < 1) continue;

            $item[0]['id'] = $value[0];
            $item[1]['nama'] = $value[1];

            array_push($provinces, $item);
        }

        return $provinces;
    }
}
