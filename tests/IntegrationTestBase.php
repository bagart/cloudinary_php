<?php 
namespace Cloudinary;

use \PHPUnit\Framework\TestCase;

use \Cloudinary\Api;

class IntegrationTestBase extends TestCase
{
    const TEST_TAG = 'php_tag';
    const API_KEY_WRONG = '1234';

    private $publicIds = [];
    
    public function tearDown()
    {
        Curl::$instance = new Curl();
        $api_key = \Cloudinary::option_get([], "api_key", \Cloudinary::config_get("api_key"));
        if ($api_key != self::API_KEY_WRONG) {
            $api = new Api();
            $api->delete_resources_by_tag(self::TEST_TAG);
        }
        
        Curl::$instance = new Curl();
    }
    
}
