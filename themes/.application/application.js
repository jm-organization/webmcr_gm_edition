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

            $('.ui.messages > .message').each(function (messageId, message) {
                let $message    = $(message),
                    $messageId  = 'message_' + (messageId + 1)
                ;

                $message.attr('id', $messageId);

                $messagesList[$messageId] = $message;
            })

        ).then(function() {

            _this.messages.list = $messagesList;

        });
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
}