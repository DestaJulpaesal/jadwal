@php
    $goalTypes = collect(\App\Enums\GoalType::cases())
        ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
        ->all();
@endphp

<x-form-section title="Rincian target" description="Tentukan hasil akhir yang ingin kamu capai.">
    <x-field id="title" label="Nama target" help="Beri nama yang jelas agar progresnya mudah dikenali." :error="$errors->first('title')" required>
        <input id="title" name="title" type="text" value="{{ old('title', $goal->title) }}" required maxlength="120"
            autocapitalize="sentences" enterkeyhint="next" data-validate-on-blur class="form-control"
            aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}"
            aria-describedby="title-help @if ($errors->has('title')) title-error @endif">
    </x-field>

    <x-segmented name="type" label="Jenis target" :options="$goalTypes"
        :value="old('type', $goal->type?->value ?? 'number')"
        help="Pilih cara aplikasi menghitung progres." :error="$errors->first('type')" required />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-field id="target_value" label="Nilai target" help="Tentukan jumlah akhir yang ingin dicapai." :error="$errors->first('target_value')" required>
            <input id="target_value" name="target_value" type="number" min="1" inputmode="numeric" pattern="[0-9]*"
                enterkeyhint="next" value="{{ old('target_value', $goal->target_value) }}" required class="form-control"
                aria-invalid="{{ $errors->has('target_value') ? 'true' : 'false' }}"
                aria-describedby="target_value-help @if ($errors->has('target_value')) target_value-error @endif">
        </x-field>
        <x-datetime-quick id="due_on" name="due_on" label="Tenggat" type="date"
            :value="$goal->due_on?->toDateString()" help="Pilih tanggal yang menjadi batas target." optional
            :error="$errors->first('due_on')" />
    </div>
</x-form-section>

<div class="space-y-4 rounded-lg border border-line p-4" data-disclosure
    data-open="{{ $errors->hasAny(['start_value', 'unit', 'habit_id', 'wallet_id', 'description']) ? 'true' : 'false' }}">
    <button type="button" class="min-h-11 font-semibold text-primary-800" data-disclosure-trigger
        aria-expanded="{{ $errors->hasAny(['start_value', 'unit', 'habit_id', 'wallet_id', 'description']) ? 'true' : 'false' }}"
        aria-controls="goal-advanced">
        <span class="inline-flex items-center gap-2"><x-icon name="menu" class="h-4 w-4" />Pengaturan lanjutan</span>
    </button>
    <div id="goal-advanced" data-disclosure-content class="space-y-4 border-t border-line pt-4">
    <div class="grid gap-4 pt-4 sm:grid-cols-2">
        <x-field id="start_value" label="Nilai awal" help="Masukkan posisi kamu sekarang untuk menghitung progres." :error="$errors->first('start_value')" optional>
            <input id="start_value" name="start_value" type="number" min="0" inputmode="numeric" pattern="[0-9]*"
                value="{{ old('start_value', $goal->start_value ?? 0) }}" class="form-control"
                aria-invalid="{{ $errors->has('start_value') ? 'true' : 'false' }}"
                aria-describedby="start_value-help @if ($errors->has('start_value')) start_value-error @endif">
        </x-field>
        <x-field id="unit" label="Satuan" help="Contoh satuan: Rupiah, halaman, atau kali." :error="$errors->first('unit')" optional>
            <input id="unit" name="unit" maxlength="40" value="{{ old('unit', $goal->unit) }}" placeholder="Rupiah, halaman, kali"
                autocapitalize="sentences" class="form-control" aria-invalid="{{ $errors->has('unit') ? 'true' : 'false' }}"
                aria-describedby="unit-help @if ($errors->has('unit')) unit-error @endif">
        </x-field>
        <x-select id="habit_id" name="habit_id" label="Kebiasaan terkait" help="Tautkan agar progres berasal dari kebiasaan yang kamu catat." optional>
            <option value="">Pilih jika target kebiasaan</option>
            @foreach ($habits as $habit)
                <option value="{{ $habit->id }}" @selected(old('habit_id', $goal->habit_id) == $habit->id)>{{ $habit->title }}</option>
            @endforeach
        </x-select>
        <x-select id="wallet_id" name="wallet_id" label="Dompet terkait" help="Tautkan ke dompet agar progres tabungan terhitung otomatis." optional>
            <option value="">Pilih jika target tabungan</option>
            @foreach ($wallets as $wallet)
                <option value="{{ $wallet->id }}" @selected(old('wallet_id', $goal->wallet_id) == $wallet->id)>{{ $wallet->name }}</option>
            @endforeach
        </x-select>
        <x-textarea id="description" name="description" label="Catatan" help="Tambahkan konteks yang membantu kamu menjaga target." optional
            :error="$errors->first('description')" :value="old('description', $goal->description)" rows="3" maxlength="500" autocomplete="off" />
    </div>
    </div>
</div>
