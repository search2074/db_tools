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

    $('.database__list').on('click', "input[name='selection[]']", function (e) {
        if($(this).parents('#right-database').length){
            alert('Действие запрещено');
            e.preventDefault().stopPropagation();
        }
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

    function onDatabaseProcessed(data) {
        if(data.success){
            $('.database__contols').removeClass('spinner').text('Finished');
            alert('Успех');
            $.pjax.reload({container:'#left-database'});
            $.pjax.reload({container:'#right-database'});
        }
        else {
            alert('Error: ' + data.error.message);
        }
    }
});
