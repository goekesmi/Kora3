<?php namespace App\Commands;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;

class SavePluginsTable extends Command implements SelfHandling, ShouldQueue {

    /*
    |--------------------------------------------------------------------------
    | Save Plugins Table
    |--------------------------------------------------------------------------
    |
    | This command handles the backup of the plugins table
    |
    */

    use InteractsWithQueue, SerializesModels;

    /**
     * Execute the command.
     */
    public function handle() {
        Log::info("Started backing up the Plugins table.");

        $table_path = $this->backup_filepath . "/plugins/";
        $table_array = $this->makeBackupTableArray("plugins");
        if($table_array == false) { return;}

        $row_id = DB::table('backup_partial_progress')->insertGetId(
            $table_array
        );

        $this->backup_fs->makeDirectory($table_path);
        DB::table('plugins')->chunk(500, function($plugins) use ($table_path, $row_id) {
            $count = 0;
            $all_plugins_data = new Collection();

            foreach($plugins as $plugin) {
                $individual_plugins_data = new Collection();

                $individual_plugins_data->put("id", $plugin->id);
                $individual_plugins_data->put("pid", $plugin->pid);
                $individual_plugins_data->put("name", $plugin->name);
                $individual_plugins_data->put("active", $plugin->active);
                $individual_plugins_data->put("url", $plugin->url);
                $individual_plugins_data->put("created_at", $plugin->created_at->toDateTimeString());
                $individual_plugins_data->put("updated_at", $plugin->updated_at->toDateTimeString());

                $all_plugins_data->push($individual_plugins_data);
                $count++;
            }

            DB::table('backup_partial_progress')->where('id',$row_id)->increment('progress',$count,['updated_at'=>Carbon::now()]);
            $increment = DB::table('backup_partial_progress')->where('id',$row_id)->pluck('progress');
            $this->backup_fs->put($table_path . $increment . ".json",json_encode($all_plugins_data));
        });
        DB::table("backup_overall_progress")->where("id", $this->backup_id)->increment("progress",1,["updated_at"=>Carbon::now()]);
    }
}