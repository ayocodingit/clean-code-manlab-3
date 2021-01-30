<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 *
 * @method static self lainnya()
 */

class LabPCREnum extends Enum
{
    public static function lainnya(): LabPCREnum
    {
        return new class () extends LabPCREnum
        {
            public function getValue(): string
            {
                return 'lainnya';
            }
            public function getIndex(): int
            {
                return 999999;
            }
        };
    }
}
