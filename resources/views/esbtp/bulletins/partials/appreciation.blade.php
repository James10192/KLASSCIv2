@if($moyenne >= 16)
    Excellent
@elseif($moyenne >= 14)
    Très Bien
@elseif($moyenne >= 12)
    Bien
@elseif($moyenne >= 10)
    Assez Bien
@elseif($moyenne >= 8)
    Passable
@else
    Insuffisant
@endif
