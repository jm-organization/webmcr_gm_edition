$(function(){
	window.onload = function () {
		tinymce.init({
			selector: "textarea.tinymce",
			language: "ru",
			theme: "modern",
			skin: 'lightgray',
			browser_spellcheck: true,
			plugins: "code preview image imagetools link textcolor wordcount pagebreak",
			toolbar: "formatselect | bold italic strikethrough | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | link image | forecolor | pagebreak | removeformat",
			pagebreak_split_block: true,
			pagebreak_separator: "{READMORE}"
		});
	}
});
