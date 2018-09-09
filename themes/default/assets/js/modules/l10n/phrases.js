/*
 * Copyright (c) 2018.
 * MagicMCR является отдельным и независимым продуктом.
 * Исходный код распространяется под лицензией GNU General Public License v3.0.
 *
 * MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.
 * Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,
 * участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.
 */

(function (windows, document, jquery)
{
    let app = windows.app;

    let $table = $('table#phrases').magicTable({
        lengthMenu: [[50, 75, 150, 250, -1], [50, 75, 150, 250, "Все"]],
        pageLength: 50,
        columns: [
            { "orderable": true },
            { "orderable": false }
        ]
    });

    /*app.ajax({
        url: ''
    });*/
}
)(window, document, jQuery);

