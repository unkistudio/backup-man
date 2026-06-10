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

$sql_file = file_exists($source . 'database/database.sql')
    ? $source . 'database/database.sql'
    : $extract_dir . 'database/database.sql';

$new_domain = $data['new_domain'] ?? '';
if ($new_domain) {
    $old_domain = detect_old_domain($sql_file);
    if ($old_domain && $old_domain !== $new_domain) {
        replace_in_file($sql_file, $old_domain, $new_domain);
        replace_in_dir($source, $old_domain, $new_domain, array($sql_file));
    }
}

copy_dir($source, $wp_root, $exclude);

require_once $wp_root . 'wp-load.php';
global $wpdb;

$sql = @file_get_contents($sql_file);
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

function detect_old_domain($sql_file) {
    $sql = @file_get_contents($sql_file);
    if (!$sql) return '';

    if (preg_match("/'siteurl'\s*,\s*'(https?:\/\/[^']+)'/i", $sql, $m)) {
        return $m[1];
    }
    if (preg_match("/'home'\s*,\s*'(https?:\/\/[^']+)'/i", $sql, $m)) {
        return $m[1];
    }
    if (preg_match_all("/'(https?:\/\/[a-z0-9._:\/-]+)'/i", $sql, $m)) {
        $freq = array_count_values($m[1]);
        arsort($freq);
        return key($freq);
    }

    return '';
}

function replace_in_file($file, $old, $new) {
    $content = @file_get_contents($file);
    if ($content === false) return;
    $content = str_replace($old, $new, $content);
    file_put_contents($file, $content);
}

function replace_in_dir($dir, $old, $new, $skip_files = array()) {
    $text_extensions = array('php', 'css', 'js', 'html', 'htm', 'txt', 'xml', 'json',
        'htaccess', 'sql', 'ini', 'conf', 'yaml', 'yml', 'md', 'less', 'scss', 'sass',
        'log', 'csv', 'rss', 'atom');
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (!$file->isFile()) continue;
        $path = $file->getPathname();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, $text_extensions)) continue;
        foreach ($skip_files as $skip) {
            if ($path === $skip) continue 2;
        }
        replace_in_file($path, $old, $new);
    }
}
