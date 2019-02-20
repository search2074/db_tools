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

    function getSelectedRecords(id) {
        var records = [];

        $('#'+id).find("input[name='selection[]']:checked").each(function () {
            if($(this).parents('.record-modified').length && $(this).parents('#right-table').length){
                return true;
            }

            var operation = null,
                $tr = $(this).parents('tr'),
                pk = $tr.data('pk');

            if($tr.hasClass('record-modified')){
                operation = 'update';
            }
            else if($tr.hasClass('record-dropped')){
                operation = 'drop';
            }
            else if($tr.hasClass('record-insert')){
                operation = 'insert';
            }

            if(operation){
                records.push({
                    operation: operation,
                    pk: pk,
                    num: this.value
                });
            }
        });

        return records;
    }

    function tableDataCompare() {
        var params = {
            source_database: sourceDatabase,
            table_name: tableName
        };

        $.get(
            "database/compare-table-data",
            params,
            onTableDataCompared
        )
            .fail(function(){alert("fatal error");});
    }

    function onTableDataCompared(html) {
        $databaseModal.find('.modal-body').html(html);
    }

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

    function onTableDataProcessed(data) {
        if(data.success){
            alert('Успех');
            $.pjax.reload({container:'#left-database-pjax-id', async: false});
            $.pjax.reload({container:'#right-database-pjax-id', async: false});
            $tableConfirmModal.modal('hide');
            tableDataCompare();
        }
        else {
            alert('Error: ' + data.error.message);
        }
    }

    var $databaseModal = $('#database__modal');
    var $tableConfirmModal = $('#table-confirm__modal');
    var tableName = "";
    var sourceDatabase = "";
    var rowRecords = [];

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

    $('.database__list').on('click', '.view-table-data-diff-btn', function(){
        tableName = this.dataset.table_name;
        sourceDatabase = this.dataset.source_database;

        $databaseModal.find('.modal-header h2').text('Изменения данных в таблице ' + tableName);
        $databaseModal.find('.modal-body').html("");
        $databaseModal.modal('show');

        tableDataCompare();
    });

    $(document).on('click', '.table__contols .start-process', function () {
        rowRecords = getSelectedRecords('left-table');
        Array.prototype.push.apply(rowRecords, getSelectedRecords('right-table'));

        if(!rowRecords.length) {
            return false;
        }

        var text = "";

        $.each(rowRecords, function (i, record) {
            switch(record.operation){
                case 'update':
                    text += "обновить запись " + record.pk + ": " + record.num + "\n";
                    break;
                case 'insert':
                    text += "вставить запись " + record.pk + ": " + record.num + "\n";
                    break;
                case 'drop':
                    text += "удалить запись " + record.pk + ": " + record.num + "\n";
                    break;
            }
        });

        if(text.length){
            $tableConfirmModal.find('.modal-body .table-changes__textbox').text(text);
            $tableConfirmModal.modal('show');
        }
    });

    $(document).on('click', '.start-process__row-data', function(){
        var params = {
            records: rowRecords,
            table_name: tableName
        };

        $.post(
            "database/process-table-data",
            params,
            onTableDataProcessed
        );
    });
});
