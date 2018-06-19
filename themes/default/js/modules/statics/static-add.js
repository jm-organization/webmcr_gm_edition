$(function () {
	window.onload = function () {
		tinymce.init({
			selector: "textarea.tinymce",
			language: "ru",
			theme: "modern",
			skin: 'lightgray',
			browser_spellcheck: true,
			plugins: "code preview image imagetools link textcolor wordcount",
			toolbar: "formatselect | bold italic strikethrough | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | link image | forecolor | removeformat",
		});
	}
});
