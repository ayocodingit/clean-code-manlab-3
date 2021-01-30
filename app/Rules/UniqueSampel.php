<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Sampel;

class UniqueSampel implements Rule
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
        $nomor_sampel = gettype($value) == 'array' ? $value['nomor'] : $value;
        $cek = Sampel::where('nomor_sampel','ilike',$nomor_sampel)->where('deleted_at',null)->first();
        return $cek == null;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.custom.nomor_sampel.unique');
    }
}
