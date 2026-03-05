<?php
/**
 * Admin and logic class for the Public Frontend Chatbot.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DSS_Public_Chat_Admin
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('wp_footer', array($this, 'render_public_chatbox'));

        // AJAX handlers
        add_action('wp_ajax_dss_send_public_chat', array($this, 'handle_public_chat'));
        add_action('wp_ajax_nopriv_dss_send_public_chat', array($this, 'handle_public_chat'));

        // Add settings section to DSS Suite
        add_action('admin_menu', array($this, 'add_settings_tab'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets($hook)
    {
        if ('dss-suite_page_dss-public-chat' !== $hook) {
            return;
        }
        wp_enqueue_media();
    }

    /**
     * Register settings for the public chatbot.
     */
    public function register_settings()
    {
        register_setting('dss_public_chat_group', 'dss_public_chat_prompt');
        register_setting('dss_public_chat_group', 'dss_public_chat_shortcuts');
        register_setting('dss_public_chat_group', 'dss_public_chat_logo');
        register_setting('dss_public_chat_group', 'dss_public_chat_api_key');
    }

    /**
     * Add a sub-menu or just hook into the main page.
     * For now, let's keep it simple and add a separate settings page for this module.
     */
    public function add_settings_tab()
    {
        add_submenu_page(
            'dss-suite',
            'Configuración Chat Público',
            'Chat Público',
            'manage_options',
            'dss-public-chat',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page()
    {
        $prompt = get_option('dss_public_chat_prompt', 'Eres un asistente amable de ' . get_bloginfo('name') . '...');
        $shortcuts = get_option('dss_public_chat_shortcuts', array());
        ?>
        <div class="wrap">
            <h1>Configuración del Chat Público</h1>
            <form method="post" action="options.php">
                <?php settings_fields('dss_public_chat_group'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="dss_public_chat_prompt">Prompt Personalizado</label></th>
                        <td>
                            <textarea name="dss_public_chat_prompt" id="dss_public_chat_prompt" rows="5"
                                class="large-text"><?php echo esc_textarea($prompt); ?></textarea>
                            <p class="description">Define cómo debe comportarse el bot con los visitantes de la web.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="dss_public_chat_api_key">Gemini API Key</label></th>
                        <td>
                            <input type="password" name="dss_public_chat_api_key" id="dss_public_chat_api_key"
                                value="<?php echo esc_attr(get_option('dss_public_chat_api_key')); ?>" class="regular-text">
                            <p class="description">Introduce la API Key específica para el chat público. Deja en blanco si
                                deseas usar la global de DSS Suite.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="dss_public_chat_logo">Logo del Chat</label></th>
                        <td>
                            <?php $logo_url = get_option('dss_public_chat_logo', DSS_PUBLIC_CHAT_URL . 'assets/images/dss-logo.svg'); ?>
                            <div class="dss-logo-preview-wrapper" style="margin-bottom: 10px;">
                                <img id="dss-logo-preview" src="<?php echo esc_url($logo_url); ?>"
                                    style="max-width: 100px; border: 1px solid #ccc; border-radius: 50%; padding: 5px; background: #f0f0f0;">
                            </div>
                            <input type="hidden" name="dss_public_chat_logo" id="dss_public_chat_logo"
                                value="<?php echo esc_url($logo_url); ?>">
                            <button type="button" id="dss-select-logo" class="button">Seleccionar Logo</button>
                            <p class="description">Elige el icono que aparecerá en el botón flotante y en la cabecera del chat.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Atajos (Shortcuts)</th>
                        <td id="dss-shortcuts-container">
                            <div class="dss-shortcuts-list">
                                <?php if (!empty($shortcuts)): ?>
                                    <?php foreach ($shortcuts as $index => $shortcut): ?>
                                        <div class="dss-shortcut-item"
                                            style="margin-bottom: 10px; border: 1px solid #ccc; padding: 10px; border-radius: 5px;">
                                            <input type="text" name="dss_public_chat_shortcuts[<?php echo $index; ?>][label]"
                                                placeholder="Título (ej: Hola)" value="<?php echo esc_attr($shortcut['label']); ?>">
                                            <input type="text" name="dss_public_chat_shortcuts[<?php echo $index; ?>][query]"
                                                placeholder="Prompt (ej: Hola, ¿cómo estás?)"
                                                value="<?php echo esc_attr($shortcut['query']); ?>" style="width: 300px;">
                                            <button type="button" class="button dss-remove-shortcut">Eliminar</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="dss-add-shortcut" class="button button-primary">Añadir Atajo</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            jQuery(document).ready(function ($) {
                $('#dss-add-shortcut').on('click', function () {
                    var index = $('.dss-shortcut-item').length;
                    var html = '<div class="dss-shortcut-item" style="margin-bottom: 10px; border: 1px solid #ccc; padding: 10px; border-radius: 5px;">' +
                        '<input type="text" name="dss_public_chat_shortcuts[' + index + '][label]" placeholder="Título">' +
                        '<input type="text" name="dss_public_chat_shortcuts[' + index + '][query]" placeholder="Prompt" style="width: 300px;">' +
                        '<button type="button" class="button dss-remove-shortcut">Eliminar</button>' +
                        '</div>';
                    $('.dss-shortcuts-list').append(html);
                });
                $(document).on('click', '.dss-remove-shortcut', function () {
                    $(this).closest('.dss-shortcut-item').remove();
                });

                // Media Uploader for Logo
                $('#dss-select-logo').on('click', function (e) {
                    e.preventDefault();
                    var frame = wp.media({
                        title: 'Seleccionar Logo del Chat',
                        button: { text: 'Usar este logo' },
                        multiple: false
                    });
                    frame.on('select', function () {
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#dss-public-chat-logo').val(attachment.url);
                        $('#dss-logo-preview').attr('src', attachment.url);
                    });
                    frame.open();
                });
            });
        </script>
        <?php
    }

    /**
     * Enqueue assets for the public side.
     */
    public function enqueue_public_assets()
    {
        wp_enqueue_style('dss-public-chat-style', DSS_PUBLIC_CHAT_URL . 'assets/css/public-chat.css', array('dashicons'), DSS_PUBLIC_CHAT_VERSION);
        wp_enqueue_script('dss-public-chat-script', DSS_PUBLIC_CHAT_URL . 'assets/js/public-chat.js', array('jquery'), DSS_PUBLIC_CHAT_VERSION, true);

        wp_localize_script('dss-public-chat-script', 'dssPublicChat', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dss_public_chat_nonce'),
        ));
    }

    /**
     * Render the chatbox on the frontend.
     */
    public function render_public_chatbox()
    {
        $shortcuts = get_option('dss_public_chat_shortcuts', array());
        $logo_url = get_option('dss_public_chat_logo', DSS_PUBLIC_CHAT_URL . 'assets/images/dss-logo.svg');
        ?>
        <div id="dss-public-chat-container">
            <div id="dss-public-chat-button">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <div id="dss-public-chat-window" style="display: none;">
                <div class="dss-public-chat-header">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="DSS Logo" class="dss-chatbox-logo"
                        style="border-radius: 50%; width: 30px; height: 30px; background: #fff; padding: 2px;">
                    <h3>Asistente Personal</h3>
                    <button id="dss-public-chat-close">&times;</button>
                </div>
                <div id="dss-public-chat-history" class="dss-public-chat-body">
                    <div class="dss-message dss-bot-message">¡Hola! Soy tu asistente de
                        <?php echo esc_html(get_bloginfo('name')); ?>. ¿En qué puedo ayudarte?
                    </div>

                    <?php if (!empty($shortcuts)): ?>
                        <div class="dss-suggestion-chips">
                            <?php foreach ($shortcuts as $shortcut): ?>
                                <button class="dss-chip" data-query="<?php echo esc_attr($shortcut['query']); ?>">
                                    <?php echo esc_html($shortcut['label']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="dss-public-chat-footer">
                    <div id="dss-preview-container" style="display: none; padding: 10px; border-top: 1px solid #eee;">
                        <img id="dss-image-preview" src="#" style="max-width: 50px; border-radius: 5px;">
                        <button id="dss-remove-image">&times;</button>
                    </div>
                    <form id="dss-public-chat-form">
                        <label for="dss-image-upload" class="dss-upload-label">
                            <span class="dashicons dashicons-camera"></span>
                            <input type="file" id="dss-image-upload" accept="image/*" style="display: none;">
                        </label>
                        <textarea name="chat_message" placeholder="Escribe aquí..." required></textarea>
                        <button type="submit" id="dss-public-chat-send">
                            <img src="<?php echo DSS_PUBLIC_CHAT_URL . 'assets/images/enviar.gif'; ?>" alt="Enviar"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle AJAX chat requests.
     */
    public function handle_public_chat()
    {
        check_ajax_referer('dss_public_chat_nonce', 'nonce');

        $message = sanitize_text_field($_POST['message']);
        $api_key = get_option('dss_public_chat_api_key');

        // Fallback to global key if specific one is empty
        if (empty($api_key)) {
            $api_key = get_option('dss_suite_gemini_api_key');
        }

        $system_prompt = get_option('dss_public_chat_prompt', 'Eres un asistente amable de ' . get_bloginfo('name') . '...');

        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API Key no configurada.'));
        }

        // Image Handling
        $image_data = '';
        $mime_type = '';
        if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
            $file = $_FILES['image'];
            if (strpos($file['type'], 'image/') === 0) {
                $image_data = base64_encode(file_get_contents($file['tmp_name']));
                $mime_type = $file['type'];
            }
        }

        // Model hierarchy (as discussed)
        $models = array('models/gemini-2.0-flash', 'models/gemini-1.5-flash', 'models/gemini-1.5-pro');
        $reply = '';
        $last_error = '';

        foreach ($models as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/" . $model . ":generateContent?key=" . $api_key;

            $contents = array();
            $user_parts = array();
            $user_parts[] = array('text' => $message);

            if ($image_data) {
                $user_parts[] = array(
                    'inline_data' => array(
                        'mime_type' => $mime_type,
                        'data' => $image_data
                    )
                );
            }

            $contents[] = array(
                'role' => 'user',
                'parts' => $user_parts
            );

            $body = array(
                'system_instruction' => array(
                    'parts' => array(array('text' => $system_prompt))
                ),
                'contents' => $contents,
                'generationConfig' => array(
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1024
                )
            );

            $response = wp_remote_post($url, array(
                'body' => json_encode($body),
                'headers' => array('Content-Type' => 'application/json'),
                'timeout' => 30
            ));

            if (!is_wp_error($response)) {
                $code = wp_remote_retrieve_response_code($response);
                $res_body = wp_remote_retrieve_body($response);
                $data = json_decode($res_body, true);

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $reply = $data['candidates'][0]['content']['parts'][0]['text'];
                    break;
                } elseif (isset($data['error']['message'])) {
                    $last_error = $data['error']['message'];
                } else {
                    $last_error = "Error HTTP $code: " . substr($res_body, 0, 100);
                }
            } else {
                $last_error = $response->get_error_message();
            }
        }

        if (!empty($reply)) {
            wp_send_json_success(array('reply' => $reply));
        } else {
            wp_send_json_error(array('message' => 'No se pudo obtener respuesta de la IA. Detalle: ' . $last_error));
        }
    }
}
