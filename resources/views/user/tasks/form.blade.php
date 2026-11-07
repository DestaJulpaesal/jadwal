<x-form-section title="Rincian tugas" description="Tuliskan rencana dengan singkat agar mudah ditemukan dan diselesaikan.">
    <x-field id="title" label="Judul tugas" help="Tulis apa yang harus dikerjakan, singkat saja." :error="$errors->first('title')" required>
        <input id="title" name="title" type="text" value="{{ old('title', $task->title ?? '') }}" required maxlength="255"
            autocapitalize="sentences" enterkeyhint="next" data-validate-on-blur
            aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}"
            aria-describedby="title-help @if ($errors->has('title')) title-error @endif" class="form-control">
        <p class="mt-1 text-right text-xs text-muted" data-character-count-for="title">{{ strlen(old('title', $task->title ?? '')) }}/255</p>
    </x-field>

    <x-textarea id="description" name="description" label="Catatan" help="Tambahkan detail, tautan, atau langkah-langkah (opsional)."
        :error="$errors->first('description')" :value="old('description', $task->description ?? '')" rows="3" maxlength="1000" optional
        autocomplete="off" enterkeyhint="next" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-select id="category_id" label="Kategori" help="Kategori membantu mengelompokkan tugas." :error="$errors->first('category_id')" optional name="category_id">
            <option value="">Tanpa kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $task->category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </x-select>
        <x-select id="task_status_id" label="Status" help="Pilih status yang paling sesuai saat ini." :error="$errors->first('task_status_id')" optional name="task_status_id">
            <option value="">Belum berstatus</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->id }}" @selected(old('task_status_id', $task->task_status_id ?? '') == $status->id)>{{ $status->name }}</option>
            @endforeach
        </x-select>
    </div>
</x-form-section>

<x-form-section title="Waktu tugas" description="Waktu mengikuti zona waktu yang tersimpan di profil.">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-datetime-quick id="starts_at" name="starts_at" label="Waktu mulai" type="datetime-local"
            :value="isset($task) && $task->starts_at ? $task->starts_at->timezone(auth()->user()->timezone)->format('Y-m-d\TH:i') : ''"
            help="Pilih tanggal dan jam mulai; waktu ini juga dipakai untuk pintasan durasi."
            :error="$errors->first('starts_at')" optional
            :time-shortcuts="['08:00' => 'Pagi 08.00', '12:00' => 'Siang 12.00', '16:00' => 'Sore 16.00', '20:00' => 'Malam 20.00']"
            autocomplete="off" />
        <x-datetime-quick id="ends_at" name="ends_at" label="Waktu selesai" type="datetime-local"
            :value="isset($task) && $task->ends_at ? $task->ends_at->timezone(auth()->user()->timezone)->format('Y-m-d\TH:i') : ''"
            help="Waktu selesai harus setelah waktu mulai."
            :error="$errors->first('ends_at')" optional
            duration-start="starts_at" duration-end="ends_at" autocomplete="off" />
        <div class="sm:col-span-2">
            <x-datetime-quick id="due_date" name="due_date" label="Jatuh tempo" type="datetime-local"
                :value="isset($task) && $task->due_date ? $task->due_date->timezone(auth()->user()->timezone)->format('Y-m-d\TH:i') : ''"
                help="Pilih batas waktu jika tugas perlu selesai sebelum waktu tertentu."
                :error="$errors->first('due_date')" optional autocomplete="off" />
        </div>
    </div>
</x-form-section>
