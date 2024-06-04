<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AgentUniqueCodeRegex implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(!preg_match('/^(?:[a-z]|[0-9]){1,10}$/', $value)) {
            $fail('The :attribute must be lowercase or numbers or both and the length between 1 and 10');
        }
    }
}
