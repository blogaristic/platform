<?php
namespace Oro\Bundle\SearchBundle\Engine;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\FlexibleEntityBundle\AttributeType\AbstractAttributeType;

class ObjectMapper extends AbstractMapper
{
    public function __construct(ContainerInterface $container, $mappingConfig)
    {
        $this->mappingConfig = $mappingConfig;
        $this->container     = $container;
    }

    /**
     * @return array
     */
    public function getMappingConfig()
    {
        return $this->mappingConfig;
    }

    /**
     * Get array with entity aliases
     *
     * @return array
     */
    public function getEntitiesLabels()
    {
        $entities = array();

        foreach ($this->mappingConfig as $class => $mappingEntity) {
            $entities[] = array(
                'alias' => isset($mappingEntity['alias']) ? $mappingEntity['alias'] : '',
                'class' => $class,
            );
        }

        return $entities;
    }

    /**
     * Get search entities list
     *
     * @return array
     */
    public function getEntities()
    {
        return array_keys($this->mappingConfig);
    }

    /**
     * Map object data for index
     *
     * @param object $object
     *
     * @return array
     */
    public function mapObject($object)
    {
        $mappingConfig = $this->mappingConfig;
        $objectData = array();

        if (is_object($object) && isset($mappingConfig[get_class($object)])) {
            $objectClass = get_class($object);
            $alias = $this->getEntityMapParameter($objectClass, 'alias', $objectClass);
            foreach ($this->getEntityMapParameter($objectClass, 'fields', array()) as $field) {
                if (!isset($field['relation_type'])) {
                    $field['relation_type'] = 'none';
                }
                $value = $this->getFieldValue($object, $field['name']);
                switch ($field['relation_type']) {
                    case Indexer::RELATION_ONE_TO_ONE:
                    case Indexer::RELATION_MANY_TO_ONE:
                        $objectData = $this->setRelatedFields(
                            $alias,
                            $objectData,
                            $field['relation_fields'],
                            $value,
                            $field['name']
                        );

                        break;
                    case Indexer::RELATION_MANY_TO_MANY:
                    case Indexer::RELATION_ONE_TO_MANY:
                        foreach ($value as $relationObject) {
                            $objectData = $this->setRelatedFields(
                                $alias,
                                $objectData,
                                $field['relation_fields'],
                                $relationObject,
                                $field['name']
                            );
                        }

                        break;
                    default:
                        if ($value) {
                            $objectData = $this->setDataValue($alias, $objectData, $field, $value);
                        }
                }
            }

            if ($this->getEntityMapParameter($objectClass, 'flexible_manager')) {
                $objectData = $this->setFlexibleFields(
                    $alias,
                    $object,
                    $objectData,
                    $this->getEntityMapParameter($objectClass, 'flexible_manager')
                );
            }
        }

        return $objectData;
    }
}
