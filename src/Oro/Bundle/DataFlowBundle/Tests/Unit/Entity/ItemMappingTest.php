<?php
namespace Oro\Bundle\DataFlowBundle\Tests\Unit\Entity;

use Oro\Bundle\DataFlowBundle\Entity\ItemMapping;
use Oro\Bundle\DataFlowBundle\Entity\FieldMapping;

/**
 * Test related class
 *
 *
 */
class ItemMappingTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ItemMapping
     */
    protected $item;

    /**
     * Setup
     */
    public function setup()
    {
        $this->item = new ItemMapping();
    }

    /**
     * Test related methods
     */
    public function testGettersSetters()
    {
        $this->assertEquals(count($this->item->getFields()), 0);
        $this->assertEquals($this->item->getId(), 0);

        $field = new FieldMapping();
        $field->setSource('my-code-src');
        $field->setDestination('my-code-dest');
        $field->setIsIdentifier(true);
        $this->item->addField($field);

        $this->assertEquals(count($this->item->getFields()), 1);

        $this->item->removeField($field);
        $this->assertEquals(count($this->item->getFields()), 0);
    }
}
