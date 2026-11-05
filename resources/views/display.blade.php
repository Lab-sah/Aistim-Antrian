<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Display — {{ $branch->name }}</title>
    <style>
        :root { --bg: #f5f6f8; --fg: #1b1f24; --card: #ffffff; --accent: #2563eb; --muted: #6b7280; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0f1216; --fg: #e6e8eb; --card: #181c22; --accent: #60a5fa; --muted: #9aa3ae; }
        }
        body { margin: 0; background: var(--bg); color: var(--fg); font-family: system-ui, sans-serif; }
        main { max-width: 900px; margin: 0 auto; padding: 16px; }
        h1 { font-size: 1.5rem; margin: 8px 0 16px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
        .card { background: var(--card); border-radius: 16px; padding: 20px; text-align: center; }
        .number { font-size: 2.5rem; font-weight: 700; color: var(--accent); }
        .muted { color: var(--muted); font-size: .9rem; }
        .empty { color: var(--muted); padding: 40px 0; text-align: center; }
    </style>
</head>
<body>
<main>
    <h1>{{ $branch->name }}</h1>
    <div class="grid" id="list"></div>
    <p class="empty" id="empty">Belum ada nomor yang dipanggil.</p>
</main>

<script>
    const dataUrl = @json(route('display.data', $branch));
    const list = document.getElementById('list');
    const empty = document.getElementById('empty');

    function render(items) {
        list.replaceChildren(...items.map((item) => {
            const card = document.createElement('div');
            card.className = 'card';
            const num = document.createElement('div');
            num.className = 'number';
            num.textContent = item.display_number;
            const loket = document.createElement('div');
            loket.className = 'muted';
            loket.textContent = item.counter ?? '';
            card.append(num, loket);
            return card;
        }));
        empty.hidden = items.length > 0;
    }

    async function poll() {
        try {
            const res = await fetch(dataUrl, { headers: { 'Accept': 'application/json' } });
            if (res.ok) render(await res.json());
        } catch (e) {
            // Abaikan dan coba lagi di polling berikutnya.
        }
    }

    poll();
    setInterval(poll, 3000);
</script>
</body>
</html>
