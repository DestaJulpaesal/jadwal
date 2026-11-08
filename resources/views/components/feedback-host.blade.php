<div data-toast-region class="pointer-events-none fixed inset-x-4 bottom-[calc(5rem+env(safe-area-inset-bottom))] z-50 flex flex-col gap-3 sm:bottom-auto sm:left-auto sm:right-4 sm:top-20 sm:w-full sm:max-w-sm">
    @if (session()->has('feedback'))
        @php($feedback = session('feedback'))
        <x-toast :type="$feedback['type']" :action="$feedback['action'] ?? null">{{ $feedback['message'] }}</x-toast>
    @endif
</div>
