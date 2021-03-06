<?php

namespace Oro\Bundle\TagBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @db_isolation
 * @reindex
 */
class ControllersTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        if (!isset($this->client)) {
            $this->client = static::createClient(array(), ToolsAPI::generateBasicHeader());
        } else {
            $this->client->restart();
        }
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->client->generate('oro_tag_index'));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
    }

    public function testIndexJson()
    {
        $tags = array('tags[_pager][_page]' => 1, 'tags[_pager][_per_page]' => 10, 'tags[_sort_by][tag]' => 'ASC');

        $this->client->request('GET', $this->client->generate('oro_tag_index', array('_format' => 'json')), $tags);
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $this->assertEquals(0, $result['options']['totalRecords']);
    }

    public function testCreate()
    {
        $this->markTestIncomplete('BAP-1260');
        $crawler = $this->client->request('GET', $this->client->generate('oro_tag_create'));
        $form = $crawler->selectButton('Save')->form();
        $form['oro_tag_tag_form[name]'] = 'testTag1';
        $this->client->submit($form);
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
    }
}
