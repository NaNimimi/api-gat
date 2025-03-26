<?php

namespace App\Http\Requests;

use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use App\Http\Requests\Contracts\ValidationContract;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseRequest extends FormRequest implements ValidationContract
{
    abstract public function rules(): array;

    public function authorize(): bool
    {
        return true;
    }

    public function getValidationRules(): array
    {
        return $this->rules();
    }

    /**
     * Validation error messages.
     */
    public function messages(): array
    {
        return array_merge($this->defaultMessages(), $this->customMessages());
    }

    /**
     * Default validation messages.
     */
    protected function defaultMessages(): array
    {
        return [
            'required' => 'Поле :attribute обязательно для заполнения',
            'string' => 'Поле :attribute должно быть строкой',
            'integer' => 'Поле :attribute должно быть целым числом',
            'numeric' => 'Поле :attribute должно быть числом',
            'array' => 'Поле :attribute должно быть массивом',
            'email' => 'Поле :attribute должно быть действительным email адресом',
            'unique' => 'Такое значение поля :attribute уже существует',
            'exists' => 'Выбранное значение для :attribute некорректно',
            'max' => [
                'numeric' => 'Поле :attribute не может быть больше :max',
                'string' => 'Поле :attribute не может быть больше :max символов',
                'array' => 'Поле :attribute не может содержать больше :max элементов',
            ],
            'min' => [
                'numeric' => 'Поле :attribute должно быть не менее :min',
                'string' => 'Поле :attribute должно содержать не менее :min символов',
                'array' => 'Поле :attribute должно содержать не менее :min элементов',
            ],
            'in' => 'Выбранное значение для :attribute ошибочно',
            'date' => 'Поле :attribute не является датой',
            'date_format' => 'Поле :attribute не соответствует формату :format',
            'boolean' => 'Поле :attribute должно быть логическим значением',
            'confirmed' => 'Поле :attribute не совпадает с подтверждением',
            'size' => [
                'numeric' => 'Поле :attribute должно быть равным :size',
                'file' => 'Размер файла в поле :attribute должен быть равен :size Кб',
                'string' => 'Количество символов в поле :attribute должно быть равным :size',
                'array' => 'Количество элементов в поле :attribute должно быть равным :size',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    protected function customMessages(): array
    {
        return [];
    }

    /**
     * Get filter parameters from the request.
     */
    protected function getFilterParameters(): array
    {
        return array_filter($this->only([
            'search',
            'orderBy',
            'sortedBy',
            'per_page',
            'filter',
        ]));
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->mergeSearch();
        $this->mergePerPage();
        $this->mergeOrderBy();
    }

    protected function mergeSearch(): void
    {
        if ($this->has('search')) {
            $this->merge(['search' => strip_tags($this->get('search'))]);
        }
    }

    protected function mergePerPage(): void
    {
        if ($this->has('per_page')) {
            $this->merge(['per_page' => (int) $this->get('per_page')]);
        }
    }

    protected function mergeOrderBy(): void
    {
        if ($this->has('orderBy')) {
            $this->merge([
                'orderBy' => strtolower($this->get('orderBy')),
                'sortedBy' => strtolower($this->get('sortedBy', 'asc')),
            ]);
        }
    }

    public function sanitizeInput(array $input): array
    {
        return array_intersect_key($input, array_flip(array_keys($this->rules())));
    }

    public function validateAndTransform(array $data): array
    {
        return $this->validate($data);
    }

    protected function failedValidation(Validator $validator): void
    {

        throw new HttpResponseException(
            (new ValidationException($validator->errors()->toArray()))->render()
        );
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            (new ForbiddenException)->render()
        );
    }

    protected function formatErrors(array $errors): array
    {
        return array_map(static function ($messages, $field) {
            return [
                'field' => $field,
                'messages' => $messages,
                'first_message' => $messages[0] ?? null,
            ];
        }, $errors, array_keys($errors));
    }
}
