@extends('app', ['page_title' => 'Record Presets', 'page_class' => 'record-preset'])

@section('leftNavLinks')
    @include('partials.menu.project', ['pid' => $form->pid])
    @include('partials.menu.form', ['pid' => $form->pid, 'fid' => $form->fid])
    @include('partials.menu.static', ['name' => 'Record Presets'])
@stop

@section('stylesheets')

@stop

@section('header')
    <section class="head">
        <a class="rotate" href="{{ URL::previous() }}"><i class="icon icon-chevron"></i></a>
        <div class="inner-wrap center">
            <h1 class="title">
                <i class="icon icon-preset"></i>
                <span>Record Presets</span>
            </h1>
            <p class="description">Use this page to view and manage record presets within this form. Record presets
                allow you to … To create a new record preset, visit the single record you wish to turn into a preset.
                On the records main page, you’ll find the option to turn the record into a preset. </p>
        </div>
    </section>
@stop

@section('body')
    @include('partials.recordPresets.modals.changeRecordPresetNameModal')
    @include('partials.recordPresets.modals.deleteRecordPresetModal')

    <section class="manage-presets center">
        @foreach($presets as $index => $preset)
            @include('partials.recordPresets.card')
        @endforeach
    </section>
@stop

@section('footer')
    @include('partials.recordPresets.javascripts')

    <script>
        changePresetNameUrl = '{{action('RecordPresetController@changePresetName')}}';
        deletePresetUrl = '{{action('RecordPresetController@deletePreset')}}';
        csrfToken = '{{csrf_token()}}';

        Kora.RecordPresets.Index();
    </script>
@stop