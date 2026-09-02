@can('inscriptions.in_kind.mark')
    @php
        $fournitures = collect($feeCategoriesWithRules ?? [])
            ->filter(fn ($item) => !empty($item['category']->accepts_in_kind) || !empty($item['satisfied_in_kind']));
    @endphp
    @if($fournitures->isNotEmpty())
        <div class="is-card">
            <div class="is-card-body">
                <div class="is-section-header">
                    <div class="is-section-icon"><i class="fas fa-box"></i></div>
                    <div class="is-section-title">Fournitures à déposer</div>
                </div>
                @foreach($fournitures as $item)
                    <div class="is-info-row" style="flex-direction:row;align-items:center;justify-content:space-between;gap:12px;">
                        <span class="is-info-val">{{ $item['category']->name }}</span>
                        @if(!empty($item['satisfied_in_kind']))
                            <span class="is-badge success"><i class="fas fa-check"></i> Déposé</span>
                        @elseif(!empty($item['can_mark_in_kind']))
                            <form method="POST" action="{{ route('esbtp.inscriptions.in-kind-deposits.store', [$inscription, $item['category']]) }}">
                                @csrf
                                <button type="submit" class="btn-acasi primary btn-sm">Marquer déposé</button>
                            </form>
                        @else
                            <span class="is-badge secondary">Non déposé</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endcan
