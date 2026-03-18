# Select2 4.1.0-rc.0 — Complete Reference

## CSS Selectors

```css
.select2-container { }                    /* Root wrapper */
.select2-container--open { }              /* Dropdown visible */
.select2-container--focus { }             /* Has focus */
.select2-container--disabled { }
.select2-container--below { }             /* Dropdown below */
.select2-container--above { }             /* Dropdown above */

.select2-selection { }                    /* Selection box */
.select2-selection--single { }
.select2-selection--multiple { }
.select2-selection__rendered { }          /* Selected item text */
.select2-selection__placeholder { }       /* Placeholder */
.select2-selection__clear { }             /* Clear (x) button */
.select2-selection__arrow { }             /* Arrow icon */

.select2-dropdown { }                     /* Dropdown menu */
.select2-dropdown--above { }
.select2-dropdown--below { }

.select2-search { }
.select2-search__field { }               /* Search input */

.select2-results { }
.select2-results__options { }            /* Scrollable area */
.select2-results__option { }             /* Each option */
.select2-results__option--selected { }
.select2-results__option--highlighted { } /* Hover/active */
.select2-results__option--disabled { }
.select2-results__group { }              /* Group header */
.select2-results__message { }            /* No results/loading */
```

## Bootstrap 5 Theme Overrides

```css
/* Match KLASSCI design system */
.select2-container--bootstrap-5 .select2-selection {
    border: 2px solid var(--border, #e2e8f0);
    border-radius: var(--radius-medium, 10px);
    min-height: 42px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.select2-container--bootstrap-5.select2-container--focus .select2-selection {
    border-color: var(--primary, #0453cb);
    box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
}

.select2-container--bootstrap-5 .select2-dropdown {
    border: none;
    border-radius: 14px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.14);
    padding: 6px;
    margin-top: 6px;
}

.select2-container--bootstrap-5 .select2-results__option {
    padding: 0.7rem 1rem;
    border-radius: 8px;
    margin-bottom: 2px;
    font-weight: 500;
    transition: all 0.15s ease;
}

.select2-container--bootstrap-5 .select2-results__option--highlighted {
    background: linear-gradient(135deg, #0453cb, #1e6fe0);
    color: white;
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.6rem 0.85rem;
    background: #fafbfd;
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field:focus {
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
}
```

## Configuration Options

```javascript
$('#mySelect').select2({
    theme: 'bootstrap-5',
    placeholder: 'Rechercher...',
    allowClear: true,
    language: 'fr',
    width: 'resolve',                    // Match element width
    dropdownParent: $('#myModal'),        // REQUIRED for modals
    minimumInputLength: 0,               // Min chars before search
    maximumSelectionLength: 5,           // Multi-select limit
    closeOnSelect: true,
    containerCssClass: 'my-container',   // Custom class on container
    dropdownCssClass: 'my-dropdown',     // Custom class on dropdown
    selectionCssClass: 'my-selection',   // Custom class on selection
    templateResult: formatOption,         // Custom dropdown rendering
    templateSelection: formatSelected,   // Custom selection rendering
    ajax: { /* see AJAX section */ }
});
```

## templateResult — Custom Dropdown Items

Return jQuery object for custom HTML (not auto-escaped). Use `.text()` for user data.

```javascript
function formatTeacher(data) {
    if (!data.id) return data.text;  // placeholder/group header

    var $el = $(
        '<div class="d-flex align-items-center gap-2 py-1">' +
            '<div class="avatar-sm"></div>' +
            '<div class="flex-grow-1 min-w-0">' +
                '<div class="fw-600 text-truncate"></div>' +
                '<div class="small text-muted text-truncate"></div>' +
            '</div>' +
            '<span class="badge"></span>' +
        '</div>'
    );

    var initial = (data.text || '?')[0].toUpperCase();
    $el.find('.avatar-sm').css({
        width: '32px', height: '32px', borderRadius: '8px',
        background: 'linear-gradient(135deg, #0453cb, #5e91de)',
        color: 'white', display: 'flex', alignItems: 'center',
        justifyContent: 'center', fontWeight: '700', fontSize: '0.8rem',
        flexShrink: '0'
    }).text(initial);

    $el.find('.fw-600').text(data.text);
    $el.find('.small').text(data.specialization || '');

    if (data.badge) {
        $el.find('.badge').text(data.badge)
            .addClass('bg-success-subtle text-success');
    } else {
        $el.find('.badge').remove();
    }

    return $el;
}
```

## templateSelection — Custom Selected Display

```javascript
function formatTeacherSelection(data) {
    if (!data.id) return data.text;
    return data.text;  // or return jQuery object for custom rendering
}
```

## AJAX Loading

```javascript
$('#mySelect').select2({
    ajax: {
        url: '/api/search',
        dataType: 'json',
        delay: 250,                // Debounce
        cache: true,
        data: function(params) {
            return {
                q: params.term,
                page: params.page || 1
            };
        },
        processResults: function(data, params) {
            return {
                results: data.items.map(item => ({
                    id: item.id,
                    text: item.name,
                    specialization: item.spec
                })),
                pagination: { more: data.has_more }
            };
        }
    },
    minimumInputLength: 1,
    templateResult: formatTeacher
});
```

## Events

```javascript
$('#mySelect').on('select2:select', function(e) {
    console.log(e.params.data);  // { id, text, element, ... }
});

$('#mySelect').on('select2:unselect', function(e) { /* removed */ });
$('#mySelect').on('select2:open', function(e) { /* opened */ });
$('#mySelect').on('select2:close', function(e) { /* closed */ });
$('#mySelect').on('change', function(e) { /* value changed */ });
```

## Programmatic Control

```javascript
$('#mySelect').val('5').trigger('change');           // Set value
$('#mySelect').val(null).trigger('change');           // Clear
$('#mySelect').select2('open');                       // Open dropdown
$('#mySelect').select2('close');                      // Close
$('#mySelect').select2('destroy');                    // Remove Select2
$('#mySelect').prop('disabled', true);                // Disable
var data = $('#mySelect').select2('data');             // Get selection
```

## Common Gotchas

1. **Modal z-index**: Always set `dropdownParent` to the modal element
2. **XSS**: Return jQuery objects with `.text()`, never concatenate user input into HTML strings
3. **Hidden elements**: Initialize Select2 AFTER element is visible (`shown.bs.modal` event)
4. **Width**: Set `width: '100%'` on the `<select>` CSS, then `width: 'resolve'` in config
5. **Re-init after AJAX content replacement**: Destroy then re-create: `$('#sel').select2('destroy').select2({...})`
6. **French language**: Include `select2/dist/js/i18n/fr.js` or set `language: 'fr'`
