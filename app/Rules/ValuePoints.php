<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValuePoints implements Rule
{
    private $max, $min;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($min, $max)
    {
        $this->max = $max;
        $this->min = $min;
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
        return (intval($value) >= $this->min && intval($value) <= $this->max);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Values are between 1 and 4.';
    }
}
