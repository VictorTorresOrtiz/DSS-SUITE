<?php
/*
 * Module: DSS SEO Manager
 * Description: Cambia etiquetas HTML y añade clases personalizadas desde el panel de ajustes.
 */

if (!defined('ABSPATH'))
    exit;

// 1. Crear el menú en el Escritorio
add_action('admin_menu', function () {
    add_options_page('Seo Manager', 'Seo manager', 'manage_options', 'tag-changer', 'tag_changer_html');
});

// 2. HTML de la Interfaz
function tag_changer_html()
{
    if (isset($_POST['save_tags'])) {
        update_option('tag_changer_rules', $_POST['tag_rules']);
    }
    $rules = get_option('tag_changer_rules', [['selector' => '', 'tag' => 'h3', 'extra_classes' => '']]);
    ?>
    <div class="wrap">
        <h1>DSS NETWORK - Seo Manager</h1>
        <p>Define el selector, la etiqueta de destino y, opcionalmente, clases adicionales separadas por espacios.</p>
        <form method="post">
            <table class="wp-list-table widefat fixed striped" id="tag-rules-table">
                <thead>
                    <tr>
                        <th style="width: 40%;">Selector CSS (Ej: .post_title)</th>
                        <th style="width: 15%;">Etiqueta (h2, h3...)</th>
                        <th style="width: 30%;">Clases Extra (Opcional)</th>
                        <th style="width: 15%;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $index => $rule): ?>
                        <tr>
                            <td><input type="text" name="tag_rules[<?php echo $index; ?>][selector]"
                                    value="<?php echo esc_attr($rule['selector']); ?>" class="large-text"
                                    placeholder="h4.post_title"></td>
                            <td><input type="text" name="tag_rules[<?php echo $index; ?>][tag]"
                                    value="<?php echo esc_attr($rule['tag']); ?>" class="small-text" placeholder="h2"></td>
                            <td><input type="text" name="tag_rules[<?php echo $index; ?>][extra_classes]"
                                    value="<?php echo esc_attr($rule['extra_classes'] ?? ''); ?>" class="large-text"
                                    placeholder="mi-clase-nueva"></td>
                            <td><button type="button" class="button remove-row">Eliminar</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button button-primary" id="add-row">Añadir Regla</button></p>
            <p><input type="submit" name="save_tags" class="button button-hero" value="Guardar Cambios"></p>
        </form>
    </div>
    <script>
        document.getElementById('add-row').addEventListener('click', function () {
            const table = document.querySelector('#tag-rules-table tbody');
            const rowCount = table.rows.length;
            const row = table.insertRow();
            row.innerHTML = `<td><input type="text" name="tag_rules[${rowCount}][selector]" class="large-text"></td>
                             <td><input type="text" name="tag_rules[${rowCount}][tag]" class="small-text"></td>
                             <td><input type="text" name="tag_rules[${rowCount}][extra_classes]" class="large-text"></td>
                             <td><button type="button" class="button remove-row">Eliminar</button></td>`;
        });
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) e.target.closest('tr').remove();
        });
    </script>
    <?php
}

// 3. Script que ejecuta los cambios en la Web
add_action('wp_footer', function () {
    $rules = get_option('tag_changer_rules', []);
    if (empty($rules))
        return;
    ?>
    <script>
        (function () {
            document.addEventListener("DOMContentLoaded", function () {
                const rules = <?php echo json_encode($rules); ?>;
                rules.forEach(rule => {
                    if (!rule.selector || !rule.tag) return;
                    document.querySelectorAll(rule.selector).forEach(el => {
                        const newTag = document.createElement(rule.tag);

                        // Copiamos clases originales + las extras
                        newTag.className = el.className;
                        if (rule.extra_classes) {
                            newTag.classList.add(...rule.extra_classes.split(' ').filter(c => c));
                        }

                        // Copiamos el resto de atributos (id, style, data...)
                        Array.from(el.attributes).forEach(attr => {
                            if (attr.name !== 'class') newTag.setAttribute(attr.name, attr.value);
                        });

                        while (el.firstChild) newTag.appendChild(el.firstChild);
                        el.parentNode.replaceChild(newTag, el);
                    });
                });
            });
        })();
    </script>
    <?php
});