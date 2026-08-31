@php
    $categoryName = $categoryName ?? null;
    if ($categoryName === null) {
        if ($paiement->fraisCategory) {
            $categoryName = $paiement->fraisCategory->name;
        } elseif ($paiement->categorie) {
            $categoryName = $paiement->categorie->nom ?? null;
        }
    }
@endphp

<div class="header-section">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <!-- Logo Column -->
            <td width="16%" style="background-color: {{ $hdrBg }}; padding: 12px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.2);">
                @if(isset($settings['show_logo']) && $settings['show_logo'] && isset($settings['logo_base64']))
                    <img src="{{ $settings['logo_base64'] }}"
                         style="max-height: 70px; max-width: 120px;"
                         alt="Logo">
                @else
                    <div style="font-size: 36px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4;">K</div>
                @endif
            </td>
            <!-- Info Column -->
            <td width="84%" style="background-color: {{ $hdrBg }}; padding: 10px 16px; vertical-align: middle;">
                <!-- School Name -->
                <div style="font-size: 19px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 2px;">
                    {{ $settings['school_name'] ?? 'KLASSCI' }}
                </div>
                <!-- Contact -->
                <div style="font-size: 12px; color: {{ $hdrText }}; opacity: 0.8; margin-bottom: 6px;">
                    @if($settings['school_address'] ?? false){{ $settings['school_address'] }}@endif
                    @if($settings['school_phone'] ?? false) &nbsp;|&nbsp; Tél: {{ $settings['school_phone'] }}@endif
                    @if($settings['school_email'] ?? false) &nbsp;|&nbsp; Email: {{ $settings['school_email'] }}@endif
                </div>
                <!-- Divider + Title -->
                <div style="border-top: 1px solid rgba(255,255,255,0.3); padding-top: 6px;">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="60%" style="font-size: 18px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px;">
                                REÇU DE PAIEMENT
                            </td>
                            <td width="40%" style="font-size: 13px; color: {{ $hdrText }}; opacity: 0.75; text-align: right;">
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
        <td>
            <div class="lbl">Matricule</div>
            <div class="val mono">{{ $paiement->etudiant->matricule }}</div>
        </td>
        <td>
            <div class="lbl">Nom et Prénoms</div>
            <div class="val">{{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet ?? 'N/A' }}</div>
        </td>
        <td>
            <div class="lbl">Filière</div>
            <div class="val">{{ $paiement->inscription->filiere->name ?? 'N/A' }}</div>
        </td>
        <td>
            <div class="lbl">Niveau</div>
            <div class="val">{{ $paiement->inscription->niveauEtude->name ?? 'N/A' }}</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="lbl">Date de paiement</div>
            <div class="val">{{ $paiement->date_paiement->format('d/m/Y') }}</div>
        </td>
        <td>
            <div class="lbl">Motif</div>
            <div class="val">{{ $paiement->motif }}</div>
        </td>
        <td>
            <div class="lbl">Mode de paiement</div>
            <div class="val">{{ $paiement->mode_paiement }}</div>
        </td>
        <td>
            <div class="lbl">Statut</div>
            <div class="val">
                <span class="badge badge-{{ $paiement->status === 'validé' ? 'success' : ($paiement->status === 'en_attente' ? 'warning' : 'danger') }}">
                    {{ $paiement->status_formatte }}
                </span>
            </div>
        </td>
    </tr>
    @if($categoryName || $paiement->fraisCategory?->accepts_in_kind || $paiement->tranche || $paiement->reference_paiement)
    <tr>
        <td>
            <div class="lbl">Catégorie de frais</div>
            <div class="val">{{ $categoryName ?? '—' }}</div>
        </td>
        <td>
            <div class="lbl">Règlement</div>
            <div class="val">{{ $paiement->fraisCategory?->accepts_in_kind ? 'Équivalent en frais (fourniture non déposée)' : '—' }}</div>
        </td>
        <td>
            <div class="lbl">Tranche</div>
            <div class="val">{{ $paiement->tranche ?: '—' }}</div>
        </td>
        <td>
            <div class="lbl">Référence</div>
            <div class="val mono">{{ $paiement->reference_paiement ?: '—' }}</div>
        </td>
    </tr>
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
    @foreach ($fraisLignes->chunk(3) as $row)
    <tr>
        @foreach ($row as $ligne)
        <td>
            <span class="chk {{ $ligne['checked'] ? '' : 'chk-off' }}">{{ $ligne['checked'] ? '[X]' : '[ ]' }}</span>
            <span class="{{ !empty($ligne['current']) ? 'fee-now' : '' }}">{{ $ligne['name'] }}</span>
            @if(!empty($ligne['in_kind']))
                <span class="fee-note">déposé</span>
            @elseif(($ligne['restant'] ?? 0) > 0)
                <span class="fee-note">{{ number_format($ligne['restant'], 0, ',', ' ') }}</span>
            @elseif(!empty($ligne['non_configure']))
                {{-- Ni coche ni montant : sans ce mot, le caissier lit une ligne
                     vide et ne peut pas distinguer « rien a payer » de « montant
                     pas encore defini par l'etablissement ». --}}
                <span class="fee-note">à définir</span>
            @endif
        </td>
        @endforeach
        @for ($i = $row->count(); $i < 3; $i++)
        <td></td>
        @endfor
    </tr>
    @endforeach
</table>
<table class="reste" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td class="reste-lbl">Reste à payer</td>
        <td class="reste-val">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</td>
    </tr>
</table>
@endif

@if($paiement->creator)
<table class="encaissed" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td class="encaissed-lbl">Encaissé par</td>
        <td class="encaissed-val">{{ $paiement->creator->name }}</td>
    </tr>
</table>
@endif

<table class="signs" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td>
            <div class="sign-title">Date d'émission</div>
            <div class="sign-line">{{ $paiement->date_validation ? $paiement->date_validation->format('d/m/Y') : date('d/m/Y') }}</div>
        </td>
        <td>
            <div class="sign-title">Signature et Cachet</div>
            {{-- Signataire = celui qui a GENERE le recu, donc celui qui a encaisse.
                 Le validateur signait auparavant un document qu'il n'avait pas
                 emis : le cachet engage la personne qui a recu l'argent et remis
                 le papier, pas celle qui a coche la validation ensuite — souvent
                 un autre poste, parfois un autre jour. C'est aussi le nom deja
                 imprime sur « Encaisse par ». --}}
            <div class="sign-line">{{ $paiement->signataire }}</div>
        </td>
    </tr>
</table>

<div class="footer-section">
    <div class="footer-warning">Ce reçu est un document officiel. Toute falsification constitue un délit passible de poursuites judiciaires.</div>
    <div class="footer-contact">{{ $settings['school_name'] ?? 'KLASSCI' }} — {{ $settings['school_address'] ?? '' }} — Tél: {{ $settings['school_phone'] ?? '' }}</div>
</div>
