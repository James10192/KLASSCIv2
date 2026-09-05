<?php

namespace App\Http\Requests\PiecesDossier;

use App\Services\CataloguePiecesDossier;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cocher une pièce au guichet.
 *
 * Tout est facultatif sauf le droit de le faire : le geste normal est une case
 * cochée, sans quantité saisie, sans date, sans fichier. Les trois champs ne
 * servent qu'à ceux qui veulent préciser.
 */
class CocherPieceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pieces_dossier.suivre') ?? false;
    }

    public function rules(): array
    {
        return [
            // Plafonnée par le même réglage que le catalogue : une école qui a
            // fixé son plafond à vingt ne doit pas pouvoir le contourner par la
            // porte du guichet.
            'quantite' => ['nullable', 'integer', 'min:1', 'max:'.app(CataloguePiecesDossier::class)->exemplairesMax()],

            // La validité court depuis la délivrance : un extrait délivré il y a
            // sept ans est déjà périmé le jour où on le dépose. La date est donc
            // utile, et jamais obligatoire — une école qui ne la note pas
            // retombe sur la date de dépôt.
            'date_delivrance' => ['nullable', 'date', 'before_or_equal:today'],

            'document_id' => ['nullable', 'integer', 'exists:esbtp_etudiant_documents,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantite.max' => "Le nombre d'exemplaires dépasse le plafond fixé par l'école.",
            'date_delivrance.before_or_equal' => 'Une pièce ne peut pas avoir été délivrée dans le futur.',
        ];
    }

    /** @return array{quantite?:int|null, date_delivrance?:string|null, document_id?:int|null} */
    public function options(): array
    {
        return [
            'quantite' => $this->filled('quantite') ? (int) $this->input('quantite') : null,
            'date_delivrance' => $this->input('date_delivrance') ?: null,
            'document_id' => $this->filled('document_id') ? (int) $this->input('document_id') : null,
        ];
    }
}
