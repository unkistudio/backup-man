<?php
/**
 * Plugin Name: Backup Man
 * Description: Backup and restore your WordPress site with a single click.
 * Version: 1.0.0
 * Author: Unki Studio
 * Author URI: https://github.com/unkistudio
 * Plugin URI: https://github.com/unkistudio/backup-man
 * Text Domain: backup-man
 */

defined('ABSPATH') || exit;

define('BACKUP_MAN_DIR', plugin_dir_path(__FILE__));
define('BACKUP_MAN_URL', plugin_dir_url(__FILE__));
define('BACKUP_MAN_BACKUPS_DIR', WP_CONTENT_DIR . '/backup-man-backups/');
define('BACKUP_MAN_VERSION', '1.0.0');

require_once BACKUP_MAN_DIR . 'includes/class-backup.php';
require_once BACKUP_MAN_DIR . 'includes/class-admin.php';

register_activation_hook(__FILE__, function() {
    wp_mkdir_p(BACKUP_MAN_BACKUPS_DIR);
    file_put_contents(BACKUP_MAN_BACKUPS_DIR . '.htaccess', "deny from all\n");
    file_put_contents(BACKUP_MAN_BACKUPS_DIR . 'index.php', "<?php\n// Silence is golden.\n");
});

function backup_man_init() {
    new Backup_Man_Admin();
}
add_action('plugins_loaded', 'backup_man_init');

add_action('admin_post_backup_man_download', function() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'backup_man_download')) wp_die('Invalid nonce.');

    $file = isset($_GET['file']) ? sanitize_file_name($_GET['file']) : '';
    $dir_name = preg_replace('/\.zip$/', '', $file);
    $path = BACKUP_MAN_BACKUPS_DIR . $dir_name . '/' . $file;

    if (!file_exists($path) || pathinfo($path, PATHINFO_EXTENSION) !== 'zip') {
        wp_die('File not found.');
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: no-cache, must-revalidate');
    readfile($path);
    exit;
});
