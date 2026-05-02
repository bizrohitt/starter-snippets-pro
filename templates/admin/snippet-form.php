<?php
/**
 * Add / Edit snippet form template.
 *
 * @var object|null  $snippet        Existing snippet object or null.
 * @var array        $conditions     Array of condition objects.
 * @var array        $errors         Validation error messages.
 * @var bool         $editing        True if editing an existing snippet.
 * @var bool         $from_template  True if snippet was created from template.
 * @var array        $templates      Available templates for selector.
 * @package StarterSnippets
 */

use StarterSnippets\Core\Config;
use StarterSnippets\Security\NonceHandler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title       = $snippet->title       ?? '';
$description = $snippet->description ?? '';
$code        = $snippet->code        ?? '';
$language    = $snippet->language    ?? 'php';
$location    = $snippet->location    ?? 'everywhere';
$priority    = $snippet->priority    ?? 10;
$status      = $snippet->status      ?? 'inactive';
$tags        = $snippet->tags        ?? '';
$snippet_id  = $snippet->id          ?? 0;

$is_pro         = \StarterSnippets\Core\starter_snippets_is_pro_active();
$pro_conditions = [ 'device', 'schedule', 'country', 'woo_cart_total', 'woo_cart_product' ];
$templates      = $templates ?? [];
?>
<div class="wrap starter-snippets-wrap">
    <h1>
        <?php echo $editing
            ? esc_html__( 'Edit Snippet', 'starter-snippets' )
            : esc_html__( 'Add New Snippet', 'starter-snippets' ); ?>
        <?php if ( $editing ) : ?>
            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . Config::MENU_SLUG . '-add&template_action=save_as_template&snippet_id=' . $snippet_id ), 'save_as_template_' . $snippet_id ) ); ?>" 
               class="page-title-action">
                <?php esc_html_e( 'Save as Template', 'starter-snippets' ); ?>
            </a>
        <?php endif; ?>
    </h1>

    <?php if ( ! empty( $templates ) && ! $editing ) : ?>
        <div class="starter-snippets-template-selector">
            <h2><?php esc_html_e( 'Start from a Template', 'starter-snippets' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Choose a template below to pre-fill the form, or start from scratch.', 'starter-snippets' ); ?></p>
            <div class="starter-snippets-template-grid">
                <?php foreach ( $templates as $tmpl ) : ?>
                    <div class="starter-snippets-template-card" data-id="<?php echo esc_attr( $tmpl->id ); ?>">
                        <div class="starter-snippets-template-card-header">
                            <span class="starter-snippets-lang starter-snippets-lang-<?php echo esc_attr( $tmpl->language ); ?>">
                                <?php echo esc_html( strtoupper( $tmpl->language ) ); ?>
                            </span>
                            <?php if ( $tmpl->is_builtin ) : ?>
                                <span class="starter-snippets-type-builtin"><?php esc_html_e( 'Built-in', 'starter-snippets' ); ?></span>
                            <?php else : ?>
                                <span class="starter-snippets-type-user"><?php esc_html_e( 'User', 'starter-snippets' ); ?></span>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo esc_html( $tmpl->title ); ?></h3>
                        <p><?php echo esc_html( wp_trim_words( $tmpl->description, 15 ) ); ?></p>
                        <button type="button" class="button button-primary starter-snippets-use-template" 
                                data-id="<?php echo esc_attr( $tmpl->id ); ?>"
                                data-title="<?php echo esc_attr( $tmpl->title ); ?>"
                                data-description="<?php echo esc_attr( $tmpl->description ); ?>"
                                data-code="<?php echo esc_attr( base64_encode( $tmpl->code ) ); ?>"
                                data-language="<?php echo esc_attr( $tmpl->language ); ?>"
                                data-location="<?php echo esc_attr( $tmpl->location ); ?>"
                                data-tags="<?php echo esc_attr( $tmpl->tags ); ?>">
                            <?php esc_html_e( 'Use Template', 'starter-snippets' ); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $errors ) ) : ?>
        <div class="notice notice-error">
            <?php foreach ( $errors as $err ) : ?>
                <p><?php echo esc_html( $err ); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" id="starter-snippets-form" class="starter-snippets-editor-form">
        <?php NonceHandler::field(); ?>
        <input type="hidden" name="starter_snippets_action" value="save_snippet">
        <input type="hidden" name="snippet_id" value="<?php echo esc_attr( $snippet_id ); ?>">

        <!-- Title -->
        <div class="starter-snippets-field">
            <label for="snippet-title"><?php esc_html_e( 'Title', 'starter-snippets' ); ?> <span class="required">*</span></label>
            <input type="text" id="snippet-title" name="title"
                   value="<?php echo esc_attr( $title ); ?>"
                   class="regular-text" required maxlength="255"
                   placeholder="<?php esc_attr_e( 'My Snippet Name', 'starter-snippets' ); ?>">
        </div>

        <!-- Description -->
        <div class="starter-snippets-field">
            <label for="snippet-description"><?php esc_html_e( 'Description', 'starter-snippets' ); ?></label>
            <textarea id="snippet-description" name="description" rows="2"
                      class="large-text"
                      placeholder="<?php esc_attr_e( 'Optional description...', 'starter-snippets' ); ?>"
            ><?php echo esc_textarea( $description ); ?></textarea>
        </div>

        <!-- Code Editor -->
        <div class="starter-snippets-field starter-snippets-code-field">
            <label for="snippet-code"><?php esc_html_e( 'Code', 'starter-snippets' ); ?> <span class="required">*</span></label>
            <textarea id="snippet-code" name="code" rows="15"
                      class="large-text code"
            ><?php echo esc_textarea( $code ); ?></textarea>
        </div>

        <div class="starter-snippets-meta-row">
            <!-- Language -->
            <div class="starter-snippets-field">
                <label for="snippet-language"><?php esc_html_e( 'Language', 'starter-snippets' ); ?></label>
                <select id="snippet-language" name="language">
                    <?php foreach ( Config::LANGUAGES as $lang ) : ?>
                        <option value="<?php echo esc_attr( $lang ); ?>" <?php selected( $language, $lang ); ?>>
                            <?php echo esc_html( strtoupper( $lang ) ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Location -->
            <div class="starter-snippets-field">
                <label for="snippet-location"><?php esc_html_e( 'Run Location', 'starter-snippets' ); ?></label>
                <select id="snippet-location" name="location">
                    <?php
                    $location_labels = [
                        'everywhere' => __( 'Everywhere (Site-wide)', 'starter-snippets' ),
                        'frontend'   => __( 'Frontend Only', 'starter-snippets' ),
                        'admin'      => __( 'Admin Only', 'starter-snippets' ),
                        'header'     => __( 'Header (wp_head)', 'starter-snippets' ),
                        'footer'     => __( 'Footer (wp_footer)', 'starter-snippets' ),
                    ];
                    foreach ( $location_labels as $val => $label ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $location, $val ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Priority -->
            <div class="starter-snippets-field">
                <label for="snippet-priority"><?php esc_html_e( 'Priority', 'starter-snippets' ); ?></label>
                <input type="number" id="snippet-priority" name="priority"
                       value="<?php echo esc_attr( $priority ); ?>"
                       min="1" max="999" step="1" style="width:80px;">
            </div>

            <!-- Status -->
            <div class="starter-snippets-field">
                <label for="snippet-status"><?php esc_html_e( 'Status', 'starter-snippets' ); ?></label>
                <select id="snippet-status" name="status">
                    <option value="inactive" <?php selected( $status, 'inactive' ); ?>>
                        <?php esc_html_e( 'Inactive', 'starter-snippets' ); ?>
                    </option>
                    <option value="active" <?php selected( $status, 'active' ); ?>>
                        <?php esc_html_e( 'Active', 'starter-snippets' ); ?>
                    </option>
                </select>
            </div>
        </div>

        <!-- Tags -->
        <div class="starter-snippets-field">
            <label for="snippet-tags"><?php esc_html_e( 'Tags / Categories', 'starter-snippets' ); ?></label>
            <input type="text" id="snippet-tags" name="tags"
                   value="<?php echo esc_attr( $tags ); ?>"
                   class="regular-text"
                   placeholder="<?php esc_attr_e( 'e.g. analytics, header, tracking', 'starter-snippets' ); ?>">
            <p class="description"><?php esc_html_e( 'Comma-separated tags for organizing snippets.', 'starter-snippets' ); ?></p>
        </div>

        <!-- Conditional Execution -->
        <div class="starter-snippets-conditions-section">
            <h3><?php esc_html_e( 'Conditional Execution', 'starter-snippets' ); ?></h3>
            <p class="description"><?php esc_html_e( 'Add conditions to control where and when this snippet runs. Leave empty to run everywhere.', 'starter-snippets' ); ?></p>

            <table class="widefat" id="starter-snippets-conditions-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Type', 'starter-snippets' ); ?></th>
                        <th><?php esc_html_e( 'Value', 'starter-snippets' ); ?></th>
                        <th><?php esc_html_e( 'Operator', 'starter-snippets' ); ?></th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $conditions ) ) : ?>
                        <?php foreach ( $conditions as $i => $cond ) : ?>
                            <tr class="starter-snippets-condition-row">
                                <td>
                                    <select name="conditions[<?php echo $i; ?>][condition_type]">
                                        <?php foreach ( Config::CONDITION_TYPES as $ct ) : 
                                            $is_pro_cond  = in_array( $ct, $pro_conditions, true );
                                            $disabledAttr = ( ! $is_pro && $is_pro_cond ) ? 'disabled' : '';
                                            $label        = ucwords( str_replace( '_', ' ', $ct ) ) . ( ( ! $is_pro && $is_pro_cond ) ? ' [PRO]' : '' );
                                        ?>
                                            <option value="<?php echo esc_attr( $ct ); ?>" <?php selected( $cond->condition_type, $ct ); ?> <?php echo $disabledAttr; ?>>
                                                <?php echo esc_html( $label ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td data-initial-value="<?php echo esc_attr( $cond->condition_value ); ?>">
                                    <input type="text" class="condition-value-input" name="conditions[<?php echo $i; ?>][condition_value]"
                                           value="<?php echo esc_attr( $cond->condition_value ); ?>"
                                           placeholder="<?php esc_attr_e( 'Value...', 'starter-snippets' ); ?>">
                                </td>
                                <td>
                                    <select name="conditions[<?php echo $i; ?>][condition_operator]">
                                        <option value="include" <?php selected( $cond->condition_operator, 'include' ); ?>>
                                            <?php esc_html_e( 'Include', 'starter-snippets' ); ?>
                                        </option>
                                        <option value="exclude" <?php selected( $cond->condition_operator, 'exclude' ); ?>>
                                            <?php esc_html_e( 'Exclude', 'starter-snippets' ); ?>
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="button starter-snippets-remove-condition" title="<?php esc_attr_e( 'Remove', 'starter-snippets' ); ?>">&times;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button" id="starter-snippets-add-condition">
                    <?php esc_html_e( '+ Add Condition', 'starter-snippets' ); ?>
                </button>
            </p>
        </div>

        <!-- Revisions -->
        <?php if ( $editing ) : ?>
        <div class="starter-snippets-revisions-section" style="margin-top: 30px; margin-bottom: 20px; padding-top: 20px; border-top: 1px solid #ccd0d4;">
            <h3><?php esc_html_e( 'Version History', 'starter-snippets' ); ?> <?php if ( ! $is_pro ) : ?><span class="update-plugins count-1" style="background-color:#d63638;color:#fff;border-radius:10px;padding:2px 6px;font-size:10px;margin-left:5px;">Pro</span><?php endif; ?></h3>
            
            <?php if ( ! $is_pro ) : ?>
                <p class="description"><?php esc_html_e( 'Upgrade to Pro to track snippet versions and restore previous code states.', 'starter-snippets' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=starter-snippets-license' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Unlock Pro', 'starter-snippets' ); ?></a>
            <?php elseif ( ! empty( $revisions ) ) : ?>
                <p class="description"><?php esc_html_e( 'Click "Restore" to load a past version of your code into the editor above. Remember to save your changes.', 'starter-snippets' ); ?></p>
                <table class="widefat striped" style="max-width: 800px; margin-top: 10px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'starter-snippets' ); ?></th>
                            <th><?php esc_html_e( 'Author', 'starter-snippets' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'starter-snippets' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $revisions as $rev ) : 
                            $user = get_userdata( $rev->created_by );
                            $author = $user ? $user->display_name : 'Unknown';
                        ?>
                            <tr>
                                <td><?php echo esc_html( wp_date( get_option('date_format') . ' ' . get_option('time_format'), strtotime($rev->created_at) ) ); ?></td>
                                <td><?php echo esc_html( $author ); ?></td>
                                <td>
                                    <button type="button" class="button button-small starter-snippets-restore-rev" data-code="<?php echo esc_attr( base64_encode($rev->code) ); ?>">
                                        <?php esc_html_e( 'Restore', 'starter-snippets' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <script>
                jQuery(document).ready(function($) {
                    $('.starter-snippets-restore-rev').on('click', function(e) {
                        e.preventDefault();
                        if (confirm('<?php echo esc_js( __( 'This will overwrite the current code in the editor. Proceed?', 'starter-snippets' ) ); ?>')) {
                            const b64Code = $(this).data('code');
                            
                            // Decode base64 to utf-8 properly to support special characters
                            const decoded = decodeURIComponent(escape(window.atob(b64Code)));
                            
                            if (window.starterSnippetsEditor) {
                                window.starterSnippetsEditor.codemirror.setValue(decoded);
                            } else {
                                $('#snippet-code').val(decoded);
                            }
                            
                            // Scroll to top to see it
                            $('html, body').animate({scrollTop: $('#snippet-code').offset().top - 50 }, 'fast');
                        }
                    });
                });
                </script>
            <?php else : ?>
                <p><?php esc_html_e( 'No revisions found yet.', 'starter-snippets' ); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php submit_button( $editing ? __( 'Update Snippet', 'starter-snippets' ) : __( 'Save Snippet', 'starter-snippets' ) ); ?>
    </form>
</div>

<style>
.starter-snippets-template-selector {
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}
.starter-snippets-template-selector h2 {
    margin-top: 0;
    margin-bottom: 10px;
}
.starter-snippets-template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin-top: 15px;
}
.starter-snippets-template-card {
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    transition: box-shadow 0.2s ease;
}
.starter-snippets-template-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.starter-snippets-template-card h3 {
    margin: 10px 0 5px;
    font-size: 15px;
}
.starter-snippets-template-card p {
    font-size: 13px;
    color: #666;
    margin-bottom: 10px;
}
.starter-snippets-template-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
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
    font-size: 10px;
    color: #2271b1;
    font-weight: 500;
}
.starter-snippets-type-user {
    font-size: 10px;
    color: #00a32a;
    font-weight: 500;
}
</style>

<script>
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle template selection
        $('.starter-snippets-use-template').on('click', function() {
            var $btn = $(this);
            var data = $btn.data();

            // Fill form fields
            $('#snippet-title').val(data.title);
            $('#snippet-description').val(data.description);
            $('#snippet-language').val(data.language);
            $('#snippet-location').val(data.location);
            $('#snippet-tags').val(data.tags);

            // Decode and set code
            var decodedCode = decodeURIComponent(escape(window.atob(data.code)));
            if (window.starterSnippetsEditor) {
                window.starterSnippetsEditor.codemirror.setValue(decodedCode);
            } else {
                $('#snippet-code').val(decodedCode);
            }

            // Scroll to form
            $('html, body').animate({
                scrollTop: $('#starter-snippets-form').offset().top - 50
            }, 'fast');

            // Hide template selector
            $('.starter-snippets-template-selector').slideUp();
        });
    });
})(jQuery);
</script>

<!-- Hidden template for new condition rows -->
<script type="text/html" id="tmpl-starter-snippets-condition-row">
    <tr class="starter-snippets-condition-row">
        <td>
            <select name="conditions[{{data.index}}][condition_type]">
                <?php foreach ( Config::CONDITION_TYPES as $ct ) : 
                    $is_pro_cond  = in_array( $ct, $pro_conditions, true );
                    $disabledAttr = ( ! $is_pro && $is_pro_cond ) ? 'disabled' : '';
                    $label        = ucwords( str_replace( '_', ' ', $ct ) ) . ( ( ! $is_pro && $is_pro_cond ) ? ' [PRO]' : '' );
                ?>
                    <option value="<?php echo esc_attr( $ct ); ?>" <?php echo $disabledAttr; ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td data-initial-value="">
            <input type="text" class="condition-value-input" name="conditions[{{data.index}}][condition_value]"
                   placeholder="<?php esc_attr_e( 'Value...', 'starter-snippets' ); ?>">
        </td>
        <td>
            <select name="conditions[{{data.index}}][condition_operator]">
                <option value="include"><?php esc_html_e( 'Include', 'starter-snippets' ); ?></option>
                <option value="exclude"><?php esc_html_e( 'Exclude', 'starter-snippets' ); ?></option>
            </select>
        </td>
        <td>
            <button type="button" class="button starter-snippets-remove-condition" title="<?php esc_attr_e( 'Remove', 'starter-snippets' ); ?>">&times;</button>
        </td>
    </tr>
</script>
