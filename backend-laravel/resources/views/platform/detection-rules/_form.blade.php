{{-- Formulaire d'édition d'une règle de détection (admin) --}}
<form method="POST"
      action="{{ route('platform.detection-rules.update', $rule) }}"
      class="config-form"
      onsubmit="return validateRuleJson(this)">
    @csrf
    @method('PUT')

    <div class="config-form-row" style="flex-wrap:wrap;gap:10px;">
        <div class="config-field" style="flex:2;min-width:160px;">
            <label>Nom</label>
            <input class="form-control" type="text" name="name"
                   value="{{ $rule->name }}" required maxlength="120">
        </div>
        <div class="config-field" style="flex:1;min-width:130px;">
            <label>Type d'événement</label>
            <input class="form-control" type="text" name="event_type"
                   value="{{ $rule->event_type }}"
                   list="event-types-list" placeholder="ex: file_modified">
        </div>
    </div>

    <div class="config-form-row" style="flex-wrap:wrap;gap:10px;margin-top:8px;">
        <div class="config-field" style="flex:1;min-width:110px;">
            <label>Niveau de risque</label>
            <select class="form-control" name="risk_level">
                @foreach (['normal','suspect','high','critical'] as $lvl)
                    <option value="{{ $lvl }}" @selected($rule->risk_level === $lvl)>{{ $lvl }}</option>
                @endforeach
            </select>
        </div>
        <div class="config-field" style="flex:1;min-width:90px;">
            <label>Poids score</label>
            <input class="form-control" type="number" name="score_weight"
                   value="{{ $rule->score_weight }}" min="0" max="1000">
        </div>
        <div class="config-field" style="flex:1;min-width:90px;">
            <label>État</label>
            <select class="form-control" name="is_enabled">
                <option value="1" @selected($rule->is_enabled)>active</option>
                <option value="0" @selected(!$rule->is_enabled)>inactive</option>
            </select>
        </div>
    </div>

    <div class="config-form-row" style="flex-wrap:wrap;gap:10px;margin-top:8px;">
        <div class="config-field" style="flex:1;min-width:160px;">
            <label>Description</label>
            <input class="form-control" type="text" name="description"
                   value="{{ $rule->description }}" maxlength="500"
                   placeholder="Description courte">
        </div>
        <div class="config-field" style="flex:1;min-width:200px;">
            <label>Conditions (JSON)
                <span style="font-size:10px;color:var(--text-muted);font-weight:400;">laisser vide pour aucune</span>
            </label>
            <textarea class="form-control rule-conditions-json" name="conditions"
                      rows="2" style="font-family:monospace;font-size:11px;"
                      placeholder="null — aucune condition">{{ $rule->conditions ? json_encode($rule->conditions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '' }}</textarea>
        </div>
    </div>

    <div class="config-actions" style="margin-top:10px;display:flex;gap:8px;align-items:center;">
        <button class="action-btn primary" type="submit">
            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
        </button>
        <form method="POST"
              action="{{ route('platform.detection-rules.destroy', $rule) }}"
              style="display:contents;"
              onsubmit="return confirm('Supprimer la règle « {{ addslashes($rule->name) }} » ? Cette action est irréversible.')">
            @csrf @method('DELETE')
            <button type="submit" class="action-btn"
                style="color:#ef4444;border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.06);">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </form>
    </div>
</form>
