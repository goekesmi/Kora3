<?php namespace App\Commands;

use Carbon\Carbon;
use App\RichTextField;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;

class SavePluginUsersTable extends Command implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * Execute the command.
     */
    public function handle() {
        Log::info("Started backing up the Plugin Users table.");

        $table_path = $this->backup_filepath . "/plugin_users/";
        $table_array = $this->makeBackupTableArray("plugin_users");
        if($table_array == false) { return;}

        $row_id = DB::table('backup_partial_progress')->insertGetId(
            $table_array
        );

        $this->backup_fs->makeDirectory($table_path);
        DB::table('plugin_users')->chunk(500, function($pluginUsers) use ($table_path, $row_id) {
            $count = 0;
            $all_pluginusers_data = new Collection();

            foreach($pluginUsers as $pluginuser) {
                $individual_pluginusers_data = new Collection();

                $individual_pluginusers_data->put("plugin_id", $pluginuser->plugin_id);
                $individual_pluginusers_data->put("gid", $pluginuser->gid);
                $individual_pluginusers_data->put("created_at", $pluginuser->created_at->toDateTimeString());
                $individual_pluginusers_data->put("updated_at", $pluginuser->updated_at->toDateTimeString());

                $all_pluginusers_data->push($individual_pluginusers_data);
                $count++;
            }

            DB::table('backup_partial_progress')->where('id',$row_id)->increment('progress',$count,['updated_at'=>Carbon::now()]);
            $increment = DB::table('backup_partial_progress')->where('id',$row_id)->pluck('progress');
            $this->backup_fs->put($table_path . $increment . ".json",json_encode($all_pluginusers_data));
        });
        DB::table("backup_overall_progress")->where("id", $this->backup_id)->increment("progress",1,["updated_at"=>Carbon::now()]);
    }
}