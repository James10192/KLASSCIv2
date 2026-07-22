<div x-show="tab==='deliberation'" x-cloak>
    <div class="juy-card">
        <h2><i class="fas fa-scale-balanced"></i> Décisions ({{ $jury->decisions->count() }})</h2>

        <template x-if="decisions.length === 0">
            <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.88rem;">
                <i class="fas fa-bolt" style="font-size:2rem;color:#cbd5e1;display:block;margin-bottom:.5rem;"></i>
                Aucune décision. Utilisez « Appliquer décisions auto » pour générer les décisions automatiques.
            </div>
        </template>

        <template x-if="decisions.length > 0">
            <table class="juy-decision-table">
                <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Moyenne</th>
                    <th>Crédits</th>
                    <th>Auto</th>
                    <th>Décision</th>
                    <th>Mention</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                    <template x-for="d in decisions" :key="d.id">
                        <tr @click="openOverride(d)">
                            <td x-text="d.etudiant_name"></td>
                            <td x-text="d.moyenne_generale ? Number(d.moyenne_generale).toFixed(2) : '—'"></td>
                            <td><span x-text="d.credits_obtenus"></span> / <span x-text="d.credits_attendus"></span></td>
                            <td><span :class="'juy-dec-chip juy-dec-chip--'+d.decision_auto" x-text="d.decision_auto || '—'"></span></td>
                            <td>
                                <span :class="'juy-dec-chip juy-dec-chip--'+d.decision" x-text="d.decision"></span>
                                <template x-if="d.override_par_jury"><span class="juy-override-badge">Override</span></template>
                            </td>
                            <td x-text="d.mention || '—'"></td>
                            <td>
                                @can('lmd.jury.deliberate')
                                @if(!$jury->isLocked())
                                <button type="button" style="background:none;border:none;color:#0453cb;cursor:pointer;font-size:.85rem;" @click.stop="openOverride(d)">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                                @endif
                                @endcan
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>
    </div>
</div>
