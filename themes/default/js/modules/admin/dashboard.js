$(document).ready(function(){
    // Start carusel for modules
    $("#modules_carusel").owlCarousel({
        center: true,
        items:10,
        loop:true,
        margin:10,
        nav:true
    });

    (function () {

        let user_groups = $('#user-group-statistic').data('data');
        let statistic = {
            _data: [],
            _message: {
                colors: []
            },
            _title: 'user-group',
        };

        for (let group in user_groups) {
            statistic._data.push({
                label: user_groups[group].TITLE,
                value: user_groups[group].COUNT
            });

            statistic._message.colors.push(user_groups[group].COLOR);
        }
        statistic._message = JSON.stringify(statistic._message);

        mcr.buildDountGraph(statistic);

    })(this);

    (function () {

        let users = $('#user-date-reg-statistic').data('data');
        let statistic = {
            _data: users,
            _title: 'user-date-reg',
        };

        mcr.buildLineGraph(statistic);

    })(this);
});