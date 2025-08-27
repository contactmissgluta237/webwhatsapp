<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PromptLengthRule implements ValidationRule
{
    private const MAX_LENGTH = 10000;

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && strlen($value) > self::MAX_LENGTH) {
            $fail(__('Le prompt ne peut pas dépasser :max caractères. Actuel : :count caractères.', [
                'max' => self::MAX_LENGTH,
                'count' => strlen($value),
            ]));
        }
    }
}
