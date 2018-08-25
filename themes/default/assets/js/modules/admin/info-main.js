/*function load_last_version(){
 $.ajax({
 url: "http://api.webmcr.com/?do=versions&limit=1",
 dataType: "json",
 type: "GET",
 async: true,
 cache: false,
 contentType: false,
 processData: false,
 beforeSend: function(){ $("#api-engine-version").html(mcr.loader); },
 success: function(json){
 data = json.data[0];
 if(json.type=='success'){
 $("#api-engine-version").text(data.title+' '+data.version);
 }else{
 $("#api-engine-version").text(json.message);
 }
 }
 });
 }*/

function load_git_version() {
	$.getJSON("https://api.github.com/repos/jm-organization/webmcr_gm_edition/releases", function (json) {

		if ($.isEmptyObject(json)) {
			return;
		}

		// version-on-server
		$('#version-on-server').html('<a href="' + json[0]['html_url'] + '" target="_blank">' + json[0]['tag_name'] + '</a>');
		$('#update-info-panel').attr('data-version-on-server', json[0]['tag_name']);
	});
}


function load_git_dev_version() {
	$.getJSON("https://api.github.com/repos/jm-organization/webmcr_gm_edition/tags", function (json) {

		if ($.isEmptyObject(json)) {
			return;
		}

		$('#version-dev').html('<a href="' + json[0]['zipball_url'] + '" target="_blank">' + json[0]['name'] + '</a>');
	});
}

$(function () {

	if ($(document).has('#update-info-panel')) {
		if (!navigator.onLine) {
			notify(lng.error, lng_im.e_connection, 1);
			return;
		}

		$("#api-engine-news, #api-engine-version, #git-engine-version, #git-dev-version").html("∞");

		// load_last_version();
		load_git_version();
		load_git_dev_version();

		check_on_new_version();

		$('#re-check').on('click', check_on_new_version);
	}

	function check_on_new_version() {
		mcr.loading(true);

		setTimeout(function () {
			// Убираем префикс для получения версии для сравнения.
			let current_version = $('#update-info-panel').data('version').replace('webmcr_gm_edition_', '');
			let version_on_server = $('#update-info-panel').attr('data-version-on-server').replace('webmcr_gm_edition_', '');

			if (current_version < version_on_server) {
				$('#update-status').show();
				$('#update-message').html(
					lng.you_are_can_update + ' <div class="sub header" style="display:block;font-size: 65%;line-height: 6px;" id="version-current">webmcr_gm_edition_' + current_version + '</div>'
				);
			} else {
				$('#update-status').hide();
				$('#update-message').html(
					lng.you_are_updated  + ' <div class="sub header" style="display:block;font-size: 65%;line-height: 6px;" id="version-current">webmcr_gm_edition_' + current_version + '</div>'
				);
			}

			mcr.loading(false);
		}, 800);
	}

});
