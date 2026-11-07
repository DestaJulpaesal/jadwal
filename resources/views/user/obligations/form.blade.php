<x-form-section title="Rincian pengingat" description="Pengingat bulanan tetap menggunakan data pembayaran yang sudah tercatat.">
    @if (! $lockedByPayments)
        <x-segmented name="kind" label="Jenis" :options="['bill' => 'Tagihan', 'installment' => 'Cicilan']" :value="old('kind', $obligation->kind?->value ?? 'bill')" required help="Pilih tagihan rutin atau cicilan dengan jumlah pembayaran terbatas." />
    @else
        <p class="text-sm text-muted">Jenis: <strong>{{ $obligation->kind->label() }}</strong>. Jenis, tanggal, dan jumlah cicilan dikunci karena pembayaran sudah tercatat.</p>
    @endif

    <x-field id="obligation_title" label="Nama" help="Tulis nama tagihan atau cicilan yang mudah dikenali." :error="$errors->first('title')" required>
        <input id="obligation_title" name="title" value="{{ old('title', $obligation->title) }}" required maxlength="120" autocapitalize="sentences" data-validate-on-blur class="form-control">
    </x-field>

    <x-money-input id="obligation_amount" name="amount" label="Nominal" :value="$obligation->amount" :error="$errors->first('amount')" />

    @if (! $lockedByPayments)
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field id="obligation_due_day" label="Tanggal jatuh tempo" help="Pilih tanggal 1–31; bulan pendek menyesuaikan hari terakhir." :error="$errors->first('due_day')" required>
                <input id="obligation_due_day" name="due_day" type="number" min="1" max="31" inputmode="numeric" pattern="[0-9]*" value="{{ old('due_day', $obligation->due_day) }}" required class="form-control">
            </x-field>
            <x-datetime-quick id="obligation_starts_on" name="starts_on" label="Tanggal mulai" type="date" :value="$obligation->starts_on?->toDateString() ?? $today"
                help="Pilih tanggal mulai pengingat jatuh tempo." required :error="$errors->first('starts_on')" />
        </div>
        <div data-conditional-field data-condition-name="kind" data-condition-value="installment" data-condition-required hidden>
            <x-field id="installments_total" label="Jumlah cicilan" help="Kosongkan hanya untuk tagihan tanpa batas." :error="$errors->first('installments_total')" required>
                <input id="installments_total" name="installments_total" type="number" min="1" max="120" inputmode="numeric" pattern="[0-9]*" value="{{ old('installments_total', $obligation->installments_total) }}" class="form-control">
            </x-field>
        </div>
    @else
        <div class="grid gap-2 rounded-lg bg-canvas p-3 text-sm text-muted sm:grid-cols-3">
            <p>Jatuh tempo tanggal {{ $obligation->due_day }} setiap bulan</p>
            <p>Mulai {{ $obligation->starts_on->translatedFormat('j M Y') }}</p>
            @if ($obligation->kind->value === 'installment')
                <p>{{ $obligation->installments_total }} cicilan</p>
            @endif
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <x-field id="remind_days_before" label="Ingatkan (hari sebelumnya)" help="Tentukan berapa hari sebelum jatuh tempo pengingat dikirim." :error="$errors->first('remind_days_before')" required>
            <input id="remind_days_before" name="remind_days_before" type="number" min="0" max="30" inputmode="numeric" pattern="[0-9]*" value="{{ old('remind_days_before', $obligation->remind_days_before ?? 3) }}" required class="form-control">
        </x-field>
        <x-select id="obligation_wallet_id" name="wallet_id" label="Dompet pembayaran" help="Pilih dompet yang biasa dipakai; boleh ditentukan nanti." optional>
            <option value="">Pilih nanti</option>
            @foreach ($wallets as $wallet)
                <option value="{{ $wallet->id }}" @selected(old('wallet_id', $obligation->wallet_id) == $wallet->id)>{{ $wallet->name }}</option>
            @endforeach
        </x-select>
        <x-select id="obligation_category_id" name="money_category_id" label="Kategori pengeluaran" help="Kelompokkan pembayaran pada laporan keuangan." optional>
            <option value="">Tanpa kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('money_category_id', $obligation->money_category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </x-select>
        <x-textarea id="obligation_note" name="note" label="Catatan" help="Tambahkan keterangan yang perlu diingat (opsional)." optional :error="$errors->first('note')" :value="$obligation->note" rows="2" maxlength="255" autocomplete="off" />
    </div>
</x-form-section>
