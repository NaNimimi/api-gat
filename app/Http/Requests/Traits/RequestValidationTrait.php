<?php

namespace App\Http\Requests\Traits;

use Illuminate\Foundation\Http\FormRequest;

trait RequestValidationTrait
{
    protected function prepareNumericArray(string $key): void
    {
        /**
         * @var $this FormRequest
         */
        if ($this->has($key)) {
            $this->merge([
                $key => array_map('intval',
                    array_filter($this->get($key), 'is_numeric')
                ),
            ]);
        }
    }

    protected function stripTags(string $key): void
    {
        /**
         * @var $this FormRequest
         */
        if ($this->has($key)) {
            $this->merge([$key => strip_tags($this->get($key))]);
        }
    }
}
