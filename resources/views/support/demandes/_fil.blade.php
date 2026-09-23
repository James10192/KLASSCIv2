@if(empty($messages))
    <p class="sd-vide">Le support n'a pas encore répondu. Vous serez prévenu ici dès que ce sera le cas.</p>
@else
    <div class="sd-fil">
        @foreach($messages as $m)
            <div class="sd-msg {{ ($m['auteur'] ?? '') === 'SUPPORT' ? 'sd-msg--support' : 'sd-msg--ecole' }}">
                <div class="sd-msg-auteur"><strong>{{ $m['nom'] ?? '' }}</strong> · {{ \App\Domain\Support\Services\DateDuMaster::afficher($m['le'] ?? null, 'd M Y à H:i') }}</div>
                <p>{{ $m['corps'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
@endif
