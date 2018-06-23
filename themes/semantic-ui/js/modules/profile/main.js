$(function () {

	$(".skin-uploader, .cloak-uploader").on("input change", function () {
		$(this).submit();
	});

	$('.menu .item').tab({
		history: true,
		historyType: 'hash'
	});

});
