@extends('layouts.app')

@section('title', 'Double authentification - KLASSCI')

@push('styles')
<style>
    .da-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem; }
    .da-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .da-hero-left { display: flex; align-items: center; gap: 1rem; }
    .da-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; color: #fff; }
    .da-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .da-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
    .da-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.75rem; box-shadow: 0 1px 3px rgba(15,23,42,.04); margin-bottom: 1.25rem; }
    .da-etape { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
    .da-num { width: 30px; height: 30px; border-radius: 50%; background: #0453cb; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; flex-shrink: 0; }
    .da-etape-corps { flex: 1; min-width: 0; }
    .da-etape h3 { font-size: .98rem; font-weight: 700; color: #1e293b; margin: .2rem 0 .4rem; }
    .da-etape p { font-size: .88rem; color: #64748b; margin: 0 0 .6rem; line-height: 1.55; }
    .da-qr { display: inline-block; padding: .85rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; }
    .da-secret { font-family: 'Courier New', monospace; font-size: 1rem; letter-spacing: .12em; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: .65rem .9rem; display: inline-block; word-break: break-all; }
    .da-code-input { font-family: 'Courier New', monospace; font-size: 1.3rem; letter-spacing: .3em; text-align: center; max-width: 220px; padding: .6rem; border: 1px solid #cbd5e1; border-radius: 10px; }
    .da-etat { display: inline-flex; align-items: center; gap: .5rem; padding: .4rem .85rem; border-radius: 8px; font-size: .84rem; font-weight: 600; }
    .da-etat--on { background: rgba(16,185,129,.1); color: #047857; border: 1px solid rgba(16,185,129,.25); }
    .da-etat--off { background: rgba(100,116,139,.1); color: #475569; border: 1px solid rgba(100,116,139,.2); }
    .da-note { background: #f8fafc; border-left: 3px solid #0453cb; padding: .85rem 1rem; border-radius: 0 8px 8px 0; font-size: .86rem; color: #475569; line-height: 1.6; }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="da-hero">
        <div class="da-hero-top">
            <div class="da-hero-left">
                <div class="da-hero-icon"><i class="fas fa-shield-halved"></i></div>
                <div>
                    <h1>Double authentification</h1>
                    <p>Un code depuis votre téléphone, en plus de votre mot de passe.</p>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="da-card">
        @if ($confirmee)
            <span class="da-etat da-etat--on"><i class="fas fa-check-circle"></i> Activée sur votre compte</span>

            <p style="margin-top:1rem;font-size:.9rem;color:#475569;line-height:1.6;">
                À votre prochaine connexion, votre mot de passe ne suffira plus : il vous sera
                demandé le code affiché par votre application.
                Il vous reste <strong>{{ $codesRestants }}</strong> code(s) de secours.
            </p>

            <hr style="margin:1.5rem 0;border-color:#e2e8f0;">

            <h3 style="font-size:.95rem;font-weight:700;color:#1e293b;">Désactiver</h3>
            <p style="font-size:.87rem;color:#64748b;line-height:1.55;">
                Votre mot de passe vous est redemandé : sans cela, une session laissée ouverte
                sur un poste partagé suffirait à retirer la protection.
            </p>

            <form method="POST" action="{{ route('securite.double-auth.desactiver') }}" style="display:flex;gap:.6rem;align-items:flex-start;flex-wrap:wrap;">
                @csrf
                <div>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                           placeholder="Votre mot de passe" style="max-width:260px;" required>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-outline-danger">Désactiver</button>
            </form>
        @else
            <span class="da-etat da-etat--off"><i class="fas fa-circle-minus"></i> Pas encore activée</span>

            @if ($exigeeParLEcole)
                <div class="da-note" style="margin-top:1rem;">
                    Votre établissement demande la double authentification pour votre fonction.
                    Tant que vous ne l'avez pas mise en place, vous vous connectez normalement —
                    rien n'est bloqué.
                </div>
            @endif

            <div style="margin-top:1.75rem;">
                <div class="da-etape">
                    <div class="da-num">1</div>
                    <div class="da-etape-corps">
                        <h3>Installez une application d'authentification</h3>
                        <p>
                            Google Authenticator, Microsoft Authenticator ou FreeOTP, depuis le magasin
                            d'applications de votre téléphone. Elles sont gratuites et fonctionnent sans
                            connexion internet.
                        </p>
                    </div>
                </div>

                <div class="da-etape">
                    <div class="da-num">2</div>
                    <div class="da-etape-corps">
                        <h3>Scannez ce code</h3>
                        <p>Dans l'application, choisissez « Ajouter un compte », puis scannez :</p>
                        <div class="da-qr">{!! $qrCode !!}</div>
                        <p style="margin-top:.9rem;">
                            Si vous ne pouvez pas scanner, saisissez cette clé à la main :
                        </p>
                        <div class="da-secret">{{ $secret }}</div>
                    </div>
                </div>

                <div class="da-etape">
                    <div class="da-num">3</div>
                    <div class="da-etape-corps">
                        <h3>Recopiez le code affiché</h3>
                        <p>
                            Six chiffres, qui changent toutes les trente secondes. C'est ce qui confirme
                            que votre téléphone et ce compte sont bien liés.
                        </p>
                        <form method="POST" action="{{ route('securite.double-auth.confirmer') }}"
                              style="display:flex;gap:.6rem;align-items:flex-start;flex-wrap:wrap;">
                            @csrf
                            <div>
                                <input type="text" name="code" class="da-code-input @error('code') is-invalid @enderror"
                                       inputmode="numeric" autocomplete="one-time-code" maxlength="7"
                                       placeholder="000000" required autofocus>
                                @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn-acasi primary">Activer</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
