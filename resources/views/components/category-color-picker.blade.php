@props(['id', 'value' => '#2563EB', 'label' => 'Warna kategori'])

@php
    $colors = [
        '#1D4ED8' => 'Biru',
        '#15803D' => 'Hijau',
        '#6D28D9' => 'Ungu',
        '#C2410C' => 'Jingga',
        '#B91C1C' => 'Merah',
        '#0F766E' => 'Teal',
        '#475569' => 'Abu-abu',
    ];
    $currentColor = strtoupper($value);
    $hasNamedColor = array_key_exists($currentColor, $colors);
@endphp

<div data-category-color-picker>
    <x-field :id="$id" :label="$label" help="Pilih warna bernama atau gunakan pemilih warna; contoh teks menunjukkan kontrasnya." required>
        <div class="mt-1 grid gap-2 sm:grid-cols-2">
            <input id="{{ $id }}" name="color" type="color" value="{{ $value }}" required
                aria-label="{{ $label }} · pemilih warna bebas" aria-describedby="{{ $id }}-help {{ $id }}-preview"
                class="h-12 w-full rounded-lg border border-line bg-white p-1">
            <select data-color-preset aria-label="{{ $label }} · warna bernama" class="form-control">
                @unless ($hasNamedColor)
                    <option value="custom" selected>Warna khusus ({{ $currentColor }})</option>
                @endunless
                @foreach ($colors as $hex => $name)
                    <option value="{{ $hex }}" @selected($currentColor === $hex)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <p id="{{ $id }}-preview" data-color-preview class="mt-2 rounded-lg p-2 text-sm font-medium" aria-live="polite">
            Contoh teks kategori pada warna {{ $currentColor }}.
        </p>
    </x-field>
</div>
