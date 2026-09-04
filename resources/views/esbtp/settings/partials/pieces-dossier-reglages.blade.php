{{--
    Les réglages du catalogue des pièces à fournir.

    Ils existent dans `settings` depuis la migration du catalogue, mais aucune
    page ne les montrait : une école qui réclamait vingt-quatre photos restait
    bloquée au plafond de vingt sans autre recours que du SQL. Un réglage que
    personne ne peut changer n'est pas un réglage, c'est une constante avec une
    clé — d'où ce bloc.

    Les clés viennent des constantes du service, jamais de chaînes écrites ici :
    un renommage laisserait sinon le formulaire poster une clé disparue, et le
    réglage retomberait à sa valeur d'usine en silence (leçon de la PR #591).
--}}
@php
    $_cataloguePieces = \App\Services\CataloguePiecesDossier::class;
    $_cleExemplairesMax = $_cataloguePieces::REGLAGE_EXEMPLAIRES_MAX;
    $_cleFormeDefaut = $_cataloguePieces::REGLAGE_FORME_DEFAUT;
    $_cleEcheanceDefaut = $_cataloguePieces::REGLAGE_ECHEANCE_DEFAUT;
    $_cleEpuisement = $_cataloguePieces::REGLAGE_EPUISEMENT;
    $_cleRestitution = $_cataloguePieces::REGLAGE_RESTITUTION_ANNULATION;

    $_formes = \App\Enums\FormePieceDossier::selectOptions();
    $_echeances = \App\Enums\EcheancePieceDossier::selectOptions();
    $_epuisements = [
        'bloquer' => "Bloquer — l'inscription attend que la pièce soit redéposée",
        'signaler' => 'Signaler — le dossier est marqué incomplet, rien n\'est empêché',
        'silence' => 'Ne rien signaler',
    ];

    $_valExemplairesMax = \App\Helpers\SettingsHelper::get($_cleExemplairesMax, '20');
    $_valFormeDefaut = (string) \App\Helpers\SettingsHelper::get($_cleFormeDefaut, 'copie');
    $_valEcheanceDefaut = (string) \App\Helpers\SettingsHelper::get($_cleEcheanceDefaut, 'inscription');
    $_valEpuisement = (string) \App\Helpers\SettingsHelper::get($_cleEpuisement, 'signaler');
@endphp

<div class="bc-card">
    <div class="bc-icon"><i class="fas fa-folder-open"></i></div>
    <div class="bc-body">
        <div class="bc-label">Pièces à fournir au dossier</div>
        <div class="bc-desc">
            Ce que l'école propose par défaut quand elle ajoute une pièce à
            @can('pieces_dossier.view')<a href="{{ route('esbtp.pieces-dossier.index') }}">son catalogue</a>@else son catalogue @endcan.
            Ces valeurs pré-remplissent le formulaire ; elles n'imposent rien, chaque pièce reste modifiable.
        </div>

        <div class="row g-2" style="margin-top:.6rem;max-width:560px;">
            <div class="col-12">
                <label class="bc-desc" for="pd-exemplaires-max" style="display:block;margin-bottom:.2rem;">Plafond du nombre d'exemplaires</label>
                <input type="number" class="form-control form-control-sm" id="pd-exemplaires-max"
                       name="{{ $_cleExemplairesMax }}" min="1" max="99"
                       value="{{ $_valExemplairesMax }}">
                <div class="bc-desc" style="margin-top:.25rem;">
                    Au-delà, la saisie d'une pièce est refusée. C'est un garde-fou contre la faute de
                    frappe — quatre photos d'identité, ce n'est pas quarante — et non une limite
                    technique : montez-le si votre dossier type l'exige.
                </div>
            </div>

            <div class="col-6" style="margin-top:.4rem;">
                <label class="bc-desc" for="pd-forme-defaut" style="display:block;margin-bottom:.2rem;">Forme proposée par défaut</label>
                <select class="form-control form-control-sm" id="pd-forme-defaut" name="{{ $_cleFormeDefaut }}">
                    @foreach($_formes as $_valeur => $_libelle)
                        <option value="{{ $_valeur }}" {{ $_valFormeDefaut === (string) $_valeur ? 'selected' : '' }}>{{ $_libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6" style="margin-top:.4rem;">
                <label class="bc-desc" for="pd-echeance-defaut" style="display:block;margin-bottom:.2rem;">Échéance proposée par défaut</label>
                <select class="form-control form-control-sm" id="pd-echeance-defaut" name="{{ $_cleEcheanceDefaut }}">
                    @foreach($_echeances as $_valeur => $_libelle)
                        <option value="{{ $_valeur }}" {{ $_valEcheanceDefaut === (string) $_valeur ? 'selected' : '' }}>{{ $_libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12" style="margin-top:.6rem;">
                <label class="bc-desc" for="pd-epuisement" style="display:block;margin-bottom:.2rem;">Quand le dépôt d'un étudiant est épuisé</label>
                <select class="form-control form-control-sm" id="pd-epuisement" name="{{ $_cleEpuisement }}">
                    @foreach($_epuisements as $_valeur => $_libelle)
                        <option value="{{ $_valeur }}" {{ $_valEpuisement === (string) $_valeur ? 'selected' : '' }}>{{ $_libelle }}</option>
                    @endforeach
                </select>
                <div class="bc-desc" style="margin-top:.25rem;">
                    Une pièce qui dure est déposée une fois et consommée à chaque inscription : six photos
                    couvrent trois années à deux par an. Ce réglage dit ce qui se passe quand il n'en reste plus.
                    <strong>Il ne produira d'effet qu'avec le suivi pièce par pièce</strong>, qui n'est pas encore livré.
                </div>
            </div>
            {{-- La case vit DANS le corps, et non dans le `bc-toggle` de droite :
                 là-bas elle aurait eu l'air de commander toute la carte, donc
                 d'éteindre les réglages du catalogue. --}}
            <div class="col-12" style="margin-top:.6rem;display:flex;align-items:center;gap:.6rem;">
                <label class="form-switch-modern" style="flex:0 0 auto;">
                    <input type="checkbox" name="{{ $_cleRestitution }}" value="1"
                           {{ \App\Helpers\SettingsHelper::get($_cleRestitution, '1') == '1' ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
                <div class="bc-desc" style="margin:0;">
                    <strong>Une inscription annulée rend ses exemplaires au dépôt.</strong>
                    Décoché, ils restent retenus jusqu'à la fin de l'année.
                    Comme le réglage ci-dessus, il attend le suivi pièce par pièce.
                </div>
            </div>
        </div>
    </div>
</div>
