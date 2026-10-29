@extends('layouts.app')

@section('title', 'Recouvrement quotidien')

@section('content')
@php
    // Shell mobile : le DOM de bureau reste dans .m-only-desktop, l'écran mobile
    // (maquette S['comptable:recouvrement']) vit à côté, sur le MÊME état Alpine.
    $reShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);

    // Contact prêt à l'emploi pour chaque ligne : numéro lisible (PhoneFormatter),
    // identifiant wa.me et E.164 pour tel:. Rien n'est recalculé côté navigateur.
    $reRows = collect($rows)->map(function (array $r) {
        $nom = trim((string) ($r['etudiant_nom'] ?? ''));
        $mots = preg_split('/\s+/u', $nom) ?: [];
        $mots = array_values(array_filter($mots, fn ($m) => $m !== ''));
        $initiales = '';
        if (count($mots) > 0) {
            $initiales = mb_substr($mots[0], 0, 1, 'UTF-8') . (count($mots) > 1 ? mb_substr($mots[count($mots) - 1], 0, 1, 'UTF-8') : '');
        }
        $tel = $r['phone'] ?? null;

        return array_merge($r, [
            'initiales' => $initiales !== '' ? mb_strtoupper($initiales, 'UTF-8') : '?',
            'phone_lisible' => \App\Domain\Notifications\PhoneFormatter::toReadable($tel),
            'wa_id' => \App\Domain\Notifications\PhoneNormalizer::toWhatsAppId($tel),
            'tel_e164' => \App\Domain\Notifications\PhoneNormalizer::toE164($tel),
        ]);
    })->values()->all();

    $reFileCount = count($reRows);
    $reSoldeFile = array_sum(array_map(fn ($r) => (float) ($r['solde_restant'] ?? 0), $reRows));
    $reRetards = array_map(fn ($r) => (int) ($r['jours_retard'] ?? 0), $reRows);
    $reRetardMin = $reFileCount > 0 ? min($reRetards) : 0;
    $reDejaRelances = count(array_filter($reRows, fn ($r) => (int) ($r['relances_today'] ?? 0) > 0));
    $reDateJour = \Illuminate\Support\Str::ucfirst(now()->translatedFormat('l j F'));
    $reEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $reEcoleNom = $reEcole['name'] ?: ($reEcole['acronym'] ?: config('app.name'));
    $rePeutExporter = auth()->user()?->can('comptabilite.recouvrement.access') ?? false;

    $reConfig = [
        'rows' => $reRows,
        'whatsappTemplate' => $whatsappTemplate,
        'schoolName' => $schoolName,
        'logIntentUrl' => route('esbtp.comptabilite.recouvrement.log-intent'),
        'confirmSentUrl' => route('esbtp.comptabilite.recouvrement.confirm-sent'),
        'markDoneUrl' => route('esbtp.comptabilite.recouvrement.mark-done'),
        'csrf' => csrf_token(),
    ];
@endphp
<div class="container-fluid re-page"
     x-data="recouvrement({{ \Illuminate\Support\Js::from($reConfig) }})">

<div class="{{ $reShell ? 'm-only-desktop' : '' }}">
    {{-- ============================ HERO ============================ --}}
    <div class="re-hero">
        <div class="re-hero-top">
            <div class="re-hero-left">
                <div class="re-hero-icon"><i class="fas fa-hand-holding-usd"></i></div>
                <div>
                    <h1>Recouvrement quotidien</h1>
                    <p>Liste priorisée des étudiants à relancer aujourd'hui — appel direct, WhatsApp ou email en 1 clic.</p>
                </div>
            </div>
            <div class="re-hero-right">
                <a href="{{ route('esbtp.comptabilite.analytics.index') }}" class="re-btn re-btn--glass">
                    <i class="fas fa-chart-line"></i> Analytics
                </a>
                <x-export-modal
                    :preview-url="route('esbtp.comptabilite.recouvrement.preview-pdf')"
                    :pdf-url="route('esbtp.comptabilite.recouvrement.export-pdf')"
                    :excel-url="route('esbtp.comptabilite.recouvrement.export-excel')"
                    :email-url="route('esbtp.comptabilite.recouvrement.email-pdf')"
                    button-class="re-btn re-btn--glass" />
            </div>
        </div>

        <div class="re-kpis">
            <div class="re-kpi">
                <div class="re-kpi-icon"><i class="fas fa-fire"></i></div>
                <div>
                    <div class="re-kpi-value">{{ $buckets['haut'] ?? 0 }}</div>
                    <div class="re-kpi-label">Étudiants à haut risque</div>
                </div>
            </div>

            <div class="re-kpi">
                <div class="re-kpi-icon"><i class="fas fa-coins"></i></div>
                <div>
                    <div class="re-kpi-value">{{ number_format($totalSoldeHaut, 0, ',', ' ') }} <span class="re-kpi-unit">FCFA</span></div>
                    <div class="re-kpi-label">Solde non recouvré (haut risque)</div>
                </div>
            </div>

            <div class="re-kpi">
                <div class="re-kpi-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <div class="re-kpi-value">{{ $buckets['moyen'] ?? 0 }}</div>
                    <div class="re-kpi-label">Sous surveillance</div>
                </div>
            </div>

            <div class="re-kpi">
                <div class="re-kpi-icon"><i class="fas fa-users"></i></div>
                <div>
                    <div class="re-kpi-value">{{ $totalActifs }}</div>
                    <div class="re-kpi-label">Total étudiants actifs</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ FILTERS ============================ --}}
    <div class="re-filters">
        <input type="text" class="re-input" placeholder="Rechercher par nom..." x-model="search">

        <select class="re-input" x-model="levelFilter">
            <option value="">Tous les niveaux</option>
            <option value="haut">Haut risque uniquement</option>
            <option value="moyen">Surveillance</option>
            <option value="bas">Bas risque</option>
        </select>

        <select class="re-input" x-model="retardFilter">
            <option value="">Tout retard</option>
            <option value="30">≥ 30 jours</option>
            <option value="60">≥ 60 jours</option>
            <option value="90">≥ 90 jours</option>
        </select>

        <div class="re-filter-stat" x-text="`${filteredRows.length} / ${rows.length} étudiants`"></div>
    </div>

    {{-- ============================ TABLE ============================ --}}
    <div class="re-card">
        <template x-if="filteredRows.length === 0">
            <div class="re-empty">
                <i class="fas fa-check-circle"></i>
                <p x-text="rows.length === 0 ? 'Aucun étudiant à risque dans ce périmètre.' : 'Aucun résultat avec ces filtres.'"></p>
            </div>
        </template>

        <template x-if="filteredRows.length > 0">
            <div class="table-responsive">
                <table class="re-table">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Classe</th>
                            <th class="text-end">Solde</th>
                            <th class="text-center">Retard</th>
                            <th class="text-center">Niveau</th>
                            <th class="text-center" style="min-width: 280px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in filteredRows" :key="row.inscription_id">
                            <tr :class="row.confirmed ? 're-row--done' : ''">
                                <td>
                                    <div class="re-cell-student">
                                        <strong x-text="row.etudiant_nom"></strong>
                                        <small x-show="row.has_valid_phone" class="re-phone">
                                            <i class="fas fa-phone"></i>
                                            <span x-text="row.phone_lisible"></span>
                                        </small>
                                        <small x-show="!row.has_valid_phone" class="re-warn">
                                            <i class="fas fa-exclamation-triangle"></i> Téléphone invalide
                                        </small>
                                        <small x-show="row.relances_today > 0" class="re-info">
                                            <i class="fas fa-history"></i>
                                            <span x-text="`Déjà ${row.relances_today} relance(s) aujourd'hui`"></span>
                                        </small>
                                    </div>
                                </td>
                                <td x-text="row.classe_nom"></td>
                                <td class="text-end">
                                    <strong x-text="formatMoney(row.solde_restant)"></strong> FCFA
                                </td>
                                <td class="text-center">
                                    <span class="re-chip re-chip--retard" x-text="row.jours_retard + ' j'"></span>
                                </td>
                                <td class="text-center">
                                    <span class="re-level" :class="'re-level--' + row.level" x-text="capitalize(row.level)"></span>
                                </td>
                                <td>
                                    <div class="re-actions">
                                        <button class="re-action re-action--whatsapp"
                                                :disabled="!row.has_valid_phone || row.confirmed"
                                                @click="dispatch(row, 'whatsapp_deeplink')"
                                                title="Envoyer un WhatsApp">
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                        <button class="re-action re-action--tel"
                                                :disabled="!row.has_valid_phone || row.confirmed"
                                                @click="dispatch(row, 'tel')"
                                                title="Appeler">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        <button class="re-action re-action--email"
                                                :disabled="!row.email || row.confirmed"
                                                @click="dispatch(row, 'email')"
                                                title="Envoyer un email">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                        <button class="re-action re-action--done"
                                                :disabled="row.confirmed"
                                                @click="markDone(row)"
                                                title="Marquer comme relancé">
                                            <i class="fas fa-check"></i>
                                            <span x-show="row.confirmed">Relancé</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    {{-- Toast feedback (bureau) --}}
    <div class="re-toast" x-show="toast" x-transition :class="'re-toast--' + (toastType || 'info')">
        <i class="fas" :class="toastType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'"></i>
        <span x-text="toast"></span>
    </div>
</div>

@if($reShell)
{{-- ============================ ÉCRAN MOBILE (shell m-*) ============================ --}}
{{-- La barre d'onglets et la navbar mobile sont rendues par le layout. --}}
<div class="m-only-mobile m-screen rcm-screen">
    {{-- Le bouton d'export n'existe que si la personne peut exporter (même garde que les routes). --}}
    <x-m.appbar title="Recouvrement"
                :sub="$reDateJour"
                :back="\App\Support\PorteDeRoute::ouverte('esbtp.comptabilite.dashboard', auth()->user()) ? route('esbtp.comptabilite.dashboard') : route('dashboard')"
                :action="$rePeutExporter ? 'dl' : null"
                action-label="Exporter la file du jour"
                x-on:click="mOuvrir('rcm-exports')" />

    <div class="m-body" data-m-ptr="reload">
        <section class="m-hero">
            <span class="k" x-text="mLibelleFile()">File du jour{{ $reRetardMin > 0 ? ' · retards ≥ ' . $reRetardMin . ' j' : '' }}</span>
            <span class="v"><span x-text="mFileTotale.length">{{ $reFileCount }}</span><small x-text="mLibelleSolde()">{{ $reFileCount > 1 ? 'étudiants' : 'étudiant' }} · {{ number_format($reSoldeFile, 0, ',', ' ') }} FCFA</small></span>
            <div class="row">
                <span class="pill" x-text="mDejaRelances + (mDejaRelances > 1 ? ' relancés aujourd\'hui' : ' relancé aujourd\'hui')">{{ $reDejaRelances }} {{ $reDejaRelances > 1 ? 'relancés' : 'relancé' }} aujourd'hui</span>
                <span class="pill">{{ $buckets['haut'] ?? 0 }} à haut risque</span>
            </div>
        </section>

        @if($reFileCount === 0)
            <x-m.empty icon="check" title="Personne à relancer" text="Aucun étudiant à risque dans ce périmètre pour {{ $reEcoleNom }}. La file se remplit au fil des retards de paiement." />
        @else
            <div class="m-seg" role="tablist" aria-label="Filtrer la file">
                <button type="button" role="tab"
                        x-bind:aria-selected="mSeg === 'file' ? 'true' : 'false'"
                        x-bind:class="mSeg === 'file' ? 'on' : ''"
                        x-on:click="mSeg = 'file'"
                        x-text="'À relancer · ' + mFileTotale.length">À relancer · {{ $reFileCount }}</button>
                <button type="button" role="tab"
                        x-bind:aria-selected="mSeg === 'relances' ? 'true' : 'false'"
                        x-bind:class="mSeg === 'relances' ? 'on' : ''"
                        x-on:click="mSeg = 'relances'"
                        x-text="'Relancés · ' + mRelancesTotale.length">Relancés · 0</button>
            </div>

            <label class="m-search">
                <x-m.icon name="search" />
                <input type="search" placeholder="Rechercher un étudiant" x-model="search" autocomplete="off" aria-label="Rechercher un étudiant">
            </label>

            <div class="m-list one" x-show="mListe.length > 0">
                <template x-for="row in mListe" :key="row.inscription_id">
                    <div class="m-row rcm-row"
                         x-bind:class="{ 'is-leaving': row.leaving, 'is-busy': row.busy, 'is-done': row.confirmed }"
                         role="button" tabindex="0"
                         x-on:click="mFiche(row)"
                         x-on:keydown.enter.prevent="mFiche(row)">
                        <div class="av" aria-hidden="true" x-text="row.initiales"></div>
                        <div class="tt">
                            <b x-text="row.etudiant_nom"></b>
                            <span x-text="mSousTitre(row)"></span>
                            <small class="rcm-line" x-show="row.phone_lisible" x-text="row.phone_lisible"></small>
                            <small class="rcm-line warn" x-show="!row.has_valid_phone">Téléphone manquant ou invalide</small>
                            <small class="rcm-line info" x-show="row.relances_today > 0" x-text="'Déjà relancé ' + row.relances_today + ' fois aujourd\'hui'"></small>
                        </div>
                        <div class="tr rcm-tr">
                            <a class="m-chip ok rcm-wa"
                               x-show="row.wa_id"
                               x-bind:href="mWaUrl(row)"
                               target="_blank" rel="noopener"
                               x-on:click.stop="mIntent(row, 'whatsapp_deeplink')"
                               aria-label="Envoyer un WhatsApp">
                                <x-m.icon name="msg" /> WhatsApp
                            </a>
                            <span class="m-chip mute rcm-wa is-off" x-show="!row.wa_id">Sans numéro</span>
                            <button type="button" class="rcm-fait"
                                    x-show="!row.confirmed"
                                    x-bind:disabled="row.busy"
                                    x-on:click.stop="mFait(row)"
                                    aria-label="Marquer comme relancé">
                                <x-m.icon name="check" /> Fait
                            </button>
                            <span class="m-chip ok" x-show="row.confirmed">Relancé</span>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="mListe.length === 0" x-cloak>
                <x-m.empty icon="check" title="Rien dans cette liste">
                    <span x-text="mVide()"></span>
                </x-m.empty>
            </div>
        @endif
    </div>

    {{-- Feuille : fiche de l'étudiant + canaux --}}
    <x-m.sheet id="rcm-fiche" title="Relancer">
        <template x-if="mSel">
            <div class="rcm-fiche">
                <div class="m-note">
                    <div class="av" x-text="mSel.initiales"></div>
                    <div>
                        <div class="nm" x-text="mSel.etudiant_nom"></div>
                        <div class="rcm-sub" x-text="mSel.classe_nom"></div>
                    </div>
                    <span class="m-chip" x-bind:class="mNiveauChip(mSel.level)" x-text="capitalize(mSel.level)"></span>
                </div>

                <div class="rcm-cnt">
                    <div><b x-text="formatMoney(mSel.solde_restant) + ' FCFA'"></b><span>Reste dû</span></div>
                    <div><b x-text="mSel.jours_retard + ' j'"></b><span>Retard</span></div>
                    <div><b x-text="mSel.relances_today"></b><span>Relances ce jour</span></div>
                </div>

                <div class="m-menu">
                    <a x-show="mSel.wa_id" x-bind:href="mWaUrl(mSel)" target="_blank" rel="noopener"
                       x-on:click="mIntent(mSel, 'whatsapp_deeplink')">
                        <x-m.icon name="msg" /><span>WhatsApp · message pré-rempli</span><x-m.icon name="chr" class="ch" />
                    </a>
                    <a x-show="mSel.tel_e164" x-bind:href="'tel:' + mSel.tel_e164"
                       x-on:click="mIntent(mSel, 'tel')">
                        <x-m.icon name="phone" /><span x-text="'Appeler · ' + (mSel.phone_lisible || '')"></span><x-m.icon name="chr" class="ch" />
                    </a>
                    <a x-show="mSel.email" x-bind:href="mMailUrl(mSel)"
                       x-on:click="mIntent(mSel, 'email')">
                        <x-m.icon name="file" /><span>Envoyer un e-mail</span><x-m.icon name="chr" class="ch" />
                    </a>
                    <button type="button" x-show="!mSel.confirmed" x-bind:disabled="mSel.busy" x-on:click="mFait(mSel, true)">
                        <x-m.icon name="check" /><span>Marquer « relancé »</span><x-m.icon name="chr" class="ch" />
                    </button>
                </div>

                <p class="rcm-hint" x-show="!mSel.has_valid_phone">Aucun numéro valide : mettez à jour la fiche de l'étudiant pour relancer par WhatsApp ou par appel.</p>
            </div>
        </template>
    </x-m.sheet>

    @can('comptabilite.recouvrement.access')
    {{-- Feuille : exports de la file (mêmes routes que le bureau, filtre recherche conservé) --}}
    <x-m.sheet id="rcm-exports" title="Exporter la file" :sub="$reEcoleNom . ' · ' . $reDateJour">
        <div class="m-menu">
            <a x-bind:href="mExportUrl(@js(route('esbtp.comptabilite.recouvrement.preview-pdf')))" target="_blank" rel="noopener">
                <x-m.icon name="file" /><span>Aperçu PDF</span><x-m.icon name="chr" class="ch" />
            </a>
            <a x-bind:href="mExportUrl(@js(route('esbtp.comptabilite.recouvrement.export-pdf')))">
                <x-m.icon name="dl" /><span>Télécharger le PDF</span><x-m.icon name="chr" class="ch" />
            </a>
            <a x-bind:href="mExportUrl(@js(route('esbtp.comptabilite.recouvrement.export-excel')))">
                <x-m.icon name="dl" /><span>Télécharger en Excel</span><x-m.icon name="chr" class="ch" />
            </a>
        </div>
    </x-m.sheet>
    @endcan
</div>
@endif

</div>

<x-fab-encaisser />
@endsection

@push('styles')
<style>
[x-cloak] { display: none !important; }

:root {
    --re-primary: #0453cb;
    --re-primary-d: #033a8e;
    --re-secondary: #5e91de;
    --re-dark: #0f172a;
    --re-text: #1e293b;
    --re-muted: #64748b;
    --re-border: #e2e8f0;
    --re-success: #10b981;
    --re-warning: #f59e0b;
    --re-danger: #dc2626;
    --re-whatsapp: #25D366;
}

.re-page { padding: 1rem 0; }

/* Hero */
.re-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.re-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.re-hero-left { display: flex; align-items: center; gap: 1rem; }
.re-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.re-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.re-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: 0; }
.re-hero-right { display: flex; gap: .5rem; flex-wrap: wrap; }

.re-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    border-radius: 10px; padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
    transition: all .2s ease;
}
.re-btn--glass {
    background: rgba(255,255,255,.15); color: #fff;
    border: 1px solid rgba(255,255,255,.2);
}
.re-btn--glass:hover {
    background: rgba(255,255,255,.25); color: #fff;
    transform: translateY(-1px);
}

.re-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.re-kpi {
    flex: 1; min-width: 180px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}
.re-kpi-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; color: #fff; flex-shrink: 0;
}
.re-kpi-value { font-size: 1.25rem; font-weight: 700; color: #fff; line-height: 1.1; }
.re-kpi-unit { font-size: .68rem; font-weight: 500; opacity: .65; }
.re-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

/* Filters */
.re-filters {
    display: flex; gap: .75rem; flex-wrap: wrap; align-items: center;
    background: #fff; padding: 1rem 1.25rem; border-radius: 12px;
    border: 1px solid var(--re-border); margin-bottom: 1rem;
}
.re-input {
    padding: .55rem .85rem; border-radius: 10px;
    border: 1px solid var(--re-border); font-size: .88rem;
    transition: border-color .15s ease;
    flex: 1; min-width: 180px; max-width: 280px;
}
.re-input:focus {
    outline: none; border-color: var(--re-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.re-filter-stat {
    margin-left: auto; font-size: .82rem; color: var(--re-muted); font-weight: 600;
}

/* Card */
.re-card {
    background: #fff;
    border: 1px solid var(--re-border);
    border-radius: 14px;
    padding: 0;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    overflow: hidden;
}

/* Table */
.re-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
.re-table thead th {
    background: #fafbfc; font-weight: 600; color: var(--re-text);
    border-bottom: 2px solid var(--re-border); padding: 1rem .85rem;
    text-align: left; font-size: .8rem; text-transform: uppercase;
    letter-spacing: .03em;
}
.re-table .text-end { text-align: right; }
.re-table .text-center { text-align: center; }
.re-table tbody td { padding: .85rem; vertical-align: middle; border-bottom: 1px solid var(--re-border); }
.re-table tbody tr:last-child td { border-bottom: none; }
.re-table tbody tr:hover { background: rgba(4,83,203,.02); }
.re-row--done { opacity: .55; background: rgba(16,185,129,.04) !important; }

.re-cell-student strong { color: var(--re-dark); }
.re-cell-student small { display: block; margin-top: .15rem; font-size: .72rem; }
.re-warn { color: var(--re-warning); }
.re-info { color: var(--re-muted); display: inline-flex; align-items: center; gap: .35rem; }
.re-info i { font-size: .65rem; }
.re-phone {
    color: var(--re-primary); display: inline-flex; align-items: center; gap: .35rem;
    font-family: 'SFMono-Regular', Menlo, Consolas, monospace;
    font-size: .72rem; margin-top: .15rem;
}
.re-phone i { font-size: .6rem; opacity: .7; }

.re-chip {
    display: inline-block; padding: .25rem .6rem;
    border-radius: 999px; font-size: .72rem; font-weight: 600;
}
.re-chip--retard { background: rgba(245,158,11,.12); color: #b45309; }

.re-level {
    display: inline-block; padding: .25rem .65rem;
    border-radius: 999px; font-size: .72rem; font-weight: 600;
}
.re-level--haut { background: rgba(220,38,38,.12); color: var(--re-danger); }
.re-level--moyen { background: rgba(245,158,11,.12); color: #b45309; }
.re-level--bas { background: rgba(16,185,129,.12); color: #047857; }

/* Actions */
.re-actions { display: flex; gap: .35rem; justify-content: center; }
.re-action {
    width: 38px; height: 38px; border-radius: 10px;
    border: 1px solid var(--re-border); background: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .95rem; cursor: pointer; transition: all .15s ease;
    color: var(--re-muted);
}
.re-action:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15,23,42,.08);
}
.re-action:disabled { opacity: .35; cursor: not-allowed; }

.re-action--whatsapp { color: var(--re-whatsapp); border-color: rgba(37,211,102,.2); }
.re-action--whatsapp:hover:not(:disabled) { background: rgba(37,211,102,.08); }

.re-action--tel { color: var(--re-primary); border-color: rgba(4,83,203,.2); }
.re-action--tel:hover:not(:disabled) { background: rgba(4,83,203,.08); }

.re-action--email { color: var(--re-muted); }
.re-action--email:hover:not(:disabled) { background: rgba(100,116,139,.08); color: var(--re-text); }

.re-action--done {
    color: var(--re-success); border-color: rgba(16,185,129,.25);
    width: auto; padding: 0 .85rem; gap: .35rem;
    font-size: .82rem; font-weight: 600;
}
.re-action--done:hover:not(:disabled) { background: rgba(16,185,129,.08); }

/* Empty */
.re-empty { text-align: center; padding: 3rem 1rem; color: var(--re-muted); }
.re-empty i { font-size: 2.5rem; color: var(--re-success); margin-bottom: .75rem; }
.re-empty p { margin: 0; font-size: .9rem; }

/* Toast */
.re-toast {
    position: fixed; bottom: 24px; right: 24px;
    padding: .85rem 1.25rem; border-radius: 12px;
    background: #fff; box-shadow: 0 8px 30px rgba(15,23,42,.15);
    border: 1px solid var(--re-border);
    display: flex; align-items: center; gap: .65rem;
    font-size: .9rem; z-index: 1000; max-width: 400px;
}
.re-toast--success { border-color: rgba(16,185,129,.3); color: #047857; }
.re-toast--success i { color: var(--re-success); }
.re-toast--error { border-color: rgba(220,38,38,.3); color: var(--re-danger); }
.re-toast--error i { color: var(--re-danger); }
.re-toast--info i { color: var(--re-primary); }

/* Responsive */
@@media (max-width: 992px) {
    .re-actions { flex-wrap: wrap; }
}
@@media (max-width: 768px) {
    .re-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .re-hero h1 { font-size: 1.2rem; }
    .re-kpi { min-width: 140px; }
    .re-table { font-size: .82rem; }
    .re-table thead th, .re-table tbody td { padding: .65rem .5rem; }
    .re-action { width: 34px; height: 34px; font-size: .85rem; }
    .re-action--done { padding: 0 .65rem; }
}

/* ===================== ÉCRAN MOBILE — namespace rcm-* ===================== */
.rcm-row { cursor: pointer; transition: transform 260ms cubic-bezier(.22,1,.36,1), opacity 260ms ease, background 120ms ease; }
.rcm-row .tt { gap: 3px; }
.rcm-row .tt span { white-space: normal; line-height: 1.3; }
.rcm-row .rcm-line { display: block; font-size: 11.5px; color: #0453cb; font-variant-numeric: tabular-nums; }
.rcm-row .rcm-line.warn { color: #8a5200; }
.rcm-row .rcm-line.info { color: #64748b; }
.rcm-row.is-leaving { transform: translateX(110%); opacity: 0; }
.rcm-row.is-busy { opacity: .6; pointer-events: none; }
.rcm-row.is-done { background: #f6fbf8; }
.rcm-tr { gap: 6px; }
.rcm-wa { min-height: 44px; padding: 0 12px; font-size: 12.5px; border-radius: 12px; text-decoration: none; -webkit-tap-highlight-color: transparent; }
.rcm-wa svg { width: 16px; height: 16px; }
.rcm-wa.is-off { min-height: 32px; }
.rcm-wa:active { transform: scale(.96); }
.rcm-fait {
    min-height: 44px; min-width: 44px; padding: 0 12px; border-radius: 12px;
    border: 1.5px solid #c7d7f3; background: #fff; color: #0453cb;
    font: inherit; font-weight: 700; font-size: 12.5px;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    cursor: pointer; -webkit-tap-highlight-color: transparent;
}
.rcm-fait svg { width: 16px; height: 16px; }
.rcm-fait:active { background: rgba(4,83,203,.06); }
.rcm-fait:disabled { opacity: .6; cursor: wait; }

.rcm-fiche { display: grid; gap: 12px; }
.rcm-fiche .m-note { grid-template-columns: 44px 1fr auto; }
.rcm-sub { font-size: 12px; color: #64748b; }
.rcm-cnt { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
.rcm-cnt > div { background: #f4f6fb; border-radius: 12px; padding: 10px 6px; text-align: center; }
.rcm-cnt b { display: block; font-size: 15px; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
.rcm-cnt span { font-size: 10.5px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.rcm-hint { margin: 0; font-size: 12.5px; color: #8a5200; background: #fff3df; border-radius: 12px; padding: 10px 12px; }
</style>
@endpush

@push('scripts')
<script>
if (typeof window.recouvrement !== 'function') {
window.recouvrement = function (config) {
    return {
        rows: config.rows.map(r => ({ ...r, confirmed: false, lastRelanceId: null, busy: false, leaving: false })),
        search: '',
        levelFilter: '',
        retardFilter: '',
        toast: null,
        toastType: 'info',
        config,

        /* ---------- écran mobile ---------- */
        mSeg: 'file',
        mSel: null,

        init() {
            window.exportFilters = () => ({
                search: this.search,
                level: this.levelFilter,
                retard_min: this.retardFilter,
            });
        },

        get filteredRows() {
            return this.rows.filter(row => {
                if (this.levelFilter && row.level !== this.levelFilter) return false;
                if (this.retardFilter && row.jours_retard < parseInt(this.retardFilter)) return false;
                if (this.search && !row.etudiant_nom.toLowerCase().includes(this.search.toLowerCase())) return false;
                return true;
            });
        },

        formatMoney(value) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(value || 0));
        },

        capitalize(s) {
            return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
        },

        buildMessage(row) {
            const prenom = row.prenoms || row.etudiant_nom.split(' ')[0];
            return this.config.whatsappTemplate
                .replace(/\{prenom\}/g, prenom)
                .replace(/\{nom\}/g, row.etudiant_nom)
                .replace(/\{solde\}/g, this.formatMoney(row.solde_restant))
                .replace(/\{retard\}/g, row.jours_retard)
                .replace(/\{ecole\}/g, this.config.schoolName);
        },

        /* Un seul aller-retour JSON pour toutes les mutations (bureau et mobile). */
        async postJson(url, body) {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(body),
            });
            if (response.status === 429) {
                return { success: false, error: 'Trop d\'actions à la suite, patientez une minute.' };
            }
            const data = await response.json().catch(() => ({}));
            if (!response.ok && data.success === undefined) {
                return { success: false, error: data.message || data.error || ('Erreur ' + response.status) };
            }
            return data;
        },

        async dispatch(row, channel) {
            const message = this.buildMessage(row);
            try {
                const data = await this.postJson(this.config.logIntentUrl, {
                    inscription_id: row.inscription_id,
                    channel: channel,
                    message: message,
                });

                if (!data.success) {
                    this.showToast(data.error_reason || data.error || 'Erreur', 'error');
                    return;
                }

                row.lastRelanceId = data.relance_id;
                if (data.deeplink_url && data.deeplink_url !== '#') {
                    window.open(data.deeplink_url, '_blank', 'noopener');
                }
                this.showToast('Action enregistrée — pensez à confirmer après envoi', 'info');
            } catch (e) {
                this.showToast('Erreur réseau', 'error');
            }
        },

        /* Confirme la relance : confirme l'intention loggée s'il y en a une, sinon crée une relance manuelle. */
        async envoyerFait(row) {
            let url, body;
            if (row.lastRelanceId) {
                url = this.config.confirmSentUrl;
                body = { relance_id: row.lastRelanceId };
            } else {
                url = this.config.markDoneUrl;
                body = { inscription_id: row.inscription_id };
            }
            return this.postJson(url, body);
        },

        async markDone(row) {
            try {
                const data = await this.envoyerFait(row);
                if (data.success) {
                    row.confirmed = true;
                    this.showToast('Relance confirmée', 'success');
                } else {
                    this.showToast(data.error || 'Erreur', 'error');
                }
            } catch (e) {
                this.showToast('Erreur réseau', 'error');
            }
        },

        /* Sous 992px avec le shell, le toast sombre du socle ; sinon le toast de bureau. */
        showToast(message, type = 'info') {
            const shell = document.body.classList.contains('has-m-shell')
                && window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches;
            if (shell) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: message } }));
                return;
            }
            this.toast = message;
            this.toastType = type;
            setTimeout(() => { this.toast = null; }, 3500);
        },

        /* ---------- écran mobile : file, segments, fiche ---------- */
        mMatch(row) {
            return !this.search || row.etudiant_nom.toLowerCase().includes(this.search.toLowerCase());
        },
        get mFileTotale() { return this.rows.filter(r => !r.confirmed); },
        get mRelancesTotale() { return this.rows.filter(r => r.confirmed); },
        get mListe() {
            const base = this.mSeg === 'file' ? this.mFileTotale : this.mRelancesTotale;
            return base.filter(r => this.mMatch(r));
        },
        get mSolde() {
            return this.mFileTotale.reduce((s, r) => s + (Number(r.solde_restant) || 0), 0);
        },
        get mRetardMin() {
            if (this.mFileTotale.length === 0) return null;
            const min = Math.min(...this.mFileTotale.map(r => Number(r.jours_retard) || 0));
            return min > 0 ? min : null;
        },
        get mDejaRelances() {
            return this.rows.filter(r => r.confirmed || (Number(r.relances_today) || 0) > 0).length;
        },
        mLibelleFile() {
            return this.mRetardMin !== null ? 'File du jour · retards ≥ ' + this.mRetardMin + ' j' : 'File du jour';
        },
        mLibelleSolde() {
            const n = this.mFileTotale.length;
            return (n > 1 ? 'étudiants' : 'étudiant') + ' · ' + this.formatMoney(this.mSolde) + ' FCFA';
        },
        mSousTitre(row) {
            return [row.classe_nom, this.formatMoney(row.solde_restant) + ' FCFA', row.jours_retard + ' j de retard']
                .filter(Boolean).join(' · ');
        },
        mNiveauChip(level) {
            if (level === 'haut') return 'bad';
            if (level === 'moyen') return 'warn';
            return 'ok';
        },
        mVide() {
            if (this.mSeg === 'file') {
                return this.search ? 'Aucun étudiant ne correspond à cette recherche.' : 'Toute la file du jour a été relancée.';
            }
            return this.search ? 'Aucun étudiant ne correspond à cette recherche.' : 'Aucune relance confirmée pour l\'instant.';
        },
        mWaUrl(row) {
            if (!row || !row.wa_id) return '#';
            return 'https://wa.me/' + row.wa_id + '?text=' + encodeURIComponent(this.buildMessage(row));
        },
        mMailUrl(row) {
            if (!row || !row.email) return '#';
            return 'mailto:' + row.email + '?subject=' + encodeURIComponent('Solde de scolarité')
                + '&body=' + encodeURIComponent(this.buildMessage(row));
        },
        mExportUrl(base) {
            const q = (this.search || '').trim();
            if (!q) return base;
            return base + (base.includes('?') ? '&' : '?') + 'search=' + encodeURIComponent(q);
        },
        mOuvrir(id) {
            window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: id } }));
        },
        mFermer(id) {
            window.dispatchEvent(new CustomEvent('m-sheet:close', { detail: { id: id } }));
        },
        mFiche(row) {
            this.mSel = row;
            this.mOuvrir('rcm-fiche');
        },

        /* Le lien wa.me / tel: / mailto: s'ouvre dans le geste de l'utilisateur ;
           l'intention est journalisée en arrière-plan, sans bloquer l'ouverture. */
        async mIntent(row, channel) {
            try {
                const data = await this.postJson(this.config.logIntentUrl, {
                    inscription_id: row.inscription_id,
                    channel: channel,
                    message: this.buildMessage(row),
                });
                if (data.success) {
                    row.lastRelanceId = data.relance_id;
                    row.relances_today = (Number(row.relances_today) || 0) + 1;
                } else {
                    this.showToast(data.error_reason || data.error || 'Relance non enregistrée', 'error');
                }
            } catch (e) {
                this.showToast('Relance non enregistrée (réseau)', 'error');
            }
        },

        /* « Fait » : la ligne glisse hors de la file, le suivant prend sa place. */
        async mFait(row, depuisFiche = false) {
            if (!row || row.busy || row.confirmed) return;
            row.busy = true;
            try {
                const data = await this.envoyerFait(row);
                if (!data.success) {
                    this.showToast(data.error || 'Erreur', 'error');
                    return;
                }
                if (depuisFiche) this.mFermer('rcm-fiche');
                const index = this.mListe.indexOf(row);
                const suivant = this.mSeg === 'file' ? (this.mListe[index + 1] || null) : null;
                row.leaving = true;
                await new Promise(resolve => setTimeout(resolve, 260));
                row.confirmed = true;
                row.leaving = false;
                const nom = row.etudiant_nom;
                this.showToast(nom + ' marqué « relancé »' + (suivant ? ' · suivant : ' + suivant.etudiant_nom : ''), 'success');
            } catch (e) {
                this.showToast('Erreur réseau', 'error');
            } finally {
                row.busy = false;
            }
        },
    };
};
}
</script>
@endpush
