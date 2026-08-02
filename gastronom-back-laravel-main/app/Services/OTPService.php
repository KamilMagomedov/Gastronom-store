<?php

namespace App\Services;

use Ichtrojan\Otp\Models\Otp as Model;
use Ichtrojan\Otp\Otp;

class OTPService
{
    public function __construct(public Otp $otp) {}

    /**
     * @throws \Exception
     */
    public function generate(string $identifier, string $type = 'numeric', int $length = 4, int $validity = 10): object
    {
        return $this->otp->generate($identifier, $type, $length, $validity);
    }

    public function validate(string $identifier, string|int $token): object
    {
        return $this->otp->validate($identifier, $token);
    }

    public function deleteOtps(string $identifier): int
    {
        return Model::where('identifier', $identifier)->delete();
    }
}
