<?php

namespace Cloudinary;

use Exception;
use Cloudinary;
use PHPUnit\Framework\TestCase;
use Cloudinary\Api\NotFound;
use Cloudinary\Api\BadRequest;

/**
 * Class MetadataTest
 * @package Cloudinary
 */
class MetadataTest extends TestCase
{
    private static $unique_external_id_general;
    private static $unique_external_id_string;
    private static $unique_external_id_int;
    private static $unique_external_id_date;
    private static $unique_external_id_enum;
    private static $unique_external_id_enum_2;
    private static $unique_external_id_set;
    private static $unique_external_id_set_2;
    private static $unique_external_id_for_deletion;
    private static $datasource_single = [
        [
            'value' => 'v1',
            'external_id' => 'externalId1',
        ]
    ];
    private static $datasource_multiple = [
        [
            'value' => 'v2',
            'external_id' => 'externalId1',
        ],
        [
            'value' => 'v3'
        ],
        [
            'value' => 'v4'
        ],
    ];

    /**
     * @var  \Cloudinary\Api $api
     */
    private $api;

    public static function setUpBeforeClass()
    {
        if (!Cloudinary::config_get("api_secret")) {
            self::markTestSkipped('Please setup environment for Api test to run');
        }
        self::$unique_external_id_general = 'metadata_external_id_general_' . UNIQUE_TEST_TAG;
        self::$unique_external_id_string = 'metadata_external_id_string_' . UNIQUE_TEST_TAG;
        self::$unique_external_id_int = 'metadata_external_id_int_' . UNIQUE_TEST_TAG;
        self::$unique_external_id_date = 'metadata_external_id_date_' . UNIQUE_TEST_TAG;
        self::$unique_external_id_enum = 'metadata_external_id_enum_' . UNIQUE_TEST_TAG;
        self::$unique_external_id_enum_2 = 'metadata_external_id_enum_2_' . UNIQUE_TEST_TAG;
        self::$unique_external_id_set = 'metadata_external_id_set_' . UNIQUE_TEST_TAG;
        self::$unique_external_id_set_2 = 'metadata_external_id_set_2_' . UNIQUE_TEST_TAG;
        self::$unique_external_id_for_deletion = 'metadata_deletion_test_' . UNIQUE_TEST_TAG;
        try {
            (new Api())->add_metadata_field([
                'external_id' => self::$unique_external_id_general,
                'label' => self::$unique_external_id_general,
                'type' => 'string'
            ]);
            (new Api())->add_metadata_field([
                'datasource' => [
                    'values' => self::$datasource_multiple
                ],
                'external_id' => self::$unique_external_id_enum_2,
                'label' => self::$unique_external_id_enum_2,
                'type' => 'enum'
            ]);
            (new Api())->add_metadata_field([
                'datasource' => [
                    'values' => self::$datasource_multiple
                ],
                'external_id' => self::$unique_external_id_set_2,
                'label' => self::$unique_external_id_set_2,
                'type' => 'set'
            ]);
            (new Api())->add_metadata_field([
                'external_id' => self::$unique_external_id_for_deletion,
                'label' => self::$unique_external_id_for_deletion,
                'type' => 'integer'
            ]);
        } catch (Exception $e) {
            self::fail(
                'Exception thrown while adding metadata field in MetadataFieldsTest::setUpBeforeClass() - ' .
                $e->getMessage()
            );
        }
    }

    protected function setUp()
    {
        $this->api = new Api();
    }

    public static function tearDownAfterClass()
    {
        $api = new Api();

        try {
            $api->delete_metadata_field(self::$unique_external_id_general);
            $api->delete_metadata_field(self::$unique_external_id_string);
            $api->delete_metadata_field(self::$unique_external_id_int);
            $api->delete_metadata_field(self::$unique_external_id_date);
            $api->delete_metadata_field(self::$unique_external_id_enum);
            $api->delete_metadata_field(self::$unique_external_id_enum_2);
            $api->delete_metadata_field(self::$unique_external_id_set);
            $api->delete_metadata_field(self::$unique_external_id_set_2);
        } catch (Exception $e) {
            self::fail(
                'Exception thrown while deleting metadata fields in MetadataFieldsTest::tearDownAfterClass() - ' .
                $e->getMessage()
            );
        }
        self::delete_extra_metadata_fields($api);
    }

    /**
     * Delete leftover metadata fields which should have been deleted in case something broke and they were not deleted
     *
     * @param \Cloudinary\Api $api
     */
    private static function delete_extra_metadata_fields($api)
    {
        $externalIds = array(
            self::$unique_external_id_for_deletion,
        );
        foreach ($externalIds as $externalId) {
            try {
                $api->delete_metadata_field($externalId);
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Asserts that a given object fits the generic structure of a metadata field
     *
     * @see https://cloudinary.com/documentation/admin_api#generic_structure_of_a_metadata_field
     *
     * @param $metadataField    The object to test
     * @param string $type      The type of metadata field we expect
     * @param array $values     An associative array where the key is the name of the parameter to check and the value
     *                          is the value
     */
    private function assert_metadata_field($metadataField, $type = null, $values = array())
    {
        $this->assertInternalType('string', $metadataField['external_id']);
        if ($type) {
            $this->assertEquals($type, $metadataField['type']);
        } else {
            $this->assertContains($metadataField['type'], ['string', 'integer', 'date', 'enum', 'set']);
        }
        $this->assertInternalType('string', $metadataField['label']);
        $this->assertInternalType('boolean', $metadataField['mandatory']);
        $this->assertArrayHasKey('default_value', $metadataField);
        $this->assertArrayHasKey('validation', $metadataField);
        if (in_array($metadataField['type'], ['enum', 'set'])) {
            $this->assert_metadata_field_datasource($metadataField['datasource']);
        }

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $metadataField[$key]);
        }
    }

    /**
     * Asserts that a given object fits the generic structure of a metadata field datasource
     *
     * @see https://cloudinary.com/documentation/admin_api#datasource_values
     *
     * @param $dataSource
     */
    private function assert_metadata_field_datasource($dataSource)
    {
        $this->assertNotEmpty($dataSource);
        $this->assertArrayHasKey('values', $dataSource);
        if (!empty($values)) {
            $this->assertInternalType('string', $dataSource['values'][0]['value']);
            $this->assertInternalType('string', $dataSource['values'][0]['external_id']);
            $this->assertContains($dataSource['values'][0]['state'], ['active', 'inactive']);
        }
    }

    /**
     * Test getting a list of all metadata fields
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_list_metadata_fields()
    {
        $result = $this->api->list_metadata_fields();

        $this->assertGreaterThanOrEqual(1, count($result['metadata_fields']));
        $this->assert_metadata_field($result['metadata_fields'][0]);
    }

    /**
     * Test getting a metadata field by external id
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_get_metadata_field()
    {
        $result = $this->api->metadata_field_by_field_id(self::$unique_external_id_general);

        $this->assert_metadata_field($result, 'string', ['label' => self::$unique_external_id_general]);
    }

    /**
     * Test creating a string metadata field
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_create_string_metadata_field()
    {
        $result = $this->api->add_metadata_field([
            'external_id' => self::$unique_external_id_string,
            'label' => self::$unique_external_id_string,
            'type' => 'string'
        ]);

        $this->assert_metadata_field($result, 'string', [
            'label' => self::$unique_external_id_string,
            'external_id' => self::$unique_external_id_string,
            'mandatory' => false
        ]);
    }

    /**
     * Test creating an integer metadata field
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_create_int_metadata_field()
    {
        $result = $this->api->add_metadata_field([
            'external_id' => self::$unique_external_id_int,
            'label' => self::$unique_external_id_int,
            'type' => 'integer'
        ]);

        $this->assert_metadata_field($result, 'integer', [
            'label' => self::$unique_external_id_int,
            'external_id' => self::$unique_external_id_int,
            'mandatory' => false
        ]);
    }

    /**
     * Test creating a date metadata field
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_create_date_metadata_field()
    {
        $result = $this->api->add_metadata_field([
            'external_id' => self::$unique_external_id_date,
            'label' => self::$unique_external_id_date,
            'type' => 'date'
        ]);

        $this->assert_metadata_field($result, 'date', [
            'label' => self::$unique_external_id_date,
            'external_id' => self::$unique_external_id_date,
            'mandatory' => false
        ]);
    }

    /**
     * Test creating an Enum metadata field
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_create_enum_metadata_field()
    {
        $result = $this->api->add_metadata_field([
            'datasource' => [
                'values' => self::$datasource_single
            ],
            'external_id' => self::$unique_external_id_enum,
            'label' => self::$unique_external_id_enum,
            'type' => 'enum'
        ]);

        $this->assert_metadata_field($result, 'enum', [
            'label' => self::$unique_external_id_enum,
            'external_id' => self::$unique_external_id_enum,
            'mandatory' => false
        ]);
    }

    /**
     * Test creating a set metadata field
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_create_set_metadata_field()
    {
        $result = $this->api->add_metadata_field([
            'datasource' => [
                'values' => self::$datasource_multiple
            ],
            'external_id' => self::$unique_external_id_set,
            'label' => self::$unique_external_id_set,
            'type' => 'set'
        ]);

        $this->assert_metadata_field($result, 'set', [
            'label' => self::$unique_external_id_set,
            'external_id' => self::$unique_external_id_set,
            'mandatory' => false
        ]);
    }

    /**
     * Update a metadata field by external id
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_update_metadata_field()
    {
        $newLabel = 'update_metadata_test_' . self::$unique_external_id_general;
        $newDefaultValue = 'update_metadata_test_' . self::$unique_external_id_general;

        // Call the API to update the metadata field
        // Will also attempt to update some fields that cannot be updated (external_id and type) which will be ignored
        $result = $this->api->update_metadata_field(
            self::$unique_external_id_general,
            [
                'external_id' => self::$unique_external_id_set,
                'label' => $newLabel,
                'type' => 'integer',
                'mandatory' => true,
                'default_value' => $newDefaultValue
            ]
        );

        $this->assert_metadata_field($result, 'string', [
            'external_id' => self::$unique_external_id_general,
            'label' => $newLabel,
            'default_value' => $newDefaultValue,
            'mandatory' => true,
        ]);
    }

    /**
     * Update a metadata field datasource
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_update_metadata_field_datasource()
    {
        $result = $this->api->update_metadata_field_datasource(
            self::$unique_external_id_enum_2,
            self::$datasource_single
        );

        $this->assert_metadata_field_datasource($result);
        assertArrayContainsArray($this, $result['values'], self::$datasource_single[0]);
        $this->assertCount(count(self::$datasource_multiple), $result['values']);
        $this->assertEquals(self::$datasource_single[0]['value'], $result['values'][0]['value']);
    }

    /**
     * Deletes a metadata field definition by its external id.
     * The field should no longer be considered a valid candidate for all other endpoints (it will not show up in the
     * list of fields, etc).
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_delete_metadata_field()
    {
        $this->api->delete_metadata_field(self::$unique_external_id_for_deletion);

        $this->setExpectedException('\Cloudinary\Api\NotFound');
        $this->api->metadata_field_by_field_id(self::$unique_external_id_for_deletion);
    }

    /**
     * Delete entries in a metadata field datasource
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_delete_metadata_field_data_source()
    {
        $result = $this->api->delete_datasource_entries(
            self::$unique_external_id_set_2,
            [
                self::$datasource_multiple[0]['external_id']
            ]
        );

        $this->assert_metadata_field_datasource($result);
        $this->assertCount(count(self::$datasource_multiple) - 1, $result['values']);

        $values = array_column($result['values'], 'value');

        $this->assertContains(self::$datasource_multiple[1]['value'], $values);
        $this->assertContains(self::$datasource_multiple[2]['value'], $values);
    }

    /**
     * Test date field validation
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_date_field_default_value_validation()
    {
        $validation = [
            'rules' => [
                [
                    'type' => 'greater_than',
                    'equals' => false,
                    'value' => date('Y-m-d', time() - 60*60*24*3)
                ],
                [
                    'type' => 'less_than',
                    'equals' => false,
                    'value' => date('Y-m-d')
                ],
            ],
            'type' => 'and'
        ];
        $metadata_field = [
            'label' => 'date-validation-' . self::$unique_external_id_date,
            'type' => 'date',
            'default_value' => date('Y-m-d', time() - 60*60*24),
            'validation' => $validation
        ];

        $result = $this->api->add_metadata_field($metadata_field);

        $this->assert_metadata_field($result, 'date', [
            'validation' => $validation,
            'default_value' => $metadata_field['default_value'],
        ]);

        $this->api->delete_metadata_field($result['external_id']);

        $this->setExpectedException('\Cloudinary\Api\BadRequest');
        $metadata_field['default_value'] = date('Y-m-d', time() + 60*60*24*3);
        $this->api->add_metadata_field($metadata_field);
    }

    /**
     * Test integer field single validation
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_integer_field_single_validation()
    {
        $validation = ['type' => 'less_than', 'equals' => true, 'value' => 5];
        $metadata_field = [
            'label' => 'validation-' . self::$unique_external_id_int,
            'type' => 'integer',
            'default_value' => 5,
            'validation' => $validation
        ];

        $result = $this->api->add_metadata_field($metadata_field);

        $this->assert_metadata_field($result, 'integer', [
            'validation' => $validation,
            'default_value' => $metadata_field['default_value'],
        ]);

        $this->api->delete_metadata_field($result['external_id']);

        $this->setExpectedException('\Cloudinary\Api\BadRequest');
        $metadata_field['default_value'] = 6;
        $this->api->add_metadata_field($metadata_field);
    }
}
