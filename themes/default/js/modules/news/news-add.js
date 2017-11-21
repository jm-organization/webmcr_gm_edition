function check_switch(checkbox, callback_true, callback_false) {
	if ($(checkbox).is(':checked')) {
		callback_true();
	} else {
		callback_false();
	}
}

$(document).ready(function () {
	tinymce.init({
		selector: "textarea.tinymce",
		language: "ru",
		theme: "modern",
		skin: 'lightgray',
		browser_spellcheck: true,
		pagebreak_split_block: true,
		plugins: "code preview image imagetools link textcolor wordcount pagebreak",
		menubar: "file edit insert view format table tools",
		toolbar1: "bold italic strikethrough | formatselect fontsizeselect | backcolor forecolor",
		toolbar2: "alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | link image | pagebreak | removeformat",
		pagebreak_separator: "{READMORE}"
	});

	// <<JS::SWITCH
	$('.switch').on(
		'click',
		'[name="planed_publish"]'
		+', [name="closed_comments"]',
		function () {
			switch (this.name) {
				case 'planed_publish':
					check_switch('[name="planed_publish"]', function () {
						$('#date_publish').show();
					}, function () {
						$('#date_publish').hide();
					});
					break;
				case 'closed_comments':
					check_switch('[name="closed_comments"]', function () {
						$('#date_cs').show();
					}, function () {
						$('#date_cs').hide();
					});
					break;
				default:
					break;
			}
		}
	);
	check_switch('[name="planed_publish"]', function () {
		$('#date_publish').show();
	}, function () {
		$('#date_publish').hide();
	});
	check_switch('[name="closed_comments"]', function () {
		$('#date_cs').show();
	}, function () {
		$('#date_cs').hide();
	});
	// >>

	// DataTime Picker
	$('#input_publish_time, #input_date_cs').datetimepicker({
		lang:'ru',
		timepicker:true,
		value:'',
		format:'d.m.Y H:i:s',
		minDate:'+1970/01/02'
	});
});
