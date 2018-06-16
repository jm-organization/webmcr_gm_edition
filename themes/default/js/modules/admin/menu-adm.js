$(document).ready(function() {
    $('[name="page_id"]').on('input', function () {
        var page_id = this.value;
        var page_prefix = $('[name="url"]').data('prefix');

        $('[name="url"]').val( page_prefix + page_id );
        $('#copylink').attr( 'data-clipboard-text', $('#copylink').data('prefix') + page_id );
        $('#page_url').val(page_id);
    });

    var image = $('.menu-adm-change .menu-icon > .icon-input:checked + .icon-img').css('background-image').replace(/url\("(.*?)"\)/ig, '$1');
    set_menu_icon('#menu_icon', image);

    $('[name="icon"]').on('click', function () {
        image = $(this).data('icon');
        set_menu_icon('#menu_icon', image);
        $('#icons-list').removeClass('open');
    });


    $('#btnGroupDrop1').on('click', function () {
        $( $(this).data('target') ).toggleClass('open');
    });

    function set_menu_icon(container, img) {
        $(container).attr('src', img);
    }
});