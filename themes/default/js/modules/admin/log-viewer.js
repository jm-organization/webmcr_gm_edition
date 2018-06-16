// LogViewer class
    function LogViewer(container) {
        this.container = $(container);

        $('body').append(
            '<div class="dropdown-menu" id="context_menu">\n' +
            '    <a class="dropdown-item" id="delete_url" href="#"><i class="fa fa-trash"></i> '+lng.delete+'</a>\n' +
            '    <a class="dropdown-item" id="download_url" href="#"><i class="fa fa-download"></i> '+lng.download+'</a>\n' +
            '    <!--<div class="dropdown-divider"></div>\n' +
            '    <a class="dropdown-item" id="archivate_url" href="#"><i class="fa fa-file-archive-o"></i> <?/*=$this->l10n->gettext(\'archivate\');*/?></a>-->\n' +
            '</div>'
        );

        this.context_menu = $('#context_menu');

        $.fn.extend(this);
    }

    LogViewer.prototype.constructor = LogViewer;

    /**
     * @return {null}
     */
    LogViewer.prototype.GetFile = function (file) {
        let file_name = file;
        if (typeof file === 'undefined') {
            file_name = this.data('file-name');
        }
        let $log_content_container = this.container.find('#log-content');

        $.ajax({
            type: 'POST',
            url: 'index.php?mode=ajax&do=logs',
            cache: false,
            dataType: 'json',
            data: { file: file_name, mcr_secure: mcr.meta_data.secure },

            success: function (data) {
                $log_content_container.SetFile(data);
            },

            error: function (data) {

            }
        });
    };

    /**
     * @return {null}
     */
    LogViewer.prototype.SetFile = function (data) {
        this.html(data._data);

        this.container.find('#log-title').removeClass('text-muted').html(data._title);

        mcr.loading(false);
    };

    /**
     * @return {null}
     */
    LogViewer.prototype.UpdateFile = function () { return null; };

    /**
     * @return {null}
     */
    LogViewer.prototype.SetFileTitle = function () { return null; };

    /**
     * @return {boolean}
     */
    LogViewer.prototype.DrawContextMenu = function (options) {
        console.log(this.context_menu.find('a'), options);

        this.context_menu.find('a#delete_url').attr('href', options.delete_url);
        this.context_menu.find('a#download_url').attr('href', options.download_url);
        // this.context_menu.find('a#archivate_url').attr('href', options.archivate_url);

        this.context_menu.css(options).show();

        return true;
    };

    LogViewer.prototype.HideContextMenu = function (options) {
        this.context_menu.find('a').attr('href', '#');

        this.context_menu.hide();
    };


$(document).ready(function () {

    if ($(this).has('#log-viewer')) {
        (function () {

            let $log_viewer = new LogViewer('#log-viewer');
            console.log($log_viewer);

            $("body").click(function (e) {
                if ($(e.target).closest("#context_menu").length === 0) {
                    $log_viewer.HideContextMenu();
                }
            }).on('contextmenu', function() { $log_viewer.HideContextMenu(); });

            $log_viewer.container.find('#logs-list').scroll(function () { $log_viewer.HideContextMenu(); });

            $log_viewer.container.find('#logs-list').on('contextmenu', 'li', function (e) {
                let delete_url = $(this).data('delete') + '&file=' + $(this).data('file-name'),
                    // archivate_url = $(this).data('archivate') + '&file=' + $(this).data('file-name'),
                    download_url = $(this).data('download') + '&file=' + $(this).data('file-name')
                ;

                $(this).DrawContextMenu({
                    top: e.pageY + 10,
                    left: e.pageX,

                    delete_url: delete_url,
                    // archivate_url: archivate_url,
                    download_url: download_url
                });

                return false;
            });

            $log_viewer.container.find('#logs-list').on('click', 'li', function (e) {
                mcr.loading(true);

                $(this).GetFile();
            });

            $log_viewer.container.find('#update-file').on('click', function (e) {
                mcr.loading(true);

                let file = $(this).container.find('#log-title').text();

                $(this).GetFile(file);
            });

        })();
    }

});