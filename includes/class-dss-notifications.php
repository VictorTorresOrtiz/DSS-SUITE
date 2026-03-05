<?php
/**
 * DSS Notifications Backend Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class DSS_Notifications
{
    private static $instance = null;
    private $notifications = array();

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_footer', array($this, 'render_pending_notifications'), 99);
    }

    public function enqueue_assets()
    {
        wp_enqueue_style('dss-notifications', DSS_SUITE_PLUGIN_URL . 'assets/css/dss-notifications.css', array('dashicons'), DSS_SUITE_VERSION);
        wp_enqueue_script('dss-notifications', DSS_SUITE_PLUGIN_URL . 'assets/js/dss-notifications.js', array(), DSS_SUITE_VERSION, true);
    }

    /**
     * Add a notification to the queue for the current request.
     */
    public function add($message, $type = 'info', $title = 'DSS Suite', $duration = 5000)
    {
        $this->notifications[] = array(
            'message' => $message,
            'type' => $type,
            'title' => $title,
            'duration' => $duration,
        );
    }

    /**
     * Persist a notification across redirects using transients.
     */
    public function add_persistent($message, $type = 'info', $title = 'DSS Suite')
    {
        $user_id = get_current_user_id();
        $transient_key = 'dss_notif_' . $user_id;
        $existing = get_transient($transient_key) ?: array();
        $existing[] = array(
            'message' => $message,
            'type' => $type,
            'title' => $title,
        );
        set_transient($transient_key, $existing, 30); // 30 seconds should be enough
    }

    public function render_pending_notifications()
    {
        $user_id = get_current_user_id();
        $transient_key = 'dss_notif_' . $user_id;
        $persistent = get_transient($transient_key) ?: array();

        if (!empty($persistent)) {
            $this->notifications = array_merge($this->notifications, $persistent);
            delete_transient($transient_key);
        }

        if (empty($this->notifications)) {
            return;
        }

        ?>
        <script type="text/javascript">
            window.dssPendingNotifications = <?php echo json_encode($this->notifications); ?>;
        </script>
        <?php
    }
}
