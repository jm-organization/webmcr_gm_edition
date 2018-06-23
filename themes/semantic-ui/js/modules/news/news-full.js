$(function () {
	var wbbOpt = {
		lang: "ru",
		buttons: "bold,italic,underline,|,img,,link,quote,|,bullist,|,justifyleft,justifycenter,justifyright,|,fontcolor,smilebox"
	};

	$("#wysibb-editor").wysibb(wbbOpt);

	$("body").on("click", "#add_comment", function () {

		$("#wysibb-editor").sync();

		mcr.loading();

		var message = $('textarea[name="message"]')[0], nid = parseInt(mcr.getUrlParam('id'));

		var formdata = new FormData();

		formdata.append('id', nid);
		formdata.append('message', message.value);
		formdata.append('mcr_secure', mcr.meta_data.secure);

		$.ajax({
			url: "index.php?mode=ajax&do=modules|news|add_comment",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function (data) {
				mcr.logger(data);
				mcr.notify(lng.error, lng_nf.e_add_comment);
			},

			success: function (data) {

				if (!data._type) {
					return mcr.notify(data._title, data._message);
				}

				if ($(".comment-id").hasClass("none")) {
					$(".comment-id.none").remove();
				}

				$(".comment-list-content").hide().prepend(data._data).fadeIn(400, function () {
					message.value = '';
					var com_count = parseInt($("#comment-count").text()) + 1;
					$("#comment-count").text(com_count);

					mcr.notify(data._title, data._message, 3);
				});
			}
		});

		return false;
	});

	$("body").on("click", ".mcr-del-comment", function () {

		var that = $(this);

		if (!confirm(lng_nf.del_confirm_comment)) {
			return false;
		}

		mcr.loading();

		var nid = parseInt(mcr.getUrlParam('id')), id = parseInt($(this).attr("data-id"));

		var formdata = new FormData();

		formdata.append('id', id);
		formdata.append('nid', nid);
		formdata.append('mcr_secure', mcr.meta_data.secure);

		$.ajax({
			url: "index.php?mode=ajax&do=modules|news|delete_comment",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function (data) {
				mcr.logger(data);
				mcr.notify(lng.error, lng_nf.e_delete_comment);
			},
			success: function (data) {

				if (!data._type) {
					return mcr.notify(data._title, data._message);
				}

				that.closest('.mcr-comment-id').fadeOut(400, function () {

					$(this).remove();

					var com_count = parseInt($("#comment-count").text()) - 1;
					$("#comment-count").text(com_count);

					mcr.notify(data._title, data._message, 3);
				});
			}
		});

		return false;
	});

	$("body").on("click", ".mcr-get-comment", function () {

		mcr.loading();

		var nid = parseInt(mcr.getUrlParam('id')), id = parseInt($(this).attr("data-id"));

		var formdata = new FormData();

		formdata.append('id', id);
		formdata.append('nid', nid);
		formdata.append('mcr_secure', mcr.meta_data.secure);

		$.ajax({
			url: "index.php?mode=ajax&do=modules|news|get_comment",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function (data) {
				mcr.logger(data);
				mcr.notify(lng.error, lng_nf.e_get_comment);
			},
			success: function (data) {

				if (!data._type) {
					return mcr.notify(data._title, data._message);
				}

				var bb_value = '[quote]' + '[b]' + data._data.login + ':[/b] ' + data._data.text + '[/quote]'
				var bb_current = $("#wysibb-editor").bbcode();
				$("#wysibb-editor").bbcode(bb_current + bb_value);

				var html_value = '<blockquote>' + '<b>' + data._data.text + ':</b> ' + data._data.text + '</blockquote>'
				var html_current = $("#wysibb-editor").htmlcode();
				$("#wysibb-editor").htmlcode(html_current + html_value);

				mcr.loading(false);
			}
		});

		return false;

	});

	$("body").on("click", ".mcr-edt-comment", function () {

		mcr.loading();

		var nid = parseInt(mcr.getUrlParam('id')), id = parseInt($(this).attr("data-id"));

		var formdata = new FormData();

		formdata.append('id', id);
		formdata.append('nid', nid);
		formdata.append('mcr_secure', mcr.meta_data.secure);

		$.ajax({
			url: "index.php?mode=ajax&do=modules|news|get_comment",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function (data) {
				mcr.logger(data);
				mcr.notify(lng.error, lng_nf.e_edit_comment);
			},
			success: function (data) {

				if (!data._type) {
					return mcr.notify(data._title, data._message);
				}

				variables = {
					id: id,
					create: data._data.create,
					login: data._data.login,
					text: data._data.text,
				};

				$('.mcr-comment-id#' + id).tmpl('mcr-comment-edt-tmpl', variables);

				mcr.loading(false);
			}
		});

		return false;

	});

	$("body").on("click", ".mcr-edt-save", function () {

		mcr.loading();

		var id = $(this).attr("id");

		var message = $('#mcr-edit-form-' + id + ' textarea')[0], nid = parseInt(mcr.getUrlParam('id'));

		var formdata = new FormData();

		formdata.append('id', id);
		formdata.append('nid', nid);
		formdata.append('message', message.value);
		formdata.append('mcr_secure', mcr.meta_data.secure);

		$.ajax({
			url: "index.php?mode=ajax&do=modules|news|edit_comment",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function (data) {
				mcr.logger(data);
				mcr.notify(lng.error, lng_nf.e_save_comment);
			},
			success: function (data) {

				if (!data._type) {
					return mcr.notify(data._title, data._message);
				}

				$("#mcr-edit-form-" + id).remove();

				$(".mcr-comment-id#" + id + " .mcr-comment-id-content").hide().prepend(data._data).fadeIn(400, function () {
					$(this).html(data._data);
					mcr.notify(data._title, data._message, 3);
				});
			}
		});

		return false;
	});

	$("body").on("click", ".like, .dislike", function () {

		mcr.loading();

		var nid = $(this).data('nid'), value = ($(this).hasClass("like")) ? 1 : 0;

		var formdata = new FormData();

		formdata.append('nid', nid);
		formdata.append('value', value);
		formdata.append('mcr_secure', mcr.meta_data.secure);

		$.ajax({
			url: "index.php?mode=ajax&do=modules|news|news_like",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function (data) {
				mcr.logger(data);
				mcr.notify(lng.error, lng_nf.e_vote);
			},
			success: function (data) {

				if (!data._type) {
					return mcr.notify(data._title, data._message);
				}

				$(".block-like#votes_" + nid + " .likes").hide().fadeIn(400, function () {
					$(this).text(data._data.likes);
				});

				$(".block-like#votes_" + nid + " .dislikes").hide().fadeIn(400, function () {
					$(this).text(data._data.dislikes);
				});

				mcr.notify(data._title, data._message, 3);
			}
		});

		return false;
	});
});