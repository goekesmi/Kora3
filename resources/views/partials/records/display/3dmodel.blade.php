@foreach(explode('[!]',$typedField->model) as $opt)
    @if($opt != '')
        <?php
        $name = explode('[Name]',$opt)[1];
        $parts = explode('.', $name);
        $type = array_pop($parts);
        if(in_array($type, array('stl','obj')))
            $model_link = action('FieldAjaxController@getFileDownload',['flid' => $field->flid, 'rid' => $record->rid, 'filename' => $name]);
        ?>
    @endif
@endforeach
<div class="model-player-div model-player-div-js" model-link="{{$model_link}}" model-id="{{$field->flid}}_{{$record->rid}}"
    model-color="{{\App\Http\Controllers\FieldController::getFieldOption($field,'ModelColor')}}"
    bg1-color="{{\App\Http\Controllers\FieldController::getFieldOption($field,'BackColorOne')}}"
    bg2-color="{{\App\Http\Controllers\FieldController::getFieldOption($field,'BackColorTwo')}}">
    <canvas id="cv{{$field->flid}}_{{$record->rid}}" class="model-player-canvas">
        It seems you are using an outdated browser that does not support canvas :-(
    </canvas><br>
{{--    <button id="cvfs{{$field->flid}}_{{$record->rid}}" type="button">FULLSCREEN</button>--}}
</div>