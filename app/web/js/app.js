'use strict';

$( document ).ready(function() {
    console.log('app started');

    function getSelectedTables(id){
        var values = [];
        $('#'+id).find("input[name='selection[]']:checked").each(function () {
            values.push($(this).parents('tr').find('.table-name__title').text());
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
            "database/process",
            params,
            onDatabaseProcessed
        );
    });

    function onDatabaseProcessed(data) {
        if(data.success){
            $.pjax.reload({container:'#left-database-pjax-id', async: false});
            $.pjax.reload({container:'#right-database-pjax-id', async: false});
            alert('Успех');
        }
        else {
            alert('Error: ' + data.error.message);
        }

        $('.database__contols .start-process')
            .removeClass('spinner')
            .text('Start process')
            .prop("disabled", false);
    }
});
