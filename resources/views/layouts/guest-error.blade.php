<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'KLASSCI — Information')</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; background: #f6f4f0; color: #1e293b; }
        main { width: 100%; }
        a:focus-visible, button:focus-visible { outline: 3px solid #0453cb; outline-offset: 3px; }
        .pe-container,.ar-container { margin: 1rem auto!important; }
    </style>
</head>
<body>
    <main id="main-content">@yield('content')</main>
</body>
</html>
