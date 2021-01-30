<?php

use Illuminate\Database\Seeder;
use App\Models\Kelurahan;

class KelurahanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->items() as $item) {
            Kelurahan::updateOrCreate($item);
        }
    }

    public function items(): array
    {
        $csv = array_map('str_getcsv', file(base_path() . "/database/seeds/csv/dagri_kelurahan.csv"));
        $villages = [];

        foreach ($csv as $key => $value) {
            if ($key < 1) continue;

            $item['id'] = $value[0];
            $item['nama'] = $value[1];
            $item['kecamatan_id'] = $value[2];

            array_push($villages, $item);
        }

        return $villages;
    }
}
