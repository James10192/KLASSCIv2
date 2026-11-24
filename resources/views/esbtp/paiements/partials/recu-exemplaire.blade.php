@php
    $categoryName = $categoryName ?? null;
    if ($categoryName === null) {
        if ($paiement->fraisCategory) {
            $categoryName = $paiement->fraisCategory->name;
        } elseif ($paiement->categorie) {
            $categoryName = $paiement->categorie->nom ?? null;
        }
    }

    // Un versement peut couvrir PLUSIEURS frais. N'imprimer que la categorie
    // designee au guichet fait mentir le recu des que la somme a ete repartie :
    // l'etudiant lit « Scolarite » sur un papier qui a aussi paye sa ramette.
    $recuVentilation = $paiement->ventilation();

    if ($recuVentilation->count() > 1) {
        $categoryName = $recuVentilation
            ->map(fn ($part) => $part['nom'].' : '.number_format($part['montant'], 0, ',', ' '))
            ->implode(' · ');
    }
@endphp

<div class="header-section">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <!-- Logo Column -->
            <td width="16%" style="background-color: {{ $hdrBg }};  text-align: center; vertical-align: middle; border-right: 0.5pt solid {{ $hdrText }};">
                @if(isset($settings['show_logo']) && $settings['show_logo'] && isset($settings['logo_base64']))
                    <img src="{{ $settings['logo_base64'] }}" alt="Logo">
                @else
                    <div class="rf-title" style="font-weight: bold; color: {{ $hdrText }};">{{ mb_substr($settings['school_name'] ?? 'KLASSCI', 0, 1) }}</div>
                @endif
            </td>
            <!-- Info Column -->
            <td width="84%" style="background-color: {{ $hdrBg }};  vertical-align: middle;">
                <!-- School Name -->
                <div class="rf-school" style="font-weight: bold; color: {{ $hdrText }};">
                    {{ $settings['school_name'] ?? 'KLASSCI' }}
                </div>
                <!-- Contact -->
                <div class="rf-contact" style="color: {{ $hdrText }};">
                    @if($settings['school_address'] ?? false){{ $settings['school_address'] }}@endif
                    @if($settings['school_phone'] ?? false) &nbsp;|&nbsp; Tél: {{ $settings['school_phone'] }}@endif
                    @if($settings['school_email'] ?? false) &nbsp;|&nbsp; Email: {{ $settings['school_email'] }}@endif
                </div>
                <!-- Divider + Title -->
                <div style="border-top: 0.5pt solid {{ $hdrText }};">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="60%" class="rf-title" style="font-weight: bold; color: {{ $hdrText }};">
                                REÇU DE PAIEMENT
                            </td>
                            <td width="40%" class="rf-year" style="color: {{ $hdrText }}; text-align: right;">
                                {{ $paiement->inscription->anneeUniversitaire->name ?? '' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
</div>

<table class="copy-bar" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td class="copy-tag">{{ $copyTag }}</td>
        <td class="copy-num">
            Reçu N°
            <span class="copy-num-val">{{ $paiement->numero_recu }}</span>
        </td>
    </tr>
</table>

<table class="meta" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td width="18%">
            <div class="lbl">Matricule</div>
            <div class="val mono rf-matricule">{{ $paiement->etudiant->matricule }}</div>
        </td>
        <td width="34%">
            <div class="lbl">Nom et Prénoms</div>
            <div class="val rf-student">{{ $paiement->etudiant?->nom_complet ?: $paiement->etudiant?->user?->name ?? 'N/A' }}</div>
        </td>
        <td width="30%">
            <div class="lbl">Filière</div>
            <div class="val rf-filiere">{{ $paiement->inscription->filiere->name ?? 'N/A' }}</div>
        </td>
        <td width="18%">
            <div class="lbl">Niveau</div>
            <div class="val rf-niveau">{{ $paiement->inscription->niveauEtude->name ?? 'N/A' }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="lbl">Date de paiement</div>
            <div class="val rf-date">{{ $paiement->date_paiement->format('d/m/Y') }}</div>
            {{-- La date métier n'a aucune heure (colonne DATE). L'heure vérifiable
                 de saisie vient de created_at, pas de date_paiement à 00:00. --}}
            @if($paiement->created_at)
                <div class="rf-timestamp">Saisi le {{ $paiement->created_at->format('d/m/Y à H:i') }}</div>
            @endif
            @if($paiement->date_validation)
                <div class="rf-timestamp">Validé le {{ $paiement->dateValidationLisible() }}</div>
            @endif
        </td>
        <td>
            <div class="lbl">Statut d'affectation</div>
            <div class="val rf-affectation">{{ $affectationLabel ?? $paiement->inscription?->affectationStatusLabel() ?? '—' }}</div>
        </td>
        <td>
            <div class="lbl">Mode de paiement</div>
            <div class="val rf-mode">{{ $paiement->mode_paiement }}</div>
        </td>
        <td>
            <div class="lbl">Statut</div>
            <div class="val rf-status">
                <span class="badge badge-{{ $paiement->status === 'validé' ? 'success' : ($paiement->status === 'en_attente' ? 'warning' : 'danger') }}">
                    {{ $paiement->status_formatte }}
                </span>
            </div>
        </td>
    </tr>
    @if($categoryName || $paiement->fraisCategory?->accepts_in_kind || $paiement->tranche || $paiement->reference_paiement)
    <tr>
        <td colspan="4">
            <div class="lbl">Catégorie de frais</div>
            <div class="val rf-category">{{ $categoryName ?? '—' }}</div>
            @if($paiement->ventilation_rectifiee_le)
                {{-- On ne REECRIT pas un recu deja remis : deux exemplaires du
                     meme numero diraient des choses differentes sans qu'aucun ne
                     dise lequel fait foi. On le rend AUTO-DECLARANT — celui qui
                     compare les deux voit immediatement lequel est le rectifie,
                     et de quand. --}}
                <div class="rf-fee_note" style="color: #92400e;">
                    Ventilation rectifiée le {{ $paiement->ventilation_rectifiee_le->format('d/m/Y') }}
                    — montant et numéro de reçu inchangés.
                </div>
            @endif
        </td>
    </tr>
    @if($paiement->fraisCategory?->accepts_in_kind || $paiement->tranche || $paiement->reference_paiement)
    <tr>
        <td colspan="2">
            <div class="lbl">Règlement</div>
            <div class="val rf-reglement">{{ $paiement->fraisCategory?->accepts_in_kind ? 'Équivalent en frais (fourniture non déposée)' : '—' }}</div>
        </td>
        <td>
            <div class="lbl">Tranche</div>
            <div class="val rf-tranche">{{ $paiement->tranche ?: '—' }}</div>
        </td>
        <td>
            <div class="lbl">Référence</div>
            <div class="val mono rf-reference">{{ $paiement->reference_paiement ?: '—' }}</div>
        </td>
    </tr>
    @endif
    @endif
</table>

<table class="amount-section" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td class="amount-label">Montant du paiement</td>
        <td class="amount-value">{{ number_format($paiement->montant, 0, ',', ' ') }} <span>FCFA</span></td>
        <td class="amount-words">{{ ucfirst(\App\Services\NumberToWords::convert($paiement->montant)) }} Francs CFA</td>
    </tr>
</table>

@php
    $fraisLignes = collect($fraisLignes ?? []);
    $resteAPayer = (float) ($resteAPayer ?? 0);
@endphp
@if($fraisLignes->isNotEmpty())
<table class="fees" width="100%" border="0" cellspacing="0" cellpadding="0">
    @foreach ($fraisLignes->chunk((int) $print['fee_columns']) as $row)
    <tr>
        @foreach ($row as $ligne)
        <td>
            <span class="chk {{ $ligne['checked'] ? '' : 'chk-off' }}">{{ $ligne['checked'] ? '[X]' : '[ ]' }}</span>
            <span class="{{ !empty($ligne['current']) ? 'fee-now' : '' }}">{{ $ligne['name'] }}</span>
            @if(!empty($ligne['in_kind']))
                <span class="fee-note">reçu en nature</span>
            @elseif(($ligne['restant'] ?? 0) > 0)
                <span class="fee-note">{{ number_format($ligne['restant'], 0, ',', ' ') }}</span>
            @elseif(!empty($ligne['non_configure']))
                <span class="fee-note">exempté</span>
            @endif
        </td>
        @endforeach
        @for ($i = $row->count(); $i < (int) $print['fee_columns']; $i++)
        <td></td>
        @endfor
    </tr>
    @endforeach
</table>
<table class="reste" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td class="reste-lbl">Déjà versé</td>
        <td class="reste-val">{{ number_format((float) ($totalVerse ?? 0), 0, ',', ' ') }} FCFA</td>
        <td class="reste-lbl">Reste à payer</td>
        <td class="reste-val rf-balance">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</td>
    </tr>
    </table>
    @endif

    @php
        $versementsAvant = collect($versementsAvant ?? [])->take(-2)->values();
        $versementsApres = collect($versementsApres ?? [])->take(2)->values();
    @endphp
    @if($versementsAvant->isNotEmpty() || $versementsApres->isNotEmpty())
    <table class="vers" width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <div class="vers-lbl">Versements antérieurs</div>
                @forelse ($versementsAvant as $v)
                    <div class="vers-ligne">{{ optional($v->date_paiement)->format('d/m/Y') }} · {{ number_format((float) $v->montant, 0, ',', ' ') }} FCFA · {{ $v->numero_recu }}</div>
                @empty
                    <div class="vers-ligne">—</div>
                @endforelse
            </td>
            <td>
                <div class="vers-lbl">Versements postérieurs</div>
                @forelse ($versementsApres as $v)
                    <div class="vers-ligne">{{ optional($v->date_paiement)->format('d/m/Y') }} · {{ number_format((float) $v->montant, 0, ',', ' ') }} FCFA · {{ $v->numero_recu }}</div>
                @empty
                    <div class="vers-ligne">—</div>
                @endforelse
            </td>
        </tr>
    </table>
    @endif

    @if($paiement->creator)
<table class="encaissed" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td class="encaissed-lbl">Encaissé par</td>
        <td class="encaissed-val">{{ $paiement->encaisseur_recu }}</td>
    </tr>
</table>
@endif

<table class="signs" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td>
            <div class="sign-title">{{ $paiement->date_validation ? 'Validation' : 'Saisie' }}</div>
            <div class="sign-line">{{ $paiement->date_validation ? $paiement->dateValidationLisible() : ($paiement->created_at?->format('d/m/Y à H:i') ?? '—') }}</div>
        </td>
        <td>
            <div class="sign-title">Signature et Cachet</div>
            {{-- Signataire = celui qui a GENERE le recu, donc celui qui a encaisse.
                 Le validateur signait auparavant un document qu'il n'avait pas
                 emis : le cachet engage la personne qui a recu l'argent et remis
                 le papier, pas celle qui a coche la validation ensuite — souvent
                 un autre poste, parfois un autre jour. C'est aussi le nom deja
                 imprime sur « Encaisse par ». --}}
            <div class="sign-line">{{ $paiement->encaisseur_recu }}</div>
        </td>
    </tr>
</table>

<div class="footer-section">
    <div class="footer-warning">Ce reçu est un document officiel. Toute falsification constitue un délit passible de poursuites judiciaires.</div>
    <div class="footer-contact">{{ $settings['school_name'] ?? 'KLASSCI' }} — {{ $settings['school_address'] ?? '' }}@if(!empty($settings['school_phone'])) — Tél: {{ $settings['school_phone'] }}@endif</div>
</div>
