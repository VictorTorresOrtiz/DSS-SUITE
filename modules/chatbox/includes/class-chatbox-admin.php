<?php
/**
 * Admin class for the Chatbox module.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DSS_Chatbox_Admin
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_footer', array($this, 'render_chatbox'));
        add_action('wp_ajax_dss_send_chatbox_inquiry', array($this, 'handle_chat_inquiry'));
    }

    /**
     * Enqueue CSS and JS.
     */
    public function enqueue_assets()
    {
        wp_enqueue_style('dss-chatbox-style', DSS_SUITE_PLUGIN_URL . 'modules/chatbox/assets/css/chatbox.css', array('dashicons'), DSS_CHATBOX_VERSION);
        wp_enqueue_script('dss-chatbox-script', DSS_SUITE_PLUGIN_URL . 'modules/chatbox/assets/js/chatbox.js', array('jquery'), DSS_CHATBOX_VERSION, true);

        wp_localize_script('dss-chatbox-script', 'dssChatbox', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dss_chatbox_nonce'),
        ));
    }

    /**
     * Render the chatbox HTML in the admin footer.
     */
    public function render_chatbox()
    {
        ?>
        <div id="dss-chatbox-container">
            <div id="dss-chatbox-button">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <div id="dss-chatbox-window">
                <div class="dss-chatbox-header">
                    <div class="dss-header-title">
                        <img src="<?php echo DSS_CHATBOX_URL . 'assets/images/dss-logo.svg'; ?>" alt="DSS Logo"
                            class="dss-chatbox-logo">
                        <h3>Asistente personal DSS Network</h3>
                    </div>
                    <button id="dss-chatbox-close">&times;</button>
                </div>
                <div id="dss-chatbox-history" class="dss-chatbox-body">
                    <div class="dss-message dss-bot-message">¡Hola! Soy el asistente de DSS NETWORK. ¿En qué puedo ayudarte hoy?
                    </div>

                    <!-- Suggestion Chips inside history for inline placement -->
                    <div class="dss-suggestion-chips">
                        <button class="dss-chip" data-query="Crea una entrada de blog sobre ">📝 Crear Entrada</button>
                        <button class="dss-chip" data-query="Crea un producto llamado ">🛍️ Crear Producto</button>
                        <button class="dss-chip" data-query="¿Cómo puedo optimizar mi sitio?">🚀 Opt. Sitio</button>
                    </div>
                </div>
                <div class="dss-chatbox-footer">
                    <form id="dss-chatbox-form">
                        <textarea name="chat_message" placeholder="Escribe tu duda aquí..." required></textarea>
                        <button type="submit" id="dss-chat-send">
                            <img src="<?php echo DSS_CHATBOX_URL . 'assets/images/enviar.gif'; ?>" alt="Enviar"
                                class="dss-send-gif">
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle AJAX chat inquiry with Gemini AI.
     */
    public function handle_chat_inquiry()
    {
        check_ajax_referer('dss_chatbox_nonce', 'nonce');

        $message = isset($_POST['chat_message']) ? sanitize_textarea_field($_POST['chat_message']) : '';
        $api_key = get_option('dss_suite_gemini_api_key');

        if (empty($message)) {
            wp_send_json_error(array('message' => 'El mensaje no puede estar vacío.'));
        }

        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'Error: Configura tu Gemini API Key en los ajustes de DSS Suite.'));
        }

        // System Prompt
        $system_prompt = "Asistente experto de soporte para DSS NETWORK (https://dssnetwork.es). 
        Tu objetivo es ayudar a los clientes con dudas sobre WordPress, servicios de DSS y soporte técnico. 
        Sé amable, profesional y proporciona respuestas completas y detalladas. 
        Si no estás seguro de algo, sugiere contactar a v.torres@dssnetwork.es. Idioma: Español.";

        // --- FASE DE DESCUBRIMIENTO DE MODELO ---
        // Consultamos qué modelos están disponibles exactamente para esta API Key
        $list_url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $api_key;
        $list_response = wp_remote_get($list_url);
        $available_models = array();

        if (!is_wp_error($list_response)) {
            $list_data = json_decode(wp_remote_retrieve_body($list_response), true);
            if (isset($list_data['models'])) {
                foreach ($list_data['models'] as $m) {
                    if (in_array('generateContent', $m['supportedGenerationMethods'])) {
                        $available_models[] = $m['name']; // Nombre completo: models/gemini-1.5-flash
                    }
                }
            }
        }

        // Si falla el descubrimiento, usamos fallback manual (sin el prefijo models/ para compatibilidad con la URL)
        if (empty($available_models)) {
            $available_models = array('models/gemini-1.5-flash', 'models/gemini-pro', 'models/gemini-1.0-pro');
        }

        // Definición de herramientas (Tools) para Gemini
        $tools = array(
            array(
                'function_declarations' => array(
                    array(
                        'name' => 'create_wp_post',
                        'description' => 'Crea una nueva entrada (blog post) en WordPress.',
                        'parameters' => array(
                            'type' => 'object',
                            'properties' => array(
                                'title' => array('type' => 'string', 'description' => 'El título de la entrada.'),
                                'content' => array('type' => 'string', 'description' => 'El contenido detallado de la entrada en formato HTML o texto plano.'),
                            ),
                            'required' => array('title', 'content')
                        )
                    ),
                    array(
                        'name' => 'create_wc_product',
                        'description' => 'Crea un nuevo producto en WooCommerce.',
                        'parameters' => array(
                            'type' => 'object',
                            'properties' => array(
                                'name' => array('type' => 'string', 'description' => 'Nombre del producto.'),
                                'price' => array('type' => 'string', 'description' => 'Precio del producto (solo el número).'),
                                'description' => array('type' => 'string', 'description' => 'Descripción del producto.')
                            ),
                            'required' => array('name', 'price')
                        )
                    )
                )
            )
        );

        $reply = '';
        $last_error = '';

        foreach ($available_models as $full_model_name) {
            $url = "https://generativelanguage.googleapis.com/v1beta/" . $full_model_name . ":generateContent?key=" . $api_key;

            $body = array(
                'system_instruction' => array(
                    'parts' => array(array('text' => $system_prompt))
                ),
                'contents' => array(
                    array(
                        'role' => 'user',
                        'parts' => array(array('text' => $message))
                    )
                ),
                'tools' => $tools,
                'generationConfig' => array(
                    'temperature' => 0.7,
                    'maxOutputTokens' => 2048
                )
            );

            $response = wp_remote_post($url, array(
                'body' => json_encode($body),
                'headers' => array('Content-Type' => 'application/json'),
                'timeout' => 30
            ));

            if (is_wp_error($response)) {
                $last_error = $response->get_error_message();
                continue;
            }

            $res_body = wp_remote_retrieve_body($response);
            $data = json_decode($res_body, true);

            // Manejo de llamadas a funciones (Function Calling)
            if (isset($data['candidates'][0]['content']['parts'][0]['functionCall'])) {
                $call = $data['candidates'][0]['content']['parts'][0]['functionCall'];
                $function_name = $call['name'];
                $args = $call['args'];

                $tool_result = $this->execute_ai_tool($function_name, $args);

                // Añadimos el historial para que la IA genere el texto final
                $body['contents'][] = array(
                    'role' => 'model',
                    'parts' => array(array('functionCall' => $call))
                );
                $body['contents'][] = array(
                    'role' => 'function',
                    'parts' => array(
                        array(
                            'functionResponse' => array(
                                'name' => $function_name,
                                'response' => array('output' => $tool_result)
                            )
                        )
                    )
                );

                $final_response = wp_remote_post($url, array(
                    'body' => json_encode($body),
                    'headers' => array('Content-Type' => 'application/json'),
                    'timeout' => 30
                ));

                if (!is_wp_error($final_response)) {
                    $final_data = json_decode(wp_remote_retrieve_body($final_response), true);
                    if (isset($final_data['candidates'][0]['content']['parts'][0]['text'])) {
                        $reply = $final_data['candidates'][0]['content']['parts'][0]['text'];
                        break;
                    }
                }
            }

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $reply = $data['candidates'][0]['content']['parts'][0]['text'];
                break;
            } elseif (isset($data['error']['message'])) {
                $last_error = $data['error']['message'];
                continue;
            }
        }

        if (!empty($reply)) {
            wp_send_json_success(array('reply' => $reply));
        } else {
            $model_list = !empty($available_models) ? implode(', ', $available_models) : 'ninguno';
            wp_send_json_error(array('message' => "La IA no pudo responder. Modelos detectados: $model_list. Último error: $last_error"));
        }
    }

    /**
     * Executes a tool called by the AI.
     */
    private function execute_ai_tool($name, $args)
    {
        switch ($name) {
            case 'create_wp_post':
                $post_id = wp_insert_post(array(
                    'post_title' => sanitize_text_field($args['title']),
                    'post_content' => wp_kses_post($args['content']),
                    'post_status' => 'draft',
                    'post_type' => 'post'
                ));

                if (is_wp_error($post_id)) {
                    return "Error al crear la entrada: " . $post_id->get_error_message();
                }

                return "Entrada creada correctamente como borrador. ID: " . $post_id . ". Enlace de edición: " . get_edit_post_link($post_id);

            case 'create_wc_product':
                if (!class_exists('WooCommerce')) {
                    return "Error: WooCommerce no está activo en este sitio.";
                }

                $product = new WC_Product_Simple();
                $product->set_name(sanitize_text_field($args['name']));
                $product->set_status('draft');
                $product->set_regular_price(sanitize_text_field($args['price']));
                $product->set_description(wp_kses_post($args['description'] ?? ''));
                $product_id = $product->save();

                return "Producto creado correctamente como borrador. ID: " . $product_id . ". Enlace de edición: " . get_edit_post_link($product_id);

            default:
                return "Herramienta no reconocida.";
        }
    }
}
