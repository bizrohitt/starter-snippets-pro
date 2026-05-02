<?php
/**
 * Templates page template.
 *
 * @var array<object>  $templates       List of templates.
 * @var int            $total           Total template count.
 * @var string         $filter_type     Current filter type.
 * @var string         $filter_lang     Current language filter.
 * @var string         $search          Current search query.
 * @var int            $page            Current page number.
 * @var int            $per_page        Items per page.
 * @var int            $builtin_count    Count of built-in templates.
 * @var int            $user_count       Count of user templates.
 * @var array<string>  $messages        Success messages.
 * @var array<string>  $errors          Error messages.
 * @package StarterSnippets
 */

use StarterSnippets\Core\Config;
use StarterSnippets\Security\NonceHandler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$languages = [
    'all'  => __( 'All Languages', 'starter-snippets' ),
    'php'  => 'PHP',
    'js'   => 'JavaScript',
    'css'  => 'CSS',
    'html' => 'HTML',
];

$type_filters = [
    'all'     => __( 'All Templates', 'starter-snippets' ),
    'builtin' => __( 'Built-in', 'starter-snippets' ),
    'user'    => __( 'My Templates', 'starter-snippets' ),
];

$total_pages = ceil( $total / $per_page );
?>
<div class="wrap starter-snippets-wrap">
    <h1>
        <?php esc_html_e( 'Snippet Templates', 'starter-snippets' ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=starter-snippets-templates&action=reinstall' ) ); ?>" 
           class="page-title-action" 
           onclick="return confirm('<?php esc_attr_e( 'This will reinstall all built-in templates. Continue?', 'starter-snippets' ); ?>');">
            <?php esc_html_e( 'Reinstall Built-in Templates', 'starter-snippets' ); ?>
        </a>
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

    <div class="starter-snippets-templates-header">
        <div class="starter-snippets-templates-stats">
            <span class="starter-snippets-stat">
                <strong><?php echo esc_html( $builtin_count ); ?></strong> 
                <?php esc_html_e( 'Built-in', 'starter-snippets' ); ?>
            </span>
            <span class="starter-snippets-stat">
                <strong><?php echo esc_html( $user_count ); ?></strong> 
                <?php esc_html_e( 'My Templates', 'starter-snippets' ); ?>
            </span>
        </div>

        <form method="get" class="starter-snippets-templates-filters">
            <input type="hidden" name="page" value="starter-snippets-templates">

            <select name="filter_type" id="filter-type">
                <?php foreach ( $type_filters as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter_type, $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="filter_language" id="filter-language">
                <?php foreach ( $languages as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter_lang, $value ); ?>>
                        <?php echo 'all' === $value ? esc_html( $label ) : esc_html( strtoupper( $value ) ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" 
                   placeholder="<?php esc_attr_e( 'Search templates...', 'starter-snippets' ); ?>" 
                   class="search-input">

            <?php submit_button( __( 'Filter', 'starter-snippets' ), 'secondary', '', false ); ?>
        </form>
    </div>

    <?php if ( empty( $templates ) ) : ?>
        <div class="notice notice-info">
            <p><?php esc_html_e( 'No templates found matching your criteria.', 'starter-snippets' ); ?></p>
        </div>
    <?php else : ?>
        <table class="widefat starter-snippets-templates-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Title', 'starter-snippets' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'starter-snippets' ); ?></th>
                    <th><?php esc_html_e( 'Language', 'starter-snippets' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'starter-snippets' ); ?></th>
                    <th><?php esc_html_e( 'Uses', 'starter-snippets' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'starter-snippets' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $templates as $template ) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $template->title ); ?></strong>
                            <?php if ( ! empty( $template->tags ) ) : ?>
                                <div class="starter-snippets-template-tags">
                                    <?php foreach ( explode( ',', $template->tags ) as $tag ) : ?>
                                        <span class="starter-snippets-tag"><?php echo esc_html( trim( $tag ) ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="starter-snippets-description">
                            <span class="starter-snippets-desc-text"><?php echo esc_html( wp_trim_words( $template->description, 10 ) ); ?></span>
                            <?php if ( strlen( $template->description ) > 100 ) : ?>
                                <button type="button" class="starter-snippets-expand-desc button-link" 
                                        data-full="<?php echo esc_attr( $template->description ); ?>">
                                    <?php esc_html_e( 'Read more', 'starter-snippets' ); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="starter-snippets-lang starter-snippets-lang-<?php echo esc_attr( $template->language ); ?>">
                                <?php echo esc_html( strtoupper( $template->language ) ); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ( $template->is_builtin ) : ?>
                                <span class="starter-snippets-type-builtin"><?php esc_html_e( 'Built-in', 'starter-snippets' ); ?></span>
                            <?php else : ?>
                                <span class="starter-snippets-type-user"><?php esc_html_e( 'User', 'starter-snippets' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $template->usage_count ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=starter-snippets-templates&action=use-template&template_id=' . $template->id ), 'use_template_' . $template->id ) ); ?>" 
                               class="button button-primary button-small">
                                <?php esc_html_e( 'Use Template', 'starter-snippets' ); ?>
                            </a>
                            
                            <?php if ( ! $template->is_builtin ) : ?>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=starter-snippets-templates&action=delete&template_id=' . $template->id ), 'delete_template_' . $template->id ) ); ?>" 
                                   class="button button-small starter-snippets-delete-template"
                                   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this template?', 'starter-snippets' ); ?>');">
                                    <?php esc_html_e( 'Delete', 'starter-snippets' ); ?>
                                </a>
                            <?php endif; ?>
                            
                            <button type="button" class="button button-small starter-snippets-preview-template" 
                                    data-code="<?php echo esc_attr( base64_encode( $template->code ) ); ?>"
                                    data-language="<?php echo esc_attr( $template->language ); ?>">
                                <?php esc_html_e( 'Preview', 'starter-snippets' ); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php
                        printf(
                            esc_html( _n( '%s item', '%s items', $total, 'starter-snippets' ) ),
                            number_format_i18n( $total )
                        );
                        ?>
                    </span>
                    <span class="pagination-links">
                        <?php
                        $base_url = add_query_arg( [
                            'page'          => 'starter-snippets-templates',
                            'filter_type'   => $filter_type,
                            'filter_language' => $filter_lang,
                            's'             => $search,
                        ], admin_url( 'admin.php' ) );

                        echo paginate_links( [
                            'base'      => $base_url . '&paged=%#%',
                            'format'    => '',
                            'current'   => $page,
                            'total'     => $total_pages,
                            'prev_text' => '&larr;',
                            'next_text' => '&rarr;',
                        ] );
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div id="starter-snippets-template-preview-modal" class="starter-snippets-modal" style="display:none;">
    <div class="starter-snippets-modal-content">
        <span class="starter-snippets-modal-close">&times;</span>
        <h2><?php esc_html_e( 'Code Preview', 'starter-snippets' ); ?></h2>
        <pre><code id="starter-snippets-preview-code"></code></pre>
        <p style="text-align:center; margin-top:15px;">
            <a href="#" id="starter-snippets-preview-use" class="button button-primary">
                <?php esc_html_e( 'Use This Template', 'starter-snippets' ); ?>
            </a>
        </p>
    </div>
</div>

<style>
.starter-snippets-templates-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}
.starter-snippets-templates-stats {
    display: flex;
    gap: 20px;
}
.starter-snippets-stat {
    padding: 5px 15px;
    background: #f0f0f1;
    border-radius: 4px;
}
.starter-snippets-templates-filters {
    display: flex;
    gap: 10px;
    align-items: center;
}
.starter-snippets-templates-filters select,
.starter-snippets-templates-filters input {
    margin: 0;
}
.starter-snippets-template-tags {
    display: flex;
    gap: 5px;
    margin-top: 5px;
    flex-wrap: wrap;
}
.starter-snippets-tag {
    font-size: 11px;
    padding: 2px 6px;
    background: #e0e0e0;
    border-radius: 3px;
    color: #555;
}
.starter-snippets-lang {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}
.starter-snippets-lang-php { background: #8892be; color: #fff; }
.starter-snippets-lang-js { background: #f7df1e; color: #000; }
.starter-snippets-lang-css { background: #264de4; color: #fff; }
.starter-snippets-lang-html { background: #e34c26; color: #fff; }
.starter-snippets-type-builtin {
    color: #2271b1;
    font-weight: 500;
}
.starter-snippets-type-user {
    color: #00a32a;
    font-weight: 500;
}
.starter-snippets-description {
    max-width: 300px;
}
.starter-snippets-desc-text {
    display: block;
}
.starter-snippets-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
    overflow: auto;
}
.starter-snippets-modal-content {
    background: #fff;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    border-radius: 4px;
    position: relative;
}
.starter-snippets-modal-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 30px;
    cursor: pointer;
    color: #666;
}
.starter-snippets-modal-close:hover {
    color: #000;
}
#starter-snippets-preview-code {
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

    $(document).ready(function() {
        // Expand description
        $('.starter-snippets-expand-desc').on('click', function() {
            var $btn = $(this);
            var fullText = $btn.data('full');
            var $descText = $btn.siblings('.starter-snippets-desc-text');
            
            if ($btn.text() === '<?php esc_js_e( 'Read more', 'starter-snippets' ); ?>') {
                $descText.text(fullText);
                $btn.text('<?php esc_js_e( 'Show less', 'starter-snippets' ); ?>');
            } else {
                $descText.text(fullText.substring(0, 100) + '...');
                $btn.text('<?php esc_js_e( 'Read more', 'starter-snippets' ); ?>');
            }
        });

        // Preview modal
        $('.starter-snippets-preview-template').on('click', function() {
            var $btn = $(this);
            var code = atob($btn.data('code'));
            var lang = $btn.data('language');
            var $modal = $('#starter-snippets-template-preview-modal');
            
            $('#starter-snippets-preview-code').text(code);
            $('#starter-snippets-preview-code').attr('class', 'language-' + lang);
            $modal.show();
        });

        // Close modal
        $('.starter-snippets-modal-close').on('click', function() {
            $('#starter-snippets-template-preview-modal').hide();
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#starter-snippets-template-preview-modal').hide();
            }
        });

        // Close on click outside
        $('#starter-snippets-template-preview-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
    });
})(jQuery);
</script>
