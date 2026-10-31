<div>
    <label for="title" class="mb-1 block font-medium">Judul</label>
    <input id="title" name="title" value="{{ old('title', $task->title ?? '') }}" required maxlength="255" class="w-full rounded border-gray-300">
    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="description" class="mb-1 block font-medium">Deskripsi</label>
    <textarea id="description" name="description" rows="4" class="w-full rounded border-gray-300">{{ old('description', $task->description ?? '') }}</textarea>
    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="category_id" class="mb-1 block font-medium">Kategori (opsional)</label>
    <select id="category_id" name="category_id" class="w-full rounded border-gray-300">
        <option value="">Tanpa kategori</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $task->category_id ?? '') == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    @error('category_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="category_name" class="mb-1 block font-medium">Atau buat kategori baru</label>
    <input id="category_name" name="category_name" value="{{ old('category_name') }}" maxlength="255" class="w-full rounded border-gray-300">
    @error('category_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="category_color" class="mb-1 block font-medium">Warna kategori baru</label>
    <input id="category_color" type="color" name="category_color" value="{{ old('category_color', '#3B82F6') }}" class="h-11 w-16 rounded border border-gray-300 p-1">
    @error('category_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="task_status_id" class="mb-1 block font-medium">Status</label>
    <select id="task_status_id" name="task_status_id" class="w-full rounded border-gray-300">
        <option value="">Belum berstatus</option>
        @foreach ($statuses as $status)
            <option value="{{ $status->id }}" @selected(old('task_status_id', $task->task_status_id ?? '') == $status->id)>{{ $status->name }}</option>
        @endforeach
    </select>
    @error('task_status_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="starts_at" class="mb-1 block font-medium">Waktu mulai</label>
    <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', isset($task) && $task->starts_at ? $task->starts_at->timezone(auth()->user()->timezone)->format('Y-m-d\TH:i') : '') }}" class="w-full rounded border-gray-300">
    @error('starts_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="ends_at" class="mb-1 block font-medium">Waktu selesai</label>
    <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', isset($task) && $task->ends_at ? $task->ends_at->timezone(auth()->user()->timezone)->format('Y-m-d\TH:i') : '') }}" class="w-full rounded border-gray-300">
    @error('ends_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="due_date" class="mb-1 block font-medium">Jatuh tempo</label>
    <input id="due_date" type="datetime-local" name="due_date" value="{{ old('due_date', isset($task) && $task->due_date ? $task->due_date->timezone(auth()->user()->timezone)->format('Y-m-d\TH:i') : '') }}" class="w-full rounded border-gray-300">
    @error('due_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
