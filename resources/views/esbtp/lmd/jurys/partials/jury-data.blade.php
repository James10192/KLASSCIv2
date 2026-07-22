@php
    $decisionsData = $jury->decisions->map(function ($d) {
        return [
            'id' => $d->id,
            'etudiant_id' => $d->etudiant_id,
            'etudiant_name' => trim(($d->etudiant?->nom ?? '') . ' ' . ($d->etudiant?->prenoms ?? '')) ?: ('Étudiant #' . $d->etudiant_id),
            'decision_auto' => $d->decision_auto,
            'decision' => $d->decision,
            'mention' => $d->mention,
            'override_par_jury' => (bool) $d->override_par_jury,
            'motif_override' => $d->motif_override,
            'vote_resultat' => $d->vote_resultat,
            'moyenne_generale' => $d->moyenne_generale,
            'credits_obtenus' => (int) $d->credits_obtenus,
            'credits_attendus' => (int) $d->credits_attendus,
        ];
    })->values();

    $membresData = $jury->membres->map(function ($m) use ($jury) {
        return [
            'id' => $m->id,
            'user_id' => $m->user_id,
            'user_name' => $m->user?->name ?: 'Utilisateur #' . $m->user_id,
            'role' => $m->role,
            'present' => (bool) $m->present,
            'has_signed' => $m->hasSigned(),
            'can_sign' => $m->canBeSignedBy((int) auth()->id()) && !$m->hasSigned() && !$jury->isLocked(),
        ];
    })->values();
@endphp
