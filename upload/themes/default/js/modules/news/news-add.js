$(function(){
	window.onload = function () {
		tinymce.init({
			selector: "textarea.tinymce",
			language: "ru",
			theme: "modern",
			skin: 'lightgray',
			browser_spellcheck: true,
			plugins: "code preview image imagetools link textcolor wordcount pagebreak",
			menubar: "file edit insert view format table tools",
			toolbar1: "bold italic strikethrough | formatselect fontsizeselect | backcolor forecolor",
			toolbar2: "alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | link image | pagebreak | removeformat",
			pagebreak_separator: "{READMORE}"
		});
	}
});
