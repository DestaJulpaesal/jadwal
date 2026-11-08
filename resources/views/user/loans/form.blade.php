@php($loan = $loan ?? null)
@php($lockedByRepayments = $lockedByRepayments ?? false)

<x-form-section title="Rincian utang/piutang" description="Catat arah, pihak terkait, tanggal, dan dompet yang dipakai.">
    @if (! $lockedByRepayments)
        <x-segmented name="direction" label="Arah" :options="['lent' => 'Saya meminjamkan', 'borrowed' => 'Saya berutang']"
            :value="old('direction', $loan?->direction?->value ?? 'lent')"
            help="Meminjamkan berarti orang lain berutang kepadamu; berutang berarti kamu wajib membayar." required />
        <x-field id="loan_counterparty" label="Nama pihak" help="Nama orang atau pihak yang terkait dengan utang/piutang ini."
            :error="$errors->first('counterparty')" required>
            <input id="loan_counterparty" name="counterparty" value="{{ old('counterparty', $loan?->counterparty) }}" maxlength="80"
                required autocapitalize="words" data-validate-on-blur class="form-control">
        </x-field>
        <x-money-input id="loan_amount" name="amount" label="Nominal (Rupiah)" :value="$loan?->amount ?? ''" :error="$errors->first('amount')" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-datetime-quick id="loan_date" name="loan_date" label="Tanggal transaksi" type="date"
                :value="$loan?->loan_date?->toDateString() ?? $today" help="Tanggal saat pinjaman diberikan atau diterima."
                :shortcuts="['Hari ini']" required :error="$errors->first('loan_date')" max="{{ $today }}" />
            <x-datetime-quick id="loan_due_on" name="due_on" label="Jatuh tempo" type="date"
                :value="$loan?->due_on?->toDateString()" help="Tanggal yang disepakati untuk pelunasan; boleh dikosongkan."
                :shortcuts="['Hari ini', 'Besok']" optional :error="$errors->first('due_on')" />
        </div>
        <x-select id="loan_wallet_id" name="wallet_id" label="Dompet" help="Pilih jika transaksi awal mengubah saldo; boleh dikosongkan."
            optional :error="$errors->first('wallet_id')">
            <option value="">Tidak mengubah saldo</option>
            @foreach ($wallets as $wallet)
                <option value="{{ $wallet->id }}" @selected((string) old('wallet_id', $loan?->wallet_id) === (string) $wallet->id)>{{ $wallet->name }}</option>
            @endforeach
        </x-select>
    @else
        <x-field id="loan_counterparty" label="Nama pihak" help="Nama orang atau pihak yang terkait dengan utang/piutang ini."
            :error="$errors->first('counterparty')" required>
            <input id="loan_counterparty" name="counterparty" value="{{ old('counterparty', $loan->counterparty) }}" maxlength="80"
                required autocapitalize="words" data-validate-on-blur class="form-control">
        </x-field>
        <div class="rounded-lg bg-canvas p-3 text-sm text-muted">
            <p><strong>Arah:</strong> {{ $loan->direction->label() }}</p>
            <p><strong>Nominal:</strong> <span data-private>{{ rupiah($loan->amount) }}</span></p>
            <p><strong>Tanggal transaksi:</strong> {{ $loan->loan_date->translatedFormat('j M Y') }}</p>
            <p><strong>Dompet:</strong> {{ $loan->wallet?->name ?? 'Tidak mengubah saldo' }}</p>
            <p class="mt-1">Rincian transaksi awal dikunci karena pembayaran sudah tercatat.</p>
        </div>
        <x-datetime-quick id="loan_due_on" name="due_on" label="Jatuh tempo" type="date"
            :value="$loan->due_on?->toDateString()" help="Tanggal yang disepakati untuk pelunasan; boleh dikosongkan."
            :shortcuts="['Hari ini', 'Besok']" optional :error="$errors->first('due_on')" min="{{ $loan->loan_date->toDateString() }}" />
    @endif
    <x-textarea id="loan_note" name="note" label="Catatan" help="Keterangan tambahan yang perlu diingat."
        optional :error="$errors->first('note')" :value="$loan?->note" rows="2" maxlength="255" />
</x-form-section>
