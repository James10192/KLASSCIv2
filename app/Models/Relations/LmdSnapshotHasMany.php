<?php

namespace App\Models\Relations;

use App\Models\ESBTPLMDBulletin;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * Relation enfant d'un bulletin LMD officiel.
 *
 * Les mutations unitaires passent par les événements des modèles UE/ECUE, mais
 * `relation()->delete()` et `relation()->update()` exécutent des requêtes bulk et
 * contournent ces événements. Ce relation object ferme précisément cette porte
 * lorsque le bulletin parent est déjà publié.
 */
class LmdSnapshotHasMany extends HasMany
{
    private function assertSnapshotMutable(): void
    {
        $parent = $this->getParent();

        if ($parent instanceof ESBTPLMDBulletin && $parent->is_published) {
            throw ValidationException::withMessages([
                'bulletin' => 'Ce bulletin LMD est publié et figé. Dépubliez-le avant de modifier ou supprimer ses résultats.',
            ]);
        }
    }

    public function delete()
    {
        $this->assertSnapshotMutable();

        return $this->getQuery()->delete();
    }

    public function update(array $values)
    {
        $this->assertSnapshotMutable();

        return $this->getQuery()->update($values);
    }
}
