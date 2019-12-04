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
        try {
            (new Api())->add_metadata_field([
                'external_id' => self::$unique_external_id_general,
                'label' => self::$unique_external_id_general,
                'type' => 'string'
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

    /**
     * @throws \Cloudinary\Api\GeneralError
     */
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
    }

    /**
     * Asserts that a given object fits the generic structure of a metadata field
     *
     * @see https://cloudinary.com/documentation/admin_api#generic_structure_of_a_metadata_field
     *
     * @param $metadataField
     * @param string $type
     */
    private function assert_metadata_field($metadataField, $type = null)
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
    }

    /**
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
     * Get metadata fields
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_list_metadata_fields()
    {
        $result = $this->api->list_metadata_fields();

        $this->assertNotEmpty($result['metadata_fields']);
        $this->assert_metadata_field($result['metadata_fields'][0]);
    }

    /**
     * Get a metadata field by external id
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_get_metadata_field()
    {
        $result = $this->api->metadata_field_by_field_id(self::$unique_external_id_general);

        $this->assert_metadata_field($result, 'string');
    }

    /**
     * Create a string metadata field
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

        $this->assert_metadata_field($result, 'string');
        $this->assertEquals(self::$unique_external_id_string, $result['label']);
        $this->assertEquals(self::$unique_external_id_string, $result['external_id']);
        $this->assertFalse($result['mandatory']);
    }

    /**
     * Create an int metadata field
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

        $this->assert_metadata_field($result,'integer');
        $this->assertEquals(self::$unique_external_id_int, $result['label']);
        $this->assertEquals(self::$unique_external_id_int, $result['external_id']);
        $this->assertFalse($result['mandatory']);
    }

    /**
     * Create a date metadata field
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

        $this->assert_metadata_field($result, 'date');
        $this->assertEquals(self::$unique_external_id_date, $result['label']);
        $this->assertEquals(self::$unique_external_id_date, $result['external_id']);
        $this->assertFalse($result['mandatory']);
    }

    /**
     * Create an Enum metadata field
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

        $this->assert_metadata_field($result, 'enum');
        $this->assertEquals(self::$unique_external_id_enum, $result['label']);
        $this->assertEquals(self::$unique_external_id_enum, $result['external_id']);
        $this->assertFalse($result['mandatory']);
    }

    /**
     * Create a set metadata field
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

        $this->assert_metadata_field($result, 'set');
        $this->assertEquals(self::$unique_external_id_set, $result['label']);
        $this->assertEquals(self::$unique_external_id_set, $result['external_id']);
        $this->assertFalse($result['mandatory']);
    }

    /**
     * Update a metadata field by external id
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_update_metadata_field()
    {
        $newLabel = 'updating-' . self::$unique_external_id_general;
        $newDefaultValue = 'updating-' . self::$unique_external_id_general;

        $result = $this->api->update_metadata_field(
            self::$unique_external_id_general,
            [
                'external_id' => self::$unique_external_id_set,
                'label' => $newLabel,
                'type' => 'string',
                'mandatory' => true,
                'default_value' => $newDefaultValue
            ]
        );

        $this->assert_metadata_field($result, 'string');
        $this->assertEquals(self::$unique_external_id_general, $result['external_id']);
        $this->assertEquals($newLabel, $result['label']);
        $this->assertEquals($newDefaultValue, $result['default_value']);
        $this->assertTrue($result['mandatory']);
    }

    /**
     * Update a metadata field datasource
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_update_metadata_field_datasource()
    {
        $result = $this->api->add_metadata_field([
            'datasource' => [
                'values' => self::$datasource_multiple
            ],
            'external_id' => self::$unique_external_id_enum_2,
            'label' => self::$unique_external_id_enum_2,
            'type' => 'enum'
        ]);

        $this->assert_metadata_field($result, 'enum');

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
     * Delete a metadata field by external id
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_delete_metadata_field()
    {
        $tempMetadataFieldId = 'deletion-' . self::$unique_external_id_int;

        $result = $this->api->add_metadata_field([
            'external_id' => $tempMetadataFieldId,
            'label' => $tempMetadataFieldId,
            'type' => 'integer'
        ]);

        $this->assert_metadata_field($result, 'integer');

        $this->api->delete_metadata_field($tempMetadataFieldId);

        $hasException = false;
        try {
            $this->api->metadata_field_by_field_id($tempMetadataFieldId);
        } catch (NotFound $e) {
            $hasException = true;
        }

        $this->assertTrue($hasException, "The metadata field {$tempMetadataFieldId} was not deleted");
    }

    /**
     * Delete entries in a metadata field datasource
     *
     * @throws \Cloudinary\Api\GeneralError
     */
    public function test_delete_metadata_field_data_source()
    {
        $result = $this->api->add_metadata_field([
            'datasource' => [
                'values' => self::$datasource_multiple
            ],
            'external_id' => self::$unique_external_id_set_2,
            'label' => self::$unique_external_id_set_2,
            'type' => 'set'
        ]);

        $this->assert_metadata_field($result, 'set');

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

        $this->assert_metadata_field($result, 'date');
        $this->assertEquals($result['validation'], $validation);
        $this->assertEquals($result['default_value'], $metadata_field['default_value']);

        $this->api->delete_metadata_field($result['external_id']);

        $hasException = false;
        try {
            $metadata_field['default_value'] = date('Y-m-d', time() + 60*60*24*3);
            $this->api->add_metadata_field($metadata_field);
        } catch (BadRequest $e) {
            $hasException = true;
        }

        $this->assertTrue($hasException, "The metadata field with illegal value was added");
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

        $this->assert_metadata_field($result, 'integer');
        $this->assertEquals($result['validation'], $validation);
        $this->assertEquals($result['default_value'], $metadata_field['default_value']);

        $this->api->delete_metadata_field($result['external_id']);

        $hasException = false;
        try {
            $metadata_field['default_value'] = 6;
            $this->api->add_metadata_field($metadata_field);
        } catch (BadRequest $e) {
            $hasException = true;
        }

        $this->assertTrue($hasException, "The metadata field with illegal value was added");
    }
}
