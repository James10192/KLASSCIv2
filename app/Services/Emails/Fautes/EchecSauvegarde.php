<?php

namespace App\Services\Emails\Fautes;

use RuntimeException;

/** La sauvegarde prealable n'a pas pu etre ecrite et relue : rien n'est corrige. */
class EchecSauvegarde extends RuntimeException {}
