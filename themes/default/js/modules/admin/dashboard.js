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
});

function ChatBuild(container, charttype, properties) {

    let chart_properties = { type: charttype };

    $.extend(chart_properties, properties);

    return new Chart(container, chart_properties);

}