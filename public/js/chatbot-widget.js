(function () {
    'use strict';

    function debugWarn() { if (window.console && console.warn) { console.warn.apply(console, arguments); } }
    function debugError() { if (window.console && console.error) { console.error.apply(console, arguments); } }

    function resolvePath(obj, path) {
        if (!obj) {
            return undefined;
        }
        return path.split('.').reduce(function (acc, key) {
            if (acc && Object.prototype.hasOwnProperty.call(acc, key)) {
                return acc[key];
            }
            return undefined;
        }, obj);
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderTemplate(template, data) {
        if (!template) {
            return '';
        }

        var rendered = template;

        rendered = rendered.replace(/{{#if\s+([\w.]+)}}([\s\S]*?){{\/if}}/g, function (match, key, inner) {
            var value = resolvePath(data, key);
            var isTruthy = Array.isArray(value) ? value.length > 0 : !!value;
            return isTruthy ? renderTemplate(inner, data) : '';
        });

        rendered = rendered.replace(/{{#each\s+([\w.]+)}}([\s\S]*?){{\/each}}/g, function (match, key, inner) {
            var collection = resolvePath(data, key);
            if (!Array.isArray(collection) || collection.length === 0) {
                return '';
            }

            return collection.map(function (item) {
                var mergedContext = Object.assign({}, data, item);
                return renderTemplate(inner, mergedContext);
            }).join('');
        });

        rendered = rendered.replace(/{{([\w.]+)}}/g, function (match, key) {
            var value = resolvePath(data, key);
            return value === undefined || value === null ? '' : escapeHtml(value);
        });

        return rendered;
    }

    function formatTimeLabel(isoDate) {
        if (!isoDate) {
            return '';
        }
        try {
            var date = new Date(isoDate);
            if (Number.isNaN(date.getTime())) {
                return '';
            }
            return date.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return '';
        }
    }

    function formatText(content) {
        if (!content) {
            return '';
        }
        return content
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    function buildFooterElement(data) {
        if (!data) {
            return null;
        }

        var totalCount = typeof data.total_count === 'number' ? data.total_count : null;
        var totalAvailable = typeof data.total_available === 'number' ? data.total_available : totalCount;

        var hasSummary = totalCount !== null || totalAvailable !== null;
        var hasLink = typeof data.deep_link === 'string' && data.deep_link.length > 0;

        if (!hasSummary && !hasLink) {
            return null;
        }

        var footer = document.createElement('div');
        footer.className = 'chatbot-table-footer';

        if (hasSummary) {
            var summary = document.createElement('p');
            summary.className = 'text-muted';
            var summaryText = totalCount !== null ? totalCount + ' résultat(s) affiché(s)' : '';
            if (totalAvailable !== null) {
                summaryText += ' sur ' + totalAvailable;
            }
            summary.textContent = summaryText.trim();
            footer.appendChild(summary);
        }

        if (hasLink) {
            var link = document.createElement('a');
            link.className = 'btn-acasi secondary btn-sm';
            link.href = data.deep_link;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.innerHTML = '<i class="fas fa-external-link-alt"></i> Ouvrir la page';
            footer.appendChild(link);
        }

        return footer;
    }

    function buildTableFromData(data) {
        var wrapper = document.createElement('div');
        wrapper.className = 'chatbot-data-table';

        var table = document.createElement('table');
        table.className = 'table table-hover';

        var thead = document.createElement('thead');
        var headerRow = document.createElement('tr');
        (data.columns || []).forEach(function (column) {
            var th = document.createElement('th');
            th.textContent = column.label || '';
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);

        var tbody = document.createElement('tbody');
        var columnCount = data.column_count || (data.columns ? data.columns.length : 1);

        (data.rows || []).forEach(function (row) {
            var tr = document.createElement('tr');
            (row.cells || []).forEach(function (cell) {
                var td = document.createElement('td');
                if (cell.badge) {
                    var badge = document.createElement('span');
                    badge.className = 'badge badge-' + cell.badge;
                    badge.textContent = cell.value || '';
                    td.appendChild(badge);
                } else {
                    td.textContent = cell.value || '';
                }
                tr.appendChild(td);
            });
            tbody.appendChild(tr);

            if (row.actions && row.actions.length) {
                var actionsRow = document.createElement('tr');
                var actionsCell = document.createElement('td');
                actionsCell.colSpan = row.column_count || columnCount;
                actionsCell.className = 'chatbot-row-actions';

                row.actions.forEach(function (action) {
                    if (!action || !action.url) {
                        return;
                    }
                    var link = document.createElement('a');
                    link.className = 'btn-acasi secondary btn-xs';
                    link.href = action.url;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    if (action.icon) {
                        link.innerHTML = '<i class="' + action.icon + '"></i> ' + (action.label || 'Voir');
                    } else {
                        link.textContent = action.label || 'Voir';
                    }
                    actionsCell.appendChild(link);
                });

                actionsRow.appendChild(actionsCell);
                tbody.appendChild(actionsRow);
            }
        });

        table.appendChild(tbody);
        wrapper.appendChild(table);

        var footer = buildFooterElement(data);
        if (footer) {
            wrapper.appendChild(footer);
        }

        return wrapper;
    }

    function buildCardsFromData(data) {
        var container = document.createElement('div');

        var grid = document.createElement('div');
        grid.className = 'chatbot-card-grid';

        (data.cards || []).forEach(function (card) {
            var cardElement = document.createElement('div');
            cardElement.className = 'chatbot-card';

            var header = document.createElement('div');
            header.className = 'chatbot-card-header';

            var headerText = document.createElement('div');
            var title = document.createElement('h5');
            title.textContent = card.title || '';
            headerText.appendChild(title);

            if (card.subtitle) {
                var subtitle = document.createElement('p');
                subtitle.className = 'chatbot-card-subtitle';
                subtitle.textContent = card.subtitle;
                headerText.appendChild(subtitle);
            }

            header.appendChild(headerText);

            if (Array.isArray(card.badges) && card.badges.length) {
                var badgesWrapper = document.createElement('div');
                badgesWrapper.className = 'chatbot-card-badges';
                card.badges.forEach(function (badgeData) {
                    var badge = document.createElement('span');
                    badge.className = 'badge badge-' + (badgeData.style || 'secondary');
                    badge.textContent = badgeData.label || '';
                    badgesWrapper.appendChild(badge);
                });
                header.appendChild(badgesWrapper);
            }

            cardElement.appendChild(header);

            if (Array.isArray(card.meta) && card.meta.length) {
                var body = document.createElement('div');
                body.className = 'chatbot-card-body';
                card.meta.forEach(function (metaRow) {
                    var row = document.createElement('div');
                    row.className = 'chatbot-card-row';

                    var label = document.createElement('span');
                    label.className = 'chatbot-card-label';
                    label.textContent = metaRow.label || '';

                    var value = document.createElement('span');
                    value.className = 'chatbot-card-value';
                    value.textContent = metaRow.value || '';

                    row.appendChild(label);
                    row.appendChild(value);
                    body.appendChild(row);
                });
                cardElement.appendChild(body);
            }

            if (Array.isArray(card.actions) && card.actions.length) {
                var actions = document.createElement('div');
                actions.className = 'chatbot-card-actions';
                card.actions.forEach(function (action) {
                    if (!action || !action.url) {
                        return;
                    }
                    var link = document.createElement('a');
                    link.className = 'btn-acasi secondary btn-xs';
                    link.href = action.url;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    if (action.icon) {
                        link.innerHTML = '<i class="' + action.icon + '"></i> ' + (action.label || 'Voir');
                    } else {
                        link.textContent = action.label || 'Voir';
                    }
                    actions.appendChild(link);
                });
                cardElement.appendChild(actions);
            }

            grid.appendChild(cardElement);
        });

        container.appendChild(grid);

        var footer = buildFooterElement(data);
        if (footer) {
            container.appendChild(footer);
        }

        return container;
    }

    function buildChecklistFromData(data) {
        var container = document.createElement('div');
        container.className = 'chatbot-checklist';

        (data.sections || []).forEach(function (section) {
            var sectionEl = document.createElement('div');
            sectionEl.className = 'chatbot-checklist-section';

            if (section.title) {
                var sectionTitle = document.createElement('div');
                sectionTitle.className = 'chatbot-checklist-section-title';
                sectionTitle.textContent = section.title;
                sectionEl.appendChild(sectionTitle);
            }

            (section.steps || []).forEach(function (step) {
                var stepEl = document.createElement('div');
                stepEl.className = 'chatbot-checklist-step';

                var iconMap = { done: 'fa-check', next: 'fa-arrow-right', blocked: 'fa-lock', todo: 'fa-circle' };
                var icon = document.createElement('div');
                icon.className = 'chatbot-checklist-icon ' + (step.status || 'todo');
                icon.innerHTML = '<i class="fas ' + (iconMap[step.status] || 'fa-circle') + '"></i>';
                stepEl.appendChild(icon);

                var textWrap = document.createElement('div');
                textWrap.className = 'chatbot-checklist-text';

                var title = document.createElement('div');
                title.className = 'chatbot-checklist-title';
                title.textContent = step.title || '';
                textWrap.appendChild(title);

                if (step.description) {
                    var desc = document.createElement('div');
                    desc.className = 'chatbot-checklist-desc';
                    desc.textContent = step.description;
                    textWrap.appendChild(desc);
                }

                if (step.deep_link) {
                    var action = document.createElement('a');
                    action.className = 'chatbot-deep-link';
                    action.href = step.deep_link;
                    action.target = '_blank';
                    action.rel = 'noopener noreferrer';
                    action.innerHTML = '<i class="fas fa-arrow-right"></i> ' + (step.action_label || 'Ouvrir');
                    textWrap.appendChild(action);
                }

                stepEl.appendChild(textWrap);
                sectionEl.appendChild(stepEl);
            });

            container.appendChild(sectionEl);
        });

        if (data.progress_percent !== undefined) {
            var progress = document.createElement('div');
            progress.className = 'chatbot-checklist-progress';
            progress.innerHTML = '<div class="chatbot-checklist-progress-bar"><div class="chatbot-checklist-progress-fill" style="width:' + data.progress_percent + '%"></div></div>' +
                '<span class="chatbot-checklist-progress-text">' + data.progress_percent + '%</span>';
            container.appendChild(progress);
        }

        return container;
    }

    function buildFormFromData(data, widget) {
        var container = document.createElement('div');
        container.className = 'chatbot-form';

        if (data.title) {
            var title = document.createElement('h4');
            title.className = 'chatbot-form-title';
            title.textContent = data.title;
            container.appendChild(title);
        }

        if (data.description) {
            var desc = document.createElement('p');
            desc.className = 'chatbot-form-description';
            desc.textContent = data.description;
            container.appendChild(desc);
        }

        var errorBox = document.createElement('div');
        errorBox.className = 'chatbot-form-error';
        errorBox.style.display = 'none';
        container.appendChild(errorBox);

        var form = document.createElement('form');
        form.className = 'chatbot-form-body';
        form.dataset.actionUrl = data.action_url || '';
        form.dataset.actionMethod = data.action_method || 'POST';

        var fields = data.fields || [];

        function addField(field) {
            if (!field || !field.name) {
                return;
            }

            if (field.type === 'hidden') {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = field.name;
                hidden.value = field.value || '';
                form.appendChild(hidden);
                return;
            }

            var wrapper = document.createElement('div');
            wrapper.className = 'chatbot-form-group';

            if (field.type !== 'checkbox') {
                var label = document.createElement('label');
                label.textContent = field.label || field.name;
                if (field.required) {
                    label.innerHTML = label.textContent + ' <span class="chatbot-form-required">*</span>';
                }
                wrapper.appendChild(label);
            }

            var input;
            if (field.type === 'textarea') {
                input = document.createElement('textarea');
                input.rows = field.rows || 3;
            } else if (field.type === 'select') {
                input = document.createElement('select');
                (field.options || []).forEach(function (option) {
                    var opt = document.createElement('option');
                    opt.value = option.value !== undefined ? option.value : '';
                    opt.textContent = option.label || option.value || '';
                    if (field.value !== undefined && String(field.value) === String(opt.value)) {
                        opt.selected = true;
                    }
                    input.appendChild(opt);
                });
            } else if (field.type === 'checkbox') {
                input = document.createElement('input');
                input.type = 'checkbox';
                input.checked = !!field.value;
            } else {
                input = document.createElement('input');
                input.type = field.type || 'text';
            }

            input.name = field.name;
            if (field.placeholder && input.type !== 'select') {
                input.placeholder = field.placeholder;
            }
            if (field.required) {
                input.required = true;
            }
            if (field.min !== undefined) {
                input.min = field.min;
            }
            if (field.max !== undefined) {
                input.max = field.max;
            }
            if (field.step !== undefined) {
                input.step = field.step;
            }
            if (field.value !== undefined && field.type !== 'select' && field.type !== 'checkbox') {
                input.value = field.value;
            }

            if (field.type === 'checkbox') {
                var checkboxWrapper = document.createElement('label');
                checkboxWrapper.className = 'chatbot-form-checkbox';
                checkboxWrapper.appendChild(input);
                var checkboxLabel = document.createElement('span');
                checkboxLabel.textContent = field.label || field.name;
                checkboxWrapper.appendChild(checkboxLabel);
                wrapper.appendChild(checkboxWrapper);
            } else {
                wrapper.appendChild(input);
            }

            if (field.help) {
                var help = document.createElement('small');
                help.className = 'chatbot-form-help';
                help.textContent = field.help;
                wrapper.appendChild(help);
            }

            var error = document.createElement('div');
            error.className = 'chatbot-form-error-field';
            error.style.display = 'none';
            wrapper.appendChild(error);

            form.appendChild(wrapper);
        }

        fields.forEach(addField);

        if (data.hidden_fields) {
            Object.keys(data.hidden_fields).forEach(function (key) {
                addField({ name: key, type: 'hidden', value: data.hidden_fields[key] });
            });
        }

        var footer = document.createElement('div');
        footer.className = 'chatbot-form-footer';
        var submit = document.createElement('button');
        submit.type = 'submit';
        submit.className = 'btn-acasi primary';
        submit.textContent = data.submit_label || 'Envoyer';
        footer.appendChild(submit);
        container.appendChild(form);
        container.appendChild(footer);
        // form is inside container, footer is sibling

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!form.dataset.actionUrl) {
                return;
            }

            errorBox.style.display = 'none';
            errorBox.textContent = '';
            Array.prototype.slice.call(form.querySelectorAll('.chatbot-form-error-field')).forEach(function (el) {
                el.style.display = 'none';
                el.textContent = '';
            });

            var payload = {};
            Array.prototype.slice.call(form.elements).forEach(function (element) {
                if (!element.name) {
                    return;
                }
                if (element.type === 'checkbox') {
                    payload[element.name] = element.checked ? 1 : 0;
                } else {
                    payload[element.name] = element.value;
                }
            });

            if (!payload.conversation_id && widget && widget.state.currentConversationId) {
                payload.conversation_id = widget.state.currentConversationId;
            }

            submit.disabled = true;
            submit.textContent = 'Envoi...';

            fetch(form.dataset.actionUrl, {
                method: form.dataset.actionMethod || 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': widget && widget.config ? (widget.config.csrfToken || '') : ''
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (response) {
                    if (!response || !response.success) {
                        if (response && response.errors) {
                            Object.keys(response.errors).forEach(function (key) {
                                var fieldError = form.querySelector('[name="' + key + '"]');
                                if (fieldError) {
                                    var errorEl = fieldError.closest('.chatbot-form-group').querySelector('.chatbot-form-error-field');
                                    if (errorEl) {
                                        errorEl.textContent = response.errors[key][0] || 'Champ invalide.';
                                        errorEl.style.display = 'block';
                                    }
                                }
                            });
                        }

                        errorBox.textContent = (response && response.message) ? response.message : 'Erreur lors de l\'envoi.';
                        errorBox.style.display = 'block';
                        throw new Error('form_error');
                    }

                    if (widget && response.conversation_id) {
                        widget.state.currentConversationId = response.conversation_id;
                    }

                    if (widget) {
                        widget.appendAssistantMessage({
                            content: response.message,
                            display_type: response.display_type,
                            display_data: response.display_data,
                            deep_link: response.deep_link,
                            created_at: new Date().toISOString()
                        });
                        widget.fetchConversations();
                    }

                    var successTitle = data.success_title || 'Terminé';
                    var successText = response.form_message || data.success_message || response.message || 'Formulaire envoyé.';

                    var successCard = document.createElement('div');
                    successCard.className = 'chatbot-form-success';
                    successCard.innerHTML = '<div class="chatbot-form-success-icon"><i class="fas fa-check"></i></div>' +
                        '<div class="chatbot-form-success-body">' +
                        '<div class="chatbot-form-success-title">' + successTitle + '</div>' +
                        '<div class="chatbot-form-success-text">' + successText + '</div>' +
                        '</div>';

                    container.innerHTML = '';
                    container.appendChild(successCard);
                })
                .catch(function (error) {
                    if (error && error.message !== 'form_error') {
                        errorBox.textContent = 'Erreur lors de l\'envoi.';
                        errorBox.style.display = 'block';
                    }
                })
                .finally(function () {
                    submit.disabled = false;
                    submit.textContent = data.submit_label || 'Envoyer';
                });
        });

        return container;
    }

    function buildFollowUpChips(followUp) {
        if (!Array.isArray(followUp) || followUp.length === 0) {
            return null;
        }

        var container = document.createElement('div');
        container.className = 'chatbot-follow-up';

        followUp.forEach(function (suggestion) {
            if (!suggestion) {
                return;
            }
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'chatbot-follow-up-chip';
            chip.textContent = suggestion;
            chip.addEventListener('click', function () {
                var textarea = document.getElementById('chatbot-textarea');
                if (textarea) {
                    textarea.value = suggestion;
                    textarea.dispatchEvent(new Event('input'));
                    textarea.focus();
                }
            });
            container.appendChild(chip);
        });

        return container;
    }

    function buildActionChips(actions) {
        if (!Array.isArray(actions) || actions.length === 0) {
            return null;
        }

        var container = document.createElement('div');
        container.className = 'chatbot-follow-up';

        actions.forEach(function (action) {
            if (!action || !action.label) {
                return;
            }

            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'chatbot-follow-up-chip';
            chip.textContent = action.label;
            chip.dataset.action = action.action || '';
            chip.dataset.value = action.value || '';
            container.appendChild(chip);
        });

        return container;
    }

    function createTypingIndicator() {
        var wrapper = document.createElement('div');
        wrapper.className = 'chatbot-typing';

        var avatar = document.createElement('div');
        avatar.className = 'chatbot-message-avatar';
        avatar.innerHTML = '<i class="fas fa-robot"></i>';

        var dots = document.createElement('div');
        dots.className = 'chatbot-typing-dots';
        dots.setAttribute('aria-label', 'Assistant en train de répondre');

        for (var i = 0; i < 3; i += 1) {
            dots.appendChild(document.createElement('span'));
        }

        wrapper.appendChild(avatar);
        wrapper.appendChild(dots);

        return wrapper;
    }

    function ChatbotWidget(config) {
        this.config = config || {};
        this.defaults = {
            width: 440,
            height: 520,
            minWidth: 360,
            minHeight: 360,
            maxWidth: 860,
            maxHeight: 860
        };

        this.state = {
            isOpen: false,
            isSending: false,
            isFullscreen: false,
            isResizing: false,
            conversations: [],
            currentConversationId: null,
            typingElement: null,
            dimensions: this.loadStoredDimensions()
        };

        this.widget = document.getElementById('chatbot-widget');
        if (!this.widget) {
            return;
        }

        this.toggleButton = this.widget.querySelector('#chatbot-toggle');
        this.window = this.widget.querySelector('#chatbot-window');
        this.backdrop = document.getElementById('chatbot-backdrop');
        this.messagesContainer = this.widget.querySelector('#chatbot-messages');
        this.textarea = this.widget.querySelector('#chatbot-textarea');
        this.sendButton = this.widget.querySelector('#chatbot-send');
        this.closeButton = this.widget.querySelector('#chatbot-close');
        this.expandButton = this.widget.querySelector('#chatbot-expand');
        this.conversationToggle = this.widget.querySelector('#chatbot-conversations-toggle');
        this.conversationsPanel = this.widget.querySelector('#chatbot-conversations');
        this.conversationList = this.widget.querySelector('#chatbot-conversation-list');
        this.newConversationButton = this.widget.querySelector('#chatbot-new-conversation');
        this.toast = this.widget.querySelector('#chatbot-toast');
        this.resizeHandle = this.widget.querySelector('#chatbot-resize-handle');
        this.settingsButton = this.widget.querySelector('#chatbot-settings');
        this.settingsModal = document.getElementById('chatbot-settings-modal');
        this.settingsClose = document.getElementById('chatbot-settings-close');
        this.settingsCancel = document.getElementById('chatbot-settings-cancel');
        this.settingsSave = document.getElementById('chatbot-settings-save');
        this.settingsName = document.getElementById('chatbot-pref-name');
        this.settingsStyle = document.getElementById('chatbot-pref-style');
        this.settingsTone = document.getElementById('chatbot-pref-tone');
        this.settingsClarify = document.getElementById('chatbot-pref-clarify');
        this.settingsNotes = document.getElementById('chatbot-pref-notes');

        this.typingElement = null;
        this.expandButtonIcon = this.expandButton ? this.expandButton.querySelector('i') : null;

        this.init();
    }

    ChatbotWidget.prototype.loadStoredDimensions = function () {
        try {
            if (typeof window !== 'undefined' && window.localStorage) {
                var raw = window.localStorage.getItem('KLASSCI_CHATBOT_SIZE');
                if (raw) {
                    var parsed = JSON.parse(raw);
                    if (parsed && typeof parsed.width === 'number' && typeof parsed.height === 'number') {
                        return {
                            width: parsed.width,
                            height: parsed.height
                        };
                    }
                }
            }
        } catch (error) {
            debugWarn('Chatbot widget: impossible de charger les dimensions stockées', error);
        }

        return {
            width: this.defaults.width,
            height: this.defaults.height
        };
    };

    ChatbotWidget.prototype.init = function () {
        var self = this;

        if (!this.toggleButton || !this.window || !this.messagesContainer || !this.textarea || !this.sendButton) {
            debugWarn('Chatbot widget: éléments manquants, initialisation annulée.');
            return;
        }

        this.toggleButton.setAttribute('aria-expanded', 'false');
        this.window.setAttribute('aria-hidden', 'true');

        this.toggleButton.addEventListener('click', function () {
            self.toggle();
        });

        if (this.closeButton) {
            this.closeButton.addEventListener('click', function () {
                self.close();
            });
        }

        if (this.backdrop) {
            this.backdrop.addEventListener('click', function () {
                self.close();
            });
        }

        this.sendButton.addEventListener('click', function () {
            self.handleSend();
        });

        this.textarea.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                self.handleSend();
            }
        });

        this.textarea.addEventListener('input', function () {
            this.style.height = 'auto';
            var maxHeight = 120;
            var newHeight = Math.min(this.scrollHeight, maxHeight);
            this.style.height = newHeight + 'px';
        });

        if (this.conversationToggle && this.conversationsPanel) {
            this.conversationToggle.addEventListener('click', function () {
                self.conversationsPanel.classList.toggle('is-open');
                self.conversationToggle.classList.toggle('is-active');
            });
        }

        if (this.expandButton) {
            this.expandButton.addEventListener('click', function () {
                if (!self.state.isOpen) {
                    self.open();
                }
                self.setFullscreen(!self.state.isFullscreen);
            });
        }

        if (this.resizeHandle) {
            this.resizeHandle.addEventListener('pointerdown', function (event) {
                if (self.state.isFullscreen) {
                    return;
                }
                self.startResize(event);
            });
        }

        if (this.newConversationButton) {
            this.newConversationButton.addEventListener('click', function () {
                self.startNewConversation();
            });
        }

        if (this.settingsButton && this.settingsModal) {
            this.settingsButton.addEventListener('click', function () {
                self.openSettingsModal();
            });
        }

        if (this.settingsClose) {
            this.settingsClose.addEventListener('click', function () {
                self.closeSettingsModal();
            });
        }

        if (this.settingsCancel) {
            this.settingsCancel.addEventListener('click', function () {
                self.closeSettingsModal();
            });
        }

        if (this.settingsSave) {
            this.settingsSave.addEventListener('click', function () {
                self.savePreferences();
            });
        }

        if (this.settingsModal) {
            this.settingsModal.addEventListener('click', function (event) {
                if (event.target === self.settingsModal) {
                    self.closeSettingsModal();
                }
            });
        }

        document.addEventListener('click', function (event) {
            if (!self.widget.contains(event.target) && self.conversationsPanel && self.conversationsPanel.classList.contains('is-open')) {
                self.conversationsPanel.classList.remove('is-open');
                if (self.conversationToggle) {
                    self.conversationToggle.classList.remove('is-active');
                }
            }
        });

        if (this.messagesContainer) {
            this.messagesContainer.addEventListener('click', function (event) {
                var target = event.target.closest('.chatbot-follow-up-chip');
                if (target && target.dataset && target.dataset.action) {
                    event.preventDefault();
                    self.handleActionChipClick(target);
                }
            });
        }

        window.addEventListener('resize', function () {
            if (!self.state.isFullscreen) {
                self.applyDimensions();
            }
        });

        this.applyDimensions();
        this.updateBackdropVisibility();
        this.renderEmptyState();

        if (this.settingsButton) {
            this.settingsButton.disabled = !this.state.isOpen;
            this.settingsButton.classList.toggle('is-disabled', !this.state.isOpen);
        }
    };

    ChatbotWidget.prototype.toggle = function () {
        if (this.state.isOpen) {
            this.close();
        } else {
            this.open();
        }
    };

    ChatbotWidget.prototype.open = function () {
        var _this = this;

        this.state.isOpen = true;
        this.window.classList.add('is-open');
        this.window.setAttribute('aria-hidden', 'false');
        this.window.setAttribute('aria-modal', 'true');
        this.toggleButton.classList.add('is-active');
        this.toggleButton.setAttribute('aria-expanded', 'true');

        if (this.settingsButton) {
            this.settingsButton.disabled = false;
            this.settingsButton.classList.remove('is-disabled');
        }

        if (this.state.conversations.length === 0) {
            this.fetchConversations();
        }

        this.applyDimensions();
        this.applyFullscreenState();
        this.updateBackdropVisibility();

        window.requestAnimationFrame(function () {
            _this.textarea.focus();
        });
    };

    ChatbotWidget.prototype.close = function () {
        this.state.isOpen = false;
        this.window.classList.remove('is-open');
        this.window.setAttribute('aria-hidden', 'true');
        this.window.setAttribute('aria-modal', 'false');
        this.toggleButton.classList.remove('is-active');
        this.toggleButton.setAttribute('aria-expanded', 'false');

        if (this.settingsButton) {
            this.settingsButton.disabled = true;
            this.settingsButton.classList.add('is-disabled');
        }

        this.closeSettingsModal();

        this.setFullscreen(false);
        this.updateBackdropVisibility();
    };

    ChatbotWidget.prototype.showToast = function (message, duration) {
        var self = this;
        if (!this.toast) {
            return;
        }
        this.toast.textContent = message;
        this.toast.classList.add('is-visible');
        setTimeout(function () {
            self.toast.classList.remove('is-visible');
        }, duration || 2600);
    };

    ChatbotWidget.prototype.openSettingsModal = function () {
        if (!this.settingsModal || !this.state.isOpen) {
            return;
        }
        this.settingsModal.classList.add('is-open');
        this.settingsModal.setAttribute('aria-hidden', 'false');
        this.fetchPreferences();
    };

    ChatbotWidget.prototype.closeSettingsModal = function () {
        if (!this.settingsModal) {
            return;
        }
        this.settingsModal.classList.remove('is-open');
        this.settingsModal.setAttribute('aria-hidden', 'true');
    };

    ChatbotWidget.prototype.fetchPreferences = function () {
        var self = this;
        if (!this.config.routes || !this.config.routes.preferences) {
            return;
        }

        fetch(this.config.routes.preferences, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (!payload || !payload.preferences) {
                    return;
                }

                var prefs = payload.preferences;
                if (self.settingsName) self.settingsName.value = prefs.preferred_name || '';
                if (self.settingsStyle) self.settingsStyle.value = prefs.response_style || 'standard';
                if (self.settingsTone) self.settingsTone.value = prefs.response_tone || 'pedagogique';
                if (self.settingsClarify) self.settingsClarify.value = prefs.clarification_mode || 'auto';
                if (self.settingsNotes) self.settingsNotes.value = prefs.notes || '';
            })
            .catch(function () {
                self.showToast('Impossible de charger les préférences');
            });
    };

    ChatbotWidget.prototype.savePreferences = function () {
        var self = this;
        if (!this.config.routes || !this.config.routes.preferencesUpdate) {
            return;
        }

        var payload = {
            preferred_name: this.settingsName ? this.settingsName.value.trim() : '',
            response_style: this.settingsStyle ? this.settingsStyle.value : 'standard',
            response_tone: this.settingsTone ? this.settingsTone.value : 'pedagogique',
            clarification_mode: this.settingsClarify ? this.settingsClarify.value : 'auto',
            notes: this.settingsNotes ? this.settingsNotes.value.trim() : ''
        };

        fetch(this.config.routes.preferencesUpdate, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.config.csrfToken || ''
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (payload && payload.success) {
                    self.showToast('Préférences enregistrées');
                    self.closeSettingsModal();
                    return;
                }

                self.showToast('Impossible de sauvegarder');
            })
            .catch(function () {
                self.showToast('Impossible de sauvegarder');
            });
    };

    ChatbotWidget.prototype.handleActionChipClick = function (chip) {
        var self = this;
        var action = chip.dataset.action;
        var value = chip.dataset.value || '';

        if (action === 'save_preferred_name' && this.config.routes && this.config.routes.preferencesMemory) {
            fetch(this.config.routes.preferencesMemory, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    type: 'preferred_name',
                    value: value
                })
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    if (payload && payload.success) {
                        self.showToast('Nom préféré enregistré');
                        chip.remove();
                    } else {
                        self.showToast('Impossible d\'enregistrer');
                    }
                })
                .catch(function () {
                    self.showToast('Impossible d\'enregistrer');
                });
            return;
        }

        if (action === 'open_form' && this.config.routes) {
            var target = value || '';
            var route = null;
            var categoryId = null;
            var focusField = null;

            if (target.indexOf('frais_config:') === 0) {
                route = this.config.routes.formFraisConfig;
                categoryId = target.split(':')[1] || null;
            } else if (target === 'frais_category') {
                route = this.config.routes.formFraisCategory;
            } else if (target === 'frais_config') {
                route = this.config.routes.formFraisConfig;
            } else if (target.indexOf('inscriptions_filter:') === 0) {
                route = this.config.routes.formInscriptionsFilter;
                focusField = target.split(':')[1] || null;
            } else if (target === 'inscriptions_filter') {
                route = this.config.routes.formInscriptionsFilter;
            }

            if (!route) {
                return;
            }

            chip.disabled = true;
            var typing = createTypingIndicator();
            this.messagesContainer.appendChild(typing);
            this.scrollToBottom();

            var url = route;
            var params = [];
            if (this.state.currentConversationId) {
                params.push('conversation_id=' + encodeURIComponent(this.state.currentConversationId));
            }
            if (categoryId) {
                params.push('category_id=' + encodeURIComponent(categoryId));
            }
            if (params.length) {
                url += (url.indexOf('?') === -1 ? '?' : '&') + params.join('&');
            }

            if (focusField) {
                url += (url.indexOf('?') === -1 ? '?' : '&') + 'focus_field=' + encodeURIComponent(focusField);
            }

            fetch(url, {
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    typing.remove();
                    if (!payload || !payload.success) {
                        throw new Error('Erreur');
                    }

                    if (payload.conversation_id) {
                        self.state.currentConversationId = payload.conversation_id;
                    }

                    self.appendAssistantMessage({
                        content: payload.message,
                        display_type: payload.display_type,
                        display_data: payload.display_data,
                        deep_link: payload.deep_link,
                        created_at: new Date().toISOString()
                    });

                    if (focusField) {
                        setTimeout(function () {
                            var field = self.messagesContainer.querySelector('[name="' + focusField + '"]');
                            if (field) {
                                field.focus();
                            }
                        }, 120);
                    }
                })
                .catch(function () {
                    typing.remove();
                    self.showToast('Impossible de charger le formulaire');
                })
                .finally(function () {
                    chip.disabled = false;
                });
        }
    };

    ChatbotWidget.prototype.fetchConversations = function () {
        var self = this;
        if (!this.config.routes || !this.config.routes.conversations) {
            return;
        }

        fetch(this.config.routes.conversations, {
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Erreur ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                if (!data || !data.success) {
                    throw new Error('Réponse inattendue');
                }
                self.state.conversations = data.conversations || [];
                self.renderConversationList();
                if (!self.state.currentConversationId && self.state.conversations.length > 0) {
                    self.loadConversation(self.state.conversations[0].id);
                }
            })
            .catch(function (error) {
                debugError('Chatbot widget - impossible de récupérer les conversations', error);
                self.showToast('Impossible de charger les conversations');
            });
    };

    ChatbotWidget.prototype.renderConversationList = function () {
        var self = this;
        if (!this.conversationList) {
            return;
        }
        this.conversationList.innerHTML = '';

        if (this.state.conversations.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'chatbot-conversation-empty';
            empty.innerHTML = '<p>Aucune conversation</p>';
            this.conversationList.appendChild(empty);
            return;
        }

        this.state.conversations.forEach(function (conversation) {
            var item = document.createElement('div');
            item.className = 'chatbot-conversation-item';
            item.dataset.conversationId = conversation.id;

            if (self.state.currentConversationId === conversation.id) {
                item.classList.add('active');
            }

            var title = document.createElement('div');
            title.className = 'chatbot-conversation-title';
            title.textContent = conversation.title || 'Conversation';

            var meta = document.createElement('div');
            meta.className = 'chatbot-conversation-meta';
            meta.textContent = conversation.last_activity || '';

            var actions = document.createElement('div');
            actions.className = 'chatbot-conversation-actions';

            var editBtn = document.createElement('button');
            editBtn.className = 'chatbot-conversation-action-btn';
            editBtn.setAttribute('type', 'button');
            editBtn.setAttribute('title', 'Renommer');
            editBtn.innerHTML = '<i class="fas fa-pen"></i>';

            editBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                self.startInlineRename(item, conversation);
            });

            var deleteBtn = document.createElement('button');
            deleteBtn.className = 'chatbot-conversation-action-btn delete';
            deleteBtn.setAttribute('type', 'button');
            deleteBtn.setAttribute('title', 'Supprimer');
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';

            deleteBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                self.deleteConversation(conversation.id);
            });

            actions.appendChild(editBtn);
            actions.appendChild(deleteBtn);

            item.appendChild(title);
            item.appendChild(meta);
            item.appendChild(actions);

            item.addEventListener('click', function () {
                if (item.querySelector('.chatbot-conversation-rename')) {
                    return;
                }
                self.loadConversation(conversation.id);
                if (self.conversationsPanel) {
                    self.conversationsPanel.classList.remove('is-open');
                }
                if (self.conversationToggle) {
                    self.conversationToggle.classList.remove('is-active');
                }
            });

            self.conversationList.appendChild(item);
        });
    };

    ChatbotWidget.prototype.startInlineRename = function (item, conversation) {
        var self = this;
        if (!item || !conversation) {
            return;
        }

        var titleEl = item.querySelector('.chatbot-conversation-title');
        if (!titleEl) {
            return;
        }

        var input = document.createElement('input');
        input.type = 'text';
        input.maxLength = 40;
        input.className = 'chatbot-conversation-rename';
        input.value = conversation.title || '';

        titleEl.replaceWith(input);
        input.focus();

        var commit = function () {
            var newTitle = input.value.trim();
            if (!newTitle) {
                self.renderConversationList();
                return;
            }
            self.updateConversationTitle(conversation.id, newTitle);
        };

        input.addEventListener('blur', commit);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                commit();
            } else if (event.key === 'Escape') {
                self.renderConversationList();
            }
        });
    };

    ChatbotWidget.prototype.updateConversationTitle = function (conversationId, title) {
        var self = this;
        if (!this.config.routes || !this.config.routes.conversationTitle) {
            return;
        }

        var url = this.config.routes.conversationTitle.replace('__ID__', conversationId);

        fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.config.csrfToken || ''
            },
            credentials: 'same-origin',
            body: JSON.stringify({ title: title })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (payload && payload.success) {
                    self.state.conversations = self.state.conversations.map(function (item) {
                        if (item.id === conversationId) {
                            item.title = payload.conversation.title || title;
                        }
                        return item;
                    });
                    self.renderConversationList();
                    self.showToast('Titre mis à jour');
                    return;
                }
                self.showToast('Impossible de renommer');
                self.renderConversationList();
            })
            .catch(function () {
                self.showToast('Impossible de renommer');
                self.renderConversationList();
            });
    };

    ChatbotWidget.prototype.deleteConversation = function (conversationId) {
        var self = this;
        if (!this.config.routes || !this.config.routes.delete) {
            return;
        }

        var url = this.config.routes.delete.replace('__ID__', conversationId);

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': this.config.csrfToken,
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Erreur ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                if (data && data.success) {
                    self.state.conversations = self.state.conversations.filter(function (item) {
                        return item.id !== conversationId;
                    });

                    if (self.state.currentConversationId === conversationId) {
                        self.state.currentConversationId = null;
                        self.renderEmptyState();
                    }

                    self.renderConversationList();
                    self.showToast('Conversation supprimée');
                }
            })
            .catch(function (error) {
                debugError('Chatbot widget - suppression conversation', error);
                self.showToast('Suppression impossible');
            });
    };

    ChatbotWidget.prototype.startNewConversation = function () {
        this.state.currentConversationId = null;
        this.renderEmptyState();
        this.textarea.value = '';
        this.textarea.focus();
    };

    ChatbotWidget.prototype.loadConversation = function (conversationId) {
        var self = this;
        if (!this.config.routes || !this.config.routes.history) {
            return;
        }

        this.state.currentConversationId = conversationId;
        this.messagesContainer.innerHTML = '';
        this.messagesContainer.appendChild(createTypingIndicator());

        var url = this.config.routes.history.replace('__ID__', conversationId);

        fetch(url, {
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Erreur ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                self.messagesContainer.innerHTML = '';
                if (!data || !data.success) {
                    throw new Error('Réponse inattendue');
                }

                (data.messages || []).forEach(function (message) {
                    if (message.role === 'user') {
                        self.appendUserMessage(message.content, message.created_at);
                    } else {
                        self.appendAssistantMessage(message);
                    }
                });

                self.scrollToBottom();
                self.renderConversationList();
            })
            .catch(function (error) {
                debugError('Chatbot widget - chargement historique', error);
                self.renderEmptyState('Impossible de charger l\'historique.');
            });
    };

    ChatbotWidget.prototype.renderEmptyState = function (message) {
        this.messagesContainer.innerHTML = '';

        var empty = document.createElement('div');
        empty.className = 'chatbot-empty';
        empty.innerHTML = '<div class="chatbot-empty-icon"><i class="fas fa-robot"></i></div>' +
            '<h5>Assistant KLASSCI</h5>' +
            '<p>' + (message || 'Posez une question sur vos paiements, étudiants, classes ou inscriptions.') + '</p>' +
            '<div class="chatbot-empty-suggestions">' +
            '<button type="button">Mes paiements</button>' +
            '<button type="button">Liste étudiants</button>' +
            '<button type="button">Classes disponibles</button>' +
            '</div>';

        this.messagesContainer.appendChild(empty);

        var self = this;
        empty.querySelectorAll('.chatbot-empty-suggestions button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (self.textarea) {
                    self.textarea.value = btn.textContent;
                    self.textarea.dispatchEvent(new Event('input'));
                    self.textarea.focus();
                }
            });
        });
    };

    ChatbotWidget.prototype.handleSend = function () {
        var self = this;
        if (this.state.isSending) {
            return;
        }

        var content = (this.textarea.value || '').trim();
        if (!content) {
            this.showToast('Écrivez un message avant d\'envoyer.');
            return;
        }

        this.appendUserMessage(content, new Date().toISOString());

        this.textarea.value = '';
        this.textarea.style.height = 'auto';

        this.state.isSending = true;
        this.sendButton.disabled = true;

        this.typingElement = createTypingIndicator();
        this.messagesContainer.appendChild(this.typingElement);
        this.scrollToBottom();

        var payload = {
            message: content,
            current_url: window.location.href,
            current_path: window.location.pathname,
            page_title: document.title
        };

        if (this.state.currentConversationId) {
            payload.conversation_id = this.state.currentConversationId;
        }

        fetch(this.config.routes.message, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.config.csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Erreur ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                if (self.typingElement) {
                    self.typingElement.remove();
                    self.typingElement = null;
                }

                if (!data || !data.success) {
                    throw new Error(data && data.message ? data.message : 'Réponse invalide');
                }

                if (data.conversation_id) {
                    self.state.currentConversationId = data.conversation_id;
                }

                self.appendAssistantMessage({
                    content: data.message,
                    display_type: data.display_type,
                    display_data: data.display_data,
                    deep_link: data.deep_link,
                    created_at: new Date().toISOString()
                });

                self.fetchConversations();
            })
            .catch(function (error) {
                debugError('Chatbot widget - envoi message', error);
                if (self.typingElement) {
                    self.typingElement.remove();
                    self.typingElement = null;
                }
                self.appendErrorMessage('Désolé, une erreur est survenue. Réessayez dans un instant.');
            })
            .finally(function () {
                self.state.isSending = false;
                self.sendButton.disabled = false;
                self.scrollToBottom();
            });
    };

    ChatbotWidget.prototype.appendUserMessage = function (content, createdAt) {
        var wrapper = document.createElement('div');
        wrapper.className = 'chatbot-message user';

        var bubble = document.createElement('div');
        bubble.className = 'chatbot-message-bubble';
        bubble.innerHTML = formatText(content);

        wrapper.appendChild(bubble);

        if (createdAt) {
            var meta = document.createElement('div');
            meta.className = 'chatbot-message-time';
            meta.textContent = formatTimeLabel(createdAt);
            wrapper.appendChild(meta);
        }

        this.messagesContainer.appendChild(wrapper);
        this.scrollToBottom();
    };

    ChatbotWidget.prototype.appendAssistantMessage = function (message) {
        var wrapper = document.createElement('div');
        wrapper.className = 'chatbot-message assistant';

        var avatar = document.createElement('div');
        avatar.className = 'chatbot-message-avatar';
        avatar.innerHTML = '<i class="fas fa-robot"></i>';

        var messageContent = document.createElement('div');
        messageContent.className = 'chatbot-message-bubble';

        if (message.content) {
            var text = document.createElement('div');
            text.className = 'chatbot-message-text-content';
            text.innerHTML = formatText(message.content);
            messageContent.appendChild(text);
        }

        var displayData = message.display_data || {};

        if (message.display_type === 'table' && Array.isArray(displayData.columns)) {
            messageContent.classList.add('has-table');
            messageContent.appendChild(buildTableFromData(displayData));
        } else if (message.display_type === 'cards' && Array.isArray(displayData.cards)) {
            messageContent.classList.add('has-cards');
            messageContent.appendChild(buildCardsFromData(displayData));
        } else if (message.display_type === 'checklist' && displayData.sections) {
            messageContent.classList.add('has-checklist');
            messageContent.appendChild(buildChecklistFromData(displayData));
        } else if (message.display_type === 'form' && displayData.fields) {
            messageContent.classList.add('has-form');
            messageContent.appendChild(buildFormFromData(displayData, this));
        } else if (message.display_type === 'table' && displayData.template_html) {
            messageContent.classList.add('has-table');
            var htmlTable = renderTemplate(displayData.template_html, displayData);
            var containerTable = document.createElement('div');
            containerTable.innerHTML = htmlTable;
            Array.prototype.slice.call(containerTable.childNodes).forEach(function (node) {
                messageContent.appendChild(node);
            });
        } else if (message.display_type === 'cards' && displayData.template_html) {
            messageContent.classList.add('has-cards');
            var htmlCards = renderTemplate(displayData.template_html, displayData);
            var containerCards = document.createElement('div');
            containerCards.innerHTML = htmlCards;
            Array.prototype.slice.call(containerCards.childNodes).forEach(function (node) {
                messageContent.appendChild(node);
            });
        }

        if (message.deep_link && message.display_type !== 'cards' && message.display_type !== 'table') {
            var link = document.createElement('a');
            link.className = 'chatbot-deep-link';
            link.href = message.deep_link;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.innerHTML = '<i class="fas fa-external-link-alt"></i> Ouvrir la page';
            messageContent.appendChild(link);
        }

        if (message.created_at) {
            var meta = document.createElement('div');
            meta.className = 'chatbot-message-time';
            meta.textContent = formatTimeLabel(message.created_at);
            wrapper.appendChild(meta);
        }

        if (displayData.follow_up) {
            var followUp = buildFollowUpChips(displayData.follow_up);
            if (followUp) {
                messageContent.appendChild(followUp);
            }
        }

        if (displayData.follow_up_actions) {
            var actions = buildActionChips(displayData.follow_up_actions);
            if (actions) {
                messageContent.appendChild(actions);
            }
        }

        if (displayData.focus_field) {
            setTimeout(function () {
                var field = messageContent.querySelector('[name="' + displayData.focus_field + '"]');
                if (field) {
                    field.focus();
                }
            }, 120);
        }

        wrapper.appendChild(avatar);
        wrapper.appendChild(messageContent);
        this.messagesContainer.appendChild(wrapper);
        this.scrollToBottom();
    };

    ChatbotWidget.prototype.appendErrorMessage = function (message) {
        var wrapper = document.createElement('div');
        wrapper.className = 'chatbot-message assistant';

        var avatar = document.createElement('div');
        avatar.className = 'chatbot-message-avatar';
        avatar.innerHTML = '<i class="fas fa-robot"></i>';

        var content = document.createElement('div');
        content.className = 'chatbot-message-bubble';
        content.innerHTML = '<div class="chatbot-error">' + formatText(message) + '</div>';

        wrapper.appendChild(avatar);
        wrapper.appendChild(content);
        this.messagesContainer.appendChild(wrapper);
        this.scrollToBottom();
    };

    ChatbotWidget.prototype.applyDimensions = function () {
        if (!this.window) {
            return;
        }

        if (this.state.isFullscreen) {
            this.window.style.width = '';
            this.window.style.height = '';
            return;
        }

        var dims = this.state.dimensions || {};
        var minWidth = this.defaults.minWidth;
        var minHeight = this.defaults.minHeight;
        var availableWidth = Math.max(window.innerWidth - 64, minWidth);
        var availableHeight = Math.max(window.innerHeight - 128, minHeight);
        var maxWidth = Math.max(minWidth, Math.min(this.defaults.maxWidth, availableWidth));
        var maxHeight = Math.max(minHeight, Math.min(this.defaults.maxHeight, availableHeight));

        var width = Math.min(Math.max(dims.width || this.defaults.width, minWidth), maxWidth);
        var height = Math.min(Math.max(dims.height || this.defaults.height, minHeight), maxHeight);

        this.window.style.width = width + 'px';
        this.window.style.height = height + 'px';

        this.state.dimensions.width = width;
        this.state.dimensions.height = height;
    };

    ChatbotWidget.prototype.persistDimensions = function () {
        try {
            if (typeof window !== 'undefined' && window.localStorage && this.state.dimensions) {
                window.localStorage.setItem('KLASSCI_CHATBOT_SIZE', JSON.stringify(this.state.dimensions));
            }
        } catch (error) {
            debugWarn('Chatbot widget: impossible de sauvegarder les dimensions', error);
        }
    };

    ChatbotWidget.prototype.startResize = function (event) {
        var self = this;

        event.preventDefault();

        if (this.state.isFullscreen || !this.window) {
            return;
        }

        this.state.isResizing = true;
        this.window.classList.add('is-resizing');
        document.body.classList.add('chatbot-resizing');

        var startX = event.clientX;
        var startY = event.clientY;
        var startRect = this.window.getBoundingClientRect();
        var minWidth = this.defaults.minWidth;
        var minHeight = this.defaults.minHeight;
        var maxWidth = Math.max(minWidth, Math.min(window.innerWidth - 64, this.defaults.maxWidth));
        var maxHeight = Math.max(minHeight, Math.min(window.innerHeight - 128, this.defaults.maxHeight));

        if (this.window.setPointerCapture) {
            try {
                this.window.setPointerCapture(event.pointerId);
            } catch (captureError) {
                // ignore capture errors (e.g., pointer already captured)
            }
        }

        function onMove(moveEvent) {
            if (!self.state.isResizing) {
                return;
            }

            var deltaX = moveEvent.clientX - startX;
            var deltaY = moveEvent.clientY - startY;

            var newHeight = Math.min(Math.max(startRect.height - deltaY, minHeight), maxHeight);
            self.state.dimensions.height = newHeight;
            self.applyDimensions();
        }

        function onUp() {
            self.state.isResizing = false;
            self.window.classList.remove('is-resizing');
            document.body.classList.remove('chatbot-resizing');
            if (self.window.releasePointerCapture) {
                try {
                    self.window.releasePointerCapture(event.pointerId);
                } catch (releaseError) {
                    // ignore
                }
            }
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            document.removeEventListener('pointercancel', onUp);
            self.persistDimensions();
        }

        document.addEventListener('pointermove', onMove);
        document.addEventListener('pointerup', onUp);
        document.addEventListener('pointercancel', onUp);
    };

    ChatbotWidget.prototype.setFullscreen = function (value) {
        var nextValue = !!value;

        if (nextValue && !this.state.isFullscreen && this.window) {
            var rect = this.window.getBoundingClientRect();
            this.state.dimensions.width = rect.width;
            this.state.dimensions.height = rect.height;
            this.persistDimensions();
        }

        this.state.isFullscreen = nextValue;
        this.applyFullscreenState();
        this.applyDimensions();
        this.updateBackdropVisibility();
        if (!nextValue) {
            this.persistDimensions();
        }
    };

    ChatbotWidget.prototype.applyFullscreenState = function () {
        if (!this.window) {
            return;
        }

        if (this.state.isFullscreen) {
            this.window.classList.add('is-fullscreen');
            document.body.classList.add('chatbot-fullscreen-active');
        } else {
            this.window.classList.remove('is-fullscreen');
            document.body.classList.remove('chatbot-fullscreen-active');
        }

        if (this.expandButton) {
            var label = this.state.isFullscreen ? 'Réduire' : 'Agrandir';
            this.expandButton.setAttribute('aria-pressed', this.state.isFullscreen ? 'true' : 'false');
            this.expandButton.setAttribute('title', label);
            this.expandButton.setAttribute('aria-label', label);
        }

        if (this.expandButtonIcon) {
            if (this.state.isFullscreen) {
                this.expandButtonIcon.classList.remove('fa-expand');
                this.expandButtonIcon.classList.add('fa-compress');
            } else {
                this.expandButtonIcon.classList.add('fa-expand');
                this.expandButtonIcon.classList.remove('fa-compress');
            }
        }
    };

    ChatbotWidget.prototype.updateBackdropVisibility = function () {
        if (!this.backdrop) {
            return;
        }

        if (this.state.isOpen && this.state.isFullscreen) {
            this.backdrop.classList.add('is-visible');
        } else {
            this.backdrop.classList.remove('is-visible');
        }
    };

    ChatbotWidget.prototype.scrollToBottom = function () {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    };

    function initChatbotWidget() {
        if (!window.KLASSCI_CHATBOT_CONFIG) {
            return;
        }
        new ChatbotWidget(window.KLASSCI_CHATBOT_CONFIG);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChatbotWidget);
    } else {
        initChatbotWidget();
    }
}());
