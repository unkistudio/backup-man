<?php

defined('ABSPATH') || exit;

class Backup_Man_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_post_backup_man_create_backup', array($this, 'handle_backup'));
        add_action('admin_post_backup_man_restore_backup', array($this, 'handle_restore'));
        add_action('admin_post_backup_man_delete_backup', array($this, 'handle_delete'));
        add_action('admin_post_backup_man_increase_limits', array($this, 'handle_increase_limits'));
        add_action('admin_notices', array($this, 'show_notices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_backup-man') return;
        wp_enqueue_style('backup-man-admin', BACKUP_MAN_URL . 'assets/css/admin.css', array(), '1.0.0');
        wp_enqueue_script('backup-man-admin', BACKUP_MAN_URL . 'assets/js/admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('backup-man-admin', 'backupMan', array(
            'maxUpload' => wp_max_upload_size(),
            'maxUploadDisplay' => $this->format_size(wp_max_upload_size()),
        ));
    }

    public function add_menu() {
        add_menu_page(
            'Backup Man',
            'Backup Man',
            'manage_options',
            'backup-man',
            array($this, 'render_page'),
            'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJjdXJyZW50Q29sb3IiIHN0cm9rZS13aWR0aD0iMiI+PHBhdGggZD0iTTIxIDE2VjhhMiAyIDAgMCAwLTEtMS43M2wtNy00YTIgMiAwIDAgMC0yIDBsLTcgNEEyIDIgMCAwIDAgMyA4djhhMiAyIDAgMCAwIDEgMS43M2w3IDRhMiAyIDAgMCAwIDIgMGw3LTRBMiAyIDAgMCAwIDIxIDE2eiIvPjxwb2x5bGluZSBwb2ludHM9IjMuMjcgNi45NiAxMiAxMi4wMSAyMC43MyA2Ljk2Ii8+PGxpbmUgeDE9IjEyIiB5MT0iMjIuMDgiIHgyPSIxMiIgeTI9IjEyIi8+PC9zdmc+',
            100
        );
    }

    public function render_page() {
        $backups = $this->get_backups();
        $total_size = array_sum(array_map(function($b) { return $b['raw_size']; }, $backups));
        ?>
        <div class="bm-wrap">
            <div class="bm-header">
                <div class="bm-header-inner">
                    <div class="bm-brand">
                        <svg class="bm-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                            <line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                        <div>
                            <h1>Backup Man</h1>
                            <p class="bm-tagline">One-click backup &amp; restore for your WordPress site</p>
                        </div>
                    </div>
                    <div class="bm-stats">
                        <div class="bm-stat">
                            <span class="bm-stat-value"><?php echo count($backups); ?></span>
                            <span class="bm-stat-label">Backups</span>
                        </div>
                        <div class="bm-stat">
                            <span class="bm-stat-value"><?php echo $total_size > 0 ? size_format($total_size) : '—'; ?></span>
                            <span class="bm-stat-label">Total Size</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bm-grid">
                <div class="bm-card bm-card--create">
                    <div class="bm-card-header">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                        <h2>Create Backup</h2>
                    </div>
                    <div class="bm-card-body">
                        <p>Generates a complete backup of your database and all WordPress files.</p>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="bm-create-form">
                            <?php wp_nonce_field('backup_man_backup', 'backup_man_nonce'); ?>
                            <input type="hidden" name="action" value="backup_man_create_backup">
                            <button type="submit" class="bm-btn bm-btn--primary" id="bm-create-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                                <span>Create Backup</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bm-card bm-card--restore">
                    <div class="bm-card-header">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                        <h2>Restore from File</h2>
                    </div>
                    <div class="bm-card-body">
                        <p>Upload a previously exported <code>.zip</code> backup to restore your site.</p>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" id="bm-restore-form">
                            <?php wp_nonce_field('backup_man_restore', 'backup_man_nonce'); ?>
                            <input type="hidden" name="action" value="backup_man_restore_backup">
                            <div class="bm-file-input">
                                <input type="file" name="backup_file" accept=".zip" id="bm-file" required>
                                <label for="bm-file" class="bm-file-label">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    <span class="bm-file-text">Choose a backup file&hellip;</span>
                                </label>
                            </div>
                            <button type="submit" class="bm-btn bm-btn--warning">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                <span>Restore</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bm-card bm-card--limits">
                <div class="bm-card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <h2>PHP Limits</h2>
                </div>
                <div class="bm-card-body">
                    <div class="bm-limits-row">
                        <div class="bm-limit-item">
                            <span class="bm-limit-label">upload_max_filesize</span>
                            <span class="bm-limit-value"><?php echo esc_html(ini_get('upload_max_filesize')); ?></span>
                        </div>
                        <div class="bm-limit-item">
                            <span class="bm-limit-label">post_max_size</span>
                            <span class="bm-limit-value"><?php echo esc_html(ini_get('post_max_size')); ?></span>
                        </div>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="bm-limits-form">
                            <?php wp_nonce_field('backup_man_limits', 'backup_man_nonce'); ?>
                            <input type="hidden" name="action" value="backup_man_increase_limits">
                            <label>Set both to:</label>
                            <input type="number" name="limit_mb" value="<?php echo esc_attr(get_option('backup_man_limit_mb', 1024)); ?>" min="1" max="99999"> MB
                            <button type="submit" class="bm-btn bm-btn--sm bm-btn--primary">Apply</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bm-card bm-card--backups">
                <div class="bm-card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    <h2>Existing Backups</h2>
                </div>
                <div class="bm-card-body">
                    <?php if (empty($backups)): ?>
                        <div class="bm-empty">
                            <svg class="bm-empty-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                <line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                            <h3>No backups yet</h3>
                            <p>Create your first backup to get started. Your data is worth protecting.</p>
                        </div>
                    <?php else: ?>
                        <div class="bm-backup-list">
                            <?php foreach ($backups as $backup): ?>
                                <div class="bm-backup-item">
                                    <div class="bm-backup-info">
                                        <div class="bm-backup-name">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            <span><?php echo esc_html($backup['display_name']); ?></span>
                                        </div>
                                        <div class="bm-backup-meta">
                                            <span class="bm-backup-date">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                <?php echo esc_html($backup['date']); ?>
                                            </span>
                                            <span class="bm-backup-size">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                                <?php echo esc_html($backup['size']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="bm-backup-actions">
                                        <a href="<?php echo esc_url($backup['download_url']); ?>" class="bm-btn bm-btn--sm bm-btn--outline" title="Download">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                            Download
                                        </a>
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="bm-inline-form">
                                            <?php wp_nonce_field('backup_man_restore_file', 'backup_man_nonce'); ?>
                                            <input type="hidden" name="action" value="backup_man_restore_backup">
                                            <input type="hidden" name="backup_path" value="<?php echo esc_attr($backup['path']); ?>">
                                            <button type="submit" class="bm-btn bm-btn--sm bm-btn--warning" onclick="return confirm('<?php echo esc_js('Restore this backup? This will overwrite your current site.'); ?>');">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                                Restore
                                            </button>
                                        </form>
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="bm-inline-form">
                                            <?php wp_nonce_field('backup_man_delete', 'backup_man_nonce'); ?>
                                            <input type="hidden" name="action" value="backup_man_delete_backup">
                                            <input type="hidden" name="backup_path" value="<?php echo esc_attr($backup['path']); ?>">
                                            <button type="submit" class="bm-btn bm-btn--sm bm-btn--danger" onclick="return confirm('<?php echo esc_js('Delete this backup?'); ?>');">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bm-footer">
                <span>Backup Man v<?php echo esc_html(BACKUP_MAN_VERSION); ?></span>
                <span>&middot;</span>
                <span>Backups stored in <code><?php echo esc_html(BACKUP_MAN_BACKUPS_DIR); ?></code></span>
            </div>
        </div>
        <?php
    }

    public function handle_backup() {
        check_admin_referer('backup_man_backup', 'backup_man_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        try {
            $backup = new Backup_Man_Backup();
            $zip_file = $backup->run();
            $this->redirect('success', 'Backup created successfully.', array('download' => basename($zip_file)));
        } catch (Exception $e) {
            $this->redirect('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function handle_restore() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
            $max = wp_convert_hr_to_bytes(ini_get('upload_max_filesize'));
            $post_max = wp_convert_hr_to_bytes(ini_get('post_max_size'));
            $limit = $this->format_size(min($max, $post_max));
            $this->redirect('error', "Uploaded file exceeds PHP limit ({$limit}). Increase <code>post_max_size</code> and <code>upload_max_filesize</code> in your php.ini or .user.ini file.");
            return;
        }

        $action = !empty($_POST['backup_path']) ? 'backup_man_restore_file' : 'backup_man_restore';
        check_admin_referer($action, 'backup_man_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        if (!empty($_POST['backup_path'])) {
            $zip_path = sanitize_text_field($_POST['backup_path']);
        } elseif (!empty($_FILES['backup_file'])) {
            if ($_FILES['backup_file']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['backup_file']['error'] === UPLOAD_ERR_FORM_SIZE) {
                $max = $this->format_size(wp_max_upload_size());
                $this->redirect('error', "File too large. PHP upload limit is {$max}. Increase <code>post_max_size</code> and <code>upload_max_filesize</code>.");
                return;
            }
            if ($_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                $this->redirect('error', 'Upload failed (error code ' . $_FILES['backup_file']['error'] . ').');
                return;
            }
            $zip_path = BACKUP_MAN_BACKUPS_DIR . 'upload-' . time() . '.zip';
            move_uploaded_file($_FILES['backup_file']['tmp_name'], $zip_path);
        } else {
            $this->redirect('error', 'No backup file selected.');
            return;
        }

        $token = wp_generate_password(32, false);
        file_put_contents(BACKUP_MAN_BACKUPS_DIR . '.restore-token', json_encode(array(
            'token' => $token,
            'zip_path' => $zip_path,
            'admin_url' => admin_url('admin.php'),
        )));

        wp_redirect(BACKUP_MAN_URL . 'restore.php?token=' . $token);
        exit;
    }

    public function handle_delete() {
        check_admin_referer('backup_man_delete', 'backup_man_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $path = sanitize_text_field($_POST['backup_path']);
        $zip_path = BACKUP_MAN_BACKUPS_DIR . basename($path);
        $dir_path = str_replace('.zip', '/', $zip_path);

        if (file_exists($zip_path)) unlink($zip_path);
        if (is_dir($dir_path)) $this->delete_directory($dir_path);

        $this->redirect('success', 'Backup deleted.');
    }

    public function handle_increase_limits() {
        check_admin_referer('backup_man_limits', 'backup_man_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $mb = max(1, min(99999, intval($_POST['limit_mb'] ?? 1024)));
        update_option('backup_man_limit_mb', $mb);

        $val = $mb . 'M';
        $htaccess = ABSPATH . '.htaccess';
        $userini = ABSPATH . '.user.ini';

        $content = file_exists($htaccess) ? file_get_contents($htaccess) : '';
        if (strpos($content, '# Added by Backup Man') !== false) {
            $content = preg_replace('/# Added by Backup Man\nphp_value.*(\n|$){0,3}/', '', $content);
        }
        $htaccess_new = rtrim($content) . "\n# Added by Backup Man\nphp_value upload_max_filesize {$val}\nphp_value post_max_size {$val}\n";
        file_put_contents($htaccess, $htaccess_new);

        $ini_content = file_exists($userini) ? file_get_contents($userini) : '';
        if (strpos($ini_content, '; Added by Backup Man') !== false) {
            $ini_content = preg_replace('/; Added by Backup Man\n.*(\n|$){0,3}/', '', $ini_content);
        }
        $ini_new = rtrim($ini_content) . "\n; Added by Backup Man\nupload_max_filesize = {$val}\npost_max_size = {$val}\n";
        file_put_contents($userini, $ini_new);

        $this->redirect('success', "PHP limits set to {$mb} MB. Changes take effect immediately on the next request.");
    }

    private function delete_directory($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->delete_directory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function show_notices() {
        if (empty($_GET['page']) || $_GET['page'] !== 'backup-man') return;
        if (empty($_GET['backup_notice'])) return;

        $class = $_GET['backup_notice'] === 'success' ? 'notice-success' : 'notice-error';
        $message = wp_kses($_GET['backup_message'] ?? '', array('code' => array()));
        printf('<div class="notice %s is-dismissible"><p>%s</p></div>', esc_attr($class), $message);

        if ($_GET['backup_notice'] === 'success' && !empty($_GET['download'])) {
            $url = admin_url('admin-post.php?action=backup_man_download&file=' . urlencode($_GET['download']) . '&_wpnonce=' . wp_create_nonce('backup_man_download'));
            printf('<p><a href="%s" class="button button-primary">Download Backup</a></p>', esc_url($url));
        }
    }

    private function get_backups() {
        if (!is_dir(BACKUP_MAN_BACKUPS_DIR)) return array();

        $backups = array();
        $dirs = glob(BACKUP_MAN_BACKUPS_DIR . 'backup-*/', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $dir_name = basename(rtrim($dir, '/'));
            $zip_file = $dir . $dir_name . '.zip';

            if (!file_exists($zip_file)) continue;

            $timestamp = filemtime($zip_file);
            $raw_size = filesize($zip_file);

            $backups[] = array(
                'name' => $dir_name,
                'display_name' => ucfirst(str_replace('-', ' ', preg_replace('/^backup-/', '', $dir_name))),
                'size' => size_format($raw_size),
                'raw_size' => $raw_size,
                'date' => date('j M Y, g:i a', $timestamp),
                'path' => $zip_file,
                'download_url' => admin_url('admin-post.php?action=backup_man_download&file=' . urlencode($dir_name . '.zip') . '&_wpnonce=' . wp_create_nonce('backup_man_download')),
            );
        }

        usort($backups, function($a, $b) {
            return filemtime($b['path']) - filemtime($a['path']);
        });

        return $backups;
    }

    private function redirect($notice, $message, $extra = array()) {
        $args = array_merge(array(
            'page' => 'backup-man',
            'backup_notice' => $notice,
            'backup_message' => $message,
        ), $extra);

        wp_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    private function format_size($bytes) {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
