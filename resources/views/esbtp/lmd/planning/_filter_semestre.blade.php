{{-- Filter Semestre — server-side cascade based on niveau_id.
     The list of semestres is computed in the controller (availableSemestres)
     so this partial can be re-rendered via AJAX without any Alpine option mutation. --}}
<x-au-select
    name="semestre"
    icon="fa-calendar-alt"
    placeholder="Tous semestres"
    :value="$filters['semestre']"
    :options="collect($availableSemestres)->mapWithKeys(fn ($s) => [$s => 'Semestre ' . $s])->all()"
    x-on:change="reload($event.target.value, 'semestre')" />
