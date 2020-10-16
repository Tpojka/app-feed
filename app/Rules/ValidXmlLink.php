<?php

namespace App\Rules;

use App\Service\Feed\FeedService;
use Illuminate\Contracts\Validation\Rule;

class ValidXmlLink implements Rule
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
        $feedService = new FeedService();

        return $feedService->isSourceValidXml($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be valid XML link.';
    }
}
