<?php namespace App\Commands;

use Carbon\Carbon;
use App\ComboListField;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;

class SaveComboListFieldsTable extends Command implements ShouldQueue {

    /*
    |--------------------------------------------------------------------------
    | Save Combo List Fields Table
    |--------------------------------------------------------------------------
    |
    | This command handles the backup of the combo list fields table
    |
    */

    use InteractsWithQueue, SerializesModels;

    /**
     * Execute the command.
     */
    public function handle() {
        Log::info("Started backing up the Combo List Fields table.");

        $table_path = $this->backup_filepath . "/combo_list_fields/";
        $table_array = $this->makeBackupTableArray("combo_list_fields");
        if($table_array == false) { return;}

        $row_id = DB::table('backup_partial_progress')->insertGetId(
            $table_array
        );

        $this->backup_fs->makeDirectory($table_path);
        ComboListField::chunk(500, function($combolistfields) use ($table_path, $row_id) {
            $count = 0;
            $all_combolistfield_data = new Collection();

            foreach($combolistfields as $combolistfield) {
                $individual_combolistfield_data = new Collection();

                $individual_combolistfield_data->put('id',$combolistfield->id);
                $individual_combolistfield_data->put('rid',$combolistfield->rid);
                $individual_combolistfield_data->put('fid',$combolistfield->fid);
                $individual_combolistfield_data->put('flid',$combolistfield->flid);
                $individual_combolistfield_data->put("created_at", $combolistfield->created_at->toDateTimeString());
                $individual_combolistfield_data->put("updated_at", $combolistfield->updated_at->toDateTimeString());

                $all_combolistfield_data->push($individual_combolistfield_data);
                $count++;
            }

            DB::table("backup_partial_progress")->where("id", $row_id)->increment("progress", $count, ["updated_at" => Carbon::now()] );
            $increment = DB::table("backup_partial_progress")->where("id", $row_id)->pluck("progress");
            $this->backup_fs->put($table_path . $increment . ".json", json_encode($all_combolistfield_data));
        });
        DB::table("backup_overall_progress")->where("id", $this->backup_id)->increment("progress",1,["updated_at"=>Carbon::now()]);
    }
}