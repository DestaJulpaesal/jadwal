@php
    $frequencyOptions = collect(\App\Enums\HabitFrequency::cases())
        ->mapWithKeys(fn ($frequency) => [$frequency->value => $frequency->label()])
        ->all();
@endphp

<section class="habit-form-section">
    <div class="habit-form-section-heading">
        <span class="habit-form-step">1</span>
        <div><h2>Apa yang ingin kamu bangun?</h2><p>Beri nama yang spesifik dan mudah kamu kenali.</p></div>
    </div>
    <div class="habit-form-grid">
        <div class="habit-form-field habit-form-field-wide">
            <label for="title" class="field-label">Nama kebiasaan (wajib)</label>
            <input id="title" name="title" value="{{ old('title', $habit->title) }}" required maxlength="120" class="form-control"
                placeholder="Contoh: Lari pagi" autocapitalize="sentences" enterkeyhint="next" data-validate-on-blur
                aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}"
                aria-describedby="title-help @if ($errors->has('title')) title-error @endif">
            <p id="title-help" class="field-help">Tulis nama kebiasaan yang mudah dikenali.</p>
            <x-input-error id="title-error" :messages="$errors->get('title')" />
        </div>
        <div class="habit-form-field habit-form-field-wide">
            <label for="description" class="field-label">Tujuan singkat <span class="habit-optional">(opsional)</span></label>
            <textarea id="description" name="description" rows="2" maxlength="500" class="form-control" placeholder="Contoh: Biar badan lebih bugar"
                data-autoresize autocomplete="off" enterkeyhint="next" aria-describedby="description-help @if ($errors->has('description')) description-error @endif">{{ old('description', $habit->description) }}</textarea>
            <p id="description-help" class="field-help">Jelaskan alasan atau detail kebiasaan ini (opsional).</p>
            <x-input-error id="description-error" :messages="$errors->get('description')" />
            <p class="mt-1 text-right text-xs text-muted" data-character-count-for="description">{{ strlen(old('description', $habit->description ?? '')) }}/500</p>
        </div>
    </div>
</section>

<section class="habit-form-section">
    <div class="habit-form-section-heading">
        <span class="habit-form-step">2</span>
        <div><h2>Kapan kamu melakukannya?</h2><p>Pilih pola yang paling realistis. Hari kosong tidak dihitung sebagai gagal.</p></div>
    </div>
    <div class="habit-form-grid">
        <div class="habit-form-field habit-form-field-wide">
            <x-segmented name="frequency" label="Pola kebiasaan" :options="$frequencyOptions"
                :value="old('frequency', $habit->frequency?->value ?? 'daily')"
                help="Seberapa sering kamu ingin melakukan kebiasaan ini?" required :error="$errors->first('frequency')" />
        </div>
        <div id="weekly-target-field" class="habit-form-field">
            <label for="weekly_target" class="field-label">Target per minggu (wajib untuk frekuensi mingguan)</label>
            <div class="habit-input-with-suffix"><input id="weekly_target" name="weekly_target" type="number" min="1" max="7" inputmode="numeric" pattern="[0-9]*" enterkeyhint="next" value="{{ old('weekly_target', $habit->weekly_target) }}" class="form-control" placeholder="3" aria-describedby="weekly-target-help"><span>kali</span></div>
            <p id="weekly-target-help" class="field-help">Jumlah minimal kebiasaan yang ingin kamu lakukan tiap minggu.</p>
        </div>
        <fieldset id="weekdays-field" class="habit-form-field habit-form-field-wide">
            <legend class="field-label">Hari latihan <span class="habit-optional">pilih minimal satu</span></legend>
            <div class="habit-weekday-grid">
                @foreach ([1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'] as $day => $label)
                    <label class="habit-day-setting">
                        <span><input type="checkbox" name="weekdays[]" value="{{ $day }}" @checked(in_array($day, old('weekdays', $habit->weekdays ?? [])))>{{ $label }}</span>
                        <input type="time" name="schedule_times[{{ $day }}]" value="{{ old('schedule_times.'.$day, data_get($habit->schedule_times, $day, $habit->preferred_time)) }}" class="form-control habit-day-time" aria-label="Jam {{ $label }}">
                    </label>
                @endforeach
            </div>
            <p class="field-help">Frekuensi hari tertentu: pilih hari latihan. Jam boleh berbeda; kosongkan jika fleksibel.</p>
        </fieldset>
        <div class="habit-form-field">
            <x-datetime-quick id="starts_on" name="starts_on" label="Mulai" type="date"
                :value="$habit->starts_on?->toDateString() ?? $today" help="Tanggal mulai menghitung konsistensi kebiasaan."
                :error="$errors->first('starts_on')" required />
        </div>
        <div class="habit-form-field">
            <x-datetime-quick id="ends_on" name="ends_on" label="Selesai sampai" type="date"
                :value="$habit->ends_on?->toDateString()" help="Kosongkan jika kebiasaan ini tidak memiliki tanggal selesai."
                :error="$errors->first('ends_on')" optional />
        </div>
    </div>
</section>

<section class="habit-form-section">
    <div class="habit-form-section-heading">
        <span class="habit-form-step">3</span>
        <div><h2>Kalau selesai, mau mengukur apa?</h2><p>Bagian ini opsional. Lewati jika cukup mencatat selesai atau belum.</p></div>
    </div>
    <div class="habit-target-guide">
        <strong>Contoh mudah</strong>
        <span>Lari <b>30</b> <b>menit</b></span>
        <span>Baca <b>10</b> <b>halaman</b></span>
        <span>Minum <b>8</b> <b>gelas</b></span>
    </div>
    <div class="habit-form-grid">
        <div class="habit-form-field">
            <label for="goal_value" class="field-label">Jumlah target <span class="habit-optional">(opsional)</span></label>
            <input id="goal_value" name="goal_value" type="number" min="1" max="1000000" value="{{ old('goal_value', $habit->goal_value) }}" class="form-control" placeholder="Contoh: 30" inputmode="numeric" pattern="[0-9]*" enterkeyhint="next" aria-describedby="goal-value-help">
            <p id="goal-value-help" class="field-help">Jumlah target yang ingin dicapai tiap kali, misalnya 8 gelas.</p>
        </div>
        <div class="habit-form-field">
            <label for="goal_unit" class="field-label">Satuan target</label>
            <select id="goal_unit" name="goal_unit" class="form-control">
                <option value="">Pilih satuan</option>
                @foreach (['menit', 'jam', 'km', 'meter', 'langkah', 'halaman', 'gelas', 'kali'] as $unit)
                    <option value="{{ $unit }}" @selected(old('goal_unit', $habit->goal_unit) === $unit)>{{ $unit }}</option>
                @endforeach
                <option value="other" @selected(old('goal_unit', $habit->goal_unit) === 'other' || ($habit->goal_unit && ! in_array($habit->goal_unit, ['menit', 'jam', 'km', 'meter', 'langkah', 'halaman', 'gelas', 'kali'], true)))>Lainnya</option>
            </select>
            <input id="goal_unit_other" name="goal_unit_other" maxlength="20" value="{{ old('goal_unit_other', $habit->goal_unit && ! in_array($habit->goal_unit, ['menit', 'jam', 'km', 'meter', 'langkah', 'halaman', 'gelas', 'kali'], true) ? $habit->goal_unit : '') }}" placeholder="Tulis satuan sendiri" class="form-control mt-2" aria-label="Satuan target lainnya" autocapitalize="sentences">
            <p class="field-help">Satuan menjelaskan target nilai, misalnya gelas, menit, atau halaman.</p>
        </div>
        <div id="habit-target-preview" class="habit-target-preview habit-form-field-wide" aria-live="polite">
            <span>Targetmu akan terlihat di sini setelah angka dan satuan diisi.</span>
        </div>
        <div class="habit-form-field">
            <x-datetime-quick id="preferred_time" name="preferred_time" label="Jam default" type="time"
                :value="$habit->preferred_time" help="Jam pengingat cadangan untuk hari yang belum diatur."
                :shortcuts="[]" :time-shortcuts="['08:00' => 'Pagi 08.00', '12:00' => 'Siang 12.00', '16:00' => 'Sore 16.00', '20:00' => 'Malam 20.00']"
                :error="$errors->first('preferred_time')" optional />
        </div>
        <div class="habit-form-field">
            <label for="category_id" class="field-label">Kategori <span class="habit-optional">(opsional)</span></label>
            <select id="category_id" name="category_id" class="form-control"><option value="">Tanpa kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $habit->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select>
            <p class="field-help">Kategori dikelola admin agar pilihan tetap rapi dan konsisten.</p>
        </div>
    </div>
</section>

<script>
    (() => {
        const frequencyFields = document.querySelectorAll('.habit-form [name="frequency"]');
        const weekdays = document.getElementById('weekdays-field');
        const weeklyTarget = document.getElementById('weekly-target-field');
        const help = document.getElementById('frequency-help');
        const update = () => {
            const frequency = document.querySelector('.habit-form [name="frequency"]:checked')?.value ?? 'daily';
            const isWeekdays = frequency === 'weekdays';
            const isWeekly = frequency === 'weekly_count';
            weekdays.hidden = !isWeekdays;
            weeklyTarget.hidden = !isWeekly;
            weekdays.querySelectorAll('input').forEach((input) => { input.disabled = !isWeekdays; });
            weeklyTarget.querySelectorAll('input').forEach((input) => {
                input.disabled = !isWeekly;
                input.required = isWeekly;
            });
            help.textContent = isWeekdays
                ? 'Pilih hari yang memang ingin kamu lakukan. Hari lain tidak dihitung sebagai gagal.'
                : isWeekly
                    ? 'Contoh: target 3 kali berarti cukup selesai tiga kali dalam seminggu.'
                    : 'Setiap hari berarti semua hari masuk perhitungan.';
        };
        frequencyFields.forEach((field) => field.addEventListener('change', update));
        update();

        const goalValue = document.getElementById('goal_value');
        const goalUnit = document.getElementById('goal_unit');
        const goalUnitOther = document.getElementById('goal_unit_other');
        const targetPreview = document.getElementById('habit-target-preview');
        const updateTargetPreview = () => {
            const value = goalValue.value.trim();
            const unit = goalUnit.value === 'other' ? goalUnitOther.value.trim() : goalUnit.value.trim();
            targetPreview.querySelector('span').textContent = value && unit
                ? `Targetmu: ${value} ${unit} setiap kali dilakukan.`
                : 'Targetmu akan terlihat di sini setelah angka dan satuan diisi.';
            targetPreview.classList.toggle('is-ready', Boolean(value && unit));
        };
        goalValue.addEventListener('input', updateTargetPreview);
        goalUnit.addEventListener('input', updateTargetPreview);
        goalUnitOther.addEventListener('input', updateTargetPreview);
        const updateGoalUnit = () => {
            goalUnitOther.hidden = goalUnit.value !== 'other';
            goalUnitOther.disabled = goalUnit.value !== 'other';
            if (goalUnit.value !== 'other') goalUnitOther.value = '';
        };
        goalUnit.addEventListener('change', updateGoalUnit);
        updateGoalUnit();
        updateTargetPreview();

        document.querySelectorAll('.habit-form input[type="number"], .habit-checkin-fields input[type="number"]').forEach((input) => {
            input.addEventListener('wheel', () => input.blur(), { passive: true });
        });
    })();
</script>
