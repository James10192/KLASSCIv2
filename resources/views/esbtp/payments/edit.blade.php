        <div class="mb-3">
            <label for="category_id" class="form-label">Catégorie de paiement</label>
            <select name="category_id" id="category_id" class="form-select" required>
                <option value="">-- Sélectionner --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id', $payment->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
