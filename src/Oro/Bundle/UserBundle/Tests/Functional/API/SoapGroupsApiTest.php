<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @db_isolation
 */
class SoapGroupsApiTest extends WebTestCase
{
    /** Default value for role label */
    const DEFAULT_VALUE = 'GROUP_LABEL';

    /** @var Client */
    protected $client;

    public function setUp()
    {
        if (!isset($this->client)) {
            $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());

            $this->client->soap(
                "http://localhost/api/soap",
                array(
                    'location' => 'http://localhost/api/soap',
                    'soap_version' => SOAP_1_2
                )
            );

        } else {
            $this->client->restart();
        }

        $this->client->soap(
            "http://localhost/api/soap",
            array(
                'location' => 'http://localhost/api/soap',
                'soap_version' => SOAP_1_2
            )
        );
    }

    /**
     * @param string $request
     * @param array  $response
     *
     * @dataProvider requestsApi
     */
    public function testCreateGroup($request, $response)
    {
        $result = $this->client->soapClient->createGroup($request);
        $result = ToolsAPI::classToArray($result);
        ToolsAPI::assertEqualsResponse($response, $result);
    }

    /**
     * @param string $request
     * @param array  $response
     *
     * @dataProvider requestsApi
     * @depends testCreateGroup
     */
    public function testUpdateGroup($request, $response)
    {
        $groups = $this->client->soapClient->getGroups();
        $groups = ToolsAPI::classToArray($groups);
        foreach ($groups['item'] as $group) {
            if ($group['name'] == $request['name']) {
                $groupId = $group['id'];
                break;
            }
        }
        $request['name'] .= '_Updated';
        $result = $this->client->soapClient->updateGroup($groupId, $request);
        $result = ToolsAPI::classToArray($result);
        ToolsAPI::assertEqualsResponse($response, $result);
        $group = $this->client->soapClient->getGroup($groupId);
        $group = ToolsAPI::classToArray($group);
        $this->assertEquals($request['name'], $group['name']);
    }

    /**
     * @depends testCreateGroup
     */
    public function testGetGroups()
    {
        //get roles
        $groups = $this->client->soapClient->getGroups();
        $groups = ToolsAPI::classToArray($groups);
        $this->assertEquals(6, count($groups['item']));
    }

    /**
     * @depends testGetGroups
     */
    public function testDeleteGroups()
    {
        //get roles
        $groups = $this->client->soapClient->getGroups();
        $groups = ToolsAPI::classToArray($groups);
        $this->assertEquals(6, count($groups['item']));
        foreach ($groups['item'] as $k => $group) {
            if ($k > 1) {
                //do not delete default groups
                $result = $this->client->soapClient->deleteGroup($group['id']);
                $this->assertTrue($result);
            }
        }
        $groups = $this->client->soapClient->getGroups();
        $groups = ToolsAPI::classToArray($groups);
        $this->assertEquals(2, count($groups['item']));
    }

    /**
     * Data provider for REST API tests
     *
     * @return array
     */
    public function requestsApi()
    {
        return ToolsAPI::requestsApi(__DIR__ . DIRECTORY_SEPARATOR . 'GroupRequest');
    }
}
