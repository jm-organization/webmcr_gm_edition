$(function(){
	window.onload = function () {
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
    function check_switch_state() {
			if ($('.switch .button').hasClass('right')) {
					$('[name="planed_publish"]').attr('checked', 'checked');
					$('#date_publish').show();
				} else {
					$('[name="planed_publish"]').removeAttr('checked');
					$('#date_publish').hide();
			}
		}
		
		$(document).ready(function () {
			$('.switch').on('click', '.button', function () {
				$(this).toggleClass('right');
				
				check_switch_state();
			});
			
			$('#input_publish_time').datetimepicker({
				lang:'ru',
				timepicker:true,
				value:'',
				format:'d.m.Y H:i:s'
			});
		});
	}
});
