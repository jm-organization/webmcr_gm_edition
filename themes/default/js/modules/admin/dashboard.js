$(document).ready(function(){
    // Start carusel for modules
    $("#modules_carusel").owlCarousel({
        center: true,
        items:10,
        loop:true,
        margin:10,
        nav:true
    });

    $("#themes_carusel").owlCarousel({
        center: false,
        items:2,
        loop:false,
        margin:10,
        nav:true
    });

    (function () {

        let $chart = $('#user-date-reg-statistic').parent().data('data'),
            $cart2 = $('#user-group-statistic').parent().data('data')
        ;

        ChatBuild('user-date-reg-statistic', 'line', {
            data: {
                labels: $chart.xKeys, // $chart.xKeys
                datasets: [{
                    label: lng.registered_users,
                    data: $chart.yKeys, // $chart.yKeys
                    fill: 'origin',
                    borderColor: "#43494d",
                    backgroundColor: "rgba(120, 125, 127, 0.5)",
                    lineTension: 0.4
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {  beginAtZero: true, stepSize: 4 },
                        stacked: true,
                    }]
                }
            }
        });

        //new Chart('user-date-reg-statistic',{"type":"line","data":{"labels":["January","February","March","April","May","June","July"],"datasets":[{"label":"My First Dataset","data":[65,59,80,81,56,55,40],"fill":false,"borderColor":"rgb(75, 192, 192)","lineTension":0.1}]},"options":{}});

        ChatBuild('user-group-statistic', 'pie', {
            data: {
                labels: $cart2.xKeys,
                datasets: [{
                    data: $cart2.yKeys,
                    backgroundColor: $cart2.colors
                }]
            },
            options: {
                maintainAspectRatio: false
            }
        });


    })(this);


    $('#get_more_theme_info').on('click', onOpenThemeInfoModal);
});

function ChatBuild(container, charttype, properties) {

    let chart_properties = { type: charttype };

    $.extend(chart_properties, properties);

    return new Chart(container, chart_properties);

}

function onOpenThemeInfoModal() {

    var $modal = $('#themesModal');

    var theme_cod = $(this).data('theme-cod');

    $.getJSON("index.php?mode=ajax&do=themes&theme="+theme_cod, function(theme){

        //console.log(json);
        var authors = theme.Author.split('; ');

        $modal.find('.theme-name').text(theme.ThemeName);

        $modal.find('.modal-header').css({
            'background-image': 'url(/themes/'+theme.ThemeCode+'/'+theme.Screenshots[1]+')'
        });

        $modal.find('.about-theme').html(theme.MoreAbout);

        $modal.find('.theme-version span').text(theme.Version);
        $modal.find('.theme-supported-magicmcr-version span').text(theme.SupportedMagicMCRVersion);
        $modal.find('.theme-version-info').attr('data-content', theme.VInfo);
        $modal.find('.theme-date-created span').text(theme.DateCreate);
        $modal.find('.theme-date-of-last-release span').text(theme.DateOfRelease);

        $modal.find('.theme-update-url span').text(theme.UpdateURL);

        $modal.find('.theme-author span').html('<span class="badge badge-info mr-2">'+authors.join('</span><span class="badge badge-info mr-2">')+'</span>');
        $modal.find('.theme-author-url span').html(theme.AuthorUrl);

        var gallary = '';

        for (var image in theme.Screenshots) {
            gallary +=
                '<a class="fa fa-search" href="/themes/'+theme.ThemeCode+'/'+theme.Screenshots[image]+'">' +
                '    <img src="/themes/'+theme.ThemeCode+'/'+theme.Screenshots[image]+'" >' +
                '</a>'
            ;
        }

        //console.log(gallary);

        $modal.find('#screenshots').html('<div id="lightgallery"></div>');
        $modal.find('#lightgallery').html(gallary).justifiedGallery({
            border: 6
        }).on('jg.complete', function() {
            $(this).lightGallery({
                thumbnail:true,
                animateThumb: false,
                showThumbByDefault: false
            });
        });

    });

}