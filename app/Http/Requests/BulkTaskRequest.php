<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Task::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['complete', 'status', 'delete'])],
            'task_ids' => ['required', 'array', 'min:1', 'max:50'],
            'task_ids.*' => ['required', 'integer', 'distinct', 'exists:tasks,id'],
            'task_status_id' => ['required_if:action,status', 'nullable', 'integer', 'exists:task_statuses,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'aksi tugas',
            'task_ids' => 'tugas terpilih',
            'task_ids.*' => 'tugas',
            'task_status_id' => 'status',
        ];
    }
}
