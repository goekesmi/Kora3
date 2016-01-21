@extends('app')

@section('leftNavLinks')
    @include('partials.menu.project', ['pid' => $form->pid])
    @include('partials.menu.form', ['pid' => $form->pid, 'fid' => $form->fid])
@stop

@section('content')
    <span><h1>{{ $form->name }}</h1></span>

    <div><b>{{trans('records_show.name')}}:</b> {{ $form->slug }}</div>
    <div><b>{{trans('records_show.desc')}}:</b> {{ $form->description }}</div>

    <div>
        <a href="{{ action('RecordController@index',['pid' => $form->pid, 'fid' => $form->fid]) }}">[{{trans('records_show.records')}}]</a>
        @if(\Auth::user()->canIngestRecords($form))
            <a href="{{ action('RecordController@create',['pid' => $form->pid, 'fid' => $form->fid]) }}">[{{trans('records_show.new')}}]</a>
        @endif
    </div>

    @if (\Auth::user()->admin || \Auth::user()->isFormAdmin($form))
        <hr/>

        <h4> {{trans('records_show.panel')}}</h4>
        <form action="{{action('FormGroupController@index', ['pid'=>$form->pid, 'fid'=>$form->fid])}}" style="display: inline">
            <button type="submit" class="btn btn-default">{{trans('records_show.groups')}}</button>
        </form>
        <form action="{{action('AssociationController@index', ['fid'=>$form->fid, 'pid'=>$form->pid])}}" style="display: inline">
            <button type="submit" class="btn btn-default">{{trans('records_show.assoc')}}</button>
        </form>
        <form action="{{action('RevisionController@index', ['pid'=>$form->pid, 'fid'=>$form->fid])}}" style="display: inline">
            <button type="submit" class="btn btn-default">{{trans('records_show.revisions')}}</button>
        </form>
        <form action="{{action('RecordPresetController@index', ['pid'=>$form->pid, 'fid'=>$form->fid])}}" style="display: inline">
            <button type="submit" class="btn btn-default">{{trans('records_show.presets')}}</button>
        </form>
    @endif

    <hr/>
    <h2>{{trans('records_show.record')}}: {{$record->kid}}</h2>
    @if (\Auth::user()->admin || \Auth::user()->isFormAdmin($form))
        <input type="text" id="preset" placeholder="Enter a Name">
        <button onclick="presetRecord({{$record->rid}})">{{trans('records_show.makepreset')}}</button>
    @endif

    <div class="panel panel-default">
        @include('forms.layout.logic',['form' => $form, 'fieldview' => 'records.layout.displayfield'])
        <div><b>{{trans('records_show.owner')}}:</b> {{ $owner->username }}</div>
        <div><b>{{trans('records_show.created')}}:</b> {{ $record->created_at }}</div>
        <div class="panel-footer">
            <span>
                @if(\Auth::user()->canModifyRecords($form) || \Auth::user()->isOwner($record))
                <a href="{{ action('RecordController@edit',['pid' => $form->pid, 'fid' => $form->fid, 'rid' => $record->rid]) }}">[{{trans('records_show.edit')}}]</a>
                @endif
            </span>
            <span>
                @if(\Auth::user()->canDestroyRecords($form) || \Auth::user()->isOwner($record))
                <a onclick="deleteRecord()" href="javascript:void(0)">[{{trans('records_show.delete')}}]</a>
                @endif
            </span>
            <span>
                @if(\Auth::user()->admin || \Auth::user()->isFormAdmin($form) || \Auth::user()->isOwner($record))
                <a href='{{action('RevisionController@show', ['pid' => $form->pid, 'fid' => $form->fid, 'rid' => $record->rid])}}'>[{{trans('records_show.history')}}]</a>
                @endif
            </span>
        </div>
    </div>
@stop

@section('footer')
    <script>
        function deleteRecord() {
            var response = confirm("{{trans('records_show.areyousure')}} {{$record->kid}}?");
            if (response) {
                $.ajax({
                    //We manually create the link in a cheap way because the JS isn't aware of the fid until runtime
                    //We pass in a blank project to the action array and then manually add the id
                    url: '{{ action('RecordController@destroy', ['pid' => $form->pid, 'fid' => $form->fid, 'rid' => $record->rid]) }}',
                    type: 'DELETE',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function (result) {
                        location.href = '{{ action('RecordController@index', ['pid' => $form->pid, 'fid' => $form->fid]) }}';
                    }
                });
            }
        }

        function presetRecord(rid) {
            var name = $('#preset').val();
            if(name == '')
                alert('{{trans('records_show.mustenter')}}.');
            else {
                $.ajax({
                    url: '{{ action('RecordController@presetRecord') }} ',
                    type: 'POST',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "name": name,
                        "rid": rid
                    },
                    success: function () {
                        location.reload();
                    }
                });
            }
        }
    </script>
@stop