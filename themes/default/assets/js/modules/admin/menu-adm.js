$(document).ready(function () {

	if ($('.menu-adm-change').length != 0) {
		$('[name="page_id"]').on('input', function () {
			var page_id = this.value;
			var page_prefix = $('[name="url"]').data('prefix');

			$('[name="url"]').val(page_prefix + page_id);
			$('#copylink').attr('data-clipboard-text', $('#copylink').data('prefix') + page_id);
			$('#page_url').text(page_id);
		});

		var image = $('.menu-adm-change .menu-icon > .icon-input:checked + .icon-img').css('background-image').replace(/url\("(.*?)"\)/ig, '$1');
		set_menu_icon('#menu_icon', image);

		$('[name="icon"]').on('click', function () {
			image = $(this).data('icon');
			set_menu_icon('#menu_icon', image);
			$('#icons-list').removeClass('open');
		});


		$('#btnGroupDrop1').on('click', function () {
			$($(this).data('target')).toggleClass('open');
		});

		function set_menu_icon(container, img) {
			$(container).attr('src', img);
		}
	}

	$('[name="fixed"]').on('change', function () {
		$.ajax({
			url: '/?mode=ajax&do=menu_adm&op=attach',
			type: 'POST',
			dataType: 'json',
			data: {fixed: this.checked, page: $(this).data('page'), mcr_secure: mcr.meta_data.secure},
			complete: function (data) {
				mcr.notify(data.responseJSON._title, data.responseJSON._message, data.responseJSON._type);
			}
		});
	});

	$('#select_menu_adm_icon').popup({
        on: 'click',
        hoverable: false,
		popup: '#icons-list',
		position: 'bottom left'
    });
});