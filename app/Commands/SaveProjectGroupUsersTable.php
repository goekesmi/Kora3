<?php namespace App\Commands;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;

class SaveProjectGroupUsersTable extends Command implements ShouldQueue {

    /*
    |--------------------------------------------------------------------------
    | Save Project Group Users Table
    |--------------------------------------------------------------------------
    |
    | This command handles the backup of the project group users table
    |
    */

    use InteractsWithQueue, SerializesModels;

    /**
     * Execute the command.
     */
    public function handle() {
        Log::info("Started backing up the Project Group Users table.");

        $table_path = $this->backup_filepath . "/project_group_user/";
        $table_array = $this->makeBackupTableArray("project_group_user");
        if($table_array == false) { return;}

        $row_id = DB::table('backup_partial_progress')->insertGetId(
            $table_array
        );

        $this->backup_fs->makeDirectory($table_path);
        DB::table('project_group_user')->orderBy('project_group_id')->chunk(500, function($pgUsers) use ($table_path, $row_id) {
            $count = 0;
            $all_projectgroupuser_data = new Collection();

            foreach($pgUsers as $projectgroupuser) {
                $individual_projectgroupuser_data = new Collection();

                $individual_projectgroupuser_data->put("project_group_id", $projectgroupuser->project_group_id);
                $individual_projectgroupuser_data->put("user_id", $projectgroupuser->user_id);

                $all_projectgroupuser_data->push($individual_projectgroupuser_data);
                $count++;
            }

            DB::table('backup_partial_progress')->where('id',$row_id)->increment('progress',$count,['updated_at'=>Carbon::now()]);
            $increment = DB::table('backup_partial_progress')->where('id',$row_id)->pluck('progress');
            $this->backup_fs->put($table_path . $increment . ".json",json_encode($all_projectgroupuser_data));
        });
        DB::table("backup_overall_progress")->where("id", $this->backup_id)->increment("progress",1,["updated_at"=>Carbon::now()]);
    }
}