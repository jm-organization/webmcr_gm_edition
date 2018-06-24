// LogViewer class
function LogViewer(container) {
	this.container = $(container);

	this.context_menu =
        '<div class="menu" id="context_menu">' +
        '    <div class="item" id="delete_url" href="#">Choice 1</div>' +
        '    <div class="item" id="download_url" href="#">Choice 2</div>' +
        '</div>'
	;

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
		data: {file: file_name, mcr_secure: mcr.meta_data.secure},

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
LogViewer.prototype.UpdateFile = function () {
	return null;
};

/**
 * @return {null}
 */
LogViewer.prototype.SetFileTitle = function () {
	return null;
};

$(document).ready(function () {

    let $log_viewer = new LogViewer('#log-viewer');

    $log_viewer.container.find('#logs-list').on('click', '.item', function (e) {
        mcr.loading(true);

        $(this).GetFile();
    });

    $log_viewer.container.find('#update-file').on('click', function (e) {
        mcr.loading(true);

        let file = $(this).container.find('#log-title').text();

        $(this).GetFile(file);
    });

});