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
            echo '<div class="updated"><p>Reglas guardadas correctamente.</p></div>';
        }

        $rules = get_option('tag_changer_rules', [['selector' => '', 'tag' => 'h3', 'extra_classes' => '']]);
        ?>
        <div class="wrap dss-seo-container">
            <div class="dss-seo-header">
                <h1><span class="dashicons dashicons-text-page"
                        style="font-size: 28px; width: 28px; height: 28px; color: #2271b1;"></span> SEO Structure Manager</h1>
                <span class="dss-badge">Server-Side Engine</span>
            </div>

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
}
