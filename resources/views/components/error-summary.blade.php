@props(['errors', 'title' => 'Periksa kembali isian berikut'])

@if (count($errors))
    <section class="mb-5 rounded-xl border border-red-300 bg-danger-soft p-4 text-danger" role="alert" tabindex="-1" data-error-summary>
        <h2 class="font-semibold">{{ $title }}</h2>
        <ul class="mt-2 list-inside list-disc space-y-1">
            @foreach ($errors as $field => $messages)
                @foreach ((array) $messages as $message)
                    <li><a href="#{{ $field }}" class="underline underline-offset-2">{{ $message }}</a></li>
                @endforeach
            @endforeach
        </ul>
    </section>
@endif
