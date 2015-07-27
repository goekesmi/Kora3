@if($field->type == 'Text')
    @include('records.fieldInputs.text')
@elseif($field->type == 'Rich Text')
    @include('records.fieldInputs.richtext')
@elseif($field->type == 'Number')
    @include('records.fieldInputs.number')
@elseif($field->type == 'List')
    @include('records.fieldInputs.list')
@elseif($field->type == 'Multi-Select List')
    @include('records.fieldInputs.mslist')
@elseif($field->type == 'Generated List')
    @include('records.fieldInputs.genlist')
@endif