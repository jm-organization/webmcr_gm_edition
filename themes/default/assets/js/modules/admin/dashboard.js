$(document).ready(function () {
	// Start carusel for modules
	$("#modules_carusel").owlCarousel({
		center: false,
		items: 10,
		loop: false,
		margin: 10,
		nav: true
	});

	$("#themes_carusel").owlCarousel({
		center: false,
		items: 2,
		loop: false,
		margin: 10,
		nav: true
	});

	(function () {

		let $chart = $('#user-date-reg-statistic').parent().data('data'),
			$cart2 = $('#user-group-statistic').parent().data('data')
		;

		ChatBuild('user-date-reg-statistic', 'line', {
			data: {
				labels: $chart.xKeys, // $chart.xKeys
				datasets: [{
					label: lng.registered_users,
					data: $chart.yKeys, // $chart.yKeys
					fill: 'origin',
					borderColor: "#43494d",
					backgroundColor: "rgba(120, 125, 127, 0.5)",
					lineTension: 0.4
				}]
			},
			options: {
				maintainAspectRatio: false,
				scales: {
					yAxes: [{
						ticks: {beginAtZero: true, stepSize: 4},
						stacked: true,
					}]
				}
			}
		});

		//new Chart('user-date-reg-statistic',{"type":"line","data":{"labels":["January","February","March","April","May","June","July"],"datasets":[{"label":"My First Dataset","data":[65,59,80,81,56,55,40],"fill":false,"borderColor":"rgb(75, 192, 192)","lineTension":0.1}]},"options":{}});

		ChatBuild('user-group-statistic', 'pie', {
			data: {
				labels: $cart2.xKeys,
				datasets: [{
					data: $cart2.yKeys,
					backgroundColor: $cart2.colors
				}]
			},
			options: {
				maintainAspectRatio: false
			}
		});


	})(this);


	$('.get_more_theme_info').on('click', onOpenThemeInfoModal);

	$('#themesModal').modal('attach events', '.get_more_theme_info', 'show');

	$('#theme_install').on('click', function () {
		var theme = $(this).data('theme-cod');

		$.ajax({
			url: "index.php?mode=ajax&do=themes&op=settheme&theme=" + theme,
			type: 'GET',
			complete: function (data) {
				mcr.loading(true);

				window.location.reload();
			}
		});
	});
});

function ChatBuild(container, charttype, properties) {

	let chart_properties = {type: charttype};

	$.extend(chart_properties, properties);

	return new Chart(container, chart_properties);

}

function onOpenThemeInfoModal() {

	var $modal = $('#themesModal');

	var theme_cod = $(this).data('theme-cod');

	$.getJSON("index.php?mode=ajax&do=themes&op=gettheme&theme=" + theme_cod, function (theme) {

		var authors = theme.Author.split('; ');

		$modal.find('.theme-name').text(theme.ThemeCode);
		if (theme.ThemeName !== '') {
			$modal.find('.theme-name').text(theme.ThemeName);
		}

		$modal.find('.modal-header').css({
			'background-image': 'url(/themes/' + theme.ThemeCode + '/' + theme.Screenshots[1] + ')'
		});

		$modal.find('.about-theme').html('<span class="text-center text-muted">' + lng.theme_without_description + '</span>');
		if (theme.MoreAbout !== '') {
			$modal.find('.about-theme').html(theme.MoreAbout);
		}

		$modal.find('.theme-version span').text(theme.Version);
		$modal.find('.theme-supported-magicmcr-version span').text(theme.SupportedMagicMCRVersion);

		$modal.find('.theme-version-info').attr('data-content', theme.VInfo);
		if (theme.VInfo === '') {
			$modal.find('.theme-version-info').remove();
		}

		$modal.find('.theme-date-created span').text(theme.DateCreate);
		if (theme.DateCreate === '') {
			$modal.find('.theme-date-created').hide();
		}
		$modal.find('.theme-date-of-last-release span').text(theme.DateOfRelease);
		if (theme.DateOfRelease === '') {
			$modal.find('.theme-date-of-last-release').hide();
		}

		$modal.find('.theme-update-url span').text(theme.UpdateURL);
		if (theme.UpdateURL === '') {
			$modal.find('.theme-update-url').hide();
		}

		$modal.find('.theme-author span').html('<span class="badge badge-info mr-2">' + authors.join('</span><span class="badge badge-info mr-2">') + '</span>');
		$modal.find('.theme-author-url span').html(theme.AuthorUrl);

		var gallary = '';

		for (var image in theme.Screenshots) {
			gallary +=
				'<a class="fa fa-search" href="/themes/' + theme.ThemeCode + '/' + theme.Screenshots[image] + '">' +
				'    <img src="/themes/' + theme.ThemeCode + '/' + theme.Screenshots[image] + '" >' +
				'</a>'
			;
		}

		//console.log(gallary);

		$modal.find('#screenshots').html('<div id="lightgallery"></div>');
		$modal.find('#lightgallery').html(gallary).justifiedGallery({
			border: 6
		}).on('jg.complete', function () {
			$(this).lightGallery({
				thumbnail: true,
				animateThumb: false,
				showThumbByDefault: false
			});
		});

		$modal.find('#theme_install').attr('data-theme-cod', theme_cod);

	});

}