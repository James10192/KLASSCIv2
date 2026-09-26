{{-- Les creneaux du jour et leurs familles. Rendus par la page et par
     index(?fragment=1). Un creneau sans reservation n'apparait pas. --}}
@if($creneaux === [])
    <div class="adj-carte adj-vide">
        <i class="fas fa-calendar-day" aria-hidden="true"></i>
        <h3>Aucune famille attendue aujourd'hui</h3>
        <p>Aucun rendez-vous n'est posé sur un créneau du jour. Les familles qui réservent en ligne apparaissent ici le jour venu.</p>
        @can('inscriptions.rdv.view')
            <a class="adj-btn adj-btn--ghost" href="{{ route('esbtp.rendez-vous.index') }}"><i class="fas fa-calendar-week"></i>Planning des rendez-vous</a>
        @endcan
    </div>
@else
    @foreach($creneaux as $_bloc)
        @php $_c = $_bloc['creneau']; $_n = count($_bloc['familles']); @endphp
        <section class="adj-carte adj-creneau adj-creneau--{{ $_bloc['etat'] }}" data-adj-creneau>
            <header class="adj-creneau-tete">
                <span class="adj-heure">{{ $_c->heureDebutHi() }} – {{ $_c->heureFinHi() }}</span>
                <span class="adj-creneau-info">{{ $_n }} famille{{ $_n > 1 ? 's' : '' }}</span>
                <span class="adj-creneau-etat">{{ $_bloc['libelle'] }}</span>
            </header>
            <ul class="adj-familles">
                @foreach($_bloc['familles'] as $f)
                    @include('esbtp.admissions.aujourdhui._famille', ['f' => $f])
                @endforeach
            </ul>
        </section>
    @endforeach
@endif
