<?php
/**
 * Addon: Room Designer
 * Permite al cliente subir una foto de su habitación y genera una imagen
 * con los productos (muebles) de la tienda WooCommerce colocados.
 */

if (!defined('ABSPATH'))
    exit;

define('DSS_ROOM_DESIGNER_URL', plugin_dir_url(__FILE__));
define('DSS_ROOM_DESIGNER_DIR', plugin_dir_path(__FILE__));

class DSS_Room_Designer
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_dss_room_designer', array($this, 'handle_request'));
        add_action('wp_ajax_nopriv_dss_room_designer', array($this, 'handle_request'));
    }

    public function enqueue_assets()
    {
        wp_enqueue_style('dss-room-designer', DSS_ROOM_DESIGNER_URL . 'assets/css/room-designer.css', array('dashicons'), DSS_SUITE_VERSION);
        wp_enqueue_script('dss-room-designer', DSS_ROOM_DESIGNER_URL . 'assets/js/room-designer.js', array('jquery', 'dss-public-chat-script'), DSS_SUITE_VERSION, true);
        wp_localize_script('dss-room-designer', 'dssRoomDesigner', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dss_room_designer_nonce'),
        ));
    }

    /**
     * Get WooCommerce products with images for the AI context.
     */
    private function get_product_catalog($limit = 30)
    {
        if (!class_exists('WC_Product_Query'))
            return array();

        $query = new WC_Product_Query(array(
            'status' => 'publish',
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'stock_status' => 'instock',
        ));

        $products = $query->get_products();
        $catalog = array();

        foreach ($products as $product) {
            $image_id = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';

            if (empty($image_url))
                continue;

            $catalog[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'url' => $product->get_permalink(),
                'image_url' => $image_url,
                'short_description' => wp_strip_all_tags($product->get_short_description()),
                'categories' => wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names')),
            );
        }

        return $catalog;
    }

    /**
     * Build product catalog text for the AI prompt.
     */
    private function build_catalog_prompt($catalog)
    {
        if (empty($catalog))
            return 'No hay productos disponibles en la tienda.';

        $text = "CATÁLOGO DE PRODUCTOS DISPONIBLES EN LA TIENDA:\n\n";
        foreach ($catalog as $i => $p) {
            $cats = is_array($p['categories']) ? implode(', ', $p['categories']) : '';
            $text .= ($i + 1) . ". **{$p['name']}** - {$p['price']}€";
            if ($cats)
                $text .= " | Categorías: {$cats}";
            if ($p['short_description'])
                $text .= "\n   {$p['short_description']}";
            $text .= "\n   URL: {$p['url']}\n\n";
        }
        return $text;
    }

    /**
     * Handle the AJAX design request.
     */
    public function handle_request()
    {
        check_ajax_referer('dss_room_designer_nonce', 'nonce');

        // Get API key
        $api_key = get_option('dss_public_chat_api_key');
        if (empty($api_key)) {
            $api_key = get_option('dss_suite_gemini_api_key');
        }
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API Key no configurada.'));
        }

        // Get uploaded room image
        if (!isset($_FILES['room_image']) || empty($_FILES['room_image']['tmp_name'])) {
            wp_send_json_error(array('message' => 'Debes subir una foto de la habitación.'));
        }

        $file = $_FILES['room_image'];
        if (strpos($file['type'], 'image/') !== 0) {
            wp_send_json_error(array('message' => 'El archivo debe ser una imagen.'));
        }

        $room_image_data = base64_encode(file_get_contents($file['tmp_name']));
        $room_mime = $file['type'];

        // Get product catalog
        $catalog = $this->get_product_catalog();
        $catalog_prompt = $this->build_catalog_prompt($catalog);

        // Get product images for the AI (send up to 6 product images)
        $product_parts = array();
        $shown_products = array_slice($catalog, 0, 6);
        foreach ($shown_products as $p) {
            $img_response = wp_remote_get($p['image_url'], array('timeout' => 10));
            if (!is_wp_error($img_response)) {
                $img_body = wp_remote_retrieve_body($img_response);
                $img_type = wp_remote_retrieve_header($img_response, 'content-type');
                if ($img_body && strpos($img_type, 'image/') === 0) {
                    $product_parts[] = array(
                        'inline_data' => array(
                            'mime_type' => $img_type,
                            'data' => base64_encode($img_body),
                        )
                    );
                    $product_parts[] = array('text' => "Producto: {$p['name']} - {$p['price']}€");
                }
            }
        }

        // Build the system prompt
        $system_prompt = "Eres un diseñador de interiores profesional que trabaja para una tienda de muebles online. "
            . "Tu tarea es analizar la foto de la habitación del cliente y sugerir qué productos del catálogo "
            . "encajarían mejor en ese espacio. \n\n"
            . "INSTRUCCIONES:\n"
            . "1. Analiza la habitación: dimensiones aparentes, estilo, colores, iluminación y espacio disponible.\n"
            . "2. Selecciona los productos del catálogo que mejor encajen.\n"
            . "3. Genera una imagen de la habitación con los muebles seleccionados colocados de forma realista.\n"
            . "4. Explica brevemente por qué elegiste esos productos y dónde los colocarías.\n"
            . "5. Incluye los enlaces de los productos sugeridos.\n\n"
            . $catalog_prompt;

        // Build request parts
        $user_parts = array();
        $user_parts[] = array('text' => 'Esta es la foto de mi habitación. Por favor, sugiere muebles de tu tienda que encajen y genera una imagen con los muebles colocados.');
        $user_parts[] = array(
            'inline_data' => array(
                'mime_type' => $room_mime,
                'data' => $room_image_data,
            )
        );

        // Add product images
        if (!empty($product_parts)) {
            $user_parts[] = array('text' => "\nEstas son las fotos de algunos productos disponibles:");
            $user_parts = array_merge($user_parts, $product_parts);
        }

        $body = array(
            'system_instruction' => array(
                'parts' => array(array('text' => $system_prompt))
            ),
            'contents' => array(
                array(
                    'role' => 'user',
                    'parts' => $user_parts,
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
                'responseModalities' => array('TEXT', 'IMAGE'),
            ),
        );

        // Use Gemini 2.0 Flash for image generation
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=" . $api_key;

        $response = wp_remote_post($url, array(
            'body' => json_encode($body),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Error de conexión con la IA.'));
        }

        $res_data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($res_data['error']['message'])) {
            // Fallback: try without image generation
            unset($body['generationConfig']['responseModalities']);
            $url_fallback = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $api_key;
            $response = wp_remote_post($url_fallback, array(
                'body' => json_encode($body),
                'headers' => array('Content-Type' => 'application/json'),
                'timeout' => 60,
            ));

            if (is_wp_error($response)) {
                wp_send_json_error(array('message' => 'Error de conexión con la IA.'));
            }

            $res_data = json_decode(wp_remote_retrieve_body($response), true);
        }

        // Parse response
        $result = array(
            'text' => '',
            'image' => '',
            'products' => array(),
        );

        if (isset($res_data['candidates'][0]['content']['parts'])) {
            foreach ($res_data['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text'])) {
                    $result['text'] .= $part['text'];
                } elseif (isset($part['inline_data'])) {
                    $result['image'] = 'data:' . $part['inline_data']['mime_type'] . ';base64,' . $part['inline_data']['data'];
                }
            }
        }

        if (empty($result['text']) && empty($result['image'])) {
            wp_send_json_error(array('message' => 'No se pudo generar la propuesta. Inténtalo con otra foto.'));
        }

        // Add suggested product links
        foreach ($catalog as $p) {
            if (stripos($result['text'], $p['name']) !== false) {
                $result['products'][] = array(
                    'name' => $p['name'],
                    'price' => $p['price'],
                    'url' => $p['url'],
                    'image' => $p['image_url'],
                );
            }
        }

        wp_send_json_success($result);
    }
}

new DSS_Room_Designer();
