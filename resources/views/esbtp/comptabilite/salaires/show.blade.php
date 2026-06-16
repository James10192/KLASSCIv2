@extends('layouts.app')

@section('title', 'Bulletin de paie — KLASSCI')

@push('styles')
<style>
    .pys-wrap { max-width: 960px; margin: 0 auto; }
    .pys-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 1.9rem 2.25rem 1.6rem; color: #fff; margin-bottom: 1.25rem; box-shadow: 0 8px 30px rgba(4,83,203,.18); }
    .pys-hero-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .pys-hero-left { display: flex; align-items: center; gap: 1rem; }
    .pys-hero-avatar { width: 56px; height: 56px; border-radius: 15px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; color: #fff; flex-shrink: 0; }
    .pys-hero h1 { font-size: 1.35rem; font-weight: 700; color: #fff; margin: 0; }
    .pys-hero-meta { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .35rem; }
    .pys-pill { display: inline-flex; align-items: center; gap: .35rem; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); border-radius: 7px; padding: .2rem .55rem; font-size: .73rem; font-weight: 600; color: rgba(255,255,255,.9); }
    .pys-status { font-size: .78rem; font-weight: 800; padding: .3rem .7rem; border-radius: 8px; }
    .pys-back { display: inline-flex; align-items: center; gap: .4rem; text-decoration: none; background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.2); border-radius: 10px; padding: .5rem .9rem; font-size: .82rem; font-weight: 600; }
    .pys-net-banner { display: flex; align-items: baseline; gap: .6rem; margin-top: 1.3rem; }
    .pys-net-val { font-size: 2.1rem; font-weight: 800; color: #fff; }
    .pys-net-cur { font-size: 1rem; font-weight: 600; color: rgba(255,255,255,.7); }
    .pys-net-lbl { font-size: .8rem; color: rgba(255,255,255,.65); }

    .pys-grid { display: grid; grid-template-columns: 1.4fr .9fr; gap: 1.25rem; align-items: start; }
    .pys-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04); margin-bottom: 1.25rem; }
    .pys-card-head { display: flex; align-items: center; gap: .6rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
    .pys-card-ico { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .8rem; }
    .pys-card-title { font-size: .92rem; font-weight: 700; color: #1e293b; }
    .pys-card-body { padding: .6rem 1.25rem 1rem; }

    .pys-line { display: flex; align-items: center; justify-content: space-between; padding: .5rem 0; border-bottom: 1px solid #f6f8fb; }
    .pys-line:last-child { border-bottom: none; }
    .pys-line-lbl { font-size: .85rem; color: #334155; }
    .pys-line-detail { font-size: .68rem; color: #94a3b8; }
    .pys-line-amt { font-size: .88rem; font-weight: 700; color: #0f172a; }
    .pys-line-amt--neg { color: #b91c1c; }
    .pys-subtotal { display: flex; justify-content: space-between; padding: .65rem 0 .2rem; font-weight: 800; color: #0453cb; }

    .pys-meta-row { display: flex; align-items: center; gap: .6rem; padding: .55rem 0; border-bottom: 1px solid #f6f8fb; font-size: .82rem; }
    .pys-meta-row:last-child { border-bottom: none; }
    .pys-meta-ico { width: 30px; height: 30px; border-radius: 8px; background: rgba(4,83,203,.07); color: #0453cb; display: flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; }
    .pys-meta-lbl { color: #64748b; }
    .pys-meta-val { margin-left: auto; font-weight: 700; color: #1e293b; }

    .pys-actions { display: flex; flex-direction: column; gap: .6rem; }
    .pys-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; border: none; cursor: pointer; border-radius: 10px; padding: .7rem 1rem; font-size: .85rem; font-weight: 700; width: 100%; }
    .pys-btn--validate { background: #0453cb; color: #fff; }
    .pys-btn--pay { background: #10b981; color: #fff; }
    .pys-btn--ghost { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
    .pys-locked { font-size: .8rem; color: #065f46; background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.2); border-radius: 10px; padding: .7rem .9rem; text-align: center; }

    .pys-modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); display: flex; align-items: center; justify-content: center; padding: 1rem; z-index: 1080; }
    .pys-modal { background: #fff; border-radius: 16px; width: 100%; max-width: 460px; box-shadow: 0 24px 60px rgba(15,23,42,.25); }
    .pys-modal-head { padding: 1.1rem 1.4rem; border-bottom: 1px solid #f1f5f9; font-weight: 700; color: #1e293b; }
    .pys-modal-body { padding: 1.25rem 1.4rem; display: flex; flex-direction: column; gap: .8rem; }
    .pys-modal-foot { padding: 1rem 1.4rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: .6rem; }
    .pys-input { border: 1px solid #e2e8f0; border-radius: 9px; padding: .55rem .7rem; font-size: .85rem; width: 100%; }
    .pys-field-lbl { font-size: .68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; display: block; margin-bottom: .25rem; }
    /* Force le picker premium à remplir la cellule du modal (sinon inline-flex rétrécit). */
    .pys-au-full { display: flex !important; width: 100%; }
    .pys-au-full .au-select-trigger { width: 100%; }
    [x-cloak] { display: none !important; }

    @media (max-width: 992px) { .pys-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .pys-hero { padding: 1.4rem 1.25rem; } }
</style>
@endpush

@section('content')
@php
    $stCfg = [
        'brouillon' => ['#475569', 'rgba(100,116,139,.15)'],
        'valide'    => ['#fff', 'rgba(255,255,255,.2)'],
        'paye'      => ['#fff', 'rgba(16,185,129,.3)'],
        'annule'    => ['#fff', 'rgba(220,38,38,.3)'],
    ];
    $sc = $stCfg[$salaire->workflow_status] ?? $stCfg['brouillon'];
    $fmt = fn($v) => number_format($v, 0, ',', ' ');
@endphp
<div class="pys-wrap" x-data="{ showPay: false }">

    {{-- Hero --}}
    <div class="pys-hero">
        <div class="pys-hero-top">
            <div class="pys-hero-left">
                <div class="pys-hero-avatar">{{ \Illuminate\Support\Str::substr($salaire->teacher->user->name ?? 'E', 0, 1) }}</div>
                <div>
                    <h1>{{ $salaire->teacher->user->name ?? 'Enseignant' }}</h1>
                    <div class="pys-hero-meta">
                        <span class="pys-pill"><i class="fas fa-calendar"></i> {{ $moisLabel }} {{ $salaire->annee }}</span>
                        <span class="pys-pill"><i class="fas fa-hourglass-half"></i> {{ rtrim(rtrim(number_format($salaire->heures_total, 1, ',', ' '), '0'), ',') }}h réalisées</span>
                        <span class="pys-status" style="color:{{ $sc[0] }};background:{{ $sc[1] }};">{{ $salaire->statutLabel() }}</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('esbtp.comptabilite.salaires.index') }}" class="pys-back"><i class="fas fa-arrow-left"></i> Liste</a>
        </div>
        <div class="pys-net-banner">
            <span class="pys-net-val">{{ $fmt($salaire->net_a_payer) }}</span>
            <span class="pys-net-cur">FCFA</span>
            <span class="pys-net-lbl">· net à payer</span>
        </div>
    </div>

    @if(session('error'))
        <div class="pys-locked" style="color:#b91c1c;background:rgba(220,38,38,.08);border-color:rgba(220,38,38,.2);margin-bottom:1rem;">{{ session('error') }}</div>
    @endif

    <div class="pys-grid">
        {{-- Détail --}}
        <div>
            <div class="pys-card">
                <div class="pys-card-head">
                    <div class="pys-card-ico"><i class="fas fa-plus"></i></div>
                    <div class="pys-card-title">Gains</div>
                </div>
                <div class="pys-card-body">
                    @foreach($gains as $g)
                        <div class="pys-line">
                            <span class="pys-line-lbl">
                                {{ $g->libelle }}
                                @if($g->heures)<span class="pys-line-detail">{{ rtrim(rtrim(number_format($g->heures, 1, ',', ' '), '0'), ',') }}h × {{ $fmt($g->taux) }} FCFA</span>@endif
                            </span>
                            <span class="pys-line-amt">{{ $fmt($g->montant) }} FCFA</span>
                        </div>
                    @endforeach
                    <div class="pys-subtotal">
                        <span>Brut</span>
                        <span>{{ $fmt($salaire->salaire_base + $salaire->primes) }} FCFA</span>
                    </div>
                </div>
            </div>

            <div class="pys-card">
                <div class="pys-card-head">
                    <div class="pys-card-ico" style="background:linear-gradient(135deg,#dc2626,#b91c1c);"><i class="fas fa-minus"></i></div>
                    <div class="pys-card-title">Retenues</div>
                </div>
                <div class="pys-card-body">
                    @forelse($retenues as $r)
                        <div class="pys-line">
                            <span class="pys-line-lbl">{{ $r->libelle }}</span>
                            <span class="pys-line-amt pys-line-amt--neg">− {{ $fmt($r->montant) }} FCFA</span>
                        </div>
                    @empty
                        <div class="pys-line"><span class="pys-line-lbl" style="color:#94a3b8;">Aucune retenue</span></div>
                    @endforelse
                    <div class="pys-subtotal" style="color:#b91c1c;">
                        <span>Total retenues</span>
                        <span>− {{ $fmt($salaire->retenues) }} FCFA</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Workflow + actions --}}
        <div>
            <div class="pys-card">
                <div class="pys-card-head">
                    <div class="pys-card-ico"><i class="fas fa-route"></i></div>
                    <div class="pys-card-title">Suivi</div>
                </div>
                <div class="pys-card-body">
                    <div class="pys-meta-row">
                        <div class="pys-meta-ico"><i class="fas fa-pen"></i></div>
                        <span class="pys-meta-lbl">Préparé par</span>
                        <span class="pys-meta-val">{{ $salaire->preparePar->name ?? '—' }}</span>
                    </div>
                    <div class="pys-meta-row">
                        <div class="pys-meta-ico"><i class="fas fa-check-double"></i></div>
                        <span class="pys-meta-lbl">Validé par</span>
                        <span class="pys-meta-val">{{ $salaire->validePar->name ?? '—' }}</span>
                    </div>
                    <div class="pys-meta-row">
                        <div class="pys-meta-ico"><i class="fas fa-hand-holding-dollar"></i></div>
                        <span class="pys-meta-lbl">Payé par</span>
                        <span class="pys-meta-val">{{ $salaire->payePar->name ?? '—' }}</span>
                    </div>
                    @if($salaire->isPaye())
                        <div class="pys-meta-row">
                            <div class="pys-meta-ico"><i class="fas fa-money-bill-transfer"></i></div>
                            <span class="pys-meta-lbl">Mode</span>
                            <span class="pys-meta-val">{{ $modeLabel ?? '—' }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="pys-card">
                <div class="pys-card-body" style="padding-top:1rem;">
                    <div class="pys-actions">
                        @if($salaire->isBrouillon() && $canValidate)
                            {{-- EXCEPTION ajax-no-reload-premium : validation = transition workflow majeure --}}
                            <form method="POST" action="{{ route('esbtp.comptabilite.salaires.validate', $salaire->id) }}">
                                @csrf
                                <button type="submit" class="pys-btn pys-btn--validate"><i class="fas fa-check-double"></i> Valider le bulletin</button>
                            </form>
                        @endif
                        @if($salaire->isValide() && $canPay)
                            <button type="button" class="pys-btn pys-btn--pay" @click="showPay = true"><i class="fas fa-hand-holding-dollar"></i> Marquer comme payé</button>
                        @endif
                        @if($salaire->isPaye())
                            <div class="pys-locked"><i class="fas fa-lock"></i> Bulletin payé le {{ optional($salaire->date_paiement)->format('d/m/Y') }} — verrouillé.</div>
                        @endif
                        @if($salaire->isBrouillon() && !$canValidate)
                            <div class="pys-locked" style="color:#475569;background:rgba(100,116,139,.08);border-color:rgba(100,116,139,.2);">En attente de validation.</div>
                        @endif

                        {{-- Bulletin de paie individuel (aperçu + téléchargement) --}}
                        <div style="border-top:1px solid #f1f5f9;margin-top:.4rem;padding-top:.8rem;">
                            <span class="pys-field-lbl" style="margin-bottom:.5rem;">Bulletin de paie</span>
                            <x-pdf-actions
                                :preview-url="route('esbtp.comptabilite.salaires.payslip.preview', $salaire->id)"
                                :download-url="route('esbtp.comptabilite.salaires.payslip', $salaire->id)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal paiement --}}
    @if($salaire->isValide() && $canPay)
    <div class="pys-modal-overlay" x-show="showPay" x-cloak @keydown.escape.window="showPay=false" style="display:none;">
        <div class="pys-modal" @click.outside="showPay=false">
            <div class="pys-modal-head">Enregistrer le règlement</div>
            {{-- EXCEPTION ajax-no-reload-premium : paiement = transition workflow majeure --}}
            <form method="POST" action="{{ route('esbtp.comptabilite.salaires.pay', $salaire->id) }}">
                @csrf
                <div class="pys-modal-body">
                    <div>
                        <label class="pys-field-lbl">Mode de paiement</label>
                        <x-au-select name="mode_paiement" class="pys-au-full" :options="$modesPaiement"
                            placeholder="Choisir un mode…" icon="fa-money-bill-transfer" :searchable="true" />
                    </div>
                    <div>
                        <label class="pys-field-lbl">Référence (optionnel)</label>
                        <input type="text" name="reference_paiement" class="pys-input" placeholder="N° de transaction">
                    </div>
                    <div>
                        <label class="pys-field-lbl">Date de paiement</label>
                        <input type="date" name="date_paiement" class="pys-input" value="{{ now()->toDateString() }}">
                    </div>
                </div>
                <div class="pys-modal-foot">
                    <button type="button" class="pys-btn pys-btn--ghost" style="width:auto;" @click="showPay=false">Annuler</button>
                    <button type="submit" class="pys-btn pys-btn--pay" style="width:auto;"><i class="fas fa-check"></i> Confirmer le paiement</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
