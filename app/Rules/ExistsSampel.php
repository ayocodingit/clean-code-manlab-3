<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Sampel;

class ExistsSampel implements Rule
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
        $cek = Sampel::where('nomor_sampel', 'ilike', $value)->whereNull('register_id')->first();
        return $cek;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Nomor sampel tidak terdaftar dalam sistem';
    }
}
