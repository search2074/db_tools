'use strict';

$( document ).ready(function() {
    console.log('app started');

    function getSelectedTables(id){
        var values = [];
        $('#'+id).find("input[name='selection[]']:checked").each(function () {
            values.push($(this).parents('tr').find('.table-name').text());
        });

        return values;
    }

    $('.database__list').on('change', "input[name='selection[]']", function () {
        alert('change checkbox');
    });

    $('.database__contols').on('click', '.start-process', function(){
        $(this).addClass('spinner').text('loading...').attr("disabled", "disabled");

        var params = {
            left_tables: getSelectedTables('left-database'),
            right_tables: getSelectedTables('right-database')
        };

        $.post(
            "/database/process",
            params,
            onDatabaseProcessed
        );
    });

    function onDatabaseProcessed() {
        $('.database__contols').removeClass('spinner').text('Finished');
    }

});
