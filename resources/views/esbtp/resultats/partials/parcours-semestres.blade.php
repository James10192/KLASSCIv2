{{--
    Le parcours de l'année, en une ligne.

    Un étudiant passé en spécialité fait son semestre 1 en tronc commun et son
    semestre 2 en spécialité. Sans le dire, la page laissait croire que les deux
    semestres se lisaient sur la même classe — et choisir « Semestre 2 » en
    restant sur le tronc commun affichait zéro.

    Le semestre affiché est mis en avant ; l'autre reste lisible, pour qu'on
    voie d'où l'on vient et où l'on va.

    Cette bande ne paraît que là où « bts-journey », inclus juste au-dessus, ne
    dit rien : il ne sait lire que les parcours saisis, et le cas visé ici est
    précisément celui où personne ne l'a saisi. Deux bandes « parcours » qui se
    suivent seraient une de trop.
--}}
@php
    $semestres = $parcoursSemestres ?? [];
    $distinctes = collect($semestres)->pluck('id')->unique()->count();
@endphp

@if ($distinctes > 1 && empty($btsJourney))
    <div class="rp-parcours" role="group" aria-label="Parcours de l'année">
        <span class="rp-parcours__intro">
            <i class="fas fa-route"></i> Parcours
        </span>

        @php $rendues = 0; @endphp
        @foreach (['semestre1' => 'S1', 'semestre2' => 'S2'] as $cle => $court)
            @if (! empty($semestres[$cle]))
                @if ($rendues++ > 0)
                    <i class="fas fa-arrow-right rp-parcours__fleche" aria-hidden="true"></i>
                @endif
                <span class="rp-parcours__etape {{ ($periode ?? null) === $cle ? 'rp-parcours__etape--courante' : '' }}">
                    <span class="rp-parcours__semestre">{{ $court }}</span>
                    {{ $semestres[$cle]->name }}
                </span>
            @endif
        @endforeach
    </div>
@endif

@once
@push('styles')
<style>
    .rp-parcours {
        display: flex; align-items: center; flex-wrap: wrap;
        gap: .5rem;
        margin: 0 0 .85rem;
        padding: .6rem .85rem;
        border: 1px solid rgba(4, 83, 203, .18);
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(4, 83, 203, .04), rgba(59, 125, 219, .06));
        font-size: .82rem;
    }
    .rp-parcours__intro {
        display: inline-flex; align-items: center; gap: .4rem;
        font-size: .68rem; font-weight: 700; letter-spacing: .6px;
        text-transform: uppercase; color: #64748b;
    }
    .rp-parcours__intro i { color: #0453cb; }
    .rp-parcours__fleche { color: #94a3b8; font-size: .7rem; }
    .rp-parcours__etape {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .25rem .6rem;
        border-radius: 8px;
        background: #fff;
        border: 1px solid #e2e8f0;
        color: #475569;
    }
    .rp-parcours__etape--courante {
        border-color: rgba(4, 83, 203, .35);
        color: #0f172a;
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(4, 83, 203, .12);
    }
    .rp-parcours__semestre {
        font-size: .64rem; font-weight: 700; letter-spacing: .5px;
        padding: .1rem .35rem; border-radius: 5px;
        background: rgba(4, 83, 203, .10); color: #0453cb;
    }
</style>
@endpush
@endonce
