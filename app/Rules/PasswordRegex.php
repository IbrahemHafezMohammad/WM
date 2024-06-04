<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordRegex implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^[A-Za-z0-9!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?]{6,16}$/', $value)) {
            $fail('The :attribute must consist of lowercase/upper letters and numbers with special characters, and be between 6 and 16 characters long.');
        }        
        
    }
}
