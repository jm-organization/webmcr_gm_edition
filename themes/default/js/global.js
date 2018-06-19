var mcr = {
	debug: true, // Включение/отключение логирования ошибок в консоль [true|false]

	// Получение информации, загружаемой с сервера
	meta_data: JSON.parse($('meta[name="data"]').attr('content')),

	// Функция показа/скрытия информации о загрузке
	loading: function (status) {

		if (status !== false) {
			if (!$('#js-loader').hasClass('runclose') && !$('#js-loader').hasClass('runopen')) {
				$('#js-loader').addClass('runopen').fadeIn(300, function () {
					$(this).removeClass('runopen');
				});
			}
		} else {
			$('#js-loader').addClass('runclose').fadeOut(300, function () {
				$(this).removeClass('runclose');
			});
		}
	},

	/*
	 * Оповещение - показывает блок оповещения с различной информацией и автоматически выключает показ информации о загрузке (предыдущую функцию)
	 *
	 * @param title - Название блока
	 *
	 * @param message - Сообщение оповещения
	 *
	 * @param type - Тип оповещения 2-Ошибка(красный блок)|3-Успех(зеленый блок)|4-информация(синий блок)|остальное-примечание(оранжевый блок)
	 *
	 * @param result - Возвращаемый результат [true|false]
	 */
	notify: function (title, message, type, result) {

		var that = this;

		type = (type === undefined) ? 0 : parseInt(type);

		switch (type) {
			case 2:
				type = 'error';
				break;
			case 3:
				type = 'success';
				break;
			case 4:
				type = 'info';
				break;

			default:
				type = 'warning';
				break;
		}

		$('#js-notify').removeClass('error warning success info').addClass(type);

		$('#js-notify > #title').html(title);
		$('#js-notify > #message').html(message);

		$('#js-notify').fadeIn(300);

		that.loading(false);

		$('html, body').animate({scrollTop: $('#js-notify').offset().top - 50}, 'fast');

		if (typeof timeout != 'undefined') {
			clearTimeout(timeout);
		}

		timeout = setTimeout(function () {
			that.notify_close();
		}, 2500);

		return (result === true) ? true : false;
	},

	// Скрывает оповещение и очищает его содержимое
	notify_close: function () {
		$('#js-notify').fadeOut(500);
	},

	// Логгер ошибок в консоль
	logger: function (data) {
		if (this.debug) {
			console.log(data);
		}
	},

	// Получение параметра из URL по ключу
	getUrlParam: function (name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);
		return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	},

	// Получение всех параметров из URL
	getUrlParams: function () {
		var string = location.search.split('?')[1];

		var result = {};

		string = decodeURIComponent(string);

		if (string == undefined || string == 'undefined') {
			return result;
		}

		$.each(string.split('&'), function (key, val) {
			expl = val.split('=');

			result[expl[0]] = expl[1];
		});

		return result;
	},

	// Изменение параметра url
	changeUrlParam: function (json) {
		var get = this.getUrlParams();

		$.each(json, function (key, value) {
			if (get[key] === undefined || value !== false) {
				get[key] = value;
			}
			if (value === false && get[key] !== undefined) {
				delete get[key];
			}
		});

		if (Object.keys(get).length <= 0) {
			location.search = '';
			return false;
		}

		var string = '?';

		$.each(get, function (key, val) {
			string = string + key + '=' + val + '&';
		});

		string = string.substring(0, string.length - 1);

		location.search = string;

		return true;
	},

	/*
	 * Результирующий запрос - запрос, возвращающий результат.
	 *
	 * @param method - метод отправки запроса [GET|POST]
	 *
	 * @param url - Адрес на который будет отправляться запрос
	 *
	 * @param params - Параметры запроса [key1=param&key2=param2...]
	 *
	 */
	send_ret_req: function (method, url, params) {
		var req = null;
		try {
			req = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				req = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {
				try {
					req = new XMLHttpRequest();
				} catch (e) {
				}
			}
		}
		if (req == null) {
			throw new Error(lng.e_xmlhr);
		}

		req.open(method, url, false);
		req.send(params);

		return req.responseText;
	},

	// Получение информации о откртых и закрытых спойлерах
	spl_items: Cookies.getJSON('spl_items'),

	// Инстализация мониторинга
	init_monitoring: function () {

		if ($('.monitor-id').length <= 0) {
			return;
		}

		var that = this;

		that.loading();

		var formdata = new FormData();

		formdata.append('mcr_secure', that.meta_data.secure);

		$.ajax({
			url: "index.php?mode=ajax&do=monitoring",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function (data) {
				that.logger(data);
				that.notify(lng.error, lng.e_monitor);
			},

			success: function (data) {

				if (!data._type) {
					return that.notify(data._title, data._message);
				}

				if (data._data.length <= 0) {
					return that.loading(false);
				}

				$.each(data._data, function (key, ar) {
					$('.monitor-id#' + ar.id + ' .bar').css('width', ar.progress + '%');
					$('.monitor-id#' + ar.id + ' .progress').removeClass('progress-info').removeClass('progress-danger');

					if (ar.status == 1) {
						$('.monitor-id#' + ar.id + ' .progress').addClass('progress-info');
						$('.monitor-id#' + ar.id + ' .stats').text(ar.online + ' / ' + ar.slots);
					} else {
						$('.monitor-id#' + ar.id + ' .progress').addClass('progress-danger');
						$('.monitor-id#' + ar.id + ' .stats').text(lng.offline);
					}
				});

				that.loading(false);
			}
		});
	},

	init_filemanager: function (pge) {
		var that = this;

		var loadpage = (pge === undefined) ? 1 : pge;

		that.loading();

		var formdata = new FormData();

		formdata.append('mcr_secure', that.meta_data.secure);
		formdata.append('page', loadpage);

		$.ajax({
			url: "index.php?mode=ajax&do=filemanager",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function (data) {
				that.logger(data);
				that.notify(lng.error, lng.e_filemanager);
			},

			success: function (data) {

				if (!data._type) {
					that.loading(false);
					return;
				}

				$('.file-manager > .lastfiles').empty();

				$.each(data._data, function (key, ar) {

					if (ar.size < 1024) {
						var size = ar.size + ' ' + lng.b;
					} else if (ar.size < 1048576) {
						var size = (ar.size / 1024).toFixed(2);
						size = size + ' ' + lng.kb;
					} else if (ar.size < 1073741824) {
						var size = (ar.size / 1024 / 1024).toFixed(2);
						size = size + ' ' + lng.mb;
					} else {
						var size = (ar.size / 1024 / 1024 / 1024).toFixed(2);
						size = size + ' ' + lng.gb;
					}

					$('.file-manager > .lastfiles').append('<div class="file-line" id="' + ar.uniq + '">' +
						'<div class="line-uniq"><a href="' + ar.link + '">' + ar.uniq + '</a> <a href="#" rel="tooltip" title="' + lng.change + '" class="file-edit fa fa-pencil"></a></div>' +
						'<div class="line-oldname">' + ar.oldname + '</div>' +
						'<div class="line-size">' + size + '</div>' +
						'<div class="line-downloads"><i class="fa fa-download" rel="tooltip" title="' + lng.count_downloads + '"></i> ' + ar.downloads + '</div>' +
						'<div class="line-info"><i class="fa fa-info" rel="tooltip" title="' + lng.added + ': ' + ar.login + ' | ' + lng.date + ': ' + ar.date + '"></i></div>' +
						'<div class="line-act"><a href="#" rel="tooltip" title="' + lng.delete + '" class="file-remove fa fa-times"></a></div>' +
						'</div>');

					that.loading(false);
				});

				$('.file-manager > .lastfiles').append('<div class="pagination" id="' + loadpage + '"><ul>' +
					'<li><a href="#" class="ajax-pagin-left"><</a></li>' +
					'<li><a href="#" class="ajax-pagin-right">></a></li>' +
					'</ul></div>');
			},

			complete: function () {

				if (pge !== undefined) {
					$('html, body').stop().animate({
						scrollTop: $('.file-manager').offset().top
					}, 0);
				}
			}
		});
	},

	init_database: function (selector = '.magic-datatables', settings = {
		searching: true,
		pagin: true,
		select: true
	}) {
		var db_settings = (typeof settings !== 'undefined') ? settings : {
			searching: true,
			pagin: true,
			select: true
		};

		$(selector).DataTable(db_settings);
	},

	buildLineGraph: function (data) {
		let lines_nums = [];
		for (let i in data._data) {
			lines_nums.push(data._data[i][data._title]);
		}

		new Morris.Line({
			// ID of the element in which to draw the chart.
			element: data._title + '-statistic',
			// Chart data records -- each entry in this array corresponds to a point on
			// the chart.
			data: data._data,
			// The name of the data record attribute that contains x-values.
			xkey: 'date',
			// A list of names of data record attributes that contain y-values.
			ykeys: [data._title],
			ymin: 1,
			numLines: Math.max(...lines_nums),
			// Labels for the ykeys -- will be displayed when you hover over the
			// chart.
			labels
		:
		[lng[data._title + '_on_day']],
			hideHover
		:
		'auto',
			dateFormat
		:
		function (x) {
			let date = new Date(x);

			let IndexToMonth = [lng.jan, lng.feb, lng.mar, lng.apr, lng.may, lng.jun, lng.jul, lng.aug, lng.sep, lng.oct, lng.nov, lng.dec];

			let day = date.getDate();
			let month = IndexToMonth[date.getMonth()];
			let year = date.getFullYear();

			return day + ' ' + month + ', ' + year;
		}

		,
		resize: true
	})
		;

		mcr.loading(false);
	},

	buildDountGraph: function (data) {
		let lines_nums = [];
		for (let i in data._data) {
			lines_nums.push(data._data[i][data._title]);
		}

		let colors = [];
		if (data._title === 'user-group') {
			colors = JSON.parse(data._message).colors;
		}

		new Morris.Donut({
			// ID of the element in which to draw the chart.
			element: data._title + '-statistic',
			// Chart data records -- each entry in this array corresponds to a point on
			// the chart.
			data: data._data,
			colors: colors,
			resize: true
		});

		mcr.loading(false);
	}
};

// Функции, вызываемые при загрузке
$(function () {

	$('input[type="file"].file-inputs').bootstrapFileInput();

	// Загрузка мониторинга
	mcr.init_monitoring();

	// Активация элементов поиска
	$('#mcr-search').popup({
		popup: '.mcr-search.popup',
		on: 'click',
		inline: true,
		hoverable: true
	});

	$('#mcr-search-selector.ui.dropdown').dropdown();

	// Загрузка файлового менеджера(если доступен)
	if ($('.file-manager').length > 0) {
		mcr.init_filemanager();
	}

	// Добавление элемента защиты в html код страницы
	$('form[method="post"]').prepend('<input type="hidden" name="mcr_secure" value="' + mcr.meta_data.secure + '">');

	$(window).scroll(function () {
		var scroll = $(window).scrollTop();
		var opacity = 0.2;

		if (scroll > 150) {
			if (scroll >= 480) {
				opacity = 1;
			}

			opacity = opacity + ((scroll - 150) / 100);

			$('.navbar .bg').css('opacity', opacity);
		} else {

			$('.navbar .bg').css('opacity', 0.2);
		}
	});

	$('.navbar-toggler ').on('click', function () {
		$('nav.navbar').toggleClass('bg-faded');
	});

	// Обработчик закрытия блока оповещений
	$('body').on('click', '#js-notify > #close', function () {

		mcr.notify_close();

		return false;
	});

	$("body").on('click', '#close-notify', function () {
		$(".block-notify").fadeOut("normal", function () {
			$(this).remove();
		});
		return false;
	});

	setTimeout(function () {
		$('#close-notify').click();
	}, 1200);

	$("body").on("click", ".check-all", function () {
		var element = $(this).attr("data-for");
		var val = false;
		if ($(this)[0].checked) {
			val = true;
		}
		$("." + element).prop('checked', val);

	});

	$('body').on('click', '.remove', function () {

		if ($(this).attr("data-checkbox") != 'false') {
			var element = $(this).attr("data-for");
			var length = $('.' + element + ':checked').length;

			if (length <= 0) {
				return mcr.notify(lng.error, lng.not_selected);
			}

		}

		var text = $(this).attr("data-text");
		if (!confirm(text)) {
			return false;
		}

		return true;
	});

	$(".mcr-debug .action").on("click", function () {
		$(".mcr-debug").toggleClass("open");
		return false;
	});

	// Обработчик клика по ББ-кодам
	$("body").on("click", ".bb-panel .bb", function () {

		if ($(this).hasClass('bb-modal')) {
			return true;
		}

		// Получает идентификатор панели ББ-кодов и поля ввода
		var panel_id = $(this).closest(".bb-panel").attr("id");

		// Получаем поле ввода
		var panel_obj = $('textarea[data-for="' + panel_id + '"]')[0];

		// Фокусируем поле ввода
		panel_obj.focus();

		// Получаем позиции курсора
		var pos1 = panel_obj.selectionStart, pos2 = panel_obj.selectionEnd;

		// Получаем теги элементов
		var leftcode = ($(this).attr("data-left") == undefined) ? '' : $(this).attr("data-left");
		var rightcode = ($(this).attr("data-right") == undefined) ? '' : $(this).attr("data-right");

		var val = panel_obj.value;

		// Вставка ББ-кода в содержимое поля ввода на места выделения
		panel_obj.value = val.substr(0, pos1) + leftcode + val.substr(pos1, pos2 - pos1) + rightcode + val.substr(pos2, val.length);

		// Устанавливаем позиции курсора после вставки ББ-кода
		panel_obj.setSelectionRange(pos1 + leftcode.length, pos2 + leftcode.length);

		return false;
	});

	$("body").on("click", ".bb-panel .bb.bb-modal", function () {

		// Получает идентификатор панели ББ-кодов и поля ввода
		var panel_id = $(this).closest(".bb-panel").attr("id");

		// Получаем поле ввода
		var panel_obj = $('textarea[data-for="' + panel_id + '"]')[0];

		var modal_id = $(this).attr('href');

		// Фокусируем поле ввода
		panel_obj.focus();

		// Получаем позиции курсора
		var pos1 = panel_obj.selectionStart, pos2 = panel_obj.selectionEnd;

		// Получаем выделенный текст
		var copy = panel_obj.value.substring(pos1, pos2);

		if ($(this).attr('data-paste').length > 0) {
			var insert = parseInt($(this).attr('data-paste'));
			// Вставляем выделенный текст в поле модального окна
			$(modal_id + ' .bb-input').eq(insert).val(copy);
		}

	});

	$("body").on("click", ".bb-panel .bb-insert-input", function () {

		// Получает идентификатор панели ББ-кодов и поля ввода
		var panel_id = $(this).closest(".bb-panel").attr("id");

		// Получаем поле ввода
		var panel_obj = $('textarea[data-for="' + panel_id + '"]')[0];

		// Фокусируем поле ввода
		panel_obj.focus();

		// Получаем позиции курсора
		var pos1 = panel_obj.selectionStart;
		var pos2 = panel_obj.selectionEnd;

		var modal = $(this).closest('.modal');

		var inputs = modal.find('.bb-input');
		var code = modal.find('.data-code');
		var leftcode = code.attr('data-left');
		var rightcode = code.attr('data-right');
		var centercode = code.attr('data-center');

		$.each(inputs, function (key, elem) {

			leftcode = leftcode.replace('*' + key + '*', elem.value);
			rightcode = rightcode.replace('*' + key + '*', elem.value);
			centercode = centercode.replace('*' + key + '*', elem.value);
		});

		code = leftcode + centercode + rightcode;

		var val = panel_obj.value;

		// Вставка ББ-кода в содержимое поля ввода на места выделения
		panel_obj.value = val.substr(0, pos1) + code + val.substr(pos2, val.length);

		$('#' + modal.attr('id')).modal('hide');

		pos1 = pos1 + leftcode.length;
		pos2 = pos1 + centercode.length;

		panel_obj.setSelectionRange(pos1, pos2);

		return false;
	});

	// Обработчик клика по очистке формы от ББ-Кодов
	$('body').on('click', '.bb-panel .bb-clear', function () {

		var panel = $(this).closest('.bb-panel');

		var panel_id = panel.attr('id'), elements = panel.find('.bb');

		var panel_obj = $('textarea[data-for="' + panel_id + '"]');

		var new_val = panel_obj.val();

		elements.each(function () {

			var left = $(this).attr('data-left');
			if ($(this).closest('.bb-smiles').length <= 0) {
				var reg = new RegExp('\\[(\\w+)(="\\*(\\d+)\\*")?\\]', 'ig');
				var find = reg.exec(left);
			} else {
				var find = null;
				new_val = new_val.replace(left, '');
			}

			if (find !== null) {

				var repl = new RegExp('\\[(\\/)?' + find[1] + '(="([\\w\\s\\-\\.\\:\\;\\+\\|\\,\\?\\&\\=\\/\\*]+)?")?\\]', 'ig');
				new_val = new_val.replace(repl, '');
			}
		});

		panel_obj[0].value = new_val;

		return false;
	});

	$('body').on('click', "#mcr-search-selector > .menu > .item", function (e) {

		e.preventDefault();

		var search_val = $("#mcr-search-hidden").val();

		$("#mcr-search-selector .menu .item").removeClass("active");

		$("#mcr-search-hidden").val($(this).attr('id'));

		$(this).closest('.item').addClass("active");

		return false;

	});

	$("body").on("click", ".edit", function () {

		var element = $(this).attr("data-for");
		var length = $('.' + element + ':checked').length, link = $(this).attr("data-link");

		if (length != 1) {
			return mcr.notify(lng.error, lng.only_one);
		}

		var id = $('.' + element + ':checked').val();

		window.location.href = link + id;

		return false;
	});

	// Обработка всех групп меню в цикле
	$('.spl-body').each(function () {
		// ID группы
		var id = $(this).attr('id');

		// Проверка на существование записи о группе в куках
		if (mcr.spl_items === undefined || mcr.spl_items[id] === undefined) {
			return;
		}

		// Изменение класса группы при условии
		if (mcr.spl_items[id] === true) {
			$(this).toggleClass('closed');
			$('.spl-btn[data-for="' + id + '"]').toggleClass('closed');
		}
	});

	// Действие при клике на кнопку спойлера
	$('body').on('click', '.spl-btn', function () {

		var that = $(this);

		if (that.attr('data-block') !== undefined) {

			that.closest(that.attr('data-block')).find('.spl-body').slideToggle("fast", function () {
				that.toggleClass('closed');
				$(this).toggleClass('closed');
			});

			return false;
		}

		var element = $(this).attr("data-for");

		if (mcr.spl_items === undefined) {
			mcr.spl_items = {};
		}

		// Изменение класса при нажатии и выставление печенек
		$(".spl-body#" + element).slideToggle("fast", function () {
			that.toggleClass('closed');
			$(this).toggleClass('closed');

			mcr.spl_items[element] = (!mcr.spl_items[element]) ? true : false;

			Cookies.set('spl_items', mcr.spl_items, {expires: 365});
		});

		return false;
	});

	$('body').on('click', '.qxbb-spoiler > .qxbb-spoiler-btn', function () {

		var body = $(this).closest('.qxbb-spoiler').find('.qxbb-spoiler-body');

		if (body.is(':visible')) {
			body.fadeOut('fast');
		} else {
			body.fadeIn('normal');
		}

		return false;
	});

	// Класс для ссылок с отменой редиректа
	$("body").on("click", ".false", function () {
		return false;
	});

	$('body').on('input change', '.file-manager input[name="files"]', function () {

		mcr.loading();

		var formdata = new FormData();

		$.each($(this)[0].files, function (key, value) {
			if (value.size > 51200000) {
				return;
			}
			formdata.append('files' + key, value);
		});

		formdata.append('mcr_secure', mcr.meta_data.secure);

		$.ajax({
			url: "index.php?mode=ajax&do=filemanager&op=upload",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function (data) {
				mcr.logger(data);
				mcr.notify(lng.error, lng.e_file_load);
			},

			success: function (data) {

				if (!data._type) {
					return mcr.notify(data._title, data._message);
				}

				if (data._data.errors.length > 0) {
					mcr.notify(lng.warning, lng.e_files_not_loaded);
					mcr.logger(data._data.errors);
				}

				$.each(data._data.data, function (key, ar) {

					if (ar.size < 1024) {
						var size = ar.size + ' б';
					} else if (ar.size < 1048576) {
						var size = ar.size + ' Кб';
					} else if (ar.size < 1073741824) {
						var size = ar.size + ' Мб';
					} else {
						var size = ar.size + ' Гб';
					}

					$('.file-manager > .lastfiles').prepend('<div class="file-line" id="' + ar.uniq + '">' +
						'<div class="line-uniq"><a href="' + ar.link + '">' + ar.uniq + '</a> <a href="#" rel="tooltip" title="' + lng.change + '" class="file-edit fa fa-pencil"></a></div>' +
						'<div class="line-oldname">' + ar.oldname + '</div>' +
						'<div class="line-size">' + size + '</div>' +
						'<div class="line-downloads"><i class="fa fa-download" rel="tooltip" title="' + lng.count_downloads + '"></i> ' + ar.downloads + '</div>' +
						'<div class="line-info"><i class="fa fa-info" rel="tooltip" title="' + lng.added + ': ' + ar.login + ' | ' + lng.date + ': ' + ar.date + '"></i></div>' +
						'<div class="line-act"><a href="#" rel="tooltip" title="' + lng.delete + '" class="file-remove fa fa-times"></a></div>' +
						'</div>');

					$('.file-manager .file-input-wrapper input[type="file"].file-inputs').attr('title', lng.drop_files_here);
					$('.file-manager .file-input-wrapper > span').text(lng.drop_files_here);
					$('.file-manager .file-input-wrapper input[type="file"].file-inputs').removeAttr('style');

					mcr.loading(false);
				});
			}
		});

		return false;
	});

	$('body').on('click', '.file-manager .file-remove', function () {

		mcr.loading();

		var that = $(this);

		var id_line = that.closest('.file-line').attr('id');

		var formdata = new FormData();

		formdata.append('mcr_secure', mcr.meta_data.secure);
		formdata.append('id', id_line);

		$.ajax({
			url: "index.php?mode=ajax&do=filemanager&op=remove",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function (data) {
				mcr.logger(data);
				mcr.notify(lng.error, lng.e_file_delete);
			},
			success: function (data) {
				if (!data._type) {
					return mcr.notify(data._title, data._message);
				}

				that.closest('.file-line').fadeOut('normal', function () {
					$(this).remove();
					mcr.loading(false);
				});
			}
		});

		return false;
	});

	$('body').on('click', '.file-manager .file-edit', function () {

		mcr.loading();

		var that = $(this);

		var text = that.prev('a').text();

		if (!that.hasClass('edit-active')) {
			if (that.hasClass('fa-pencil')) {
				that.removeClass('fa-pencil');
			}
			that.addClass('fa-check edit-active');
			that.prev('a').remove();
			$('<input class="file-edit-input" type="text" value="' + text + '">').insertBefore(that);
			mcr.loading(false);
			return false;
		}

		var id_line = that.closest('.file-line').attr('id');
		var new_val = that.prev('.file-edit-input').val();

		if (new_val.length <= 0) {
			return mcr.notify(lng.error, lng.e_id_not_filled);
		}

		var formdata = new FormData();

		formdata.append('mcr_secure', mcr.meta_data.secure);
		formdata.append('id', id_line);
		formdata.append('val', new_val);

		$.ajax({
			url: "index.php?mode=ajax&do=filemanager&op=edit",
			dataType: "json",
			type: 'POST',
			contentType: false,
			processData: false,
			data: formdata,
			error: function (data) {
				mcr.logger(data);
				mcr.notify(lng.error, lng.e_file_edit);
			},
			success: function (data) {
				if (!data._type) {
					return mcr.notify(data._title, data._message);
				}

				if (that.hasClass('fa-check')) {
					that.removeClass('fa-check');
				}
				if (that.hasClass('edit-active')) {
					that.removeClass('edit-active');
				}
				that.addClass('fa-pencil');

				that.prev('.file-edit-input').remove();

				$('<a href="' + data._data.link + '">' + data._data.uniq + '</a>').insertBefore(that);

				that.closest('.file-line').attr('id', data._data.uniq);

				mcr.loading(false);
			}
		});

		return false;
	});

	$('body').on('click', '.pagination .ajax-pagin-left, .pagination .ajax-pagin-right', function () {
		var that = $(this);

		var page = $(this).closest('.pagination').attr('id');

		page = ($(this).hasClass('ajax-pagin-left')) ? parseInt(page) - 1 : parseInt(page) + 1;

		mcr.init_filemanager(page);

		return false;
	});

	$('.cpp').minicolors();

	$('body').on('click', '.is_auth_user', function () {
		if (!mcr.meta_data.is_auth) {
			mcr.notify(lng.error, lng.e_auth, false);
			return false;
		}
	});
});