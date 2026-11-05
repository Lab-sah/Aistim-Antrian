<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Petugas</title>
    <style>
        :root { --bg: #f5f6f8; --fg: #1b1f24; --card: #ffffff; --accent: #2563eb; --muted: #6b7280; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0f1216; --fg: #e6e8eb; --card: #181c22; --accent: #60a5fa; --muted: #9aa3ae; }
        }
        body { margin: 0; background: var(--bg); color: var(--fg); font-family: system-ui, sans-serif; }
        main { max-width: 360px; margin: 0 auto; padding: 16px; }
        .card { background: var(--card); border-radius: 16px; padding: 24px; margin-top: 48px; }
        label { display: block; margin-top: 12px; font-size: .9rem; color: var(--muted); }
        input[type=email], input[type=password] { width: 100%; box-sizing: border-box; padding: 12px; margin-top: 4px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 1rem; background: var(--bg); color: var(--fg); }
        button { width: 100%; margin-top: 20px; padding: 14px; font-size: 1rem; border: 0; border-radius: 12px; background: var(--accent); color: #fff; cursor: pointer; }
        .error { color: #dc2626; margin-top: 12px; }
    </style>
</head>
<body>
<main>
    <form class="card" method="post" action="{{ route('login') }}">
        @csrf
        <h1 style="font-size:1.25rem;margin:0 0 8px;">Login Petugas</h1>
        @error('email')<p class="error" role="alert">{{ $message }}</p>@enderror
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
        <button type="submit">Masuk</button>
    </form>
</main>
</body>
</html>
