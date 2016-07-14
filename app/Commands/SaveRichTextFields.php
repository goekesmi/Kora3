<?php namespace App\Commands;

use Carbon\Carbon;
use App\RichTextField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;

class SaveRichTextFields extends Command implements SelfHandling, ShouldBeQueued
{
    use InteractsWithQueue, SerializesModels;

    /**
     * Execute the command.
     */
    public function handle() {
        Log::info("Started backing up the Rich Text Fields table.");

        $table_path = $this->backup_filepath . "/rich_text_fields/";

        $row_id = DB::table('backup_partial_progress')->insertGetId(
            $this->makeBackupTableArray("rich_text_fields")
        );

        $this->backup_fs->makeDirectory($table_path);
        RichTextField::chunk(1000, function($rtfields) use ($table_path, $row_id) {
            $count = 0;
            $all_richtextfields_data = new Collection();

            foreach($rtfields as $richtextfield) {
                $individual_richtextfield_data = new Collection();

                $individual_richtextfield_data->put("id", $richtextfield->id);
                $individual_richtextfield_data->put("rid", $richtextfield->rid);
                $individual_richtextfield_data->put("flid", $richtextfield->flid);
                $individual_richtextfield_data->put("rawtext", $richtextfield->rawtext);
                $individual_richtextfield_data->put("created_at", $richtextfield->created_at->toDateTimeString());
                $individual_richtextfield_data->put("updated_at", $richtextfield->updated_at->toDateTimeString());

                $all_richtextfields_data->push($individual_richtextfield_data);
                $count++;
            }

            DB::table('backup_partial_progress')->where('id',$row_id)->increment('progress',$count,['updated_at'=>Carbon::now()]);
            $increment = DB::table('backup_partial_progress')->where('id',$row_id)->pluck('progress');
            $this->backup_fs->put($table_path . $increment . ".json",json_encode($all_richtextfields_data));
        });
    }
}