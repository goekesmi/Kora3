<div class="form-group">
    {!! Form::label($field->flid, $field->name.': ') !!}
    @if($field->required==1)
        <b style="color:red;font-size:20px">*</b>
    @endif
    <div class="list_option_form{{$field->flid}}">
        <div>
            {!! Form::select($field->flid.'[]',\App\Http\Controllers\FieldController::getDateList($field),
                explode('[!]',$field->default),['class' => 'form-control list-options'.$field->flid, 'Multiple', 'id' => 'list'.$field->flid]) !!}
            <button type="button" class="btn btn-primary remove_option{{$field->flid}}">{{trans('records_fieldInput.delete')}}</button>
            <button type="button" class="btn btn-primary move_option_up{{$field->flid}}">{{trans('records_fieldInput.up')}}</button>
            <button type="button" class="btn btn-primary move_option_down{{$field->flid}}">{{trans('records_fieldInput.down')}}</button>
        </div>
        <div class="form-inline" style="position:relative">
            {!! Form::label('eventname'.$field->flid,trans('records_fieldInput.title').': ') !!}
            <input type="text" class="form-control" id="eventname{{$field->flid}}" />
            {!! Form::label('startdatetime'.$field->flid,trans('records_fieldInput.start').': ') !!}
            <input type='text' class="form-control" id='startdatetime{{$field->flid}}' />
            {!! Form::label('enddatetime'.$field->flid,trans('records_fieldInput.end').': ') !!}
            <input type='text' class="form-control" id='enddatetime{{$field->flid}}' />
            {!! Form::label('allday'.$field->flid,trans('records_fieldInput.allday').': ') !!}
            <input type='checkbox' class="form-control" id='allday{{$field->flid}}' />
            <button type="button" class="btn btn-primary add_option{{$field->flid}}">{{trans('records_fieldInput.add')}}</button>
        </div>
    </div>
</div>

<script>
    $('#startdatetime{{$field->flid}}').datetimepicker({
        minDate:'{{ \App\Http\Controllers\FieldController::getFieldOption($field, 'Start') }}',
        maxDate:'{{ \App\Http\Controllers\FieldController::getFieldOption($field, 'End') }}'
    });
    $('#enddatetime{{$field->flid}}').datetimepicker({
        minDate:'{{ \App\Http\Controllers\FieldController::getFieldOption($field, 'Start') }}',
        maxDate:'{{ \App\Http\Controllers\FieldController::getFieldOption($field, 'End') }}'
    });

    $('.list_option_form{{$field->flid}}').on('click', '.remove_option{{$field->flid}}', function(){
        $('option:selected', '#list{{$field->flid}}').remove();
    });
    $('.list_option_form{{$field->flid}}').on('click', '.move_option_up{{$field->flid}}', function(){
        $('#list{{$field->flid}}').find('option:selected').each(function() {
            $(this).insertBefore($(this).prev());
        });
    });
    $('.list_option_form{{$field->flid}}').on('click', '.move_option_down{{$field->flid}}', function(){
        $('#list{{$field->flid}}').find('option:selected').each(function() {
            $(this).insertAfter($(this).next());
        });
    });
    $('.list_option_form{{$field->flid}}').on('click', '.add_option{{$field->flid}}', function() {
        name = $('#eventname{{$field->flid}}').val().trim();
        sTime = $('#startdatetime{{$field->flid}}').val().trim();
        eTime = $('#enddatetime{{$field->flid}}').val().trim();

        $('#eventname{{$field->flid}}').css({ "border": ''});
        $('#startdatetime{{$field->flid}}').css({ "border": ''});
        $('#enddatetime{{$field->flid}}').css({ "border": ''});

        if(name==''|sTime==''|eTime==''){
            //show error
            if(name=='')
                $('#eventname{{$field->flid}}').css({ "border": '#FF0000 1px solid'});
            if(sTime=='')
                $('#startdatetime{{$field->flid}}').css({ "border": '#FF0000 1px solid'});
            if(eTime=='')
                $('#enddatetime{{$field->flid}}').css({ "border": '#FF0000 1px solid'});
        }else {
            if ($('#allday{{$field->flid}}').is(":checked")) {
                sTime = sTime.split(" ")[0];
                eTime = eTime.split(" ")[0];
            }

            if(sTime>eTime){
                $('#startdatetime{{$field->flid}}').css({ "border": '#FF0000 1px solid'});
                $('#enddatetime{{$field->flid}}').css({ "border": '#FF0000 1px solid'});
            }else {

                val = name + ': ' + sTime + ' - ' + eTime;

                if (val != '') {
                    $('#list{{$field->flid}}').append($("<option/>", {
                        value: val,
                        text: val,
                        selected: 'selected'
                    }));
                    $('#eventname{{$field->flid}}').val('');
                    $('#startdatetime{{$field->flid}}').val('');
                    $('#enddatetime{{$field->flid}}').val('');
                }
            }
        }
    });
</script>