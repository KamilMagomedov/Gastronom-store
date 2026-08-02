<?php

namespace App\Http\Concerns;

use Illuminate\Support\Str;

trait WithTranslateMessage
{
    protected function getTranslatedOtpMessage(string $otpMessage)
    {
        $message = Str::of($otpMessage)->lower()->snake()->replace(' ', '_');

        return __('validation.'.$message);
    }
}
