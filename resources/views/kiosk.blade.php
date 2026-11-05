<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ambil Nomor — {{ $service->name }}</title>
    <style>
        :root { --bg: #f5f6f8; --fg: #1b1f24; --card: #ffffff; --accent: #2563eb; --muted: #6b7280; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0f1216; --fg: #e6e8eb; --card: #181c22; --accent: #60a5fa; --muted: #9aa3ae; }
        }
        body { margin: 0; background: var(--bg); color: var(--fg); font-family: system-ui, sans-serif; }
        main { max-width: 420px; margin: 0 auto; padding: 16px; text-align: center; }
        .card { background: var(--card); border-radius: 16px; padding: 24px; margin-top: 24px; }
        h1 { font-size: 1.25rem; margin: 0 0 4px; }
        p.muted { color: var(--muted); margin: 0; }
        button { width: 100%; padding: 16px; font-size: 1.1rem; border: 0; border-radius: 12px; background: var(--accent); color: #fff; cursor: pointer; margin-top: 16px; }
        button:disabled { opacity: .6; }
        .number { font-size: 3.5rem; font-weight: 700; margin: 16px 0 4px; }
        .error { color: #dc2626; margin-top: 12px; min-height: 1.2em; }
    </style>
</head>
<body>
<main>
    <div class="card">
        <h1>{{ $service->branch->name }}</h1>
        <p class="muted">{{ $service->name }}</p>
        <button id="take" type="button">Ambil Nomor</button>
        <div id="result" hidden>
            <p class="muted">Nomor Anda</p>
            <div class="number" id="number"></div>
            <p class="muted">Mohon menunggu, nomor Anda akan dipanggil.</p>
        </div>
        <p class="error" id="error" role="alert"></p>
    </div>
</main>

<script>
    const takeUrl = @json(route('queues.store', $service));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const btn = document.getElementById('take');

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        document.getElementById('error').textContent = '';
        try {
            const res = await fetch(takeUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            if (!res.ok) throw new Error('Gagal mengambil nomor.');
            const data = await res.json();
            document.getElementById('number').textContent = data.display_number;
            document.getElementById('result').hidden = false;
            btn.hidden = true;
        } catch (e) {
            document.getElementById('error').textContent = e.message;
            btn.disabled = false;
        }
    });
</script>
</body>
</html>
