@extends('app', ['page_title' => "Backing Up", 'page_class' => 'backup-start'])

@section('leftNavLinks')
    @include('partials.menu.static', ['name' => 'Backing Up'])
@stop

@section('header')
    <section class="head">
        <div class="inner-wrap center">
            <h1 class="title">
                <i class="icon icon-backup rotate-icon stop-rotation-js"></i>
                <span class="success-title-js">Creating Backup File</span>
            </h1>
            <div class="backup-toolbar">
                <span class="bold">Backup Name:</span>
                <?php
                    $parts = explode('___',$backupLabel);
                    $carbon = new \Carbon\Carbon($parts[1]);
                    $n = $parts[0];
                    $d = $carbon->subDay()->format('m.d.Y');
                    $t = $carbon->format('g:i A');
                ?>
                <span>{{$n}}</span><span class="time">{{$d}}</span><span class="time">{{$t}}</span>
            </div>
            <p class="description success-desc-js">The backup has started, depending on the size of your database, it may take several
                minutes to complete. Do not leave this page or close your browser until completion. When the backup is
                complete, you can see a summary of all the data that was saved. </p>
        </div>
    </section>
@stop

@section('body')
    <section class="backup-progress">
        <div class="form-group">
            <div class="progress-bar-custom">
                <span class="progress-bar-filler progress-fill-js"></span>
            </div>

            <p class="progress-bar-text progress-text-js">Backing up the things… Beep beep beep </p>
        </div>
    </section>

    <section class="backup-finish hidden">
        <div class="form-group half">
            <input type="button" class="btn download-file-js" value="Download Backup File (16TBGB)">
        </div>
        <div class="large-size-warning">
            If file is too large to download, you can download it from this folder: {Kora3}/storage/app/backups/{{$backupLabel}}
        </div>
    </section>
@stop

@section('footer')
    @include('partials.backups.javascripts')

    <script type="text/javascript">
        var startBackupUrl = '{{action('BackupController@create')}}';
        var checkProgressUrl = '{{action('BackupController@checkProgress')}}';
        var finishBackupUrl = '{{action('BackupController@finishBackup')}}';
        var downloadFileUrl = '{{action("BackupController@download",['path'=>$backupLabel])}}';
        var unlockUsersUrl = '{{action('BackupController@unlockUsers')}}';

        var buLabel = '{{ $backupLabel }}';
        var buData = '{{ $metadata }}';
        var buFiles = '{{ $files }}';

        var autoDL = '{{ $autoDownload }}';

        var CSRFToken = '{{ csrf_token() }}';

        Kora.Backups.Progress();
    </script>
@endsection

