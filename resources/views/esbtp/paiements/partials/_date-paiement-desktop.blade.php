<div class="pc-field pc-date-field"
     x-data="{
        editing: {{ old('date_paiement') && old('date_paiement') !== now()->toDateString() ? 'true' : 'false' }},
        date: @js(old('date_paiement', now()->toDateString())),
        today: @js(now()->toDateString()),
        yesterday: @js(now()->subDay()->toDateString()),
        formatDate(value) {
            if (!value) return 'Date non définie';
            const d = new Date(value + 'T12:00:00');
            return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }).format(d);
        },
        openPicker() {
            this.editing = true;
            this.$nextTick(() => {
                const el = this.$refs.dateInput;
                if (!el) return;
                if (typeof el.showPicker === 'function') {
                    try { el.showPicker(); return; } catch (e) {}
                }
                el.focus();
            });
        }
     }">
    <label for="date_paiement" class="pc-label">Date de paiement <span class="pc-req">*</span></label>

    <div class="pc-date-summary" x-show="!editing">
        <div class="pc-date-main">
            <span class="pc-date-icon"><i class="fas fa-calendar-day"></i></span>
            <span class="pc-date-copy">
                <b x-text="date === today ? 'Aujourd’hui' : formatDate(date)"></b>
                <span x-text="date === today ? formatDate(date) : 'Date réelle du versement'"></span>
            </span>
        </div>
        <button type="button" class="pc-date-edit" x-on:click="openPicker()">Modifier</button>
    </div>

    <div class="pc-date-editor" x-show="editing" x-cloak>
        <input type="date" name="date_paiement" id="date_paiement" class="form-control"
               x-ref="dateInput" x-model="date" x-bind:max="today" required>
        <div class="pc-date-actions">
            <button type="button" class="pc-date-chip" x-on:click="date = today; editing = false">Aujourd’hui</button>
            <button type="button" class="pc-date-chip" x-on:click="date = yesterday">Hier</button>
            <button type="button" class="pc-date-chip" x-on:click="editing = false">Terminé</button>
        </div>
    </div>
</div>
