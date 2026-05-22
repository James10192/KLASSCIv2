# Rule: AJAX no-reload — UI premium KLASSCI

## Quand s'active

Cette rule s'active automatiquement quand tu :
- Crées ou modifies une vue Blade premium (namespace `*-` avec hero + cards + KPIs)
- Implémentes une action utilisateur (button, form submit, link click) sur une UI premium
- Travailles sur les vues `/esbtp/lmd/jurys/*`, `/esbtp/lmd/rattrapage/*`, `/esbtp/examens/*`, `/esbtp/emploi-temps/bulk-edit`
- Réponds à un user qui demande "no-reload" / "live" / "AJAX" / "sans rechargement"

## Règle fondamentale

**Aucune action utilisateur sur une UI premium KLASSCI ne doit déclencher un page reload.**

Toutes les mutations passent par :
- `fetch()` avec JSON request/response
- Alpine `x-data` qui maintient le state local
- `dispatchEvent(new CustomEvent('...', { detail: {...} }))` pour communiquer entre composants
- Toast premium pour feedback (composant `<x-toast>`)
- `:disabled` ou `wire:loading` pour UX feedback pendant requête

## Pourquoi cette rule existe

Marcel a explicitement demandé en Iteration 4 (depth=7+) du chantier emploi-temps LMD :
> "fonctionnalité no reload page AJAX qui doit être aussi dans la rule redesign premium aussi même"

UX premium = réactif, fluide, sans rupture de contexte (scroll préservé, modal restée ouverte, sélection conservée).

## Pattern canonique

### Structure HTML/Blade

```blade
<div x-data="juryDeliberation()" x-init="init()">
    {{-- KPIs live (auto-updated via event) --}}
    <div class="juy-kpis">
        <div class="juy-kpi" x-text="kpis.admis">{{ $kpis['admis'] }}</div>
        <div class="juy-kpi" x-text="kpis.rattrapage">{{ $kpis['rattrapage'] }}</div>
    </div>

    {{-- Tableau étudiants --}}
    <table>
        <template x-for="etudiant in etudiants" :key="etudiant.id">
            <tr @click="openDecisionModal(etudiant)">
                <td x-text="etudiant.nom"></td>
                <td x-text="etudiant.decision"></td>
            </tr>
        </template>
    </table>

    {{-- Modal AJAX-driven --}}
    <div x-show="modalOpen" x-cloak @keydown.escape.window="closeModal()" class="juy-modal">
        <form @submit.prevent="saveDecision()">
            <select x-model="form.decision">...</select>
            <textarea x-model="form.motif" required></textarea>
            <button :disabled="saving" type="submit">
                <span x-show="!saving">Enregistrer</span>
                <span x-show="saving" x-cloak>Enregistrement…</span>
            </button>
        </form>
    </div>
</div>
```

### Factory Alpine

```js
function juryDeliberation() {
    return {
        // State
        etudiants: @json($etudiants),
        kpis: @json($kpis),
        modalOpen: false,
        saving: false,
        form: { decision: '', motif: '', etudiantId: null },

        // Init
        init() {
            window.addEventListener('jury:decision-updated', (ev) => {
                this.handleDecisionUpdate(ev.detail);
            });
        },

        // Actions
        openDecisionModal(etudiant) {
            this.form = {
                etudiantId: etudiant.id,
                decision: etudiant.decision,
                motif: '',
            };
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
        },

        async saveDecision() {
            this.saving = true;
            try {
                const res = await fetch(`/esbtp/lmd/jurys/{{ $jury->id }}/decisions/${this.form.etudiantId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });
                if (!res.ok) {
                    const errBody = await res.json().catch(() => ({}));
                    throw new Error(errBody.message || `Erreur HTTP ${res.status}`);
                }
                const data = await res.json();

                // Dispatch event → autres composants se mettent à jour
                window.dispatchEvent(new CustomEvent('jury:decision-updated', {
                    detail: { etudiantId: this.form.etudiantId, etudiant: data.etudiant }
                }));

                this.closeModal();
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: 'Décision enregistrée.' }
                }));
            } catch (err) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'error', message: err.message }
                }));
            } finally {
                this.saving = false;
            }
        },

        handleDecisionUpdate(detail) {
            const idx = this.etudiants.findIndex(e => e.id === detail.etudiantId);
            if (idx !== -1) {
                this.etudiants[idx] = detail.etudiant;
            }
            this.refreshKpis();
        },

        async refreshKpis() {
            const res = await fetch(`/esbtp/lmd/jurys/{{ $jury->id }}/kpis`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                this.kpis = await res.json();
            }
        }
    };
}
```

### Backend controller

```php
// PATCH /esbtp/lmd/jurys/{jury}/decisions/{etudiant}
public function updateDecision(JuryDecisionRequest $request, ESBTPLMDJury $jury, ESBTPEtudiant $etudiant): JsonResponse
{
    $this->authorize('deliberate', $jury);

    $decision = app(JuryDeliberationService::class)->overrideDecision(
        $jury, $etudiant,
        $request->validated('decision'),
        $request->validated('motif'),
        $request->validated('vote_resultat')
    );

    return response()->json([
        'success' => true,
        'etudiant' => $this->serializeEtudiant($etudiant, $decision),
    ]);
}

// GET /esbtp/lmd/jurys/{jury}/kpis
public function kpis(ESBTPLMDJury $jury): JsonResponse
{
    return response()->json([
        'admis' => $jury->decisions()->where('decision', 'admis')->count(),
        'rattrapage' => $jury->decisions()->where('decision', 'admission_rattrapage')->count(),
        // ... etc
    ]);
}
```

## Exceptions tolérées (rares — DOCUMENTER)

1. **Création initiale d'une entité majeure** (createUser, createInscription, createExamen) où reset état complet justifie reload
2. **Actions critiques sécurité** (logout, force re-login après MDP changé)
3. **Migration page** (changement de scope académique majeur, ex: changer d'année universitaire active)
4. **Submit final de jury → publication** : reload acceptable car change le statut majeur de la page

Si tu rends un reload, ajouter un commentaire :
```php
// EXCEPTION ajax-no-reload-premium : reload justifié car publication jury = change tout le contexte
return redirect()->route('esbtp.lmd.jurys.show', $jury);
```

## Anti-patterns à bloquer en review

1. ❌ **`window.location.reload()`** dans du JS (sauf cas exceptionnels documentés)
2. ❌ **`<form action="..." method="POST">`** sans `@submit.prevent` Alpine — soumission classique = reload
3. ❌ **`redirect()->back()` Laravel** après une mutation simple (édition row, suppression item) — préférer `response()->json([...])`
4. ❌ **Page-refresh après suppression** d'1 row dans une table → refetch + replace DOM uniquement
5. ❌ **`<a href="?param=newvalue">`** pour update simple → utiliser `pushState` + AJAX fetch
6. ❌ **Bouton "Actualiser"** visible utilisateur — l'UI doit se refresh automatiquement via dispatch events
7. ❌ **`return redirect()->route(...)`** sur un endpoint AJAX qui devrait retourner JSON
8. ❌ Form submit Blade legacy non-Alpine sur une page premium (mélange paradigmes)

## Audit avant commit

```bash
# Détecter les window.location.reload() dans les vues premium
grep -rn "window.location.reload" resources/views/esbtp/

# Détecter les forms classiques sans @submit.prevent dans les vues premium
grep -rEn "method=['\"]POST['\"]" resources/views/esbtp/lmd/ | grep -v "@submit.prevent"
```

## Composant `<x-toast>` standard

Toute UI premium doit utiliser le composant Toast existant (ou créer s'il manque) :

```blade
<x-toast />  {{-- inclut une seule fois dans le layout principal --}}

<!-- Dans Alpine -->
<script>
window.dispatchEvent(new CustomEvent('toast', {
    detail: { type: 'success', message: 'Action réussie.' }
    // type: 'success' | 'error' | 'warning' | 'info'
}));
</script>
```

## Voir aussi

- Memory projet : `feedback_no_reload_ajax_premium.md`
- Rule projet : `premium-redesign.md` (section AJAX no-reload OBLIGATOIRE)
- Rule projet : `jury-deliberation-uemoa.md`
- Rule projet : `blade-alpine-pitfalls.md`
- Rule projet : `feature-delivery-methodology.md` (phase 11 visual-check)
- Master plan : `docs/MASTER-PLAN-emploi-temps-lmd-unification.md`
