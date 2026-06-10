<?php

defined('ABSPATH') || exit;

class Backup_Man_Backup {

    private $backup_dir;
    private $timestamp;
    private $backup_name;

    public function __construct() {
        $this->timestamp = time();
        $this->backup_name = 'backup-' . date('Y-m-d-His', $this->timestamp);
        $this->backup_dir = BACKUP_MAN_BACKUPS_DIR . $this->backup_name . '/';
    }

    public function run() {
        wp_mkdir_p($this->backup_dir);
        wp_mkdir_p($this->backup_dir . 'database/');

        $db_file = $this->dump_database();
        $this->zip_wordpress($db_file);

        return $this->backup_dir . $this->backup_name . '.zip';
    }

    private function dump_database() {
        global $wpdb;
        $db_file = $this->backup_dir . 'database/database.sql';

        $tables = $wpdb->get_col('SHOW TABLES');
        $output = "-- Backup Man Database Dump\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";

            $create = $wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
            $output .= $create[1] . ";\n\n";

            $rows = $wpdb->get_results("SELECT * FROM `{$table}`", ARRAY_A);
            foreach ($rows as $row) {
                $values = array_map(function($val) use ($wpdb) {
                    return $val === null ? 'NULL' : "'" . $wpdb->_escape($val) . "'";
                }, array_values($row));
                $output .= "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }

        file_put_contents($db_file, $output);
        return $db_file;
    }

    private function zip_wordpress($db_file) {
        $zip_file = $this->backup_dir . $this->backup_name . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Failed to create zip file.');
        }

        $wp_root = ABSPATH;
        $exclude = array(
            BACKUP_MAN_BACKUPS_DIR,
            $this->backup_dir,
            WP_CONTENT_DIR . '/cache/',
            WP_CONTENT_DIR . '/upgrade/',
        );

        $this->add_directory_to_zip($zip, $wp_root, $wp_root, $exclude);
        $zip->addFile($db_file, 'database/database.sql');

        $zip->close();
    }

    private function add_directory_to_zip($zip, $root, $dir, $exclude) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isFile()) continue;

            $path = $file->getPathname();
            $relative = substr($path, strlen($root));

            $skip = false;
            foreach ($exclude as $ex) {
                if (strpos($path, $ex) === 0) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            $zip->addFile($path, $relative);
        }
    }
}
