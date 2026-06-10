# Backup Man

WordPress plugin for one-click backup and restore.

## Features

- **Backup** — Dumps the database and zips your entire WordPress directory into a downloadable zip
- **Restore** — Upload a backup zip or select from existing backups to restore files and database
- **PHP Limits** — Built-in widget to increase `upload_max_filesize` and `post_max_size` via `.htaccess` and `.user.ini`
- **Frontend validation** — Blocks oversized uploads before they hit the server
- **Clean UI** — No clutter, no bullshit

## Installation

1. Download or clone the repo and zip the `backup-man` folder
2. Go to **Plugins → Add New → Upload Plugin** in your WordPress admin
3. Choose the `.zip` file and click **Install Now**
4. Activate via **Plugins → Backup Man**
5. Go to **Backup Man** in the admin menu

## Requirements

- PHP 7.4+
- ZipArchive extension
- Write permissions on `wp-content/` for backup storage

## Usage

### Create a Backup

Click **Create Backup**. A zip containing your database dump and all WordPress files will be generated. Download it from the success notice or the backups list.

### Restore from a Backup

Upload a `.zip` file or use an existing backup from the list. The plugin will restore your files and database automatically.

To restore large backups, use the **PHP Limits** card to increase your PHP upload and post limits.

## Backups Location

```
wp-content/backup-man-backups/
```

## License

MIT

---

Made by [Unki Studio](https://github.com/unkistudio)
