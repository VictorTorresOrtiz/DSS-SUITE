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

    public function enqueue_admin_assets($hook)
    {
        if ('dss-suite_page_dss-public-chat' !== $hook) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_style('dss-public-chat-admin-style', DSS_PUBLIC_CHAT_URL . 'assets/css/public-chat-admin.css', array(), DSS_PUBLIC_CHAT_VERSION);
        wp_enqueue_script('dss-public-chat-admin-js', DSS_PUBLIC_CHAT_URL . 'assets/js/public-chat-admin.js', array('jquery'), DSS_PUBLIC_CHAT_VERSION, true);

        // Pass Dashicons list to JS
        wp_localize_script('dss-public-chat-admin-js', 'dssAdminIcons', array(
            'icons' => $this->get_dashicons_list()
        ));
    }

    /**
     * Get a list of common Dashicons for the select menu.
     */
    private function get_dashicons_list()
    {
        return array(
            'dashicons-format-chat' => 'Burbuja Chat',
            'dashicons-admin-comments' => 'Comentarios',
            'dashicons-smiley' => 'Cara Sonriente',
            'dashicons-info' => 'Información',
            'dashicons-welcome-learn-more' => 'Leer más',
            'dashicons-email-alt' => 'Email',
            'dashicons-phone' => 'Teléfono',
            'dashicons-whatsapp' => 'WhatsApp',
            'dashicons-admin-users' => 'Usuarios',
            'dashicons-cart' => 'Carrito',
            'dashicons-sos' => 'Ayuda/SOS',
            'dashicons-lightbulb' => 'Idea/Luz',
            'dashicons-star-filled' => 'Estrella',
            'dashicons-calendar-alt' => 'Calendario',
            'dashicons-location-alt' => 'Ubicación',
            'dashicons-megaphone' => 'Megáfono',
            'dashicons-money-alt' => 'Precio/Dinero',
        );
    }

    /**
     * Register settings for the public chatbot.
     */
    public function register_settings()
    {
        register_setting('dss_public_chat_group', 'dss_public_chat_prompt', array('capability' => 'read'));
        register_setting('dss_public_chat_group', 'dss_public_chat_shortcuts', array('capability' => 'read'));
        register_setting('dss_public_chat_group', 'dss_public_chat_logo', array('capability' => 'read'));
        register_setting('dss_public_chat_group', 'dss_public_chat_api_key', array('capability' => 'read'));
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
            'read',
            'dss-public-chat',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page()
    {
        if (!current_user_can('read')) {
            return;
        }
        $prompt = get_option('dss_public_chat_prompt', 'Eres un asistente amable de ' . get_bloginfo('name') . '...');
        $shortcuts = get_option('dss_public_chat_shortcuts', array());
        ?>
        <div class="wrap dss-public-chat-admin-wrap">
            <h1>Configuración del Chat Público</h1>
            <p style="margin-bottom: 30px; color: #64748b;">Personaliza la experiencia del chatbot que verán tus visitantes en
                el área pública.</p>

            <form method="post" action="options.php">
                <?php settings_fields('dss_public_chat_group'); ?>

                <!-- Card 1: Comportamiento e IA -->
                <div class="dss-card">
                    <div class="dss-card-header">
                        <h2><span class="dashicons dashicons-brain"></span> Identidad y Comportamiento</h2>
                    </div>

                    <div class="dss-form-group">
                        <label for="dss_public_chat_prompt">Prompt del Sistema (Personalidad)</label>
                        <textarea name="dss_public_chat_prompt" id="dss_public_chat_prompt" rows="6"
                            placeholder="Ej: Eres un experto en marketing digital..."><?php echo esc_textarea($prompt); ?></textarea>
                        <p class="dss-help-text">Instrucciones precisas para que la IA sepa cómo responder a tus clientes.</p>
                    </div>

                    <div class="dss-form-group">
                        <label for="dss_public_chat_api_key">Gemini API Key (Específica)</label>
                        <input type="password" name="dss_public_chat_api_key" id="dss_public_chat_api_key"
                            value="<?php echo esc_attr(get_option('dss_public_chat_api_key')); ?>"
                            placeholder="Deja vacío para usar la global">
                        <p class="dss-help-text">Si quieres usar una cuenta de Google diferente para el chat público, indícala
                            aquí.</p>
                    </div>
                </div>

                <!-- Card 2: Apariencia -->
                <div class="dss-card">
                    <div class="dss-card-header">
                        <h2><span class="dashicons dashicons-art"></span> Apariencia Visual</h2>
                    </div>

                    <div class="dss-form-group">
                        <label>Avatar / Icono del Asistente</label>
                        <div class="dss-logo-config">
                            <div class="dss-logo-preview-wrapper">
                                <?php $logo_url = get_option('dss_public_chat_logo', DSS_PUBLIC_CHAT_URL . 'assets/images/dss-logo.svg'); ?>
                                <img id="dss-logo-preview" src="<?php echo esc_url($logo_url); ?>">
                            </div>
                            <div>
                                <input type="hidden" name="dss_public_chat_logo" id="dss_public_chat_logo"
                                    value="<?php echo esc_url($logo_url); ?>">
                                <button type="button" id="dss-select-logo" class="button button-secondary">Cambiar
                                    Imagen</button>
                                <p class="dss-help-text">Se recomienda una imagen circular de 512x512px.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Atajos Rápidos -->
                <div class="dss-card">
                    <div class="dss-card-header">
                        <h2><span class="dashicons dashicons-external"></span> Atajos del Chat (Botones Rápidos)</h2>
                        <p class="dss-help-text">Añade botones que el usuario puede pulsar para lanzar consultas automáticas.
                        </p>
                    </div>

                    <div class="dss-shortcuts-grid">
                        <?php 
                        $icon_list = $this->get_dashicons_list();
                        if (!empty($shortcuts)): 
                        ?>
                            <?php foreach ($shortcuts as $index => $shortcut): ?>
                                <div class="dss-shortcut-card">
                                    <button type="button" class="dss-remove-shortcut">&times;</button>
                                    <div class="dss-shortcut-inputs">
                                        <div class="dss-form-group-inline">
                                            <label>Icono</label>
                                            <select name="dss_public_chat_shortcuts[<?php echo $index; ?>][icon]">
                                                <option value="">Ninguno</option>
                                                <?php foreach ($icon_list as $value => $label): ?>
                                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($shortcut['icon'] ?? '', $value); ?>>
                                                        <?php echo esc_html($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="dss-form-group-inline">
                                            <label>Etiqueta</label>
                                            <input type="text" name="dss_public_chat_shortcuts[<?php echo $index; ?>][label]"
                                                placeholder="Título" value="<?php echo esc_attr($shortcut['label']); ?>">
                                        </div>
                                    </div>
                                    <div class="dss-form-group">
                                        <label>Mensaje (Prompt)</label>
                                        <input type="text" name="dss_public_chat_shortcuts[<?php echo $index; ?>][query]"
                                            placeholder="Prompt" value="<?php echo esc_attr($shortcut['query']); ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="dss-add-button-container">
                        <button type="button" id="dss-add-shortcut" class="button">
                            <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span> Nuevo Atajo
                        </button>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <?php submit_button('Guardar Toda la Configuración', 'primary', 'submit', true, array('style' => 'height: 45px; padding: 0 30px; font-weight: 600; border-radius: 8px;')); ?>
                </div>
            </form>
        </div>
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
                                    <?php if (!empty($shortcut['icon'])): ?>
                                        <span class="dashicons <?php echo esc_attr($shortcut['icon']); ?>"></span>
                                    <?php endif; ?>
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

        // --- MODEL DISCOVERY PHASE (con caché 24h) ---
        $cache_key = 'dss_gemini_models_' . md5($api_key);
        $available_models = get_transient($cache_key);

        if (false === $available_models) {
            $available_models = array();
            $list_url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $api_key;
            $list_response = wp_remote_get($list_url);

            if (!is_wp_error($list_response)) {
                $list_data = json_decode(wp_remote_retrieve_body($list_response), true);
                if (isset($list_data['models'])) {
                    foreach ($list_data['models'] as $m) {
                        if (in_array('generateContent', $m['supportedGenerationMethods'])) {
                            // Prioritize 2.0 and 1.5 models
                            if (strpos($m['name'], 'gemini-1.5') !== false || strpos($m['name'], 'gemini-2.0') !== false) {
                                array_unshift($available_models, $m['name']);
                            } else {
                                $available_models[] = $m['name'];
                            }
                        }
                    }
                }
            }

            if (empty($available_models)) {
                $available_models = array('models/gemini-1.5-flash', 'models/gemini-pro');
            }

            set_transient($cache_key, $available_models, DAY_IN_SECONDS);
        }

        $reply = '';
        $last_error = '';

        foreach ($available_models as $model_name) {
            $url = "https://generativelanguage.googleapis.com/v1beta/" . $model_name . ":generateContent?key=" . $api_key;

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
            wp_send_json_error(array('message' => 'Lo siento, no he podido obtener una respuesta. Por favor, inténtalo de nuevo.'));
        }
    }
}
