<?php
/**
 * Cloud Library page template.
 *
 * @var array<string>  $messages  Success messages.
 * @var array<string>  $errors    Error messages.
 * @package StarterSnippets
 */

use StarterSnippets\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories = [
    ''         => __( 'All Categories', 'starter-snippets' ),
    'security' => __( 'Security', 'starter-snippets' ),
    'seo'      => __( 'SEO', 'starter-snippets' ),
    'performance' => __( 'Performance', 'starter-snippets' ),
    'social'   => __( 'Social Media', 'starter-snippets' ),
    'ecommerce' => __( 'E-Commerce', 'starter-snippets' ),
    'utility'  => __( 'Utilities', 'starter-snippets' ),
];

$languages = [
    ''    => __( 'All Languages', 'starter-snippets' ),
    'php' => 'PHP',
    'js'  => 'JavaScript',
    'css' => 'CSS',
    'html'=> 'HTML',
];
?>
<div class="wrap starter-snippets-wrap">
    <h1>
        <?php esc_html_e( 'Cloud Library', 'starter-snippets' ); ?>
        <span class="starter-snippets-badge">Pro</span>
    </h1>

    <?php if ( ! empty( $messages ) ) : ?>
        <?php foreach ( $messages as $msg ) : ?>
            <div class="notice notice-success"><p><?php echo esc_html( $msg ); ?></p></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ( ! empty( $errors ) ) : ?>
        <?php foreach ( $errors as $err ) : ?>
            <div class="notice notice-error"><p><?php echo esc_html( $err ); ?></p></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="starter-snippets-cloud-header">
        <p><?php esc_html_e( 'Browse and import community-contributed code snippets. All snippets are reviewed before being published.', 'starter-snippets' ); ?></p>
        
        <div class="starter-snippets-cloud-filters">
            <select id="cloud-category">
                <?php foreach ( $categories as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="cloud-language">
                <?php foreach ( $languages as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>

            <input type="search" id="cloud-search" placeholder="<?php esc_attr_e( 'Search snippets...', 'starter-snippets' ); ?>" class="search-input">

            <button type="button" id="cloud-search-btn" class="button"><?php esc_html_e( 'Search', 'starter-snippets' ); ?></button>
        </div>
    </div>

    <div id="starter-snippets-cloud-loading" style="display:none; text-align:center; padding:40px;">
        <span class="spinner is-active" style="float:none;"></span>
        <p><?php esc_html_e( 'Loading snippets...', 'starter-snippets' ); ?></p>
    </div>

    <div id="starter-snippets-cloud-content">
        <div id="starter-snippets-cloud-snippets" class="starter-snippets-cloud-grid">
            <div class="notice notice-info" style="grid-column: 1 / -1;">
                <p><?php esc_html_e( 'Click "Search" to browse community snippets, or use filters to narrow down results.', 'starter-snippets' ); ?></p>
            </div>
        </div>

        <div id="starter-snippets-cloud-pagination" style="display:none; margin-top:20px; text-align:center;">
            <button type="button" id="cloud-prev-page" class="button" disabled>&larr; <?php esc_html_e( 'Previous', 'starter-snippets' ); ?></button>
            <span id="cloud-page-info" style="padding: 0 15px; line-height: 30px;"></span>
            <button type="button" id="cloud-next-page" class="button" disabled><?php esc_html_e( 'Next', 'starter-snippets' ); ?> &rarr;</button>
        </div>
    </div>
</div>

<div id="starter-snippets-cloud-preview-modal" class="starter-snippets-modal" style="display:none;">
    <div class="starter-snippets-modal-content starter-snippets-modal-large">
        <span class="starter-snippets-modal-close">&times;</span>
        <h2 id="cloud-preview-title"><?php esc_html_e( 'Snippet Preview', 'starter-snippets' ); ?></h2>
        <div class="cloud-preview-meta">
            <span id="cloud-preview-author"></span> | 
            <span id="cloud-preview-language"></span> | 
            <span id="cloud-preview-installs"></span>
        </div>
        <pre><code id="cloud-preview-code"></code></pre>
        <p id="cloud-preview-description" style="margin: 15px 0;"></p>
        <p style="text-align:center; margin-top:15px;">
            <button type="button" id="cloud-preview-import" class="button button-primary">
                <?php esc_html_e( 'Import This Snippet', 'starter-snippets' ); ?>
            </button>
        </p>
    </div>
</div>

<style>
.starter-snippets-badge {
    background: #d63638;
    color: #fff;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 10px;
}
.starter-snippets-cloud-header {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}
.starter-snippets-cloud-filters {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}
.starter-snippets-cloud-filters select,
.starter-snippets-cloud-filters input {
    margin: 0;
}
.starter-snippets-cloud-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}
.starter-snippets-cloud-snippet {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 15px;
    transition: box-shadow 0.2s ease;
}
.starter-snippets-cloud-snippet:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.starter-snippets-cloud-snippet h3 {
    margin: 0 0 8px;
    font-size: 16px;
}
.starter-snippets-cloud-snippet p {
    font-size: 13px;
    color: #666;
    margin: 0 0 10px;
}
.starter-snippets-cloud-snippet-meta {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
}
.cloud-preview-meta {
    font-size: 13px;
    color: #666;
    margin-bottom: 15px;
}
.starter-snippets-modal-large {
    max-width: 900px;
}
#cloud-preview-code {
    background: #2d2d2d;
    color: #f8f8f2;
    padding: 20px;
    border-radius: 4px;
    overflow-x: auto;
    max-height: 400px;
    font-family: monospace;
    font-size: 13px;
    line-height: 1.5;
}
</style>

<script>
(function($) {
    'use strict';

    var currentPage = 1;
    var currentData = {};

    function loadSnippets(data) {
        $('#starter-snippets-cloud-loading').show();
        $('#starter-snippets-cloud-snippets').empty();

        currentData = data || {};

        $.ajax({
            url: StarterSnippets.ajaxUrl,
            type: 'POST',
            data: $.extend({
                action: 'starter_snippets_fetch_library',
                nonce: StarterSnippets.nonce
            }, currentData),
            success: function(response) {
                $('#starter-snippets-cloud-loading').hide();

                if (!response.success || !response.data.snippets.length) {
                    $('#starter-snippets-cloud-snippets').html(
                        '<div class="notice notice-warning" style="grid-column: 1 / -1;"><p><?php esc_html_e( 'No snippets found. Try different filters.', 'starter-snippets' ); ?></p></div>'
                    );
                    return;
                }

                var html = '';
                response.data.snippets.forEach(function(snippet) {
                    html += '<div class="starter-snippets-cloud-snippet">' +
                        '<div class="starter-snippets-cloud-snippet-meta">' +
                        '<span class="starter-snippets-lang starter-snippets-lang-' + snippet.language + '">' + snippet.language.toUpperCase() + '</span>' +
                        '<span style="color:#666; font-size:12px;">by ' + (snippet.author || 'Anonymous') + '</span>' +
                        '</div>' +
                        '<h3>' + snippet.title + '</h3>' +
                        '<p>' + (snippet.description || '') + '</p>' +
                        '<button type="button" class="button button-primary cloud-preview-btn" ' +
                        'data-id="' + snippet.id + '" ' +
                        'data-title="' + encodeURIComponent(snippet.title) + '" ' +
                        'data-description="' + encodeURIComponent(snippet.description || '') + '" ' +
                        'data-code="' + encodeURIComponent(snippet.code) + '" ' +
                        'data-language="' + snippet.language + '" ' +
                        'data-author="' + (snippet.author || 'Anonymous') + '" ' +
                        '>' +
                        '<?php esc_html_e( 'Preview', 'starter-snippets' ); ?>' +
                        '</button> ' +
                        '<button type="button" class="button cloud-import-btn" ' +
                        'data-id="' + snippet.id + '">' +
                        '<?php esc_html_e( 'Import', 'starter-snippets' ); ?>' +
                        '</button>' +
                        '</div>';
                });
                $('#starter-snippets-cloud-snippets').html(html);

                // Pagination
                if (response.data.total > 20) {
                    $('#starter-snippets-cloud-pagination').show();
                    var totalPages = Math.ceil(response.data.total / 20);
                    $('#cloud-page-info').text('Page ' + currentPage + ' of ' + totalPages);
                    $('#cloud-prev-page').prop('disabled', currentPage <= 1);
                    $('#cloud-next-page').prop('disabled', currentPage >= totalPages);
                } else {
                    $('#starter-snippets-cloud-pagination').hide();
                }
            },
            error: function() {
                $('#starter-snippets-cloud-loading').hide();
                $('#starter-snippets-cloud-snippets').html(
                    '<div class="notice notice-error" style="grid-column: 1 / -1;"><p><?php esc_html_e( 'Failed to load snippets. Please try again.', 'starter-snippets' ); ?></p></div>'
                );
            }
        });
    }

    $(document).ready(function() {
        $('#cloud-search-btn').on('click', function() {
            currentPage = 1;
            loadSnippets({
                category: $('#cloud-category').val(),
                language: $('#cloud-language').val(),
                search: $('#cloud-search').val(),
                page: 1
            });
        });

        $('#cloud-search').on('keypress', function(e) {
            if (e.which === 13) {
                $('#cloud-search-btn').click();
            }
        });

        $('#cloud-prev-page').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadSnippets($.extend(currentData, { page: currentPage }));
            }
        });

        $('#cloud-next-page').on('click', function() {
            currentPage++;
            loadSnippets($.extend(currentData, { page: currentPage }));
        });

        // Preview modal
        $(document).on('click', '.cloud-preview-btn', function() {
            var $btn = $(this);
            $('#cloud-preview-title').text(decodeURIComponent($btn.data('title')));
            $('#cloud-preview-description').text(decodeURIComponent($btn.data('description')));
            $('#cloud-preview-code').text(decodeURIComponent($btn.data('code')));
            $('#cloud-preview-language').text($btn.data('language').toUpperCase());
            $('#cloud-preview-author').text('by ' + $btn.data('author'));
            $('#cloud-preview-import').data('id', $btn.data('id'));
            $('#starter-snippets-cloud-preview-modal').show();
        });

        // Import from preview
        $('#cloud-preview-import').on('click', function() {
            var snippetId = $(this).data('id');
            importSnippet(snippetId);
        });

        // Direct import
        $(document).on('click', '.cloud-import-btn', function() {
            var snippetId = $(this).data('id');
            importSnippet(snippetId);
        });

        function importSnippet(id) {
            $.ajax({
                url: StarterSnippets.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'starter_snippets_import_library_snippet',
                    nonce: StarterSnippets.nonce,
                    snippet_id: id
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#starter-snippets-cloud-preview-modal').hide();
                    } else {
                        alert(response.data.message || '<?php esc_html_e( 'Import failed.', 'starter-snippets' ); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e( 'Import failed. Please try again.', 'starter-snippets' ); ?>');
                }
            });
        }

        // Close modal
        $('.starter-snippets-modal-close').on('click', function() {
            $('#starter-snippets-cloud-preview-modal').hide();
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#starter-snippets-cloud-preview-modal').hide();
            }
        });
    });
})(jQuery);
</script>
