<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Авторизуем всех аутентифицированных пользователей
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'issue_id' => ['nullable', 'exists:issues,id'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'URL обязателен для заполнения',
            'url.url' => 'Введите корректный URL',
            'url.max' => 'URL не должен превышать 255 символов',
            'title.required' => 'Заголовок обязателен для заполнения',
            'title.max' => 'Заголовок не должен превышать 255 символов',
            'issue_id.exists' => 'Выбранный выпуск не существует',
            'position.integer' => 'Позиция должна быть целым числом',
            'position.min' => 'Позиция не может быть отрицательной',
        ];
    }
}
