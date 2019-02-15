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

    $('.database__list').on('click', '.view-table-data-diff-btn', function(){
        var params = {
            source_database: this.dataset.source_database,
            table_name: this.dataset.table_name
        };

        $.get(
            "database/compare-table-data",
            params,
            onTableDataCompared.bind(this, params)
        );
    });

    function onTableDataCompared(params, html) {
        // debugger;
        var $modal = $('#database__modal');

        $modal.find('.modal-body').html(html);
        $modal.find('.modal-header h2').text('Изменения данных в таблице ' + params.table_name);
        $modal.modal('show');
    }

    $(document).on('click', '.table__contols .start-process', function () {
        var records = getSelectedRecords('left-table');

        Array.prototype.push.apply(records, getSelectedRecords('right-table'));

        debugger;

        //records getSelectedRecords('right-table');

        var $modal = $('#table-confirm__modal');

        //$modal.find('.modal-body').html();
        $modal.modal('show');

    });

    function getSelectedRecords(id) {
        var records = [];

        $('#'+id).find("input[name='selection[]']:checked").each(function () {
            var operation = null;

            if($(this).parents('tr').hasClass('record-modified')){
                operation = 'update';
            }
            else if($(this).parents('tr').hasClass('record-dropped')){
                operation = 'drop';
            }
            else if($(this).parents('tr').hasClass('record-insert')){
                operation = 'insert';
            }

            if(operation){
                records.push({
                    operation: operation,
                    num: this.value
                });
            }
        });

        return records;
    }
});
