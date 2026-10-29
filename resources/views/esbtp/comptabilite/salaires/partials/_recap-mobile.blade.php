{{--
    Récap paie — cartes-lignes MOBILE (shell m-*), maquette S['comptable:paie'].
    Reçoit $recap (lignes du contrôleur, une par enseignant) et $canCreate.
    Rendu au chargement de la page puis renvoyé par /salaires/data?mode=mobile
    (clé mobile_html) : le DOM est le même que le composant x-m.row.

    Une ligne = un enseignant sur le mois affiché : initiales, nom, heures par
    type (CM/TD/TP…) et régime, net à payer, puce de statut. Un mois déjà
    préparé mène au bulletin ; un mois « à préparer » ouvre la feuille de
    préparation (sous permission de création), sinon la ligne est inerte.
--}}
@php
    $rmFmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $rmFmtH = function ($v) {
        $h = (int) floor((float) $v);
        $m = (int) round(((float) $v - $h) * 60);
        return $h . ' h' . ($m > 0 ? sprintf(' %02d', $m) : '');
    };
    // Deux initiales, en sautant les civilités (Dr, Pr, M., Mme…).
    $rmInitiales = function (string $nom) {
        $lettres = '';
        foreach (preg_split('/[\s\-]+/u', trim($nom)) ?: [] as $mot) {
            if ($mot === '' || preg_match('/^(dr|pr|m|mr|mme|mlle)\.?$/iu', $mot)) {
                continue;
            }
            $lettres .= mb_substr($mot, 0, 1, 'UTF-8');
            if (mb_strlen($lettres, 'UTF-8') >= 2) {
                break;
            }
        }
        return $lettres !== '' ? $lettres : mb_substr($nom, 0, 2, 'UTF-8');
    };
    // Statut du mois → libellé lisible + ton de la puce (la couleur porte le sens, pas la décoration).
    $rmChips = [
        'a_preparer' => ['À préparer', 'mute'],
        'brouillon'  => ['À valider', 'warn'],
        'valide'     => ['Validé', 'info'],
        'paye'       => ['Payé', 'ok'],
        'annule'     => ['Annulé', 'bad'],
    ];
@endphp
@forelse($recap as $row)
    @php
        $rmCell = $row['months'][0] ?? null;
        $rmMulti = count($row['months']) > 1;
        $rmStatut = $rmMulti ? $row['statut'] : ($rmCell['statut'] ?? $row['statut']);
        [$rmChip, $rmTone] = $rmChips[$rmStatut] ?? [$rmStatut, 'mute'];
        $rmRegime = \App\Enums\TeacherRegime::tryFrom((string) ($row['regime'] ?? ''))?->label();
        $rmTypes = collect($row['types'])->map(fn ($t) => $t['type'] . ' ' . $rmFmtH($t['heures']))->implode(' · ');
        $rmSub = collect([
            $rmTypes !== '' ? $rmTypes : $rmFmtH($row['heures']),
            $rmRegime,
            $rmMulti ? $row['nb_mois'] . ' mois' : null,
        ])->filter()->implode(' · ');
        $rmEstime = collect($row['months'])->contains('estimation', true);
        $rmMontant = ($rmEstime ? '~ ' : '') . $rmFmt($row['net']) . ' FCFA';
        $rmHref = (! $rmMulti && $rmCell && ! empty($rmCell['has_bulletin']))
            ? route('esbtp.comptabilite.salaires.show', $rmCell['bulletin_id'])
            : null;
        $rmPreparer = $rmHref === null && ($canCreate ?? false) && $rmCell && empty($rmCell['has_bulletin']) && ! $rmMulti;
        // Objet JS sans guillemets doubles : la valeur part dans un attribut HTML délimité par des ".
        $rmDetail = sprintf('{id:%d,mois:%d,annee:%d}', (int) $row['teacher_id'], (int) ($rmCell['mois'] ?? 0), (int) ($rmCell['annee'] ?? 0));
    @endphp
    @if($rmHref)
        <x-m.row :href="$rmHref" :av="$rmInitiales($row['name'])" :title="$row['name']" :sub="$rmSub"
                 :amount="$rmMontant" :chip="$rmChip" :chip-type="$rmTone" />
    @elseif($rmPreparer)
        {{-- Pas de bulletin ce mois : la ligne ouvre la feuille « Préparer » pré-remplie.
             Évènement DOM (pas Alpine) : ce HTML arrive aussi par innerHTML après un filtre. --}}
        <x-m.row :av="$rmInitiales($row['name'])" :title="$row['name']" :sub="$rmSub"
                 :amount="$rmMontant" :chip="$rmChip" :chip-type="$rmTone"
                 role="button" tabindex="0"
                 onclick="window.dispatchEvent(new CustomEvent('paie:prepare-mobile',{detail:{{ $rmDetail }}}))"
                 onkeydown="if(event.key==='Enter'){event.preventDefault();window.dispatchEvent(new CustomEvent('paie:prepare-mobile',{detail:{{ $rmDetail }}}))}" />
    @else
        <x-m.row :av="$rmInitiales($row['name'])" :title="$row['name']" :sub="$rmSub"
                 :amount="$rmMontant" :chip="$rmChip" :chip-type="$rmTone" />
    @endif
@empty
    <x-m.empty icon="users" title="Aucune heure à payer ce mois" text="Les enseignants ayant des heures réalisées sur le mois apparaîtront ici." />
@endforelse
