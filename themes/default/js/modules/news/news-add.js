$(document).ready(function () {
	mcr.init_database('#news_list', {
		searching: true,
		language: {
			url: '/language/ru-RU/js/database.json'
		},
		"aoColumns": [
			null,
			null,
			null,
			{"bSortable": false}
		]
	});

	tinymce.init({
		selector: "textarea.tinymce",
		language: "ru",
		theme: "modern",
		skin: 'lightgray',
		browser_spellcheck: true,
		pagebreak_split_block: true,
		plugins: "code preview image imagetools link textcolor wordcount pagebreak",
		menubar: "file edit insert view format table tools",
		toolbar1: "bold italic strikethrough | formatselect fontsizeselect | backcolor forecolor | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | link image | pagebreak | removeformat",
		pagebreak_separator: "{READMORE}"
	});

	// Активируем триггер показа блока запланированной публикации
	$('#planed_publish')
		.checkbox()
		.first().checkbox({
		onChecked: function () {
			$('#date_publish').show();
		},
		onUnchecked: function () {
			$('#date_publish').hide();
		},
	})
	;

	// DataTime Picker
	$('#input_publish_time, #input_date_cs').datetimepicker({
		lang: 'ru',
		timepicker: true,
		value: '',
		format: 'd.m.Y H:i:s',
		minDate: '+1970/01/02'
	});
});
