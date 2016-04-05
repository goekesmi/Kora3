<?php

use App\GeneratedListField as GeneratedListField;

/**
 * Class GeneratedListFieldTest
 * @group field
 */
class GeneratedListFieldTest extends TestCase
{
    /**
     * Test the keyword search for a generated list field. (Identical to MSL-Field Test)
     * @group search
     */
    public function test_keywordSearch() {
        $field = new GeneratedListField();
        $field->options = "single";

        // Test a single value
        $args = ["double"];
        $this->assertFalse($field->keywordSearch($args, true));
        $this->assertFalse($field->keywordSearch($args, false));

        $args = ["dou"];
        $this->assertFalse($field->keywordSearch($args, true));
        $this->assertFalse($field->keywordSearch($args, false));

        $args = ["dou", "123fdklj", "not here", "nope"];
        $this->assertFalse($field->keywordSearch($args, true));
        $this->assertFalse($field->keywordSearch($args, false));

        $args = ["single"];
        $this->assertTrue($field->keywordSearch($args, true));
        $this->assertTrue($field->keywordSearch($args, false));

        $args = ["singx", "zing"];
        $this->assertFalse($field->keywordSearch($args, true));
        $this->assertFalse($field->keywordSearch($args, false));

        $args = ["sing", "blah blah", "bllsdfk"];
        $this->assertTrue($field->keywordSearch($args, true));
        $this->assertFalse($field->keywordSearch($args, false));

        $args = ["", null, 0];
        $this->assertFalse($field->keywordSearch($args, true));
        $this->assertFalse($field->keywordSearch($args, false));

        // Mimic how options are stored in the database.
        $field->options = "apple[!]banana[!]pear[!]peach";

        $args = ['not a match', 'no match', 'nothing'];
        $this->assertFalse($field->keywordSearch($args, true));
        $this->assertFalse($field->keywordSearch($args, false));

        $args = ['apple'];
        $this->assertTrue($field->keywordSearch($args, true));
        $this->assertTrue($field->keywordSearch($args, false));

        $args = ['ban'];
        $this->assertTrue($field->keywordSearch($args, true));
        $this->assertFalse($field->keywordSearch($args, false));

        $args = ['apple', 'banana', 'peach'];
        $this->assertTrue($field->keywordSearch($args, true));
        $this->assertTrue($field->keywordSearch($args, false));

        $args = ['apx', 'bam', 'pea'];
        $this->assertTrue($field->keywordSearch($args, true));
        $this->assertFalse($field->keywordSearch($args, false));

        $args = ['[!]'];
        $this->assertFalse($field->keywordSearch($args, true));
        $this->assertFalse($field->keywordSearch($args, false));
    }
}