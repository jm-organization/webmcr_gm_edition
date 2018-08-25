$(document).ready(function () {
	$('#permissions-container').on('click', 'span', function () {
		var permissions = $('#permissions');

		permissions.stop(true, false);

		if (!permissions.hasClass('opened')) {
			$(this).parent().animate({
				height: permissions.height() + 35
			}, {
				duration: 400,
				complete: function () {
					permissions.removeClass('hide').addClass('opened')
				}
			});

			permissions.animate({
				bottom: '-25px',
				'z-index': 1,
				opacity: 1
			}, 300);
		} else {
			permissions.animate({
				bottom: '-100%',
				'z-index': -1,
				opacity: 0
			}, {
				duration: 300,
				complete: function () {
					permissions.removeClass('opened').addClass('hide')
				}
			});

			$(this).parent().animate({
				height: 35
			}, 400);
		}
	});
});
