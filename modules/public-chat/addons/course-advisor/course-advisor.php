<?php
/**
 * Addon: Course Advisor
 * Asistente IA para webs de formaciones y cursos.
 * Asesora al visitante sobre qué curso/formación le conviene
 * según sus objetivos, nivel y disponibilidad.
 */

if (!defined('ABSPATH'))
    exit;

define('DSS_COURSE_ADVISOR_URL', plugin_dir_url(__FILE__));
define('DSS_COURSE_ADVISOR_DIR', plugin_dir_path(__FILE__));

class DSS_Course_Advisor
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_dss_course_advisor', array($this, 'handle_request'));
        add_action('wp_ajax_nopriv_dss_course_advisor', array($this, 'handle_request'));
    }

    public function enqueue_assets()
    {
        wp_enqueue_style('dss-course-advisor', DSS_COURSE_ADVISOR_URL . 'assets/css/course-advisor.css', array('dashicons'), DSS_SUITE_VERSION);
        wp_enqueue_script('dss-course-advisor', DSS_COURSE_ADVISOR_URL . 'assets/js/course-advisor.js', array('jquery', 'dss-public-chat-script'), DSS_SUITE_VERSION, true);
        wp_localize_script('dss-course-advisor', 'dssCourseAdvisor', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dss_course_advisor_nonce'),
        ));
    }

    /**
     * Get courses from WooCommerce (or any CPT).
     */
    private function get_course_catalog($limit = 50)
    {
        $courses = array();

        // Try WooCommerce products first
        if (class_exists('WC_Product_Query')) {
            $query = new WC_Product_Query(array(
                'status' => 'publish',
                'limit' => $limit,
                'orderby' => 'date',
                'order' => 'DESC',
            ));

            foreach ($query->get_products() as $product) {
                $cats = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
                $courses[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'url' => $product->get_permalink(),
                    'description' => wp_strip_all_tags($product->get_short_description()),
                    'full_description' => wp_strip_all_tags(mb_substr($product->get_description(), 0, 300)),
                    'categories' => is_array($cats) ? $cats : array(),
                    'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium') ?: '',
                );
            }
        }

        // Also check for common LMS CPTs (LearnDash, Tutor LMS, LifterLMS)
        $lms_types = array('sfwd-courses', 'courses', 'course', 'lp_course');
        $existing_types = array_filter($lms_types, 'post_type_exists');

        if (!empty($existing_types)) {
            $posts = get_posts(array(
                'post_type' => $existing_types,
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'date',
                'order' => 'DESC',
            ));

            foreach ($posts as $post) {
                $price = get_post_meta($post->ID, '_regular_price', true)
                    ?: get_post_meta($post->ID, '_price', true)
                    ?: get_post_meta($post->ID, '_sfwd-courses_course_price', true)
                    ?: '';

                $courses[] = array(
                    'id' => $post->ID,
                    'name' => $post->post_title,
                    'price' => $price,
                    'url' => get_permalink($post->ID),
                    'description' => wp_strip_all_tags(get_the_excerpt($post)),
                    'full_description' => wp_strip_all_tags(mb_substr($post->post_content, 0, 300)),
                    'categories' => array(),
                    'image' => get_the_post_thumbnail_url($post->ID, 'medium') ?: '',
                );
            }
        }

        return $courses;
    }

    /**
     * Build course catalog text for the AI prompt.
     */
    private function build_catalog_prompt($courses)
    {
        if (empty($courses))
            return 'No hay cursos disponibles actualmente.';

        $text = "CATALOGO DE CURSOS Y FORMACIONES DISPONIBLES:\n\n";
        foreach ($courses as $i => $c) {
            $cats = !empty($c['categories']) ? implode(', ', $c['categories']) : '';
            $price_str = !empty($c['price']) ? $c['price'] . '€' : 'Consultar precio';
            $text .= ($i + 1) . ". **{$c['name']}** - {$price_str}";
            if ($cats)
                $text .= " | Categorias: {$cats}";
            $text .= "\n";
            if (!empty($c['description']))
                $text .= "   {$c['description']}\n";
            if (!empty($c['full_description']) && $c['full_description'] !== $c['description'])
                $text .= "   Detalles: {$c['full_description']}\n";
            $text .= "   URL: {$c['url']}\n\n";
        }
        return $text;
    }

    /**
     * Handle AJAX advisor request.
     */
    public function handle_request()
    {
        check_ajax_referer('dss_course_advisor_nonce', 'nonce');

        $message = sanitize_text_field($_POST['message'] ?? '');
        if (empty($message)) {
            wp_send_json_error(array('message' => 'Escribe un mensaje.'));
        }

        // Get API key
        $api_key = get_option('dss_public_chat_api_key');
        if (empty($api_key)) {
            $api_key = get_option('dss_suite_gemini_api_key');
        }
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API Key no configurada.'));
        }

        // Get course catalog
        $courses = $this->get_course_catalog();
        $catalog_prompt = $this->build_catalog_prompt($courses);

        // Get custom system prompt if set
        $custom_prompt = get_option('dss_public_chat_prompt', '');
        $site_name = get_bloginfo('name');

        $system_prompt = "Eres un asesor de formacion profesional experto que trabaja para \"{$site_name}\", "
            . "un centro de formacion y cursos online. Tu objetivo es ayudar a los visitantes a encontrar "
            . "el curso o formacion que mejor se adapte a sus necesidades.\n\n"
            . "INSTRUCCIONES:\n"
            . "1. Pregunta sobre los OBJETIVOS del visitante: que quiere aprender, para que lo necesita.\n"
            . "2. Pregunta sobre su NIVEL actual: principiante, intermedio o avanzado.\n"
            . "3. Pregunta sobre su DISPONIBILIDAD: horario, dedicacion, presupuesto.\n"
            . "4. Con esa informacion, RECOMIENDA los cursos mas adecuados del catalogo.\n"
            . "5. Explica POR QUE cada curso es adecuado para el visitante.\n"
            . "6. Incluye siempre el ENLACE directo al curso recomendado.\n"
            . "7. Si no hay un curso que encaje perfectamente, sugiere el mas cercano y explica que podria complementar.\n"
            . "8. Se amable, profesional y orientado a resolver las dudas del visitante.\n"
            . "9. Responde SIEMPRE en español.\n"
            . "10. Usa formato con negritas (**texto**) para destacar nombres de cursos y puntos importantes.\n\n";

        if (!empty($custom_prompt)) {
            $system_prompt .= "INSTRUCCIONES ADICIONALES DEL CENTRO:\n{$custom_prompt}\n\n";
        }

        $system_prompt .= $catalog_prompt;

        // Conversation history
        $history = isset($_POST['history']) ? $_POST['history'] : array();
        $contents = array();

        if (!empty($history) && is_array($history)) {
            foreach ($history as $entry) {
                $role = sanitize_text_field($entry['role'] ?? '');
                $text = sanitize_text_field($entry['text'] ?? '');
                if (in_array($role, array('user', 'model')) && !empty($text)) {
                    $contents[] = array(
                        'role' => $role,
                        'parts' => array(array('text' => $text)),
                    );
                }
            }
        }

        // Add current message
        $contents[] = array(
            'role' => 'user',
            'parts' => array(array('text' => $message)),
        );

        $body = array(
            'system_instruction' => array(
                'parts' => array(array('text' => $system_prompt))
            ),
            'contents' => $contents,
            'generationConfig' => array(
                'temperature' => 0.7,
                'maxOutputTokens' => 1024,
            ),
        );

        // Try models in order
        $models = array('models/gemini-2.0-flash', 'models/gemini-1.5-flash', 'models/gemini-pro');
        $reply = '';
        $last_error = '';

        foreach ($models as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent?key={$api_key}";

            $response = wp_remote_post($url, array(
                'body' => json_encode($body),
                'headers' => array('Content-Type' => 'application/json'),
                'timeout' => 30,
            ));

            if (is_wp_error($response)) {
                $last_error = $response->get_error_message();
                continue;
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $reply = $data['candidates'][0]['content']['parts'][0]['text'];
                break;
            } elseif (isset($data['error']['message'])) {
                $last_error = $data['error']['message'];
            }
        }

        if (empty($reply)) {
            wp_send_json_error(array('message' => 'No se pudo obtener una respuesta. Intentalo de nuevo.'));
        }

        // Detect mentioned courses and add product cards
        $suggested = array();
        foreach ($courses as $c) {
            if (stripos($reply, $c['name']) !== false) {
                $suggested[] = array(
                    'name' => $c['name'],
                    'price' => $c['price'],
                    'url' => $c['url'],
                    'image' => $c['image'],
                    'categories' => $c['categories'],
                );
            }
        }

        wp_send_json_success(array(
            'reply' => $reply,
            'courses' => $suggested,
        ));
    }
}

new DSS_Course_Advisor();
