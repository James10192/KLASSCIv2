{{--
    Le résumé d'activité d'une personne, posé sur sa fiche de profil (ar-*).

    Des faits de l'année universitaire en cours, jamais une note. Le détail,
    preuves à l'appui, est sur la page d'activité de la personne.

    @param array|null $activite   ActiviteDuPersonnel::resume()
    @param int|null   $userId
--}}
@php
    $_ar = $activite ?? null;
    $_arUser = $userId ?? ($_ar['id'] ?? null);
    $_arPeutVoir = $_arUser && auth()->check()
        && (auth()->user()->can('performance.view_all') || ((int) auth()->id() === (int) $_arUser && auth()->user()->can('performance.view')));
    $_arFaits = [];
    if ($_ar) {
        if ($_ar['seances_prevues'] > 0) {
            $_arFaits[] = ['fa-chalkboard-user', $_ar['seances_tenues'].' / '.$_ar['seances_prevues'], 'séances tenues'.($_ar['retards'] ? ' · '.$_ar['retards'].' retard(s)' : '')];
        }
        if ($_ar['notes_attendues'] > 0) {
            $_arFaits[] = ['fa-pen-to-square', (int) floor($_ar['notes_recues'] / $_ar['notes_attendues'] * 100).' %', 'des notes rendues sur '.$_ar['evaluations'].' évaluation(s)'];
        }
        if ($_ar['paiements_saisis'] > 0 || $_ar['paiements_valides'] > 0) {
            $_arFaits[] = ['fa-cash-register', number_format($_ar['paiements_saisis'], 0, ',', ' '), 'paiements saisis · '.$_ar['paiements_valides'].' validés'];
        }
        if ($_ar['paiements_en_attente'] > 0) {
            $_arFaits[] = ['fa-hourglass-half', (string) $_ar['paiements_en_attente'], 'paiement(s) saisi(s) toujours en attente de validation'];
        }
        if ($_ar['inscriptions'] > 0) {
            $_arFaits[] = ['fa-user-plus', number_format($_ar['inscriptions'], 0, ',', ' '), 'inscriptions saisies'];
        }
    }
@endphp

<style>
    .ar-bloc { display: grid; gap: .75rem; }
    .ar-faits { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .75rem; }
    .ar-fait { display: flex; align-items: center; gap: .7rem; padding: .85rem 1rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; }
    .ar-fait i { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(4,83,203,.08); color: #0453cb; flex-shrink: 0; }
    .ar-fait strong { display: block; font-size: 1.1rem; color: #0f172a; font-variant-numeric: tabular-nums; }
    .ar-fait span { font-size: .76rem; color: #64748b; }
    .ar-pied { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; font-size: .78rem; color: #64748b; }
    .ar-lien { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .9rem; border-radius: 10px; border: 1px solid #cfdcf2; color: #0453cb; font-weight: 600; font-size: .8rem; text-decoration: none; background: #fff; }
    .ar-lien:hover { background: #f1f6fe; color: #033a8e; }
    .ar-vide { padding: 1.25rem; text-align: center; border: 1px dashed #d7e1ee; border-radius: 12px; background: #f8fafc; color: #64748b; font-size: .84rem; }
</style>

<div class="ar-bloc">
    @if(empty($_arFaits))
        <div class="ar-vide">Aucune séance, évaluation, inscription ni paiement enregistré à ce nom cette année universitaire.</div>
    @else
        <div class="ar-faits">
            @foreach($_arFaits as [$_arIcone, $_arValeur, $_arLibelle])
                <div class="ar-fait"><i class="fas {{ $_arIcone }}"></i><div><strong>{{ $_arValeur }}</strong><span>{{ $_arLibelle }}</span></div></div>
            @endforeach
        </div>
    @endif
    <div class="ar-pied">
        <span>Année universitaire en cours. Des faits enregistrés dans KLASSCI, sans note.</span>
        @if($_arPeutVoir)
            <a class="ar-lien" href="{{ route('esbtp.personnel.performance.show', ['user' => $_arUser]) }}"><i class="fas fa-list-check"></i>Voir le détail</a>
        @endif
    </div>
</div>
