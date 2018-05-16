<?php 
namespace Cloudinary;

use \PHPUnit\Framework\TestCase;

use \Cloudinary\Api;

/**
 * Integration Test Base Class
 */
class IntegrationTestBase extends TestCase
{
    const API_KEY_WRONG = '1234';
    
    const CONTENT_TYPE_TEXT = 'text';
    const CONTENT_TYPE_CONTEXT = 'context';
    const CONTENT_TYPE_MULTI = 'multi';
    const CONTENT_TYPE_UPLOAD = 'upload';
    
    const RESOURCE_TYPE_IMAGE = 'image';
    const RESOURCE_TYPE_RAW = 'raw';
    const RESOURCE_TYPE_VIDEO = 'video';
    
    private static $storedPublicIds = [];
    private static $storedTransformationData = [];
    private static $storedTags = [];
    
    /**
     * @inheritdoc
     */
    public function tearDown()
    {
        Curl::$instance = new Curl();
    }
    
    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass()
    {
        self::storeTag(UNIQUE_TEST_TAG);
        
        $api_key = \Cloudinary::option_get([], "api_key", \Cloudinary::config_get("api_key"));
        if ($api_key != self::API_KEY_WRONG) {
            self::deleteResourcesByStoredTags();
            self::deleteContentByStoredPublicId();
            self::deleteTransformationsByStoredData();
        }
    }
    
    /**
     * Content Type List
     * 
     * @return array
     */
    protected static function getContentTypeList()
    {
        return array(
            self::CONTENT_TYPE_TEXT,
            self::CONTENT_TYPE_CONTEXT,
            self::CONTENT_TYPE_MULTI,
            self::CONTENT_TYPE_UPLOAD,
        );
    }
    
    /**
     * Content Resource Type List
     * 
     * @return array
     */
    protected static function getContentResourceTypeList()
    {
        return array(
            self::RESOURCE_TYPE_IMAGE,
            self::RESOURCE_TYPE_RAW,
            self::RESOURCE_TYPE_VIDEO,
        );
    }
    
    /**
     * Save PublicId
     * 
     * @param string $id
     * @param string $type
     * @param string $resource_type
     * 
     * @return void
     */
    protected static function storePublicId($id, $type, $resource_type)
    {
        if (in_array($type, self::getContentTypeList()) && in_array($resource_type, self::getContentResourceTypeList())) {
            self::$storedPublicIds[$type][$resource_type][] = $id;
        }
    }
    
    /**
     * Delete content by store PublicId
     * 
     * @return void
     */
    protected static function deleteContentByStoredPublicId()
    {
        foreach (self::$storedPublicIds as $type => $resources) {
            foreach ($resources as $resource_type => $publicIds) {
                foreach ($publicIds as $publicId) {
                    Uploader::destroy($publicId, array("type" => $type, "resource_type" => $resource_type));
                }
            }
        }
    }
    
    /**
     * Save transformations data
     * 
     * @param array $transformationData
     * 
     * @return void
     */
    protected static function storeTransformationData(array $transformationData)
    {
        if (!empty($transformationData)) {
            self::$storedTransformationData[md5(json_encode($transformationData))] = $transformationData;
        }
    }
    
    /**
     * Delete Transformations by store data
     * 
     * @return void
     */
    protected static function deleteTransformationsByStoredData()
    {
        $api = new Api();
        foreach (self::$storedTransformationData as $transformationData) {
            try {
                $api->delete_transformation($transformationData);
            } catch (\Cloudinary\Api\NotFound $e) {}
        }
    }
    
    /**
     * Save tag
     * 
     * @param string $tag
     * 
     * @return void
     */
    protected static function storeTag($tag)
    {
        if (!empty($tag)) {
            self::$storedTags[md5($tag)] = $tag;
        }
    }
    
    /**
     * Delete resources by store tags
     * 
     * @return void
     */
    protected static function deleteResourcesByStoredTags()
    {
        $api = new Api();
        foreach (self::$storedTags as $tag) {
            $api->delete_resources_by_tag($tag);
        }
    }
}
