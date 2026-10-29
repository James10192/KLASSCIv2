<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * La selection depasse ce qu'un PDF peut rendre.
 *
 * DomPDF construit tout le document en memoire : au-dela de quelques centaines
 * de lignes il epuise la limite PHP et le processus meurt. Une mort par
 * epuisement memoire n'est pas rattrapable — aucun `catch` ne s'execute, le
 * `Log::error` du controleur non plus. L'utilisateur ne recoit qu'une page
 * blanche 500, et le journal du serveur reste vide : il n'a donc meme pas de
 * quoi comprendre ce qui s'est passe.
 *
 * On compte donc AVANT de rendre, et on refuse proprement. Un export qui dit
 * pourquoi il refuse se contourne (affiner les filtres, ou prendre l'Excel qui
 * n'a pas cette limite) ; un 500 muet, non.
 */
class ExportPdfTropVolumineuxException extends RuntimeException
{
}
