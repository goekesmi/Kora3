<?php namespace App\Commands;

use App\TextField;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SaveTextFieldsTable extends Command implements ShouldQueue {

    /*
    |--------------------------------------------------------------------------
    | Save Text Fields Table
    |--------------------------------------------------------------------------
    |
    | This command handles the backup of the text fields table
    |
    */

	use InteractsWithQueue, SerializesModels;

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function handle() {
		Log::info("Started backing up TextFields table");

		$table_path = $this->backup_filepath."/text_fields/";
        $table_array = $this->makeBackupTableArray("text_fields");
        if($table_array == false) { return;}

        $row_id = DB::table('backup_partial_progress')->insertGetId(
            $table_array
        );

        $this->backup_fs->makeDirectory($table_path);
        TextField::chunk(500, function ($textfields) use ($table_path, $row_id) {
            $count = 0;

            $all_textfields_data = new Collection();
            foreach($textfields as $textfield) {
                $individual_textfield_data = new Collection();

                $individual_textfield_data->put("id", $textfield->id);
                $individual_textfield_data->put("rid", $textfield->rid);
                $individual_textfield_data->put("fid", $textfield->fid);
                $individual_textfield_data->put("flid", $textfield->flid);
                $individual_textfield_data->put("text", $textfield->text);
                $individual_textfield_data->put("created_at", $textfield->created_at->toDateTimeString());
                $individual_textfield_data->put("updated_at", $textfield->updated_at->toDateTimeString());

                $all_textfields_data->push($individual_textfield_data);
                $count++;
            }
            DB::table('backup_partial_progress')->where('id', $row_id)->increment('progress', $count, ['updated_at' => Carbon::now()]);
            $increment = DB::table('backup_partial_progress')->where('id', $row_id)->pluck('progress');
            $this->backup_fs->put($table_path . $increment . ".json", json_encode($all_textfields_data));
        });
        DB::table("backup_overall_progress")->where("id", $this->backup_id)->increment("progress", 1, ["updated_at" => Carbon::now()]);

	}

}
