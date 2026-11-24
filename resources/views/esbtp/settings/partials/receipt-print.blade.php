@php
    $receiptFields = \App\Support\ReceiptPrintSettings::fields();
    $receiptValues = \App\Support\ReceiptPrintSettings::read();
@endphp
<div class="settings-section" id="receipt-print-settings" x-data="{
    fields: @js($receiptFields), values: @js($receiptValues), busy: false, message: '', url: null,
    preset(mode) {
        for (const [key, field] of Object.entries(this.fields)) {
            let value = field.default;
            if (key.startsWith('font_') && mode === 'large') value = Math.min(field.max, value + 1);
            if (key.startsWith('font_') && mode === 'compact') value = Math.max(field.min, value - 0.5);
            this.values[key] = value;
        }
    },
    async save() {
        this.busy = true; this.message = '';
        try {
            const form = new FormData();
            form.set('_token', document.querySelector('input[name=_token]').value);
            for (const [key, value] of Object.entries(this.values)) form.set('setting_receipt_print_' + key, value);
            const response = await fetch(@js(route('esbtp.settings.receipt-print')), {method: 'POST', body: form, headers: {'Accept': 'application/json'}});
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Enregistrement impossible.');
            this.message = result.message;
            window.dispatchEvent(new CustomEvent('toast', {detail: {type: 'success', message: this.message}}));
        } catch (e) { this.message = e.message; } finally { this.busy = false; }
    },
    async preview() {
        this.busy = true; this.message = '';
        try {
            const form = new FormData();
            form.set('_token', document.querySelector('input[name=_token]').value);
            form.set('receipt_preview', '1');
            for (const [key, value] of Object.entries(this.values)) form.set('setting_receipt_print_' + key, value);
            const response = await fetch(@js(route('esbtp.settings.pdf-preview')), {method: 'POST', body: form, headers: {'Accept': 'application/json'}});
            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || 'Aperçu indisponible.');
            }
            if (this.url) URL.revokeObjectURL(this.url);
            this.url = URL.createObjectURL(await response.blob());
            const pages = Number(response.headers.get('X-Receipt-Pages'));
            this.message = pages === 1 ? '1 page A4 — deux exemplaires.' : pages + ' pages : réduisez les réglages avant enregistrement.';
        } catch (e) { this.message = e.message; } finally { this.busy = false; }
    },
    destroy() { if (this.url) URL.revokeObjectURL(this.url); }
}">
    <div class="section-header">
        <div class="section-icon pdf"><i class="fas fa-print"></i></div>
        <div><h3 class="section-title">Impression des reçus A4</h3>
            <p class="section-description">Deux exemplaires, tailles physiques en points. Ces réglages ne changent pas les bulletins ni les écritures comptables.</p></div>
    </div>
    <div class="d-flex flex-wrap gap-2 my-3">
        <button type="button" class="btn btn-outline-primary" @click="preset('compact')">Compact</button>
        <button type="button" class="btn btn-outline-primary" @click="preset('default')">Équilibré / défauts</button>
        <button type="button" class="btn btn-outline-primary" @click="preset('large')">Grande lisibilité</button>
    </div>
    @foreach (['font_' => 'Typographie', 'layout' => 'Marges, logo et espacements'] as $prefix => $heading)
        <details class="mb-3" @if($loop->first) open @endif>
            <summary class="fw-semibold mb-2">{{ $heading }}</summary>
            <div class="settings-grid-2" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px">
                @foreach ($receiptFields as $key => $field)
                    @continue(($prefix === 'font_') !== str_starts_with($key, 'font_'))
                    <div>
                        <label class="form-label-modern" for="receipt-{{ $key }}">{{ $field['label'] }} ({{ $field['unit'] }})</label>
                        <input id="receipt-{{ $key }}" type="number" class="form-control form-control-modern"
                               value="{{ $receiptValues[$key] }}"
                               min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="{{ $field['step'] }}"
                               x-model.number="values.{{ $key }}">
                    </div>
                @endforeach
            </div>
        </details>
    @endforeach
    <button type="button" class="btn btn-outline-primary" @click="preview()" :disabled="busy">
        <i class="fas fa-eye"></i> <span x-text="busy ? 'Génération…' : 'Tester le PDF avant enregistrement'"></span>
    </button>
    <button type="button" class="btn btn-primary" @click="save()" :disabled="busy"><i class="fas fa-save"></i> Enregistrer les réglages du reçu</button>
    <p class="mt-2" role="status" x-text="message"></p>
    <template x-if="url"><iframe :src="url" title="Aperçu PDF de démonstration" style="width:100%;height:650px;border:1px solid #cbd5e1"></iframe></template>
    <small class="text-muted">L’aperçu utilise des données fictives. Enregistrez avec le bouton dédié, sans modifier les autres paramètres.</small>
</div>
