@if (session()->has('feedback'))
    @php($feedback = session('feedback'))
    <x-toast :type="$feedback['type']" :action="$feedback['action'] ?? null">{{ $feedback['message'] }}</x-toast>
@endif
