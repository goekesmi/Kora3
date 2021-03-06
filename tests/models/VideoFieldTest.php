<?php

use App\VideoField;
use App\Field;
use App\Revision;
use App\Http\Controllers\RevisionController;

/**
 * Class VideoFieldTest
 * @group field
 */
class VideoFieldTest extends TestCase
{
    const FILEINFO = <<<TEXT
[Name]blueimage.jpg[Name][Size]3478[Size][Type]text/csv[Type][Name]whalenia%40msu.edu.csv[Name][Size]3478[Size][Type]text/csv[Type][!][Name]Proj_Layout_2016-02-18 18-08-58.xml[Name][Size]87[Size][Type]application/xml[Type][!][Name]postmessageRelay.html[Name][Size]4087[Size][Type]text/html[Type]
TEXT;

    const OTHER_FILEINFO = <<<TEXT
[Name]redimage.jpg[Name][Size]3478[Size][Type]text/csv[Type][Name]whalenia%40msu.edu.csv[Name][Size]3478[Size][Type]text/csv[Type][!][Name]some_fine_xml.xml[Name][Size]87[Size][Type]application/xml[Type][!][Name]premessageRelay.html[Name][Size]4087[Size][Type]text/html[Type]
TEXT;

    public function test_getAdvancedSearchQuery() {
        $project = self::dummyProject();
        $form = self::dummyForm($project->pid);
        $field = self::dummyField(Field::_VIDEO, $project->pid, $form->fid);
        $record = self::dummyRecord($project->pid, $form->fid);

        $play_field = new VideoField();
        $play_field->rid = $record->rid;
        $play_field->flid = $field->flid;
        $play_field->video = self::FILEINFO;
        $play_field->save();

        // Valid.
        $dummy_query = [$field->flid . "_input" => "postmessageRelay.html"];

        $query = VideoField::getAdvancedSearchQuery($field->flid, $dummy_query);

        $rid = $query->first()->rid;
        $this->assertEquals($rid, $record->rid);

        // Invalid.
        $dummy_query = [$field->flid . "_input" => "redimage.jpg"];

        $query = VideoField::getAdvancedSearchQuery($field->flid, $dummy_query);

        $this->assertEmpty($query->get());

        // Invalid.
        $dummy_query = [$field->flid . "_input" => "premessageRelay"];

        $query = VideoField::getAdvancedSearchQuery($field->flid, $dummy_query);

        $this->assertEmpty($query->get());

        // Valid.
        $dummy_query = [$field->flid . "_input" => ".html"];

        $query = VideoField::getAdvancedSearchQuery($field->flid, $dummy_query);

        $rid = $query->first()->rid;
        $this->assertEquals($record->rid, $rid);
    }

    public function test_rollback() {
        $project = self::dummyProject();
        $form = self::dummyForm($project->pid);
        $field = self::dummyField(Field::_VIDEO, $project->pid, $form->fid);
        $record = self::dummyRecord($project->pid, $form->fid);

        $video_field = new VideoField();
        $video_field->rid = $record->rid;
        $video_field->flid = $field->flid;
        $video_field->video = self::FILEINFO;
        $video_field->save();

        $revision = RevisionController::storeRevision($record->rid, Revision::CREATE);

        $video_field->video = self::OTHER_FILEINFO;
        $video_field->save();

        $video_field = VideoField::rollback($revision, $field);
        $this->assertEquals(self::FILEINFO, $video_field->video);
    }
}