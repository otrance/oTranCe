<?php
/**
 * @group LanguageEntries
 * @group Models
 */
class LanguageEntriesTest extends ControllerTestCase
{
    /**
     * @var \Application_Model_LanguageEntries
     */
    protected $model;

    public static function setUpBeforeClass()
    {
        Testhelper::setUpDb('LanguageEntries.sql');
        Testhelper::setUpDb('db_schema_update4.sql');
        Testhelper::setUpDb('db_schema_update8.sql');
    }

    public function setUp()
    {
        // make sure actions in this test are done with the admin user
        $this->loginUser('Admin', 'admin');
        $this->model = new Application_Model_LanguageEntries();
    }

    public function testGetAllKeys()
    {
        $keys = $this->model->getAllKeys();
        $this->assertCount(110, $keys);
    }

    public function testGetAllKeysForOtherProjectWithNoTranslations()
    {
        $this->model->setActiveProject(2);
        $keys = $this->model->getAllKeys();
        $this->model->setActiveProject(1);
        $this->assertCount(0, $keys);
    }


    public function testGetTranslations()
    {
        $translations = $this->model->getTranslations(1);
        $this->assertCount(110, $translations);
        $this->assertEquals('Ersetze NULL durch', $translations[110]);

        $translations = $this->model->getTranslations(2);
        $this->assertEquals('Replace NULL with', $translations[110]);

        // positive false check with non existent language id
        $translations = $this->model->getTranslations(9999);
        $this->assertEquals(array(), $translations);

    }

    public function testGetTranslationsForOtherProjectWithNoTranslations()
    {
        // positive false check with non existent language id
        $this->model->setActiveProject(2);
        $translations = $this->model->getTranslations(1);
        $this->model->setActiveProject(1);
        $this->assertEquals(array(), $translations);

    }

    public function testGetStatus()
    {
        $languages = array(
            array('id' => 1),
            array('id' => 2)
        );

        $status = $this->model->getStatus($languages);
        // language de is at 97.27%
        $this->assertEquals(100, $status[1]['done']);
        // language en is at 100%
        $this->assertEquals(100, $status[2]['done']);
    }

    public function testGetStatusForOtherProjectWithNoTranslations()
    {
        $languages = array(
            array('id' => 1),
            array('id' => 2)
        );

        $this->model->setActiveProject(2);
        $status = $this->model->getStatus($languages);
        $this->model->setActiveProject(1);
        // language de is at 97.27%
        $this->assertEquals(0, $status[1]['done']);
        // language en is at 100%
        $this->assertEquals(0, $status[2]['done']);
    }

    public function testsGetEntriesByKey()
    {
        $entries  = $this->model->getEntriesByKey('L_CHECK');
        $expected = array(
            'id'          => 40,
            'key'         => 'L_CHECK',
            'template_id' => 1
        );
        $this->assertEquals($expected, $entries[0]);
    }

    public function testsGetEntriesByKeyForOtherProjectWithNoTranslations()
    {
        $this->model->setActiveProject(2);
        $entries  = $this->model->getEntriesByKey('L_CHECK');
        $this->model->setActiveProject(1);
        $expected = array();
        $this->assertEquals($expected, $entries);
    }

    public function testGetAssignedFileTemplate()
    {
        $template = $this->model->getAssignedFileTemplate(1);
        $expected = array(
            'id'       => 1,
            'name'     => 'Admin',
            'filename' => '{LOCALE}/lang.php'
        );
        $this->assertEquals($expected, $template);
    }

    public function testAssignFileTemplate()
    {
        // assign key 1 to template 99
        $saved = $this->model->assignFileTemplate(1, 99);
        $this->assertTrue($saved);

        // check
        $key      = $this->model->getKeyById(1);
        $expected = array(
            'id'          => '1',
            'key'         => 'L_TEST',
            'template_id' => '99',
            'project_id'  => '1',
            'dt'          => '2012-03-03 20:39:02'
        );
        $this->assertEquals($expected, $key);

        // re-assign
        $saved = $this->model->assignFileTemplate(1, 1);
        $this->assertTrue($saved);
    }

    public function testUpdateKeyName()
    {
        $updated = $this->model->updateKeyName(1, 'L_TEST_XX');
        $this->assertTrue($updated);
        // check
        $key      = $this->model->getKeyById(1);
        $expected = array(
            'id'          => '1',
            'key'         => 'L_TEST_XX',
            'template_id' => '1',
            'project_id'  => '1',
            'dt'          => '2012-03-03 20:39:02'
        );
        $this->assertEquals($expected, $key);
        // rollback
        $updated = $this->model->updateKeyName(1, 'L_TEST');
        $this->assertTrue($updated);
    }

    public function testGetEntryById()
    {
        $entry    = $this->model->getTranslationsByKeyId(1, 2);
        $expected = array(2 => 'Test records');
        $this->assertEquals($expected, $entry);

        $entry    = $this->model->getTranslationsByKeyId(1, 1);
        $expected = array(1 => 'Test eintrag');
        $this->assertEquals($expected, $entry);

        // get non existent
        $entry    = $this->model->getTranslationsByKeyId(99999, 2);
        $expected = array();
        $this->assertEquals($expected, $entry);

        // check call with invalid params
        $entry    = $this->model->getTranslationsByKeyId(0, 0);
        $expected = array();
        $this->assertEquals($expected, $entry);
    }

    public function testGetEntriesByKeys()
    {
        $keys     = array('L_TEST');
        $entry    = $this->model->getEntriesByKeys($keys, 1, 1);
        $expected = array('L_TEST' => 'Test eintrag');
        $this->assertEquals($expected, $entry);

        $entry    = $this->model->getEntriesByKeys($keys, 1, 2);
        $expected = array('L_TEST' => 'Test records');
        $this->assertEquals($expected, $entry);

        // check non existent key returns empty string
        $entry    = $this->model->getEntriesByKeys(array('IDontExist'), 1, 1);
        $expected = array('IDontExist' => '');
        $this->assertEquals($expected, $entry);
    }

    public function testGetEntryByKey()
    {
        $entry = $this->model->getEntryByKey('L_TEST', 1);
        $this->assertEquals(array('id' => 1), $entry);

        $entry = $this->model->getEntryByKey('IDontExist', 1);
        $this->assertFalse($entry);
    }

    public function testHasEntryWithKey()
    {
        $hasEntry = $this->model->hasEntryWithKey('L_TEST', 1);
        $this->assertTrue($hasEntry);

        $hasEntry = $this->model->hasEntryWithKey('IDontExist', 1);
        $this->assertFalse($hasEntry);
    }

    public function testGetEntriesByValue()
    {
        $entries  = $this->model->getEntriesByValue(array(1), 'löschen', 0, 30);
        $expected = array(
            0 => array(
                'id'          => 25,
                'key'         => 'L_AUTODELETE',
                'template_id' => 1
            ),
            1 => array(
                'id'          => 57,
                'key'         => 'L_CONFIG_AUTODELETE',
                'template_id' => 1
            ),
        );
        $this->assertEquals($expected, $entries);

        // check pagination
        $entries = $this->model->getEntriesByValue(array(1), 'Soll', 10, 10);
        $this->assertEquals(6, sizeof($entries));
        $check = array(
            'L_CONFIRM_DELETE_FILE',
            'L_CONFIRM_DELETE_TABLES',
            'L_CONFIRM_DROP_DATABASES',
            'L_CONFIRM_RECIPIENT_DELETE',
            'L_CONFIRM_TRUNCATE_DATABASES',
            'L_CONFIRM_TRUNCATE_TABLES'
        );
        foreach ($entries as $entry) {
            $this->assertTrue(in_array($entry['key'], $check));
        }
    }

    public function testGetEntriesByValueReturnsEmptyArrayOnInvalidKeyHits()
    {
        //check we get an empty array if we find hits for translations that are not assigned to a valid key
        // create defect entry  - key 9999 doesn't exist
        $entries = array(
            1 => 'Testtext',
            2 => 'Testtext2'
        );
        $this->model->saveEntries(9999, $entries);

        $entries = $this->model->getEntriesByValue(array(1), 'Testtext', 1, 2);
        $this->assertEquals(array(), $entries);
        $this->model->deleteEntryByKeyId(9999);
    }

    public function testGetEntriesByValuesResetsNrOfRecordsParamOnInvalidInput()
    {
        // check that param $nrOfRecords is set to 10 if it is lower (Result can be seen in CodeCoverage)
        $entries = $this->model->getEntriesByValue(array(1), 'IDOnTExist', 0, 1);
        $this->assertEquals(array(), $entries);
        return $entries;
    }

    public function testGetEntriesByValueReturnsEmptyArrayOnNonExistantSearchphrase()
    {
        // check for empty result set
        $entries = $this->model->getEntriesByValue(array(1), 'IDOnTExist', 0, 30);
        $this->assertEquals(array(), $entries);
        return $entries;
    }

    public function testGetEntriesByValueReturnsEmptyArrayOnEmptyLanguageIdInput()
    {
        // check param $languages with empty array returns an empty array
        $entries = $this->model->getEntriesByValue(array(), 'löschen', 0, 30);
        $this->assertEquals(array(), $entries);
        return $entries;
    }

    public function testGetEntriesBValueCanFilterByFileTemplate()
    {
        // check result is filtered by template id
        $entries  = $this->model->getEntriesByValue(array(1), 'a', 0, 2, 1);
        $expected = array(
            0 =>
            array(
                'id'          => '5',
                'key'         => 'L_ACTUALLY_INSERTED_RECORDS_OF',
                'template_id' => '1',
            ),
            1 =>
            array(
                'id'          => '1',
                'key'         => 'L_TEST',
                'template_id' => '1',
            )
        );
        $this->assertEquals($expected, $entries);
        return $entries;
    }

    public function testGetEntriesByKey()
    {
        // check that we get 10 translations for template 1
        $entries = $this->model->getEntriesByKey('', 0, 10, 1);
        $this->assertCount(10, $entries);

        // positive false check - check that we get 0 translations for template 2
        $entries = $this->model->getEntriesByKey('L_ADD', 0, 10, 2);
        $this->assertCount(0, $entries);
    }

    public function testGetUntranslated()
    {
        // check we get 3 untranslated phrases in total
        $entries = $this->model->getUntranslated();
        $this->assertCount(3, $entries);

        // check we find 1 untranslated key with "ACTION" in key name
        $entries = $this->model->getUntranslated(1, 'ACTION');
        $this->assertCount(1, $entries);

        // check we find no untranslated key with "ACTION" in key name for template 2
        $entries = $this->model->getUntranslated(1, 'ACTION', 0, 5, 2);
        $this->assertCount(0, $entries);
    }

    public function testGetUntranslatedResetsInvalidOffset()
    {
        $entries = $this->model->getUntranslated(1, -14);
        // if illegal offset -14 is corrected to 0 we should get 3 hits
        $this->assertCount(3, $entries);

        // while we are here and triggered a search, we check the method getRowCount()
        $foundRows = $this->model->getRowCount();
        $this->assertEquals(3, $foundRows);
    }

    public function testGetUntranslatedKey()
    {
        // check we find the first untranslated key of language 1
        $keyId = $this->model->getUntranslatedKey(1);
        $this->assertEquals(2, $keyId);

        // check we get null if all keys are translated (case for language 2)
        $keyId = $this->model->getUntranslatedKey(2);
        $this->assertEquals(null, $keyId);
    }

    public function testGetUntranslatedKeyResetsIllegalOffset()
    {
        // check we find the first untranslated key of language 1
        $keyId = $this->model->getUntranslatedKey(1, -14);
        $this->assertEquals(2, $keyId);
    }

    public function testAssignTranslations()
    {
        $languageIds          = array(1, 2);
        $entries              = array(
            0 => array('id' => 2, 'key' => 'L_ACTION'),
            1 => array('id' => 6, 'key' => 'L_ADD'),
        );
        $res                  = $this->model->assignTranslations($languageIds, $entries);
        $expectedTranslations = array(
            2 => array(1 => '', 2 => 'Action'),
            6 => array(1 => 'Hinzufügen', 2 => 'Add'),
        );
        $this->assertEquals($res[2]['languages'], $expectedTranslations[2]);
        $this->assertEquals($res[6]['languages'], $expectedTranslations[6]);

        // check invalid params
        $res = $this->model->assignTranslations(array(), array());
        $this->assertEquals(array(), $res);
    }

    public function testSaveNewKey()
    {
        $res = $this->model->saveNewKey(9998, 'I_AM_A_TEST_KEY');
        $this->assertTrue($res);
    }

    /**
     * @depends testSaveNewKey
     */
    public function testDeleteLanguageEntries()
    {
        //create translations for fake language 3
        $data = array(3 => 'Test translation');
        $res = $this->model->saveEntries(9998, $data);
        $this->assertTrue($res);

        $data = array(3 => 'Test translation');
        $res = $this->model->saveEntries(1, $data);
        $this->assertTrue($res);

        // now delete all translations of this language
        $res = $this->model->deleteLanguageEntries(3);
        $this->assertTrue($res);

        // a search for translations of language 3 should now be empty
        $entries  = $this->model->getTranslations(3);
        $this->assertEquals(array(), $entries);

        //cleanup
        $res = $this->model->deleteEntryByKeyId(9998);
        $this->assertTrue($res);
    }

    public function testSaveEntriesIsSkippedOnEmptyValues()
    {
        $res = $this->model->saveEntries(9999, array());
        $this->assertTrue($res);
    }

    public function testSaveEntriesRemovesUnchangedValues()
    {
        $translations = array(
            1 => 'Test eintrag',
            2 => 'Test records',
        );
        $res = $this->model->saveEntries(1, $translations);
        $this->assertTrue($res);
    }

    public function testSaveEntriesRemovesEmptyValuesIfOldTranslationDoesntExist()
    {
        $translations = array(1 => '');
        $res = $this->model->saveEntries(10000, $translations);
        $this->assertTrue($res);
    }

    public function testValidateLanguageKey()
    {
        $res = $this->model->validateLanguageKey('L_I_AM_THE_KEYNAME', 1);
        $this->assertTrue($res);

        $messages = $this->model->getValidateMessages();
        $this->assertEquals(array(), $messages);
    }

    public function testValidateLanguageKeyDetectsIfKeyNameIsTooShort()
    {
        $res = $this->model->validateLanguageKey('', 1);
        $this->assertFalse($res);

        $messages = $this->model->getValidateMessages();
        $expected = array(
            0 => 'The name of the key is too short.'
        );
        $this->assertEquals($expected, $messages);
    }

    public function testValidateLanguageKeyDetectsIfKeyExists()
    {
        $res = $this->model->validateLanguageKey('L_TEST', 1);
        $this->assertFalse($res);

        $messages = $this->model->getValidateMessages();
        $expected = array(
            0 => 'The key \'L_TEST\' already exists in this file template.',
        );
        $this->assertEquals($expected, $messages);
    }

}
