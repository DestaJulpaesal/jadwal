<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Ringkasan harian</title></head>
<body style="margin:0;background:#f7f8f5;color:#1d2925;font-family:Arial,sans-serif;line-height:1.6">
    <main style="max-width:640px;margin:0 auto;padding:24px">
        <section style="background:#fff;border:1px solid #dce4df;border-radius:12px;padding:24px">
            <p style="color:#226b50;font-weight:bold;margin:0">Jadwal Harian</p>
            <h1 style="font-size:24px;margin:8px 0">Halo, {{ $name }}</h1>
            <p style="color:#52615b;margin:0 0 24px">{{ $date }}. Berikut hal-hal yang mungkin perlu Anda lihat.</p>
            <h2 style="font-size:18px">Tugas hari ini</h2>
            @forelse ($tasks as $task)
                <p style="border-top:1px solid #dce4df;padding-top:10px">{{ $task['time'] ? $task['time'].' · ' : '' }}{{ $task['title'] }} — {{ $task['status'] }}</p>
            @empty
                <p style="color:#52615b">Belum ada tugas terjadwal hari ini.</p>
            @endforelse
            <h2 style="font-size:18px;margin-top:24px">Kebiasaan hari ini</h2>
            @forelse ($habits as $habit)
                <p style="border-top:1px solid #dce4df;padding-top:10px">{{ $habit['title'] }} — {{ $habit['status'] }}</p>
            @empty
                <p style="color:#52615b">Belum ada kebiasaan yang dijadwalkan hari ini.</p>
            @endforelse
            <h2 style="font-size:18px;margin-top:24px">Perlu perhatian</h2>
            @forelse ($attentionItems as $item)
                <p style="border-top:1px solid #dce4df;padding-top:10px">{{ $item['title'] }} — {{ $item['status'] }}</p>
            @empty
                <p style="color:#52615b">Tidak ada tagihan atau pembayaran yang perlu diperhatikan dalam tiga hari ini.</p>
            @endforelse
        </section>
        <p style="color:#52615b;font-size:13px;text-align:center;margin-top:16px">Anda dapat mengubah atau mematikan ringkasan ini dari Profil.</p>
    </main>
</body>
</html>
