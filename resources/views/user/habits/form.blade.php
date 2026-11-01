<div>
    <label for="title" class="field-label">Nama kebiasaan</label>
    <input id="title" name="title" value="{{ old('title', $habit->title) }}" required maxlength="120" class="form-control">
</div>
<div>
    <label for="frequency" class="field-label">Jadwal</label>
    <select id="frequency" name="frequency" required class="form-control">
        @foreach (\App\Enums\HabitFrequency::cases() as $frequency)
            <option value="{{ $frequency->value }}" @selected(old('frequency', $habit->frequency?->value ?? 'daily') === $frequency->value)>{{ $frequency->label() }}</option>
        @endforeach
    </select>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="starts_on" class="field-label">Mulai</label>
        <input id="starts_on" name="starts_on" type="date" required value="{{ old('starts_on', $habit->starts_on?->toDateString() ?? $today) }}" class="form-control">
    </div>
    <div>
        <label for="ends_on" class="field-label">Berakhir (opsional)</label>
        <input id="ends_on" name="ends_on" type="date" value="{{ old('ends_on', $habit->ends_on?->toDateString()) }}" class="form-control">
    </div>
</div>
<details>
    <summary class="min-h-11 cursor-pointer py-3 font-medium text-primary-800">Pengaturan lanjutan</summary>
    <div class="space-y-4 border-t border-line pt-4">
        <div>
            <label for="description" class="field-label">Catatan</label>
            <textarea id="description" name="description" rows="3" maxlength="500" class="form-control">{{ old('description', $habit->description) }}</textarea>
        </div>
        <fieldset>
            <legend class="field-label">Hari tertentu</legend>
            <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
                @foreach ([1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'] as $day => $label)
                    <label class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-line px-3 text-sm">
                        <input type="checkbox" name="weekdays[]" value="{{ $day }}" @checked(in_array($day, old('weekdays', $habit->weekdays ?? [])))>{{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="weekly_target" class="field-label">Target kali per minggu</label><input id="weekly_target" name="weekly_target" type="number" min="1" max="7" value="{{ old('weekly_target', $habit->weekly_target) }}" class="form-control"></div>
            <div><label for="goal_value" class="field-label">Target nilai</label><input id="goal_value" name="goal_value" type="number" min="1" max="1000000" value="{{ old('goal_value', $habit->goal_value) }}" class="form-control"></div>
            <div><label for="goal_unit" class="field-label">Satuan</label><input id="goal_unit" name="goal_unit" maxlength="20" value="{{ old('goal_unit', $habit->goal_unit) }}" placeholder="menit, gelas, halaman" class="form-control"></div>
            <div><label for="preferred_time" class="field-label">Waktu yang diinginkan</label><input id="preferred_time" name="preferred_time" type="time" value="{{ old('preferred_time', $habit->preferred_time) }}" class="form-control"></div>
            <div><label for="category_id" class="field-label">Kategori</label><select id="category_id" name="category_id" class="form-control"><option value="">Tanpa kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $habit->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
        </div>
    </div>
</details>
