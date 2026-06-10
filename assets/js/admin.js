(function($) {
    'use strict';

    $(document).ready(function() {
        var maxUpload = parseInt(backupMan.maxUpload, 10);

        $('#bm-file').on('change', function() {
            var file = this.files[0];
            if (!file) return;

            $('.bm-file-text').text(file.name);
            $('.bm-file-error').remove();

            if (file.size > maxUpload) {
                $(this).closest('.bm-file-input').after(
                    '<p class="bm-file-error">File is ' + formatSize(file.size) +
                    ', but your server limit is ' + backupMan.maxUploadDisplay +
                    '. Increase <code>post_max_size</code> and <code>upload_max_filesize</code>.</p>'
                );
            }
        });

        $('#bm-restore-form').on('submit', function(e) {
            $('.bm-file-error').remove();
            var file = $('#bm-file')[0].files[0];
            if (file && file.size > maxUpload) {
                e.preventDefault();
                $('#bm-file').closest('.bm-file-input').after(
                    '<p class="bm-file-error">File too large (' + formatSize(file.size) +
                    '). Server limit is ' + backupMan.maxUploadDisplay +
                    '. Increase <code>post_max_size</code> and <code>upload_max_filesize</code>.</p>'
                );
            }
        });

        $('.bm-restore-form').on('submit', function() {
            var domain = $('#bm-new-domain-backups').val().trim();
            $(this).find('input[name="new_domain"]').val(domain);
        });

        function formatSize(bytes) {
            if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(1) + ' GB';
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return bytes + ' B';
        }
    });
})(jQuery);
