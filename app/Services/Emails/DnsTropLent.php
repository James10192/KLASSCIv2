<?php

namespace App\Services\Emails;

use RuntimeException;

/**
 * Le resolveur n'a pas repondu dans le delai : la verification MX est
 * suspendue un moment plutot que de faire attendre chaque formulaire.
 */
class DnsTropLent extends RuntimeException {}
