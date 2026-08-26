@php
    $mentionRules = \App\Services\BulletinMentionResolver::loadRules();
    $authenticityText = \App\Helpers\SettingsHelper::get(
        \App\Services\BulletinMentionResolver::AUTH_TEXT_KEY,
        \App\Services\BulletinMentionResolver::AUTH_TEXT_DEFAULT
    );
@endphp

<p class="section-description" style="margin-bottom: 12px;">
    Les mentions se cumulent : un 14/20 peut avoir Tableau d'honneur + Encouragements.
    Min inclus, max exclus. Max vide = pas de plafond.
</p>

<div class="bc-grid bc-grid-3">
    <div class="bc-card">
        <div class="bc-icon"><i class="fas fa-calculator"></i></div>
        <div class="bc-body"><div class="bc-label">Calcul Auto</div></div>
        <div class="bc-toggle">
            <label class="form-switch-modern">
                <input type="checkbox" name="bulletin_auto_calculate_mention" value="1"
                       {{ \App\Helpers\SettingsHelper::get('bulletin_auto_calculate_mention', '1') == '1' ? 'checked' : '' }}>
                <span class="slider"></span>
            </label>
        </div>
    </div>
</div>

<div id="mention-rules" style="margin-top: 12px;">
    @foreach($mentionRules as $index => $rule)
        @include('esbtp.settings.partials.mention-rule-row', ['index' => $index, 'rule' => $rule])
    @endforeach
</div>

<template id="mention-rule-template">
    @include('esbtp.settings.partials.mention-rule-row', ['index' => '__INDEX__', 'rule' => [
        'key' => '',
        'label' => '',
        'min' => null,
        'max' => null,
        'source' => 'moyenne',
        'enabled' => true,
    ]])
</template>

<button type="button" class="btn btn-outline-primary btn-sm" id="mention-rule-add" style="margin-top: 8px;">
    Ajouter une mention
</button>

<div class="bc-input-row" style="margin-top: 16px;">
    <div class="bc-icon"><i class="fas fa-stamp"></i></div>
    <div class="bc-body">
        <div class="bc-label">Texte anti-duplicata (pied de bulletin)</div>
        <input type="text" class="form-control form-control-modern"
               name="setting_bulletin_authenticity_text"
               value="{{ $authenticityText }}">
    </div>
</div>

<script>
document.getElementById('mention-rule-add')?.addEventListener('click', function () {
    const list = document.getElementById('mention-rules');
    const template = document.getElementById('mention-rule-template');
    if (!list || !template) return;
    const index = Date.now();
    const wrap = document.createElement('div');
    wrap.innerHTML = template.innerHTML.split('__INDEX__').join(String(index));
    list.appendChild(wrap.firstElementChild);
});
document.getElementById('mention-rules')?.addEventListener('click', function (event) {
    const button = event.target.closest('[data-mention-remove]');
    if (!button) return;
    button.closest('[data-mention-row]')?.remove();
});
</script>
