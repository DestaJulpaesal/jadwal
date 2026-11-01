<div class="grid gap-4 sm:grid-cols-2">
    <div><label for="title" class="field-label">Nama target</label><input id="title" name="title" value="{{ old('title', $goal->title) }}" required maxlength="120" class="form-control"></div>
    <div><label for="type" class="field-label">Jenis target</label><select id="type" name="type" class="form-control">@foreach(\App\Enums\GoalType::cases() as $type)<option value="{{ $type->value }}" @selected(old('type', $goal->type?->value ?? 'number') === $type->value)>{{ $type->label() }}</option>@endforeach</select></div>
    <div><label for="target_value" class="field-label">Nilai target</label><input id="target_value" name="target_value" inputmode="numeric" value="{{ old('target_value', $goal->target_value) }}" required class="form-control"></div>
    <div><label for="due_on" class="field-label">Target tanggal (opsional)</label><input id="due_on" name="due_on" type="date" value="{{ old('due_on', $goal->due_on?->toDateString()) }}" class="form-control"></div>
</div>
<details>
    <summary class="min-h-11 cursor-pointer py-3 font-medium text-primary-800">Pengaturan lanjutan</summary>
    <div class="grid gap-4 border-t border-line pt-4 sm:grid-cols-2">
        <div><label for="start_value" class="field-label">Nilai awal</label><input id="start_value" name="start_value" type="number" min="0" value="{{ old('start_value', $goal->start_value ?? 0) }}" class="form-control"></div>
        <div><label for="unit" class="field-label">Satuan</label><input id="unit" name="unit" maxlength="40" value="{{ old('unit', $goal->unit) }}" placeholder="Rupiah, halaman, kali" class="form-control"></div>
        <div><label for="habit_id" class="field-label">Kebiasaan yang terhubung</label><select id="habit_id" name="habit_id" class="form-control"><option value="">Pilih jika target kebiasaan</option>@foreach ($habits as $habit)<option value="{{ $habit->id }}" @selected(old('habit_id', $goal->habit_id) == $habit->id)>{{ $habit->title }}</option>@endforeach</select></div>
        <div><label for="wallet_id" class="field-label">Dompet tabungan</label><select id="wallet_id" name="wallet_id" class="form-control"><option value="">Pilih jika target tabungan</option>@foreach ($wallets as $wallet)<option value="{{ $wallet->id }}" @selected(old('wallet_id', $goal->wallet_id) == $wallet->id)>{{ $wallet->name }}</option>@endforeach</select></div>
        <div class="sm:col-span-2"><label for="description" class="field-label">Catatan</label><textarea id="description" name="description" rows="2" maxlength="500" class="form-control">{{ old('description', $goal->description) }}</textarea></div>
    </div>
</details>
