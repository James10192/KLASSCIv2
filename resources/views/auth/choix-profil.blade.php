<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Choisir un profil — KLASSCI</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 22px; font-family: system-ui, -apple-system, sans-serif; background: #f4f7fd; color: #132b4e; }
        main { width: min(100%, 680px); background: #fff; padding: clamp(24px, 5vw, 44px); border-radius: 22px; box-shadow: 0 18px 70px rgba(5,50,105,.08); }
        .logo { color: #0453cb; font-weight: 800; font-size: 24px; }
        h1 { margin: 24px 0 8px; font-size: clamp(24px, 4vw, 34px); }
        p { color: #5c6b80; line-height: 1.6; }
        .profiles { display: grid; gap: 12px; margin-top: 28px; }
        .profile { width: 100%; display: flex; align-items: center; justify-content: space-between; text-align: left; padding: 20px 22px; background: #fff; border: 1px solid #dbe5f4; border-radius: 14px; color: #132b4e; font: inherit; cursor: pointer; }
        .profile:hover, .profile:focus-visible { border-color: #0453cb; background: #f6f9ff; outline-offset: 3px; }
        .name { font-weight: 700; font-size: 17px; }
        .selected { font-size: 12px; background: #e9f1ff; color: #0453cb; border-radius: 8px; padding: 6px 10px; }
        .logout { margin-top: 28px; }
        .logout button { background: none; border: 0; color: #495b77; text-decoration: underline; cursor: pointer; font: inherit; }
        .error { color: #b42318; }
    </style>
</head>
<body>
<main>
    <div class="logo">KLASSCI</div>
    <h1>Dans quel profil souhaitez-vous travailler ?</h1>
    <p>Bonjour {{ auth()->user()->name }}. Choisissez un espace pour cette session. Vos permissions ne changent pas.</p>
    @error('role') <p class="error" role="alert">{{ $message }}</p> @enderror
    <div class="profiles">
        @foreach($roles as $role)
            <form method="POST" action="{{ route('profil.activer') }}">
                @csrf
                <input type="hidden" name="role" value="{{ $role['name'] }}">
                <button class="profile" type="submit">
                    <span class="name">{{ $role['label'] }}</span>
                    @if($profilActif === $role['name'])
                        <span class="selected">Profil actuel</span>
                    @else
                        <span aria-hidden="true">→</span>
                    @endif
                </button>
            </form>
        @endforeach
    </div>
    <form method="POST" action="{{ route('logout') }}" class="logout">
        @csrf
        <button type="submit">Se déconnecter</button>
    </form>
</main>
</body>
</html>
