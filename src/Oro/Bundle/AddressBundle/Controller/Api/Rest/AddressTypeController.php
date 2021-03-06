<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\UserBundle\Annotation\Acl;
use Oro\Bundle\AddressBundle\Entity\AddressType;

/**
 * @RouteResource("addresstype")
 * @NamePrefix("oro_api_")
 * @Acl(
 *      id="oro_address_type",
 *      name="Address type manipulation",
 *      description="Address type manipulation",
 *      parent="oro_address"
 * )
 */
class AddressTypeController extends FOSRestController implements ClassResourceInterface
{
    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get all address types items",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_address_type_list",
     *      name="View list of address types",
     *      description="View list of address types",
     *      parent="oro_address_type"
     * )
     * @return Response
     */
    public function cgetAction()
    {
        /** @var $item AddressType */
        $items = $this->getDoctrine()->getRepository('OroAddressBundle:AddressType')->findAll();

        return $this->handleView(
            $this->view($items, is_array($items) ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND)
        );
    }

    /**
     * REST GET item
     *
     * @param string $name
     *
     * @ApiDoc(
     *      description="Get address type item",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_address_type_show",
     *      name="View address type",
     *      description="View address type",
     *      parent="oro_address_type"
     * )
     * @return Response
     */
    public function getAction($name)
    {
        if (!$name) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        /** @var $item AddressType */
        $item = $this->getDoctrine()->getRepository('OroAddressBundle:AddressType')->find($name);

        return $this->handleView(
            $this->view($item, is_object($item) ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND)
        );
    }
}
