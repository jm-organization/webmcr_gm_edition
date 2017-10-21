/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/* global mcr */

$(document).ready(function () {
    mcr.init_database('#phrases', {
        searching: true,
        language: {
            url: '/language/ru_RU/js/database.json'
        }
    });
});


