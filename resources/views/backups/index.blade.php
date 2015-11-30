@extends('app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">

                @if (count($errors) > 0)
                    <div class="alert alert-danger">
                        <strong>Whoops!</strong> Make sure you entered everything correctly<br>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action={{action("BackupController@startBackup")}}>
                    <input type="hidden" name="_token" value="{{csrf_token()}}">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Backup
                        </div>
                        <div class="panel-body">
                            <p>
                                A backup file will be created and saved as a restore point on the server.  You can
                                download this file afterwards and save it somewhere safe.  You can include a name or
                                short description, the start date and time will be added for you. Depending
                                on the size of your database, this may take a few minutes to finish.
                            </p>

                            <div class="form-group">
                                <label for="backup_label">Label:</label>
                                <input type="text" class="form-control" id="backup_label" name="backup_label">
                            </div>
                            <div class="form-group">
                                <input type="submit" class="btn btn-primary form-control" id="btn_startbackup" value="Start Backup">
                            </div>
                        </div>
                    </div>
                </form>

                <form method="post" enctype="multipart/form-data" action={{action("BackupController@startRestore")}}>
                    <input type="hidden" name="_token" value="{{csrf_token()}}">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Restore
                        </div>

                        <div class="panel-body">
                            <div id="restore_warning" style="display: none;" class="alert alert-warning" role="alert"><strong></strong>You can't run a restore right now.  Wait until the backup is finished then reload the page.</div>
                            <div id="group_source_select" class="form-group">
                                <label for="backup_source">Restore from:</label>
                                <select id="backup_source" name="backup_source" class="form-control">
                                    <option value="server">a restore point saved on the server</option>
                                    <option value="upload">a backup file saved on my computer</option>
                                </select>
                            </div>

                            <div class="form-group" id="group_restore_points">
                                <label for="restore_point">Saved Restore Points:</label>
                                <select id="restore_point" name="restore_point" class="form-control">

                                    @foreach($saved_backups as $backup)
                                        <option value={{$backup->get("index")}}>{{$backup->get("date")}} | {{$backup->get("name")}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div style="display: none" class="form-group" id="group_upload_files">
                                <label for="upload_file">Upload a backup file:</label>
                                <input type="file" id="upload_file" name="upload_file">
                            </div>

                            <div id="group_restore_submit" class="form-group">
                                <input id="btn_startrestore" type="submit" class="btn btn-primary form-control" value="Start Restore">
                            </div>

                            <div id="group_restore_delete" class="form-group">
                                <button type="button"  id="btn_deleterestore" class="btn btn-primary btn-danger form-control">Delete Restore Point</button>
                            </div>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <script>

        function disableRestore(){
            $("#group_restore_submit").hide('slow');
            $("#group_restore_points").hide('slow');
            $("#group_upload_files").hide('slow');
            $("#group_source_select").hide('slow');
            $("#restore_warning").css('display','block');

        }

        function deleteRestore(){

            if(!confirm("Are you sure you want to delete this restore point and all of its files?  This cannot be reversed!")){
                return false;
            }
            if(!confirm("If you plan on restoring from a backup file of this restore point, you will need the backed up files for the Document/Gallery/Video/Playlist/Model fields from this restore point.  These files are located in storage/app/backup/files/backup_file_name/ ")){
                return false;
            }

            var deleteURL = "{{action('BackupController@delete')}}";
            var deleteIndex = $("#restore_point").val();
            $.ajax({
                url:deleteURL,
                method:'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "restore_point": deleteIndex,
                    "backup_source": "server",
                },
                success: function(data){
                 location.reload();
                },
                error: function(data){
                    if(data.status == 422){
                        alert("You did not select a valid restore point");
                    }
                   location.reload();
                }
            });



        }
        $("#backup_source").on('change',function() {
            if(this.value == 'server') {
                $("#group_upload_files").hide('slow');
                $("#upload_file").val('');
                $("#group_restore_points").show('slow');
                $("#group_restore_delete").show('slow');
            }
            else{
                $("#group_upload_files").show('slow');
                $("#group_restore_delete").hide('slow');
                $("#group_restore_points").hide('slow');
                $("#restore_point").val('');
            }
        });

        $("#btn_deleterestore").on('click',"",deleteRestore);

        //$("#btn_startbackup").on('click',"",disableRestore);
    </script>
@endsection

@stop

