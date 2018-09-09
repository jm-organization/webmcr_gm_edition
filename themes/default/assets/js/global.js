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
    let app = window.app = new mcr_application(windows, document, jquery);

    $('.popup, .tooltip').popup();

    $.each(app.messages.list, function (messageId, $message) {
        app.setTimeout(
            messageId,
            function () {
                if (mcr_application.isVisible($message)) {
                    $message.transition('fade left');
                }
            },
            app.messages.delay
        );
    });

    $('.message .close').on('click', function() {
        let $message    = $(this).closest('.message'),
            messageId   = $message.attr('id')
        ;

        app.clearTimeout(messageId);

        $message.transition('fade');
    });

    $('form[method="post"]').prepend('<input type="hidden" name="mcr_secure" value="' + app.meta.application.secure + '">');
}
)(window, document, jQuery);

