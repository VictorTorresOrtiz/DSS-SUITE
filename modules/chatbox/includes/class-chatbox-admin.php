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
        wp_enqueue_style('dss-chatbox-style', DSS_SUITE_PLUGIN_URL . 'modules/chatbox/assets/css/chatbox.css', array(), DSS_CHATBOX_VERSION);
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
                    <h3>Soporte Inteligente DSS</h3>
                    <button id="dss-chatbox-close">&times;</button>
                </div>
                <div id="dss-chatbox-history" class="dss-chatbox-body">
                    <div class="dss-message dss-bot-message">
                        ¡Hola! Soy el asistente de DSS NETWORK. ¿En qué puedo ayudarte hoy?
                    </div>
                </div>
                <div class="dss-chatbox-footer">
                    <form id="dss-chatbox-form">
                        <textarea name="chat_message" placeholder="Escribe tu duda aquí..." required></textarea>
                        <button type="submit" id="dss-chat-send">
                            <span class="dashicons dashicons-send"></span>
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
        $system_prompt = "Asistente experto de DSS NETWORK (https://dssnetwork.es). Soporte WordPress y técnico. Sé amable, conciso y profesional. Si dudas, sugiere: v.torres@dssnetwork.es. Idioma: Español.";

        // Intentamos con varios modelos por si la Key tiene restricciones
        $models = array('gemini-1.5-flash', 'gemini-1.5-flash-latest', 'gemini-pro', 'gemini-1.0-pro');
        $reply = '';
        $last_error = '';

        foreach ($models as $model_name) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model_name . ":generateContent?key=" . $api_key;

            // Usamos el formato más compatible (prompt dentro de contents) para evitar errores de versión
            $body = array(
                'contents' => array(
                    array(
                        'role' => 'user',
                        'parts' => array(
                            array('text' => "Instrucciones: " . $system_prompt . "\n\nPregunta del cliente: " . $message)
                        )
                    )
                ),
                'generationConfig' => array(
                    'temperature' => 0.7,
                    'maxOutputTokens' => 500
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

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $reply = $data['candidates'][0]['content']['parts'][0]['text'];
                break; // ¡Función encontrada!
            } elseif (isset($data['error']['message'])) {
                $last_error = $data['error']['message'];
                // Si el error es de modelo no encontrado, seguimos probando
                if (strpos(strtolower($last_error), 'not found') !== false || strpos(strtolower($last_error), 'not supported') !== false) {
                    continue;
                } else {
                    // Si es otro error (autenticación, cuota), detenemos la ejecución
                    wp_send_json_error(array('message' => 'Error de Gemini (' . $model_name . '): ' . $last_error));
                }
            }
        }

        if (!empty($reply)) {
            wp_send_json_success(array('reply' => $reply));
        } else {
            wp_send_json_error(array('message' => 'No se pudo obtener respuesta de la IA tras varios intentos. Último error: ' . $last_error));
        }
    }
}
