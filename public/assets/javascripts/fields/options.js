var Kora = Kora || {};
Kora.Fields = Kora.Fields || {};

Kora.Fields.Options = function(fieldType) {

    function initializeSelects() {
        //Most field option pages need these
        $('.single-select').chosen({
            width: '100%',
        });

        $('.multi-select').chosen({
            width: '100%',
        });
    }

    function initializeSelectAddition() {
        $('.chosen-search-input').on('keyup', function(e) {
            var container = $(this).parents('.chosen-container').first();

            if (e.which === 13 && container.find('li.no-results').length > 0) {
                var option = $("<option>").val(this.value).text(this.value);

                var select = container.siblings('.modify-select').first();

                select.prepend(option);
                select.find(option).prop('selected', true);
                select.trigger("chosen:updated");
            }
        });
    }

    //Fields that have specific functionality will have their own initialization process

    function initializeDateOptions() {
        $('.start-year-js').change(function() { printYears(); });

        $('.end-year-js').change(function() { printYears(); });

        function printYears(){
            start = $('.start-year-js').val(); end = $('.end-year-js').val();

            if(start=='')  { start = 0; }
            if(end =='') { end = 9999; }

            val = '<option></option>';
            for(var i=start;i<+end+1;i++) {
                val += "<option value=" + i + ">" + i + "</option>";
            }

            $('.default-year-js').html(val); $('.default-year-js').trigger("chosen:updated");
        }
    }

    function initializeGeneratedListOptions() {
        var listOpt = $('.genlist-options-js');
        var listDef = $('.genlist-default-js');

        listOpt.find('option').prop('selected', true);
        listOpt.trigger("chosen:updated");

        listOpt.chosen().change(function() {
            //TODO::figure out this
        });

        listOpt.bind("DOMSubtreeModified",function(){
            var options = listOpt.html();
            listDef.html(options);
            listDef.trigger("chosen:updated");
        });
    }

    function initializeListOptions() {
        var listOpt = $('.list-options-js');
        var listDef = $('.list-default-js');

        listOpt.find('option').prop('selected', true);
        listOpt.trigger("chosen:updated");

        listOpt.chosen().change(function() {
            //TODO::figure out this
        });

        listOpt.bind("DOMSubtreeModified",function(){
            var options = listOpt.html();
            listDef.html(options);
            listDef.trigger("chosen:updated");
        });
    }

    function initializeMultiSelectListOptions() {
        var listOpt = $('.mslist-options-js');
        var listDef = $('.mslist-default-js');

        listOpt.find('option').prop('selected', true);
        listOpt.trigger("chosen:updated");

        listOpt.chosen().change(function() {
            //figure out this
        });

        listOpt.bind("DOMSubtreeModified",function(){
            var options = listOpt.html();
            listDef.html(options);
            listDef.trigger("chosen:updated");
        });
    }

    function initializeScheduleOptions() {
        $('.add-new-event-js').on('click', function(e) {
            e.preventDefault();

            var nameInput = $('.event-name-js');
            var sTimeInput = $('.event-start-time-js');
            var eTimeInput = $('.event-end-time-js');

            var name = nameInput.val().trim();
            var sTime = sTimeInput.val().trim();
            var eTime = eTimeInput.val().trim();

            if(name==''|sTime==''|eTime=='') {
                //TODO::show error
            } else {
                if($('.event-allday-js').is(":checked")) {
                    sTime = sTime.split(" ")[0];
                    eTime = eTime.split(" ")[0];
                }

                if(sTime>eTime) {
                    //TODO::show error
                }else {
                    val = name + ': ' + sTime + ' - ' + eTime;

                    if(val != '') {
                        //Value is good so let's add it
                        var option = $("<option>").val(val).text(val);
                        var select = $('.default-event-js');

                        select.prepend(option);
                        select.find(option).prop('selected', true);
                        select.trigger("chosen:updated");

                        nameInput.val('');
                    }
                }
            }
        });
    }

    initializeSelects();

    switch(fieldType) {
        case 'Date':
            initializeDateOptions();
            break;
        case 'Generated List':
            initializeSelectAddition();
            initializeGeneratedListOptions();
            break;
        case 'List':
            initializeSelectAddition();
            initializeListOptions();
            break;
        case 'Multi-Select List':
            initializeSelectAddition();
            initializeMultiSelectListOptions();
            break;
        case 'Schedule':
            initializeScheduleOptions();
            break;
        default:
            break;
    }
}