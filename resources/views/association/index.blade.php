@extends('app', ['page_title' => "{$form->name} Association Permissions", 'page_class' => 'form-association-permissions'])

@section('leftNavLinks')
    @include('partials.menu.project', ['pid' => $form->pid])
    @include('partials.menu.form', ['pid' => $form->pid, 'fid' => $form->fid])
    @include('partials.menu.static', ['name' => 'Form Associations'])
@stop

@section('header')
    <section class="head">
        <div class="inner-wrap center">
            <h1 class="title">
                <i class="icon icon-form-associations"></i>
                <span>Form Association Permissions</span>
            </h1>
            <p class="description">This page allows you to grant association access for other forms. Associating other forms will allow them to search within this form. Select "Create a New Form Association" below, to begin creating a new form association. The newly associated form will then appear in the list below. You may also request association permission for this form to associate with other forms.</p>
            <div class="content-sections">
                <a href="#create" class="section underline-middle underline-middle-hover toggle-by-name active">Create Form Association</a>
                <a href="#request" class="section underline-middle underline-middle-hover toggle-by-name">Request Form Association</a>
            </div>
        </div>
    </section>
@stop

@section('body')
    @include("partials.formAssociations.newPermissionModal")
    @include("partials.formAssociations.requestPermissionModal")
    @include("partials.formAssociations.deletePermissionModal")

    <section class="create-section">
        <section class="new-object-button center">
            @if(\Auth::user()->isProjectAdmin($project))
                <form action="#">
                    <input class="new-permission-js" type="submit" value="Create a New Form Association">
                </form>
            @endif
        </section>

        <section class="permission-association-selection center permission-association-js create">
            <p class="description create-description-js {{count($assocs) === 0 ? 'hidden' : ''}}">The following forms are allowed to associate with and can search within this form:</p>
            @foreach ($assocs as $index=>$a)
                <?php $f = \App\Form::where('fid', '=', $a->assocForm)->first() ?>
                <div class="association association-js card {{ $index == 0 ? 'active' : '' }}" id="create-{{$f->fid}}">
                    <div class="header {{ $index == 0 ? 'active' : '' }}">
                        <div class="left pl-m">
                            <a class="title association-toggle-by-name-js" href="#">
                                <span class="name name-js">{{ $f->name }}</span>
                            </a>
                        </div>

                        <div class="card-toggle-wrap">
                            <a href="#" class="card-toggle association-toggle-js">
                                <span class="chevron-text">{{ $f->project()->get()->first()->name }}</span>
                                <i class="icon icon-chevron {{ $index == 0 ? 'active' : '' }}"></i>
                            </a>
                        </div>
                    </div>
                    <div class="content content-js {{ $index == 0 ? 'active' : '' }}">
                        <div class="description">
                            <p>{{ $f->description }}</p>
                        </div>
                        <div class="footer">
                            <a class="quick-action trash-container delete-permission-association-js left" href="#" data-form="{{$a->assocForm}}" data-reverse="false">
                                <i class="icon icon-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>
        
    </section>
    <section class="request-section hidden">
        <p class="description center">You may also request association permissions for this form to associate with other forms. Select "Request Form Association" to begin. Once requested, a notification will be sent to the admins of the selected form to allow association from your form.</p>
        <section class="new-object-button center">
            @if(\Auth::user()->isProjectAdmin($project))
                <form action="#">
                    <input class="request-permission-js" type="submit" value="Request Form Association">
                </form>
            @endif
        </section>
        <section class="permission-association-selection center permission-association-js request">
            <p class="description request-description-js {{count($available_associations) === 0 ? 'hidden' : ''}}">{{$form->name}} is allowed to associate with and can search within the following forms:</p>
            @foreach ($available_associations as $index=>$a)
                <?php $f = \App\Form::where('fid', '=', $a->dataForm)->first() ?>
                <div class="association association-js card {{ $index == 0 ? 'active' : '' }}" id="request-{{$f->fid}}">
                    <div class="header {{ $index == 0 ? 'active' : '' }}">
                        <div class="left pl-m">
                            <a class="title association-toggle-by-name-js" href="#">
                                <span class="name name-js">{{ str_replace($f->project()->get()->first()->name." ", "", $f->name) }}</span>
                            </a>
                        </div>

                        <div class="card-toggle-wrap">
                            <a href="#" class="card-toggle association-toggle-js">
                                <span class="chevron-text">{{ $f->project()->get()->first()->name }}</span>
                                <i class="icon icon-chevron {{ $index == 0 ? 'active' : '' }}"></i>
                            </a>
                        </div>
                    </div>
                    <div class="content content-js {{ $index == 0 ? 'active' : '' }}">
                        <div class="description">
                            <p>{{ $f->description }}</p>
                        </div>
                        <div class="footer">
                            <a class="quick-action trash-container delete-permission-association-js left" href="#" data-form="{{$a->dataForm}}" data-reverse='true'>
                                <i class="icon icon-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>
    </section>
@stop

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3>{{trans('association_index.manageassoc')}}</h3>
                    </div>
                    <div class="panel-body">
                        <div id="form_select">{{trans('association_index.forms')}}:
                            <select id="selected_assoc">
                                @foreach(\App\Form::all() as $f)
                                
                                @endforeach
                            </select>
                            <button id="add_assoc" class="btn btn-primary">{{trans('association_index.addallowed')}}</button>
                        </div>
                        <br>
                        <div id="form_allowed">
                            <b>{{trans('association_index.allowedassoc')}}</b>
                            <hr>
                            @foreach($assocs as $a)
                                <?php
                                    $f = \App\Form::where('fid','=',$a->assocForm)->first();
                                ?>
                                <p id="form_assoc_listitem">{{$f->name}} <a class="delete_assoc" fid="{{$f->fid}}" href="javascript:void(0)">[X]</a></p>
                            @endforeach
                        </div>
                        <div>
                            <br>
                            <b>{{trans('association_index.formsassoc')}}</b>
                            <hr>
                            @foreach(\App\Http\Controllers\AssociationController::getAvailableAssociations($form->fid) as $a)
                                <?php
                                $f = \App\Form::where('fid','=',$a->dataForm)->first();
                                $p = \App\Http\Controllers\ProjectController::getProject($f->pid);
                                ?>
                                <p id="assoc_to_listitem">{{$p->name}} | {{$f->name}}</p>
                            @endforeach
                        </div>
                        {!! Form::open(['action'=>['AssociationController@requestAccess',$form->pid,$form->fid]]) !!}
                        <div id="request_select">{{trans('association_index.requestfull')}}:
                            <select id="req_avail_assoc" name="rfid">
                                @foreach(\App\Http\Controllers\AssociationController::getRequestableAssociations($form->fid) as $f)
                                    <?php
                                    $p = \App\Http\Controllers\ProjectController::getProject($f->pid);
                                    ?>
                                    <option value="{{$f->fid}}">{{$p->name}} | {{$f->name}}</option>
                                @endforeach
                            </select>
                            <button id="request_assoc" class="btn btn-primary">{{trans('association_index.request')}}</button>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascripts')
    @include('partials.formAssociations.javascripts')

    <script type="text/javascript">
        var CSRFToken = '{{ csrf_token() }}';
        var pid = '{{ $project->pid }}';
        var createAssociationPath = '{{ action('AssociationController@create', ["pid" => $project->pid, "fid" => $form->fid]) }}';
        var requestAssociationPath = '{{ action('AssociationController@requestAccess', ["pid" => $project->pid, "fid" => $form->fid]) }}';
        var destroyAssociationPath = '{{ action('AssociationController@destroy', ["pid" => $project->pid, "fid" => $form->fid]) }}';
        var destroyReverseAssociationPath = '{{ action('AssociationController@destroyReverse', ["pid" => $project->pid, "fid" => $form->fid]) }}';
        Kora.FormAssociations.Index();
    </script>
@stop