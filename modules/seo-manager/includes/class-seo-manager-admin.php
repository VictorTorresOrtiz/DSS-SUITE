<?php
/**
 * Admin logic for SEO Manager module.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DSS_SEO_Manager_Admin
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_dss_seo_audit_scan', array($this, 'ajax_audit_scan'));

        // Server-side replacement engine
        if (!is_admin()) {
            add_action('wp', array($this, 'start_buffer'));
        }
    }

    /**
     * Enqueue Admin Assets.
     */
    public function enqueue_admin_assets($hook)
    {
        if ('dss-suite_page_tag-changer' !== $hook) {
            return;
        }
        wp_enqueue_style('dss-seo-manager-style', DSS_SEO_MANAGER_URL . 'assets/css/seo-manager-admin.css', array('dashicons'), DSS_SEO_MANAGER_VERSION);
        wp_enqueue_script('dss-seo-audit', DSS_SEO_MANAGER_URL . 'assets/js/seo-audit.js', array('jquery'), DSS_SEO_MANAGER_VERSION, true);
        wp_localize_script('dss-seo-audit', 'dssSeoAudit', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dss_seo_audit_nonce'),
        ));
    }

    /**
     * Add sub-menu to DSS Suite.
     */
    public function add_menu_page()
    {
        add_submenu_page(
            'dss-suite',
            'SEO Manager',
            'SEO Manager',
            'manage_options',
            'tag-changer',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render settings page.
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['save_tags'])) {
            check_admin_referer('dss_seo_rules_nonce');
            $sanitized_rules = array();
            if (isset($_POST['tag_rules']) && is_array($_POST['tag_rules'])) {
                foreach ($_POST['tag_rules'] as $rule) {
                    if (!empty($rule['selector']) && !empty($rule['tag'])) {
                        $sanitized_rules[] = array(
                            'selector' => sanitize_text_field($rule['selector']),
                            'tag' => sanitize_text_field($rule['tag']),
                            'extra_classes' => sanitize_text_field($rule['extra_classes'] ?? '')
                        );
                    }
                }
            }
            update_option('tag_changer_rules', $sanitized_rules);
            if (class_exists('DSS_Notifications')) {
                DSS_Notifications::get_instance()->add_persistent('Las reglas SEO se han guardado correctamente.', 'success', 'SEO Manager');
            }
            wp_safe_redirect(admin_url('admin.php?page=tag-changer'));
            exit;
        }

        $rules = get_option('tag_changer_rules', [['selector' => '', 'tag' => 'h3', 'extra_classes' => '']]);
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'rules';
        ?>
        <div class="wrap dss-seo-container">
            <div class="dss-seo-header">
                <h1><span class="dashicons dashicons-text-page"
                        style="font-size: 28px; width: 28px; height: 28px; color: #2271b1;"></span> SEO Structure Manager</h1>
                <span class="dss-badge">Server-Side Engine</span>
            </div>

            <nav class="dss-seo-tabs">
                <a href="<?php echo esc_url(admin_url('admin.php?page=tag-changer&tab=rules')); ?>"
                   class="dss-seo-tab <?php echo $active_tab === 'rules' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-editor-code"></span> Reglas de Estructura
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=tag-changer&tab=audit')); ?>"
                   class="dss-seo-tab <?php echo $active_tab === 'audit' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-search"></span> Auditoría SEO
                </a>
            </nav>

            <?php if ($active_tab === 'rules'): ?>

            <p style="color: #64748b; font-size: 15px; margin-bottom: 25px;">
                Cambia dinámicamente las etiquetas HTML (h1-h6, div, p) basándote en selectores CSS.
                <strong>Procesado en el servidor</strong> para máxima compatibilidad con Google y otros buscadores.
            </p>

            <div class="dss-seo-card">
                <form method="post">
                    <?php wp_nonce_field('dss_seo_rules_nonce'); ?>
                    <table class="wp-list-table widefat fixed striped dss-seo-table" id="tag-rules-table">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Selector CSS (Ej: .post_title)</th>
                                <th style="width: 15%;">Nueva Etiqueta</th>
                                <th style="width: 30%;">Clases Extra (Opcional)</th>
                                <th style="width: 15%;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rules))
                                $rules = [['selector' => '', 'tag' => 'h3', 'extra_classes' => '']]; ?>
                            <?php foreach ($rules as $index => $rule): ?>
                                <tr>
                                    <td><input type="text" name="tag_rules[<?php echo $index; ?>][selector]"
                                            value="<?php echo esc_attr($rule['selector']); ?>" class="large-text dss-seo-input"
                                            placeholder="h4.post_title"></td>
                                    <td>
                                        <select name="tag_rules[<?php echo $index; ?>][tag]" class="dss-seo-input">
                                            <?php
                                            $tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p', 'span', 'section'];
                                            foreach ($tags as $t) {
                                                echo '<option value="' . $t . '" ' . selected($rule['tag'], $t, false) . '>' . $t . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="tag_rules[<?php echo $index; ?>][extra_classes]"
                                            value="<?php echo esc_attr($rule['extra_classes'] ?? ''); ?>"
                                            class="large-text dss-seo-input" placeholder="mi-clase-nueva"></td>
                                    <td><span class="dashicons dashicons-trash remove-row" title="Eliminar regla"></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="margin-top: 20px; display: flex; gap: 10px;">
                        <button type="button" class="button" id="add-row">
                            <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span> Añadir Regla
                        </button>
                        <input type="submit" name="save_tags" class="button button-primary button-large"
                            value="Guardar Cambios">
                    </div>
                </form>
            </div>

            <script>
                jQuery(document).ready(function ($) {
                    $('#add-row').on('click', function () {
                        const table = $('#tag-rules-table tbody');
                        const rowCount = table.find('tr').length;
                        const row = `<tr>
                                        <td><input type="text" name="tag_rules[${rowCount}][selector]" class="large-text dss-seo-input" placeholder="Selector CSS"></td>
                                        <td>
                                            <select name="tag_rules[${rowCount}][tag]" class="dss-seo-input">
                                                <option value="h1">h1</option><option value="h2">h2</option><option value="h3" selected>h3</option>
                                                <option value="h4">h4</option><option value="h5">h5</option><option value="h6">h6</option>
                                                <option value="div">div</option><option value="p">p</option><option value="span">span</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="tag_rules[${rowCount}][extra_classes]" class="large-text dss-seo-input"></td>
                                        <td><span class="dashicons dashicons-trash remove-row"></span></td>
                                     </tr>`;
                        table.append(row);
                    });
                    $(document).on('click', '.remove-row', function () {
                        $(this).closest('tr').remove();
                    });
                });
            </script>

            <?php else: ?>

            <p style="color: #64748b; font-size: 15px; margin-bottom: 25px;">
                Analiza la estructura de encabezados (H1-H6) de tus páginas publicadas.
                Detecta <strong>H1 duplicados</strong>, saltos de jerarquía y problemas de estructura SEO.
            </p>

            <div class="dss-seo-card">
                <div class="dss-audit-controls">
                    <button type="button" class="button button-primary button-large" id="dss-audit-start">
                        <span class="dashicons dashicons-search" style="vertical-align: middle;"></span> Escanear Páginas Publicadas
                    </button>
                    <span class="dss-audit-status" id="dss-audit-status"></span>
                </div>
                <div class="dss-audit-progress" id="dss-audit-progress" style="display:none;">
                    <div class="dss-audit-progress-bar">
                        <div class="dss-audit-progress-fill" id="dss-audit-progress-fill"></div>
                    </div>
                    <span class="dss-audit-progress-text" id="dss-audit-progress-text"></span>
                </div>
                <nav class="dss-audit-type-tabs" id="dss-audit-type-tabs" style="display:none;"></nav>
                <div id="dss-audit-results"></div>
                <div class="dss-audit-pagination" id="dss-audit-pagination" style="display:none;"></div>
            </div>

            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Start Output Buffering.
     */
    public function start_buffer()
    {
        ob_start(array($this, 'buffer_callback'));
    }

    /**
     * Buffer Callback Logic.
     */
    public function buffer_callback($buffer)
    {
        if (empty($buffer) || strpos($buffer, '<html') === false) {
            return $buffer;
        }

        $rules = get_option('tag_changer_rules', []);
        if (empty($rules)) {
            return $buffer;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        $html = mb_convert_encoding($buffer, 'HTML-ENTITIES', 'UTF-8');
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        foreach ($rules as $rule) {
            $selector = $rule['selector'];
            $newTagName = $rule['tag'];
            $extraClasses = $rule['extra_classes'] ?? '';

            $query = "";
            if (strpos($selector, '.') === 0) {
                $class = substr($selector, 1);
                $query = "//*[contains(concat(' ', normalize-space(@class), ' '), ' " . $class . " ')]";
            } elseif (preg_match('/^([a-zA-Z0-9]+)\.([a-zA-Z0-9_-]+)$/', $selector, $matches)) {
                $tag = $matches[1];
                $class = $matches[2];
                $query = "//" . $tag . "[contains(concat(' ', normalize-space(@class), ' '), ' " . $class . " ')]";
            } elseif (preg_match('/^([a-zA-Z0-9]+)$/', $selector)) {
                $query = "//" . $selector;
            }

            if (empty($query))
                continue;

            $nodes = $xpath->query($query);
            if ($nodes) {
                for ($i = $nodes->length - 1; $i >= 0; $i--) {
                    $node = $nodes->item($i);
                    $newNode = $dom->createElement($newTagName);

                    foreach ($node->attributes as $attr) {
                        if ($attr->nodeName === 'class') {
                            $classes = $attr->nodeValue;
                            if ($extraClasses) {
                                $classes .= ' ' . $extraClasses;
                            }
                            $newNode->setAttribute('class', trim($classes));
                        } else {
                            $newNode->setAttribute($attr->nodeName, $attr->nodeValue);
                        }
                    }

                    if (!$node->hasAttribute('class') && $extraClasses) {
                        $newNode->setAttribute('class', $extraClasses);
                    }

                    while ($node->firstChild) {
                        $newNode->appendChild($node->firstChild);
                    }

                    $node->parentNode->replaceChild($newNode, $node);
                }
            }
        }

        $output = $dom->saveHTML();
        libxml_clear_errors();
        return $output;
    }

    /**
     * AJAX: Scan published pages for heading structure issues (batched).
     */
    public function ajax_audit_scan()
    {
        check_ajax_referer('dss_seo_audit_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos.');
        }

        $offset = intval($_POST['offset'] ?? 0);
        $batch_size = 5;

        $public_types = get_post_types(array('public' => true), 'objects');
        unset($public_types['attachment'], $public_types['product']);
        $type_slugs = array_keys($public_types);

        // Get total count on first batch
        $total = 0;
        if ($offset === 0) {
            $count_query = new WP_Query(array(
                'post_type' => $type_slugs,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
            ));
            $total = $count_query->found_posts;
        }

        $posts = get_posts(array(
            'post_type' => $type_slugs,
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        $results = array();

        foreach ($posts as $post) {
            $url = get_permalink($post->ID);
            $response = wp_remote_get($url, array(
                'timeout' => 10,
                'sslverify' => false,
            ));

            if (is_wp_error($response)) {
                $results[] = array(
                    'title' => $post->post_title,
                    'url' => $url,
                    'type' => $post->post_type,
                    'type_label' => isset($public_types[$post->post_type]) ? $public_types[$post->post_type]->labels->singular_name : $post->post_type,
                    'error' => true,
                    'message' => 'No se pudo acceder a la página.',
                );
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $analysis = $this->analyze_headings($body);

            $results[] = array(
                'title' => $post->post_title,
                'url' => $url,
                'type' => $post->post_type,
                'type_label' => isset($public_types[$post->post_type]) ? $public_types[$post->post_type]->labels->singular_name : $post->post_type,
                'error' => false,
                'headings' => $analysis['headings'],
                'issues' => $analysis['issues'],
            );
        }

        wp_send_json_success(array(
            'results' => $results,
            'total' => $total,
            'offset' => $offset,
            'batch_size' => $batch_size,
            'has_more' => count($posts) === $batch_size,
        ));
    }

    /**
     * Analyze heading structure from HTML.
     */
    private function analyze_headings($html)
    {
        $headings = array();
        $issues = array();

        if (preg_match_all('/<(h[1-6])[^>]*>(.*?)<\/\1>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $headings[] = array(
                    'tag' => strtoupper($match[1]),
                    'text' => wp_strip_all_tags($match[2]),
                );
            }
        }

        // Check: duplicate H1
        $h1_count = 0;
        foreach ($headings as $h) {
            if ($h['tag'] === 'H1') {
                $h1_count++;
            }
        }
        if ($h1_count === 0) {
            $issues[] = array(
                'type' => 'error',
                'message' => 'No se encontró ninguna etiqueta H1.',
            );
        } elseif ($h1_count > 1) {
            $issues[] = array(
                'type' => 'error',
                'message' => "Se encontraron {$h1_count} etiquetas H1. Solo debe haber una por página.",
            );
        }

        // Check: hierarchy jumps (e.g. H1 -> H3 skipping H2)
        $prev_level = 0;
        foreach ($headings as $h) {
            $level = (int) substr($h['tag'], 1);
            if ($prev_level > 0 && $level > $prev_level + 1) {
                $issues[] = array(
                    'type' => 'warning',
                    'message' => "Salto de jerarquía: H{$prev_level} → H{$level} (se omite H" . ($prev_level + 1) . "). Cerca de: \"" . mb_substr($h['text'], 0, 50) . "\"",
                );
            }
            $prev_level = $level;
        }

        // Check: first heading should be H1
        if (!empty($headings) && $headings[0]['tag'] !== 'H1') {
            $issues[] = array(
                'type' => 'warning',
                'message' => 'El primer encabezado no es H1 (es ' . $headings[0]['tag'] . ').',
            );
        }

        return array(
            'headings' => $headings,
            'issues' => $issues,
        );
    }
}
