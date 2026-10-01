@extends('layouts.app-public')

@section('title', 'Lien d’activation - KLASSCI')

@section('styles')
<style>
.mwa{min-height:72vh;display:grid;place-items:center;padding:24px}.mwa-card{width:min(540px,100%);background:#fff;border:1px solid #e6ebf3;border-radius:20px;box-shadow:0 18px 55px rgba(15,23,42,.10);overflow:hidden}.mwa-head{background:linear-gradient(135deg,#0a3d8f,#0453cb);color:#fff;padding:28px}.mwa-head h1{font-size:1.45rem;margin:0 0 7px}.mwa-head p{margin:0;opacity:.88}.mwa-body{padding:26px;color:#334155}.mwa-motif{background:#fffbeb;border-left:4px solid #f59e0b;border-radius:10px;padding:12px 14px;color:#92400e;margin-bottom:16px}.mwa-btn{display:inline-block;width:100%;text-align:center;border-radius:11px;padding:12px 16px;background:#0453cb;color:#fff;font-weight:800;text-decoration:none}.mwa-note{font-size:.85rem;color:#64748b;margin-top:14px}
</style>
@endsection

@section('content')
<div class="mwa">
    <div class="mwa-card">
        <div class="mwa-head">
            <h1>Ce lien ne peut plus servir</h1>
            <p>Votre dossier n’est pas perdu.</p>
        </div>
        <div class="mwa-body">
            <div class="mwa-motif">{{ $motif }}</div>
            <p>Si votre espace est déjà activé, connectez-vous avec votre mot de passe. Sinon, utilisez le lien du dernier message reçu, ou demandez à l’établissement de vous en renvoyer un.</p>
            <a class="mwa-btn" href="{{ route('login') }}">Se connecter</a>
            <div class="mwa-note">Un lien d’activation est personnel, valable 48 heures, utilisable une seule fois, et remplacé par chaque nouvel envoi.</div>
        </div>
    </div>
</div>
@endsection
