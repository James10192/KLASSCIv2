@php
    $fmt = static fn ($v): string => $v === null ? '—' : number_format((float) $v, 2, ',', ' ');
    $isLmd = ($ecran['systeme'] ?? null) === \App\Services\FraisScopeResolver::SYSTEME_LMD;
    $semestres = $ecran['semestres'] ?? [];
    $anneeLabel = $annee->display_name ?? $annee->name ?? '—';
@endphp

<x-pdf-document
    title="Relevé de notes — consultation"
    :subtitle="'Année universitaire '.$anneeLabel"
    orientation="portrait">

<style>
    .rc-warning { border: 2px solid #0453cb; background: #eff6ff; padding: 8px 10px; margin-bottom: 12px; color: #0341a0; font-size: 9px; font-weight: 700; }
    .rc-identity { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .rc-identity td { border-bottom: 1px solid #e2e8f0; padding: 5px 6px; font-size: 9px; }
    .rc-label { color: #64748b; font-size: 8px; }
    .rc-period { margin: 13px 0; page-break-inside: auto; }
    .rc-period h2 { color: #0453cb; font-size: 12px; padding: 6px 0; border-bottom: 2px solid #0453cb; page-break-after: avoid; }
    .rc-table { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 8.5px; }
    .rc-table th { background: #eaf1fc; color: #0a3d8f; text-align: left; font-size: 8px; padding: 6px; }
    .rc-table td { border-bottom: 1px solid #e2e8f0; padding: 5px 6px; vertical-align: top; }
    .rc-table .rc-num { text-align: right; white-space: nowrap; }
    .rc-table .rc-ue td { background: #f8fafc; font-weight: 700; }
    .rc-table .rc-ecue td:first-child { padding-left: 15px; }
    .rc-summary { margin: 9px 0 4px; border: 1px solid #bad1f4; background: #f8fbff; padding: 8px 10px; }
    .rc-summary strong { color: #0453cb; font-size: 12px; }
    .rc-end { margin-top: 16px; padding: 10px 12px; border: 2px solid #0453cb; page-break-inside: avoid; }
    .rc-end h3 { margin: 0 0 8px; font-size: 11px; color: #0453cb; }
    .rc-note { color: #64748b; line-height: 1.6; font-size: 8px; margin-top: 10px; }
</style>

<div class="rc-warning">
    DOCUMENT CONSULTATIF — NON OFFICIEL
    <div style="font-weight:400;margin-top:3px;color:#334155;">Destiné à la vérification personnelle des notes. Ne constitue ni un bulletin officiel, ni une attestation de réussite, ni une décision du jury.</div>
</div>

<table class="rc-identity">
    <tr>
        <td class="rc-label">ÉTUDIANT</td>
        <td><strong>{{ $etudiant->nom_complet }}</strong></td>
        <td class="rc-label">MATRICULE</td>
        <td><strong>{{ $etudiant->matricule ?: '—' }}</strong></td>
    </tr>
    <tr>
        <td class="rc-label">CLASSE</td>
        <td>{{ $classe->name ?? '—' }}</td>
        <td class="rc-label">ANNÉE</td>
        <td>{{ $anneeLabel }}</td>
    </tr>
    <tr><td class="rc-label">PARCOURS</td><td colspan="3">{{ $isLmd ? 'LMD' : 'BTS' }} — Moyennes susceptibles d'évoluer jusqu'à leur publication définitive.</td></tr>
</table>

@foreach($semestres as $sem)
    <section class="rc-period">
        <h2>{{ $sem['label'] }}</h2>
        <table class="rc-table">
            <thead>
                <tr>
                    <th style="width:58%">Enseignement</th>
                    <th style="width:17%">Coef. / crédits</th>
                    <th style="width:25%" class="rc-num">Moyenne /20</th>
                </tr>
            </thead>
            <tbody>
            @if($isLmd)
                @forelse(($sem['ues'] ?? []) as $ue)
                    <tr class="rc-ue">
                        <td>{{ $ue['code'] ? $ue['code'].' — ' : '' }}{{ $ue['name'] }} <span style="font-weight:400;color:#64748b">({{ $ue['etat_label'] ?? 'En cours' }})</span></td>
                        <td>{{ $ue['credit'] ?? '—' }} crédits</td>
                        <td class="rc-num">{{ $fmt($ue['moyenne'] ?? null) }}</td>
                    </tr>
                    @foreach(($ue['ecues'] ?? []) as $ecue)
                        <tr class="rc-ecue">
                            <td>{{ $ecue['code'] ? $ecue['code'].' — ' : '' }}{{ $ecue['name'] }}</td>
                            <td>{{ $ecue['coefficient'] ?? '—' }}</td>
                            <td class="rc-num">{{ $fmt($ecue['moyenne'] ?? null) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="3">Aucune UE disponible pour cette période.</td></tr>
                @endforelse
            @else
                @forelse(($sem['matieres'] ?? []) as $matiere)
                    <tr>
                        <td>{{ $matiere['name'] }}</td>
                        <td>{{ $matiere['coefficient'] ?? '—' }}</td>
                        <td class="rc-num">{{ $fmt($matiere['moyenne'] ?? null) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Aucune matière disponible pour cette période.</td></tr>
                @endforelse
            @endif
            </tbody>
        </table>
        <div class="rc-summary">
            <span>{{ $sem['label'] }} · Moyenne disponible : </span>
            <strong>{{ $fmt($sem['moyenne'] ?? null) }}@if(($sem['moyenne'] ?? null) !== null) /20 @endif</strong>
            @if($isLmd) · Crédits : {{ $sem['credits_acquis'] ?? 0 }}/{{ $sem['credits_attendus'] ?? 0 }} @endif
            @if(!empty($sem['coefficients_manquants']))<div style="font-size:8px;color:#64748b">Certains coefficients sont manquants : vérifiez la configuration pédagogique.</div>@endif
        </div>
    </section>
@endforeach

<div class="rc-end">
    <h3>Synthèse des moyennes</h3>
    @foreach($semestres as $sem)
        <div style="font-size:9px;margin:3px 0;">{{ $sem['label'] }} : <strong>{{ $fmt($sem['moyenne'] ?? null) }}@if(($sem['moyenne'] ?? null) !== null) /20 @endif</strong></div>
    @endforeach
    @if($isLmd)
        <p style="font-size:8px;color:#64748b;margin:7px 0 0">Les moyennes LMD sont présentées par semestre. Aucune moyenne annuelle n'est déduite arbitrairement : celle-ci relève des règles de délibération de l'établissement.</p>
    @else
        @php $annual = collect($semestres)->firstWhere('code', 'annuel'); @endphp
        <p style="font-size:10px;margin:8px 0 0;color:#0453cb"><strong>Moyenne annuelle disponible : {{ $fmt($annual['moyenne'] ?? null) }}@if(($annual['moyenne'] ?? null) !== null) /20 @endif</strong></p>
    @endif
</div>
<p class="rc-note">
    Établi depuis l'espace personnel KLASSCI à la date du {{ now()->format('d/m/Y') }}. Résultats affichés à titre informatif, susceptibles d'être corrigés ou complétés. Seuls les documents officiellement publiés et validés par l'établissement font foi. En cas d'écart, adressez une réclamation à la scolarité.
</p>
</x-pdf-document>
