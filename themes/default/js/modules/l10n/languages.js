/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/* global mcr */

$(document).ready(function () {
    mcr.init_database('#languages', {
        searching: true,
        language: {
            url: '/language/ru-RU/js/database.json'
        }
    });
    
    $('[data-role="selects"]').on('change', '[type="radio"]', function(event) {
        var element = event.target.id;
        
        switch(element) {
            case'other_dfs': $('#other_date_format').removeAttr('readonly'); break;
            case'other_tfs': $('#other_time_format').removeAttr('readonly'); break;
            default: $('[data-id="other"]').attr('readonly','readonly'); break;
        }
    });
    
    $('[data-format-id="other"]').on('input', '[type="text"]', function(event) {
        var element = event.target.id,
            element_value = $('#'+element).val();
        
        switch(element) {
            case'other_date_format': $('#other_dfs').val(element_value); break;
            case'other_time_format': $('#other_tfs').val(element_value); break;
            default: break;
        }
    });
});


