<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Loket</title>
    <style>
        :root { --bg: #f5f6f8; --fg: #1b1f24; --card: #ffffff; --accent: #2563eb; --muted: #6b7280; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0f1216; --fg: #e6e8eb; --card: #181c22; --accent: #60a5fa; --muted: #9aa3ae; }
        }
        body { margin: 0; background: var(--bg); color: var(--fg); font-family: system-ui, sans-serif; }
        main { max-width: 480px; margin: 0 auto; padding: 16px; }
        .card { background: var(--card); border-radius: 16px; padding: 24px; margin-top: 16px; text-align: center; }
        .number { font-size: 3rem; font-weight: 700; margin: 12px 0; }
        .muted { color: var(--muted); }
        .row { display: flex; gap: 12px; margin-top: 12px; }
        button { flex: 1; padding: 14px; font-size: 1rem; border: 0; border-radius: 12px; background: var(--accent); color: #fff; cursor: pointer; }
        button.secondary { background: var(--muted); }
        button:disabled { opacity: .5; }
        .error { color: #dc2626; min-height: 1.2em; }
    </style>
</head>
<body>
<main>
    @if (! $counter)
        <div class="card">
            <h2>Belum ada loket</h2>
            <p class="muted">Anda belum ditugaskan ke loket hari ini. Hubungi admin cabang.</p>
        </div>
    @else
        <div class="card">
            <p class="muted">{{ $branch->name }} — {{ $counter->name }}</p>
            <div class="number" id="current">—</div>
            <p class="muted" id="service"></p>
            <button id="call" type="button">Panggil Berikutnya</button>
            <div class="row">
                <button id="served" class="secondary" type="button" disabled>Selesai</button>
                <button id="skipped" class="secondary" type="button" disabled>Lewati</button>
            </div>
            <p class="error" id="error" role="alert"></p>
        </div>
    @endif
</main>

@if ($counter)
<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const counterId = @json($counter->id);
    const callUrl = @json(route('queues.call-next'));
    const queuesUrl = @json(url('/queues'));
    const statusUrl = (id) => `${queuesUrl}/${id}/status`;

    let currentId = null;
    const el = (id) => document.getElementById(id);

    async function request(url, method, body) {
        const res = await fetch(url, {
            method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: body ? JSON.stringify(body) : undefined,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Terjadi kesalahan.');
        return data;
    }

    function setCurrent(queue) {
        currentId = queue ? queue.id : null;
        el('current').textContent = queue ? queue.display_number : '—';
        el('service').textContent = queue ? queue.service_name ?? '' : '';
        el('served').disabled = !currentId;
        el('skipped').disabled = !currentId;
    }

    el('call').addEventListener('click', async () => {
        el('error').textContent = '';
        el('call').disabled = true;
        try {
            setCurrent(await request(callUrl, 'POST', { counter_id: counterId }));
        } catch (e) {
            el('error').textContent = e.message;
        } finally {
            el('call').disabled = false;
        }
    });

    for (const status of ['served', 'skipped']) {
        el(status).addEventListener('click', async () => {
            el('error').textContent = '';
            try {
                await request(statusUrl(currentId), 'PATCH', { status });
                setCurrent(null);
            } catch (e) {
                el('error').textContent = e.message;
            }
        });
    }
</script>
@endif
</body>
</html>
