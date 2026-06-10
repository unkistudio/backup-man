<?php

$token_file = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-content/backup-man-backups/.restore-token';

if (!isset($_GET['token']) || !file_exists($token_file)) {
    die('Invalid request.');
}

$data = json_decode(file_get_contents($token_file), true);
if (!$data || $data['token'] !== $_GET['token']) {
    die('Invalid token.');
}

$zip_path = $data['zip_path'];
$admin_url = $data['admin_url'];
unlink($token_file);

if (!file_exists($zip_path)) {
    header('Location: ' . $admin_url . '?page=backup-man&backup_notice=error&backup_message=' . urlencode('Backup file not found.'));
    exit;
}

$extract_dir = dirname($zip_path) . '/restore-tmp/';
@mkdir($extract_dir, 0755, true);

$zip = new ZipArchive();
if ($zip->open($zip_path) !== true) {
    header('Location: ' . $admin_url . '?page=backup-man&backup_notice=error&backup_message=' . urlencode('Failed to open zip.'));
    exit;
}
$zip->extractTo($extract_dir);
$zip->close();

if (file_exists($extract_dir . 'wp-load.php')) {
    $source = $extract_dir;
} else {
    $sub = glob($extract_dir . '*', GLOB_ONLYDIR);
    $source = !empty($sub[0]) ? rtrim($sub[0], '/') . '/' : $extract_dir;
}

$wp_root = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
$exclude = array(
    $source . 'wp-config.php',
    $source . 'wp-content/backup-man-backups',
    $source . 'wp-content/plugins/backup-man',
);
copy_dir($source, $wp_root, $exclude);

require_once $wp_root . 'wp-load.php';
global $wpdb;

$sql = @file_get_contents($source . 'database/database.sql');
if ($sql) {
    foreach (parse_sql($sql) as $q) {
        if (trim($q)) $wpdb->query($q);
    }
}

delete_dir($extract_dir);

if (strpos(basename($zip_path), 'upload-') === 0) {
    @unlink($zip_path);
}

wp_redirect(add_query_arg(array(
    'page' => 'backup-man',
    'backup_notice' => 'success',
    'backup_message' => urlencode('Restore completed.'),
), $admin_url));
exit;

function copy_dir($src, $dest, $exclude) {
    $dir = opendir($src);
    @mkdir($dest, 0755, true);
    while (false !== ($f = readdir($dir))) {
        if ($f === '.' || $f === '..') continue;
        $s = $src . '/' . $f;
        $d = $dest . '/' . $f;
        foreach ($exclude as $ex) {
            if (strpos($s, $ex) === 0) continue 2;
        }
        is_dir($s) ? copy_dir($s, $d, $exclude) : copy($s, $d);
    }
    closedir($dir);
}

function parse_sql($sql) {
    $queries = array();
    $cur = '';
    foreach (explode("\n", $sql) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '-') continue;
        $cur .= ' ' . $line;
        if (substr($line, -1) === ';') { $queries[] = trim($cur); $cur = ''; }
    }
    if ($cur) $queries[] = trim($cur);
    return $queries;
}

function delete_dir($dir) {
    if (!is_dir($dir)) return;
    foreach (array_diff(scandir($dir), array('.', '..')) as $f) {
        $p = $dir . '/' . $f;
        is_dir($p) ? delete_dir($p) : unlink($p);
    }
    rmdir($dir);
}
