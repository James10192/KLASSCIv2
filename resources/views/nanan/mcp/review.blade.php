<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Vérification Nanan Connect — KLASSCI</title>
<style>
body{margin:0;padding:2rem 1rem;background:#f5f8fd;color:#182b46;font:16px/1.6 system-ui,sans-serif}
main{max-width:600px;margin:2rem auto;padding:2rem;border-radius:18px;background:white;box-shadow:0 12px 32px #002a5f18}
small{color:#45556d}.brand{color:#0453cb;font-weight:800;letter-spacing:.12em}
label{display:block;margin:1rem 0 .3rem;font-weight:650}
input[type=text],input[type=number]{box-sizing:border-box;width:100%;padding:.85rem;border:1px solid #b9c8db;border-radius:8px}
input:focus{outline:2px solid #0453cb}
button{border:0;border-radius:8px;padding:.8rem 1.2rem;color:white;background:#0453cb;font-weight:700}
.notice{background:#f3f6fd;padding:1rem;border-radius:8px}
.check{display:flex;gap:.6rem;align-items:flex-start}
.check input{flex-shrink:0;margin-top:.35rem}
.error{color:#812428}.success{color:#17623b}
</style>
</head>
<body><main>
<div class="brand">KLASSCI · NANAN CONNECT</div>
<h1>Valider une connexion externe</h1>
<div class="notice">
<strong>Administrateur :</strong> vérifiez la personne et le compte OAuth par un canal indépendant
avant de valider. Un code ChatGPT transmis seul <strong>ne prouve pas</strong> que son détenteur
est bien l'employé. Vous ne pouvez pas approuver votre propre connexion.
</div>
@if(session('approval_error'))<p class="error" role="alert">{{ session('approval_error') }}</p>@endif
@if(session('approval_success'))<p class="success" role="status">{{ session('approval_success') }}</p>@endif
@if($errors->any())<p class="error" role="alert">Vérifiez les champs et votre attestation.</p>@endif
<form method="post" action="{{ route('nanan.mcp.connections.approve') }}" autocomplete="off">
@csrf
<label for="target">Identifiant de l'employé KLASSCI vérifié</label>
<input id="target" type="number" name="user_id" min="1" required>
<small>Saisir l'identifiant exact du compte local, pas un e-mail ou un nom ressemblant.</small>
<label for="code">Code de liaison communiqué par l'employé</label>
<input id="code" type="text" name="code" minlength="43" maxlength="43"
       pattern="[A-Za-z0-9_-]{43}" spellcheck="false" autocomplete="off" required>
<label class="check"><input type="checkbox" name="identity_verified" value="1" required>
<span>Je confirme avoir vérifié directement l'identité du membre du personnel,
la correspondance de son compte OAuth et son droit d'accès à cet établissement.
L'employé devra confirmer depuis sa propre session KLASSCI.</span></label>
<button type="submit">Approuver cette connexion</button>
</form>
<p><small>Le code expire après 10 minutes et ne pourra être approuvé qu'une fois.
Si vous n'avez pas pu vérifier les deux identités, refusez la demande.</small></p>
</main></body></html>
