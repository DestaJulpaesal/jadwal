<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            ? $this->user()->can('update', $task)
            : $this->user()->can('create', Task::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $this->user()->id)
                ),
            ],
            'task_status_id' => ['nullable', 'integer', 'exists:task_statuses,id'],
            'due_date' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date', 'required_with:ends_at'],
            'ends_at' => ['nullable', 'date', 'required_with:starts_at', 'after_or_equal:starts_at'],
            'recurrence' => ['sometimes', 'required', Rule::in(['none', 'daily', 'weekly', 'monthly'])],
            'recurrence_days' => ['nullable', 'array', 'required_if:recurrence,weekly', 'min:1', 'max:7'],
            'recurrence_days.*' => ['required', 'integer', 'distinct', 'between:1,7'],
            'recurrence_until' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return __('validation.catalog.task');
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul tugas',
            'description' => 'deskripsi',
            'category_id' => 'kategori',
            'task_status_id' => 'status',
            'due_date' => 'tanggal jatuh tempo',
            'starts_at' => 'waktu mulai',
            'ends_at' => 'waktu selesai',
            'recurrence' => 'pengulangan',
            'recurrence_days' => 'hari pengulangan',
            'recurrence_until' => 'batas pengulangan',
        ];
    }
}
