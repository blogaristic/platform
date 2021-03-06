<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\AbstractTypedAddressType;

class AbstractTypedAddressTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AbstractTypedAddressType
     */
    protected $type;

    /**
     * Setup test env
     */
    public function setUp()
    {
        $buildAddressFormListener
            = $this->getMockBuilder('Oro\Bundle\AddressBundle\Form\EventListener\BuildAddressFormListener')
                ->disableOriginalConstructor()
                ->getMock();
        $flexibleManager = $this->getMockBuilder('Oro\Bundle\FlexibleEntityBundle\Manager\FlexibleManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = $this->getMockForAbstractClass(
            'Oro\Bundle\AddressBundle\Form\Type\AbstractTypedAddressType',
            array(
                $flexibleManager,
                'oro_address_value',
                $buildAddressFormListener
            )
        );
    }

    public function testAddEntityFields()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->any())
            ->method('add')
            ->will($this->returnSelf());
        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'types',
                'translatable_entity',
                $this->callback(
                    function ($options) {
                        \PHPUnit_Framework_TestCase::assertArrayHasKey('class', $options);
                        \PHPUnit_Framework_TestCase::assertArrayHasKey('property', $options);
                        \PHPUnit_Framework_TestCase::assertEquals('OroAddressBundle:AddressType', $options['class']);
                        \PHPUnit_Framework_TestCase::assertEquals('label', $options['property']);
                        return true;
                    }
                )
            );

        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'primary',
                'checkbox',
                $this->isType('array')
            );

        $this->type->addEntityFields($builder);
    }
}
