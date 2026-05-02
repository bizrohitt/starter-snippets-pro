/**
 * Starter Snippets – Admin JavaScript
 *
 * @package StarterSnippets
 */

(function ($) {
    'use strict';

    const SS = window.StarterSnippets || {};

    /* ─── CodeMirror Initialization ──────────────── */
    function initCodeEditor() {
        const $codeTextarea = document.getElementById('snippet-code');
        if (!$codeTextarea) return;

        // Get CodeMirror settings from WP (set by wp_enqueue_code_editor).
        const cmSettings = window.StarterSnippetsCM;
        if (!cmSettings) return;

        const editorInstance = wp.codeEditor.initialize($codeTextarea, cmSettings);
        window.starterSnippetsEditor = editorInstance;

        // Dynamic language switching.
        const $langSelect = document.getElementById('snippet-language');
        if ($langSelect) {
            $langSelect.addEventListener('change', function () {
                const modeMap = {
                    php: 'text/x-php',
                    js: 'text/javascript',
                    css: 'text/css',
                    html: 'text/html',
                };
                const mode = modeMap[this.value] || 'text/x-php';
                editorInstance.codemirror.setOption('mode', mode);
            });
        }
    }

    /* ─── AJAX Status Toggle ─────────────────────── */
    function initToggle() {
        $(document).on('click', '.starter-snippets-toggle', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const snippetId = $btn.data('snippet-id');
            const $status = $btn.find('.starter-snippets-status');

            $btn.prop('disabled', true);

            $.post(SS.ajaxUrl, {
                action: 'starter_snippets_toggle',
                nonce: SS.nonce,
                snippet_id: snippetId,
            })
            .done(function (response) {
                if (response.success) {
                    const data = response.data;
                    $status
                        .text(data.label)
                        .removeClass('starter-snippets-status--active starter-snippets-status--inactive')
                        .addClass('starter-snippets-status--' + data.status);
                    $btn.data('status', data.status);
                } else {
                    alert(response.data?.message || SS.i18n?.error || 'Error');
                }
            })
            .fail(function () {
                alert(SS.i18n?.error || 'An error occurred.');
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
        });
    }

    /* ─── Condition Row Builder ───────────────────── */
    function initConditions() {
        let condIndex = $('#starter-snippets-conditions-table tbody tr').length;

        $('#starter-snippets-add-condition').on('click', function () {
            const template = wp.template('starter-snippets-condition-row');
            const html = template({ index: condIndex });
            $('#starter-snippets-conditions-table tbody').append(html);
            condIndex++;
            updatePlaceholders();
        });

        $(document).on('click', '.starter-snippets-remove-condition', function () {
            $(this).closest('tr').remove();
        });

        $(document).on('change', 'select[name$="[condition_type]"]', function() {
            updatePlaceholder($(this));
        });

        updatePlaceholders();
    }

    function updatePlaceholders() {
        $('select[name$="[condition_type]"]').each(function() {
            updatePlaceholder($(this));
        });
    }

    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    function updatePlaceholder($select) {
        const type = $select.val();
        const $td = $select.closest('tr').find('td').eq(1); // The Value cell is the 2nd one
        const baseName = $select.attr('name').replace('[condition_type]', '[condition_value]');
        
        let currentValue = $td.find(':input').val();
        if (currentValue === undefined) {
             currentValue = $td.data('initial-value') || '';
        }
        $td.data('initial-value', currentValue); // Store so we don't lose it while swapping
        
        let html = '';

        if (type === 'device') {
            const isMobile = currentValue.toLowerCase() === 'mobile';
            const isDesktop = currentValue.toLowerCase() === 'desktop';
            html = `<select name="${escapeHtml(baseName)}" class="condition-value-input">
                <option value="mobile" ${isMobile ? 'selected' : ''}>Mobile</option>
                <option value="desktop" ${isDesktop ? 'selected' : ''}>Desktop</option>
            </select>`;
        } else if (type === 'logged_in') {
            const isYes = ['1', 'yes', 'true'].includes(currentValue.toLowerCase());
            html = `<select name="${escapeHtml(baseName)}" class="condition-value-input">
                <option value="1" ${isYes ? 'selected' : ''}>Logged In</option>
                <option value="0" ${!isYes ? 'selected' : ''}>Logged Out</option>
            </select>`;
        } else {
            const placeholders = {
                'page_id': 'e.g. 42',
                'post_id': 'e.g. 10',
                'post_type': 'e.g. page, post, product',
                'user_role': 'e.g. administrator',
                'url_pattern': 'e.g. /checkout/.*',
                'schedule': 'YYYY-MM-DD:YYYY-MM-DD',
                'country': 'e.g. US, GB, CA',
                'woo_cart_total': 'e.g. >100 or <=50',
                'woo_cart_product': 'e.g. 123 (Product ID)'
            };
            const placeholder = placeholders[type] || 'Value...';
            html = `<input type="text" name="${escapeHtml(baseName)}" class="condition-value-input" value="${escapeHtml(currentValue)}" placeholder="${escapeHtml(placeholder)}">`;
        }

        $td.html(html);
    }

    /* ─── Bulk Action Confirmation ────────────────── */
    function initBulkActions() {
        $('form').on('submit', function (e) {
            const action = $('#starter-snippets-bulk-action').val();
            if (action === 'delete') {
                if (!confirm(SS.i18n?.confirmBulk || 'Are you sure?')) {
                    e.preventDefault();
                }
            }
        });
    }

    /* ─── Select All Checkbox ────────────────────── */
    function initSelectAll() {
        $('#cb-select-all').on('change', function () {
            $('input[name="snippet_ids[]"]').prop('checked', this.checked);
        });
    }

    /* ─── Init ───────────────────────────────────── */
    $(document).ready(function () {
        initCodeEditor();
        initToggle();
        initConditions();
        initBulkActions();
        initSelectAll();
    });

})(jQuery);
