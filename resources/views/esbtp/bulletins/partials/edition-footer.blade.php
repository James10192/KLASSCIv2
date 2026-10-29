@php
    $editionLine = \App\Services\BulletinMentionResolver::editionLine($date_edition ?? null);
@endphp
@if(($settings['bulletin_show_edition_date'] ?? '1') == '1' && $editionLine !== '')
    <div class="edition-footer">{{ $editionLine }}</div>
@endif
<div class="edition-authenticity">{{ \App\Services\BulletinMentionResolver::authenticityText() }}</div>
