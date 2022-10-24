<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class TheSame implements Rule
{
    private $theValue;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($theValue)
    {
        $this->theValue = $theValue;
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
        //var_dump("HOLA:",$attribute,$value);
        return ($this->theValue == $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Id inconsistent with parameter.';
    }
}
