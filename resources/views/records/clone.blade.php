@extends('app', ['page_title' => 'Duplicate Record', 'page_class' => 'record-create'])

@section('leftNavLinks')
    @include('partials.menu.project', ['pid' => $form->pid])
    @include('partials.menu.form', ['pid' => $form->pid, 'fid' => $form->fid])
    @include('partials.menu.record', ['pid' => $record->pid, 'fid' => $record->fid, 'rid' => $record->rid])
    @include('partials.menu.static', ['name' => 'Duplicate Record'])
@stop

@section('stylesheets')
    <link rel="stylesheet" href="{{ config('app.url') }}assets/css/vendor/datetimepicker/jquery.datetimepicker.min.css" />
@stop

@section('header')
    <section class="head">
        <a class="rotate" href="{{ URL::previous() }}"><i class="icon icon-chevron"></i></a>
        <div class="inner-wrap center">
            <h1 class="title">
                <i class="icon icon-duplicate"></i>
                <span>Duplicate Record</span>
            </h1>
            <p class="description">Adjust the number of duplicates you wish to make for record: {{$record->kid}}. Then adjust
                the record below as needed. Adjustments you make here will only be applied to the new duplicate record(s).</p>
            <div class="content-sections">
                @foreach(\App\Http\Controllers\PageController::getFormLayout($form->fid) as $page)
                    <a href="#{{$page["title"]}}" class="section underline-middle underline-middle-hover toggle-by-name">{{$page["title"]}}</a>
                @endforeach
            </div>
        </div>
    </section>
@stop

@section('body')
    @include("partials.fields.input-modals")

    <section class="filters center">
        <div class="required-tip">
            <span class="oval-icon"></span>
            <span> = Required Field</span>
        </div>
    </section>

    <section class="create-record center">
        {!! Form::model($cloneRecord = new \App\Record, ['url' => 'projects/'.$form->pid.'/forms/'.$form->fid.'/records',
            'enctype' => 'multipart/form-data', 'id' => 'new_record_form']) !!}
        @include('partials.records.form',['form' => $form, 'editRecord' => true])

        <div class="form-group mt-xxxl">
            <div class="spacer"></div>
        </div>

        <div class="form-group mt-xxxl duplicate-record-js">
            {!! Form::label('mass_creation_num', 'Select duplication amount (max 1000): ') !!}
            <input type="number" name="mass_creation_num" class="text-input" value="2" step="1" max="1000" min="1">
        </div>

        <div class="form-group mt-xxxl">
            <div class="check-box-half">
                <input type="checkbox" value="1" id="active" class="check-box-input newRecPre-check-js" />
                <span class="check"></span>
                <span class="placeholder">Create New Record Preset from this Record</span>
            </div>

            <p class="sub-text mt-sm">
                This will create a new record preset from this record.
            </p>
        </div>

        <div class="form-group mt-xl newRecPre-record-js hidden">
            {!! Form::label('record_preset_name', 'Record Preset Name: ') !!}
            <input type="text" name="record_preset_name" class="text-input" placeholder="Add Record Preset Name" disabled>
        </div>

        <div class="form-group mt-100-xl">
            {!! Form::submit('Duplicate Record',['class' => 'btn']) !!}
        </div>
        {!! Form::close() !!}
    </section>
@stop

@section('footer')

@stop

@section('javascripts')
    @include('partials.records.javascripts')

    <script src="{{ config('app.url') }}assets/javascripts/vendor/ckeditor/ckeditor.js"></script>

    <script type="text/javascript">
        getPresetDataUrl = "{{action('RecordPresetController@getData')}}";
        moveFilesUrl = '{{action('RecordPresetController@moveFilesToTemp')}}';
        geoConvertUrl = '{{ action('FieldAjaxController@geoConvert',['pid' => $form->pid, 'fid' => $form->fid, 'flid' => 0]) }}';
        csrfToken = "{{ csrf_token() }}";
        userID = "{{\Auth::user()->id}}";
        baseFileUrl = "{{config('app.url'). 'deleteTmpFile/'}}";

        Kora.Records.Create();
    </script>
@stop