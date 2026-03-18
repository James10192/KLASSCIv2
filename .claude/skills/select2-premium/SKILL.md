---
name: select2-premium
description: Style Select2 dropdowns with premium design in KLASSCI Laravel/Blade project. Use when adding or styling Select2 selects, creating searchable dropdowns, rendering custom items with avatars/badges, or fixing Select2 in Bootstrap modals. Triggers on Select2 initialization, templateResult/templateSelection, or dropdown styling.
---

# Select2 Premium — KLASSCI Design System

Style Select2 4.1.0-rc.0 dropdowns to match the KLASSCI premium design system (Bootstrap 5 theme).

## Quick Init

```javascript
$('#mySelect').select2({
    theme: 'bootstrap-5',
    language: 'fr',
    placeholder: 'Rechercher...',
    allowClear: true,
    dropdownParent: $('#myModal'),  // REQUIRED inside Bootstrap modals
    width: 'resolve'
});
```

## KLASSCI Design Rules

- Use `--primary: #0453cb` gradient for highlighted options
- Border-radius: 10-14px on dropdowns, 8px on items
- No harsh borders — use subtle `#e2e8f0` or borderless with background
- Shadow: `0 12px 40px rgba(0,0,0,0.14)` on dropdowns
- Font-weight: 500 on options, 600 on selected
- Padding: `6px` inside dropdown for rounded item spacing

## Custom Rendering with Avatars

Use `templateResult` (dropdown items) and `templateSelection` (selected display).

**Security**: Return jQuery objects with `.text()` for user data — never concatenate HTML strings.

```javascript
function formatWithAvatar(data) {
    if (!data.id) return data.text;
    var $el = $('<div class="d-flex align-items-center gap-2"></div>');
    var initial = (data.text || '?')[0].toUpperCase();
    var $avatar = $('<div></div>').text(initial).css({
        width: '32px', height: '32px', borderRadius: '8px',
        background: 'linear-gradient(135deg, #0453cb, #5e91de)',
        color: 'white', display: 'flex', alignItems: 'center',
        justifyContent: 'center', fontWeight: '700', fontSize: '0.8rem'
    });
    var $info = $('<div class="flex-grow-1 min-w-0"></div>');
    $info.append($('<div class="fw-semibold text-truncate"></div>').text(data.text));
    if (data.subtitle) {
        $info.append($('<div class="small text-muted text-truncate"></div>').text(data.subtitle));
    }
    $el.append($avatar, $info);
    return $el;
}

$('#mySelect').select2({ templateResult: formatWithAvatar });
```

## Modal Integration (CRITICAL)

Bootstrap `.modal` has `overflow-y: auto` which **clips** any absolutely-positioned child. Setting `dropdownParent` to the modal element does NOT work — the dropdown renders inside the modal but is still clipped by its overflow.

**The only working solution**: render on `document.body` with a z-index above the modal.

```javascript
$('#mySelect').select2({
    dropdownParent: $(document.body),        // NOT the modal
    dropdownCssClass: 'my-select2-dropdown', // For z-index targeting
    theme: 'bootstrap-5',
    width: '100%'
});
```

```css
/* Must be above Bootstrap modal (1055) and backdrop (1050) */
.my-select2-dropdown.select2-dropdown {
    z-index: 1075 !important;
}
```

**Why `dropdownParent: $('#myModal')` fails**: Bootstrap's `.modal.show` has `overflow-y: auto` creating a scroll container that clips absolutely-positioned children. Even with `overflow: visible` on `.modal-content`, the parent `.modal` still clips.

**Alternative**: Use Choices.js instead of Select2 in modals (see annonces/create.blade.php for example — Choices.js renders inline with `position: 'bottom'` and `overflow: visible`).

## Key Gotchas

1. **Modal overflow clips Select2**: Always use `dropdownParent: $(document.body)` + z-index 1075+
2. **Init after visible**: Use `shown.bs.modal` event, not `show.bs.modal`
3. **Width 100%**: Set `width: 100%` on `<select>` CSS + `width: 'resolve'` in config
4. **Re-init after AJAX DOM replace**: `$('#sel').select2('destroy').select2({...})`
5. **Event namespace**: Use `change.select2` to avoid conflicts with other listeners
6. **String returns are auto-escaped**: Only jQuery objects render HTML
7. **Choices.js alternative**: For modals, consider Choices.js which renders inline without overflow issues

## Reference

For full CSS selectors, AJAX patterns, events, and programmatic control:
- See [references/select2-guide.md](references/select2-guide.md)
