/*
 * Copyright (c) 2018.
 * MagicMCR является отдельным и независимым продуктом.
 * Исходный код распространяется под лицензией GNU General Public License v3.0.
 *
 * MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.
 * Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,
 * участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.
 */

(function (window, document, jquery)
{
    let app = window.app;

    let $table = $('table#phrases').magicTable({
        lengthMenu: [[25, 50, 75, 150, 250, -1], [25, 50, 75, 150, 250, "Все"]],
        pageLength: 25,
        columns: [
            { "orderable": true },
            { "orderable": false }
        ]
    });

    $('#languages').dropdown({
        onChange: function(value, text, $selectedItem) {
            window.location.href = '/admin/l10n/phrases/' + value;
        }
    });

    app.ajax({
        type: 'POST',
        dataType: 'json',
        async: true, 
        success: function (phrases) {
            $.each(phrases, function (phraseId, phrase) {
                let phrase_html = '<h5>' + phraseId + '</h5><span>' + phrase + '</span>';

                let $phrase_node = $table.row.add([ phrase_html, '' ]).draw().node();
                $($phrase_node).addClass('phrase');
            });
        }
    });
}
)(window, document, jQuery);

