<?php 
namespace Cloudinary;

use \PHPUnit\Framework\TestCase;

use \Cloudinary\Api;

class IntegrationTestBase extends TestCase
{
    const TEST_TAG = 'php_test';
    const API_KEY_WRONG = '1234';
    
    const CONTENT_TYPE_TEXT = 'text';
    const CONTENT_TYPE_CONTEXT = 'context';
    const CONTENT_TYPE_MULTI = 'multi';
    const CONTENT_TYPE_UPLOAD = 'upload';
    
    const RESOURCE_TYPE_IMAGE = 'image';
    const RESOURCE_TYPE_RAW = 'raw';
    const RESOURCE_TYPE_VIDEO = 'video';
    
    private $publicIds = [];
    
    public function tearDown()
    {
        Curl::$instance = new Curl();
        $api_key = \Cloudinary::option_get([], "api_key", \Cloudinary::config_get("api_key"));
        if ($api_key != self::API_KEY_WRONG) {
            $api = new Api();
            $api->delete_resources_by_tag(self::TEST_TAG);
        }
        
        $this->deleteContentByStorePublicId();
        
        Curl::$instance = new Curl();
    }
    
    public static function getContentTypeList()
    {
        return array(
            self::CONTENT_TYPE_TEXT,
            self::CONTENT_TYPE_CONTEXT,
            self::CONTENT_TYPE_MULTI,
            self::CONTENT_TYPE_UPLOAD,
        );
    }
    
    public static function getContentResourceTypeList()
    {
        return array(
            self::RESOURCE_TYPE_IMAGE,
            self::RESOURCE_TYPE_RAW,
            self::RESOURCE_TYPE_VIDEO,
        );
    }
    
    public function storePublicId($id, $type, $resource_type)
    {
        if (in_array($type, self::getContentTypeList()) && in_array($resource_type, self::getContentResourceTypeList())) {
            $this->publicIds[$type][$resource_type][] = $id;
        }
    }
    
    public function deleteContentByStorePublicId()
    {
        foreach ($this->publicIds as $type => $resources) {
            foreach ($resources as $resource_type => $publicIds) {
                foreach ($publicIds as $publicId) {
                    Uploader::destroy($publicId, array("type" => $type, "resource_type" => $resource_type));
                }
            }
        }
    }
}
