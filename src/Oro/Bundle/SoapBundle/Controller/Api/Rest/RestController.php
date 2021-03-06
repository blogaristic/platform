<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Rest;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestApiCrudInterface;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;
use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Oro\Bundle\SoapBundle\Controller\Api\FormHandlerAwareInterface;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\UnitOfWork;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;

abstract class RestController extends FOSRestController implements
     FormAwareInterface,
     FormHandlerAwareInterface,
     EntityManagerAwareInterface,
     RestApiCrudInterface
{
    const ITEMS_PER_PAGE = 10;

    /**
     * GET entities list
     *
     * @param int $page
     * @param int $limit
     * @return Response
     */
    public function handleGetListRequest($page = 1, $limit = self::ITEMS_PER_PAGE)
    {
        $manager = $this->getManager();
        $items = $manager->getList($limit, $page);

        $result = array();
        foreach ($items as $item) {
            $result[] = $this->getPreparedItem($item);
        }
        unset($items);

        return new Response(json_encode($result), $result ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND);
    }

    /**
     * GET single item
     *
     * @param mixed $id
     * @return Response
     */
    public function handleGetRequest($id)
    {
        $item = $this->getManager()->find($id);

        if ($item) {
            $item = $this->getPreparedItem($item);
        }
        $responseData = $item ? json_encode($item) : '';
        return new Response($responseData, $item ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND);
    }

    /**
     * Edit entity
     *
     * @param mixed $id
     * @return Response
     */
    public function handleUpdateRequest($id)
    {
        $entity = $this->getManager()->find($id);
        if (!$entity) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        if ($this->processForm($entity)) {
            $view = $this->view(null, Codes::HTTP_NO_CONTENT);
        } else {
            $view = $this->view($this->getForm(), Codes::HTTP_BAD_REQUEST);
        }
        return $this->handleView($view);
    }

    /**
     * Create new
     *
     * @return Response
     */
    public function handleCreateRequest()
    {
        $entity = $this->getManager()->createEntity();
        $isProcessed = $this->processForm($entity);

        if ($isProcessed) {
            $entityClass = ClassUtils::getRealClass(get_class($entity));
            $classMetadata = $this->getManager()->getObjectManager()->getClassMetadata($entityClass);
            $view = $this->view($classMetadata->getIdentifierValues($entity), Codes::HTTP_CREATED);
        } else {
            $view = $this->view($this->getForm(), Codes::HTTP_BAD_REQUEST);
        }
        return $this->handleView($view);
    }

    /**
     * Delete entity
     *
     * @param mixed $id
     * @return Response
     */
    public function handleDeleteRequest($id)
    {
        $entity = $this->getManager()->find($id);
        if (!$entity) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        $em = $this->getManager()->getObjectManager();
        $em->remove($entity);
        $em->flush();

        return $this->handleView($this->view(null, Codes::HTTP_NO_CONTENT));
    }

    /**
     * Prepare entity for serialization
     *
     * @param mixed $entity
     * @return array
     */
    protected function getPreparedItem($entity)
    {
        $result = array();
        /** @var UnitOfWork $uow */
        $uow = $this->getDoctrine()->getManager()->getUnitOfWork();
        foreach ($uow->getOriginalEntityData($entity) as $field => $value) {
            $accessors = array('get' . ucfirst($field), 'is' . ucfirst($field), 'has' . ucfirst($field));
            foreach ($accessors as $accessor) {
                if (method_exists($entity, $accessor)) {
                    $value = $entity->$accessor();

                    $this->transformEntityField($field, $value);
                    $result[$field] = $value;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Prepare entity field for serialization
     *
     * @param string $field
     * @param mixed $value
     */
    protected function transformEntityField($field, &$value)
    {
        if ($value instanceof Proxy && method_exists($value, '__toString')) {
            $value = (string)$value;
        } elseif ($value instanceof \DateTime) {
            $value = $value->format('c');
        }
    }

    /**
     * Process form.
     *
     * @param mixed $entity
     * @return bool
     */
    protected function processForm($entity)
    {
        return $this->getFormHandler()->process($entity);
    }
}
