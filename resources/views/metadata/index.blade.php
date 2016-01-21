@extends('app')

@section('leftNavLinks')
    @include('partials.menu.project', ['pid' => $pid])
    @include('partials.menu.form', ['pid' => $pid, 'fid' => $fid])
@stop

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-body">

                        <span><h3>{{trans('metadata_index.lod')}}</h3></span>
                        <hr>

                        <div>
                            <b class="pull-left">{{trans('metadata_index.field')}}</b>
                            <b class="pull-right">{{trans('metadata_index.lod')}}</b>
                        </div>
                        <br>

                        @include('forms.layout.logic',['form'=>$form,'fieldview' => 'metadata.fieldview'])

                        <hr>
                        <div class="checkbox">
                            <label>
                            @if ($form->public_metadata == true)
                                <input id="public_metadata" name="public_metadata" type="checkbox" value="true" checked>
                            @else
                                <input id="public_metadata" name="public_metadata" type="checkbox" value="true">
                            @endif
                                {{trans('metadata_index.viewable')}}
                            </label>
                        </div>

                        <hr>

                        @if (count($errors) > 0)
                            <div class="alert alert-danger">
                                <strong>{{trans('metadata_index.whoops')}}!</strong>  {{trans('metadata_index.makesure')}}<br><br>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        {!! Form::open(array('method'=>'post','action'=>array('MetadataController@store',$pid,$fid))) !!}
                        {!! Form::token() !!}
                        {!! Form::hidden('type', 'addmetadata') !!}
                        <div class="form-group">
                            {!! Form::label('name',trans('metadata_index.name')) !!}
                            {!! Form::text('name','',array('class'=>'form-control')) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('field',trans('metadata_index.linkedfield')) !!}
                            {!! Form::select('field',$fields,'',array('class'=>'form-control')) !!}
                        </div>

                        {!! Form::submit(trans('metadata_index.assign'),array('class'=>'btn btn-primary form-control')) !!}

                        {!! Form::close() !!}
                        <hr>
                        <p><strong>{{trans('metadata_index.automass')}}</strong></p>
                        <button id="massAssign" class="btn btn-primary form-control">{{trans('metadata_index.massassign')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


@stop

@section('footer')
    <script>

        $("#public_metadata").change(function(){
            var state = null;
            if($("#public_metadata").prop('checked')) state = true;
            else state = false;
            makeMetaPublic(state);
        });
        $("#massAssign").on('click',function(){
            massAssignMeta();
        });

        function deleteMeta(flid){
           var deleteURL ="{{action('MetadataController@destroy',compact('pid','fid'))}}";
            $.ajax({
                url:deleteURL,
                method:'DELETE',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "flid": flid
                },
                success: function(data){
                    location.reload();
                }
            });
        }
        function massAssignMeta(){
            var deleteURL ="{{action('MetadataController@massAssign',compact('pid','fid'))}}";
            $.ajax({
                url:deleteURL,
                method:'POST',
                data: {
                    "_token": "{{ csrf_token() }}"
                },
                success: function(data){
                    location.reload();
                }
            });
        }


        function makeMetaPublic(state){
            var deleteURL ="{{action('MetadataController@store',compact('pid','fid'))}}";
            $.ajax({
                url:deleteURL,
                method:'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "type":"visibility",
                    "state": state
                },
                success: function(data){
                    //location.reload();
                    console.log("Linked Open Data was changed");
                },
                error: function(jqxhr, textStatus, errorThrown){
                    console.log("Error in changing linked to open data visibility");
                    //console.log("text status: " + textStatus);
                    //console.log("error thrown: "+errorThrown);
                    alert("{{trans('metadata_index.error')}}.");
                    location.reload()
                }
            });
        }
    </script>

@stop