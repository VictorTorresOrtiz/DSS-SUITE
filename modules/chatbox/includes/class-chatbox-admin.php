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
        <div id="dss-chatbox-container" class="dss-chatbox-hidden">
            <div id="dss-chatbox-button">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <div id="dss-chatbox-window">
                <div class="dss-chatbox-header">
                    <h3>Soporte DSS NETWORK</h3>
                    <button id="dss-chatbox-close">&times;</button>
                </div>
                <div class="dss-chatbox-body">
                    <p>¿En qué podemos ayudarte hoy? Déjanos tu consulta y te responderemos lo antes posible.</p>
                    <form id="dss-chatbox-form">
                        <div class="form-group">
                            <input type="text" name="chat_name" placeholder="Tu nombre" required>
                        </div>
                        <div class="form-group">
                            <textarea name="chat_message" placeholder="¿Cuál es tu consulta?" required></textarea>
                        </div>
                        <button type="submit" class="button button-primary">Enviar Consulta</button>
                    </form>
                    <div id="dss-chatbox-response" style="display:none;"></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle AJAX chat inquiry.
     */
    public function handle_chat_inquiry()
    {
        check_ajax_referer('dss_chatbox_nonce', 'nonce');

        $name = isset($_POST['chat_name']) ? sanitize_text_field($_POST['chat_name']) : '';
        $message = isset($_POST['chat_message']) ? sanitize_textarea_field($_POST['chat_message']) : '';

        if (empty($name) || empty($message)) {
            wp_send_json_error(array('message' => 'Por favor, rellena todos los campos.'));
        }

        $to = 'v.torres@dssnetwork.es';
        $subject = 'Nueva consulta desde el Chatbox de ' . get_bloginfo('name');
        $body = "Has recibido una nueva consulta desde el chatbox de administración.\n\n";
        $body .= "Nombre: $name\n";
        $body .= "Mensaje:\n$message\n\n";
        $body .= "--- \nEnviado desde DSS SUITE.";

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        wp_mail($to, $subject, $body, $headers);

        wp_send_json_success(array('message' => '¡Gracias! Hemos recibido tu consulta y nos pondremos en contacto contigo pronto.'));
    }
}
