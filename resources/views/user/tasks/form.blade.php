<div>
    <label for="title" class="field-label">Judul tugas</label>
    <input id="title" name="title" value="{{ old('title', $task->title ?? '') }}" required maxlength="255" class="form-control" aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}" aria-describedby="title-error">
    <x-input-error id="title-error" :messages="$errors->get('title')" />
</div>

<div>
    <label for="description" class="field-label">Catatan <span class="font-normal text-muted">(opsional)</span></label>
    <textarea id="description" name="description" rows="4" class="form-control" aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}" aria-describedby="description-error">{{ old('description', $task->description ?? '') }}</textarea>
    <x-input-error id="description-error" :messages="$errors->get('description')" />
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="category_id" class="field-label">Kategori <span class="font-normal text-muted">(opsional)</span></label>
        <select id="category_id" name="category_id" class="form-control" aria-invalid="{{ $errors->has('category_id') ? 'true' : 'false' }}" aria-describedby="category-error">
            <option value="">Tanpa kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $task->category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-input-error id="category-error" :messages="$errors->get('category_id')" />
    </div>
    <div>
        <label for="task_status_id" class="field-label">Status</label>
        <select id="task_status_id" name="task_status_id" class="form-control" aria-invalid="{{ $errors->has('task_status_id') ? 'true' : 'false' }}" aria-describedby="status-error">
            <option value="">Belum berstatus</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->id }}" @selected(old('task_status_id', $task->task_status_id ?? '') == $status->id)>{{ $status->name }}</option>
            @endforeach
        </select>
        <x-input-error id="status-error" :messages="$errors->get('task_status_id')" />
    </div>
</div>

<section class="rounded-xl border border-line bg-gray-50 p-4 sm:p-5">
    <h2 class="font-semibold">Waktu tugas</h2>
    <p class="mt-1 text-sm text-muted">Gunakan waktu setempat sesuai zona waktu profil Anda.</p>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <label for="starts_at" class="field-label">Waktu mulai</label>
            <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', isset($task) && $task->starts_at ? $task->starts_at->timezone(auth()->user()->timezone)->format('Y-m-d\TH:i') : '') }}" class="form-control" aria-invalid="{{ $errors->has('starts_at') ? 'true' : 'false' }}" aria-describedby="starts-at-help starts-at-error">
            <p id="starts-at-help" class="field-help">Contoh: 15 Okt 2026, pukul 10.00.</p>
            <x-input-error id="starts-at-error" :messages="$errors->get('starts_at')" />
        </div>
        <div>
            <label for="ends_at" class="field-label">Waktu selesai</label>
            <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', isset($task) && $task->ends_at ? $task->ends_at->timezone(auth()->user()->timezone)->format('Y-m-d\TH:i') : '') }}" class="form-control" aria-invalid="{{ $errors->has('ends_at') ? 'true' : 'false' }}" aria-describedby="ends-at-help ends-at-error">
            <p id="ends-at-help" class="field-help">Waktu selesai harus setelah waktu mulai.</p>
            <x-input-error id="ends-at-error" :messages="$errors->get('ends_at')" />
        </div>
        <div class="sm:col-span-2">
            <label for="due_date" class="field-label">Jatuh tempo <span class="font-normal text-muted">(opsional)</span></label>
            <input id="due_date" type="datetime-local" name="due_date" value="{{ old('due_date', isset($task) && $task->due_date ? $task->due_date->timezone(auth()->user()->timezone)->format('Y-m-d\TH:i') : '') }}" class="form-control" aria-invalid="{{ $errors->has('due_date') ? 'true' : 'false' }}" aria-describedby="due-date-help due-date-error">
            <p id="due-date-help" class="field-help">Pilih batas waktu jika tugas perlu selesai sebelum waktu tertentu.</p>
            <x-input-error id="due-date-error" :messages="$errors->get('due_date')" />
        </div>
    </div>
</section>
