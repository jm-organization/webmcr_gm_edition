/*
 * Copyright (c) 2018.
 * MagicMCR является отдельным и независимым продуктом.
 * Исходный код распространяется под лицензией GNU General Public License v3.0.
 *
 * MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.
 * Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,
 * участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.
 */

class mcr_application
{
    constructor(windows, document, jquery)
    {
        let _this       = this;

        this.$          = jquery;
        this.messages   = { delay: 3500, list: [] };
        this.timeouts   = {};

        this.meta       = {};

        this.$('meta').each(function (id, element) {
            if (element.name !== '') {
                let value = _this.isValidJSON(element.content) ? JSON.parse(element.content) : element.content;

                _this.meta[element.name] = value;
            }
        });

        this._init_site_components();
    }

    _init_site_components()
    {
        let $               = this.$,
            $messagesList   = {},
            _this           = this
        ;

        $.when(

            // Ищим сообщения в контйнере сообщений
            // Идетитифицируем сообщения
            $('.ui.messages > .message').each(function (messageId, message) {
                let $message    = $(message),
                    $messageId  = 'message_' + (messageId + 1)
                ;

                // Унифицируем сообщение, добавляя ему идентификатор
                $message.attr('id', $messageId);

                // Регистрирунем сообщение по его идентитификатору
                $messagesList[$messageId] = $message;
            })

        ).then(function() {

            // По окончанию идентитификации сообщений
            // Регистриуем отправленные с сайта сообщения глобально
            _this.messages.list = $messagesList;

        });

        $.fn.magicTable = function (options) {
            if (typeof options !== 'object') {
                options = {};
            }

            $.extend(options, {
                sDom:
                    '<"magic_table_control_panel_top"' +
                    '   <"magic_table_length_cp"l>' +
                    '   <"magic_table_filter_cp"f>' +
                    '>' +

                    '<"magic_table_container"t>' +

                    '<"magic_table_control_panel_bottom"' +
                    '   <"magic_table_information"i>' +
                    '   <"magic_table_pagination"p>' +
                    '>'
                ,
                language: { url: "//cdn.datatables.net/plug-ins/1.10.19/i18n/Russian.json" },
            });

            // $.ajax({
            //     url: "/themes/.application/css/magictable.css",
            //     dataType: "stylesheet",
            //     success: function (data) {
            //         console.log(data);
            //     }
            // });

            return $(this).DataTable(options);
        }
    }

    isValidJSON(json)
    {
        return /^[\],:{}\s]*$/.test(json.replace(/\\["\\\/bfnrtu]/g, '@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').replace(/(?:^|:|,)(?:\s*\[)+/g, ''))
    }

    isVisible(el)
    {
        let $el = $(el);

        return $el.is(':visible') || $el.is(':not(:hidden)');
    }

    _loading(enabled)
    {
        let $loader = this.$('#js-loader');

        if (enabled) {
            if (!$loader.hasClass('runclose') && !$loader.hasClass('runopen')) {
                $loader.addClass('runopen').fadeIn(300, function () {
                    $(this).removeClass('runopen');
                });
            }
        } else {
            $loader.addClass('runclose').fadeOut(300, function () {
                $(this).removeClass('runclose');
            });
        }
    }

    setTimeout(name, callback, delay)
    {
        let _this = this;

        this.timeouts[name] = setTimeout(
            function () {
                callback();

                delete _this.timeouts[name];
            },
            delay
        );
    }

    clearTimeout(name)
    {
        let timeout = this.timeouts[name];

        clearTimeout(timeout);

        delete this.timeouts[name];
    }

    ajax(options)
    {
        let $       = this.$,
            _this   = this
        ;

        if (typeof options !== 'object') {
            options = {};
        }

        $.extend(options, {
            data: { mcr_secure: this.meta.application.secure },
            complete: function (jqXHR, textStatus) {
                _this._loading(false);
            }
        });
        
        if (typeof options.success !== 'undefined') {
            let success = options.success;

            options.success = function (data, textStatus, jqXHR) {
                _this._loading(false);

                success(data, textStatus, jqXHR);
            }
        }

        _this._loading(true);

        return $.ajax(options);
    }
}