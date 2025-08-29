<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PromptLengthRule implements ValidationRule
{
    private const DEFAULT_MAX_LENGTH = 10000;

    private int $maxLength;

    /**
     * Create a new rule instance.
     */
    public function __construct(?int $maxLength = null)
    {
        $this->maxLength = $maxLength ?? self::DEFAULT_MAX_LENGTH;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && strlen($value) > $this->maxLength) {
            $fail(__('Le prompt ne peut pas dépasser :max caractères. Actuel : :count caractères.', [
                'max' => $this->maxLength,
                'count' => strlen($value),
            ]));
        }
    }
}
