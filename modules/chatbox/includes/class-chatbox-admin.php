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

        // System Prompt optimizado
        $system_prompt = "Asistente experto de DSS NETWORK (https://dssnetwork.es). Soporte WordPress y técnico. Sé amable, conciso y profesional. Si dudas, sugiere: v.torres@dssnetwork.es. Idioma: Español.";

        $model = 'gemini-1.5-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $api_key;

        $body = array(
            'system_instruction' => array(
                'parts' => array(
                    array('text' => $system_prompt)
                )
            ),
            'contents' => array(
                array(
                    'role' => 'user',
                    'parts' => array(
                        array('text' => $message)
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.5,
                'maxOutputTokens' => 250,
                'topP' => 0.8,
                'topK' => 10
            )
        );

        $response = wp_remote_post($url, array(
            'body'    => json_encode($body),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 20
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Error de conexión con el servidor.'));
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $reply = $data['candidates'][0]['content']['parts'][0]['text'];
            wp_send_json_success(array('reply' => $reply));
        } elseif (isset($data['error'])) {
            wp_send_json_error(array('message' => 'IA Error: ' . $data['error']['message']));
        } else {
            wp_send_json_error(array('message' => 'La IA no pudo generar una respuesta.'));
        }
    } 
} 
