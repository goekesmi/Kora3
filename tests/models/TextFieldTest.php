<?php

use App\Field;
use App\Revision;
use App\TextField as TextField;
use App\Http\Controllers\RevisionController;

/**
 * Class TextFieldTest
 * @group field
 */
class TextFieldTest extends TestCase {

    /**
     * Simple text, classic lorem ipsum, no special characters.
     */
    const SIMPLE_TEXT = <<<TEXT
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam efficitur felis vel felis congue rhoncus. Aliquam mattis
iaculis metus, non tristique risus maximus a. Integer vel nibh ac nibh lobortis cursus vitae nec est. Suspendisse velit
sem, rutrum vestibulum pellentesque sit amet, tempor id tellus. Sed dictum porta nisi. Fusce vel sapien malesuada, viverra
sem et, consequat sapien. Cras ut gravida odio, vel fringilla leo. Integer interdum odio nibh, ut pharetra lectus accumsan
id. Morbi et quam ex. Proin posuere tellus sit amet ligula mattis, in vestibulum libero volutpat. Integer nec sapien lectus.
Nam sed velit metus. Praesent eu lacus id lorem commodo accumsan. Vestibulum pretium, augue ut ultrices accumsan, dui mi
tincidunt purus, vel condimentum libero nisl in justo. Hello!
TEXT;

    /**
     * Some Czech dummy text to test special characters.
     */
    const COMPLEX_TEXT = <<<TEXT
Muštby něvrzkotě ně vramy (mřímí) a běš nitlí? Fréř&ňoni $3500 zkedě||z tini nitrudr sepodi o báfé pěkmě? I nině vuni úniněchů
vlor, tiň štabli hroušhrni cešle grůcoj tlis bev puni tlýši pré šle. Midi a ti, vevlyšt a jouský hlyniv šech člyb
ptyškožra krocavě s nitý. Pipefrý dipyb mufry? Pivředizká niťou šoč pte diniré osař. Zloužlo vrozatich ryšu nišlouj šle
v ťaskzá očla. V niškou k di cruzrordli lanni, ktuviz pěv z pepy tlůtěš o ktub pěťlkedi.
TEXT;

    /**
     * Test the close character converter.
     * Just one test will suffice for this, as its the same method used for each class derived from App\BaseField.
     * @group search
     */
    public function test_convertCloseChars() {
        $converted = \App\Search::convertCloseChars(self::COMPLEX_TEXT);

        // Hand converted code by observing characters and assigning their "close enough" alternatives.
        $handConverted = <<<TEXT
Mustby nevrzkote ne vramy (mrimi) a bes nitli? Frer&noni $3500 zkede||z tini nitrudr sepodi o bafe pekme? I nine vuni uninechu
vlor, tin stabli hroushrni cesle grucoj tlis bev puni tlysi pre sle. Midi a ti, vevlyst a jousky hlyniv sech clyb
ptyskozra krocave s nity. Pipefry dipyb mufry? Pivredizka nitou soc pte dinire osar. Zlouzlo vrozatich rysu nislouj sle
v taskza ocla. V niskou k di cruzrordli lanni, ktuviz pev z pepy tlutes o ktub petlkedi.
TEXT;

        $convArr = explode(" ", $converted);
        $handArr = explode(" ", $handConverted);

        for ($i = 0; $i < count($convArr); $i++) {
            $this->assertEquals($convArr[$i], $handArr[$i]);
        }
    }

    public function test_getAdvancedSearchQuery() {
        $project = self::dummyProject();
        $form = self::dummyForm($project->pid);
        $field = self::dummyField(Field::_TEXT, $project->pid, $form->fid);
        $record = self::dummyRecord($project->pid, $form->fid);

        $text_field = new App\TextField();
        $text_field->rid = $record->rid;
        $text_field->flid = $field->flid;
        $text_field->text = "wow what a unit test!";
        $text_field->save();

        $dummy_query = [$field->flid . "_input" => "unit test"];

        $query = TextField::getAdvancedSearchQuery($field->flid, $dummy_query);
        $rid = $query->first();

        $this->assertEquals($rid->rid, $record->rid);
    }

    public function test_rollback() {
        $project = self::dummyProject();
        $form = self::dummyForm($project->pid);
        $field = self::dummyField(Field::_TEXT, $project->pid, $form->fid);
        $record = self::dummyRecord($project->pid, $form->fid);

        $old_text = "wow what a unit test!";

        $text_field = new App\TextField();
        $text_field->rid = $record->rid;
        $text_field->flid = $field->flid;
        $text_field->fid = $form->fid;
        $text_field->text = $old_text;
        $text_field->save();

        $revision = RevisionController::storeRevision($record->rid, Revision::CREATE);

        $new_text = "some new text";
        $text_field->text = $new_text;
        $text_field->save();

        $text_field = TextField::rollback($revision, $field);
        $this->assertEquals($text_field->text, $old_text);
    }
}
