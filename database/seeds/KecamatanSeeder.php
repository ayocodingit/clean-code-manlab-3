<?php

use Illuminate\Database\Seeder;
use App\Models\Kecamatan;

class KecamatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->items() as $item) {
            Kecamatan::updateOrCreate($item);
        }
    }

    public function items(): array
    {
        $csv = array_map('str_getcsv', file(base_path() . "/database/seeds/csv/dagri_kecamatan.csv"));
        $subdistricts = [];

        foreach ($csv as $key => $value) {
            if ($key < 1) continue;

            $item['id'] = $value[0];
            $item['nama'] = $value[1];
            $item['kota_id'] = $value[2];

            array_push($subdistricts, $item);
        }

        return $subdistricts;
    }
}
