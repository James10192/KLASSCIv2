@if(($settings['bulletin_show_edition_date'] ?? '1') == '1')
    <div class="edition-footer">Édition du : {{ $date_edition }}</div>
@endif
<div class="edition-authenticity">{{ \App\Services\BulletinMentionResolver::authenticityText() }}</div>
