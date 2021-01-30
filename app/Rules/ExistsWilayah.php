<?php

namespace App\Rules;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Contracts\Validation\Rule;

class ExistsWilayah implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $value = getConvertCodeDagri($value);
        switch ($attribute) {
            case 'provinsi_id':
                $models = Provinsi::find($value);
                break;
            case 'kota_id':
                $models = Kota::find($value);
                break;
            case 'kecamatan_id':
                $models = Kecamatan::find($value);
                break;
            case 'kelurahan_id':
                $models = Kelurahan::find($value);
                break;
        }
        return $models ? true : false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ':attribute tidak ditemukan';
    }
}
