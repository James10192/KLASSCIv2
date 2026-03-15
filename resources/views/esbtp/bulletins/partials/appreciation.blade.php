@if($moyenne >= 16)
    Très Bien
@elseif($moyenne >= 14)
    Bien
@elseif($moyenne >= 12)
    Assez Bien
@elseif($moyenne >= 10)
    Passable
@else
    Insuffisant
@endif
