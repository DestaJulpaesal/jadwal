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
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id),
            ],
            'task_status_id' => ['nullable', 'integer', 'exists:task_statuses,id'],
            'due_date' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date', 'required_with:ends_at'],
            'ends_at' => ['nullable', 'date', 'required_with:starts_at', 'after_or_equal:starts_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul tugas wajib diisi.',
            'title.string' => 'Judul tugas harus berupa teks.',
            'title.max' => 'Judul tugas maksimal 255 karakter.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'category_id.integer' => 'Kategori yang dipilih tidak valid.',
            'category_id.exists' => 'Kategori yang dipilih bukan milik akun Anda.',
            'task_status_id.integer' => 'Status tugas tidak valid.',
            'task_status_id.exists' => 'Status tugas yang dipilih tidak tersedia.',
            'due_date.date' => 'Format jatuh tempo tidak valid.',
            'starts_at.date' => 'Format waktu mulai tidak valid.',
            'ends_at.date' => 'Format waktu selesai tidak valid.',
            'starts_at.required_with' => 'Isi waktu mulai jika waktu selesai diisi.',
            'ends_at.required_with' => 'Isi waktu selesai jika waktu mulai diisi.',
            'ends_at.after_or_equal' => 'Waktu selesai harus sama atau setelah waktu mulai.',
        ];
    }
}
