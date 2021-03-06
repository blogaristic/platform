<?php

namespace Oro\Bundle\NotificationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\UserBundle\Annotation\Acl;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Datagrid\EmailNotificationDatagridManager;

/**
 * @Route("/email")
 * @Acl(
 *      id="oro_notification_emailnotification",
 *      name="Transactional emails",
 *      description="Notification rules manipulation",
 *      parent="root"
 * )
 */
class EmailNotificationController extends Controller
{
    /**
     * @Route(
     *      "/{_format}",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="oro_notification_emailnotification_index",
     *      name="View List of notification rules",
     *      description="View list of notification rules",
     *      parent="oro_notification_emailnotification"
     * )
     * @Template()
     */
    public function indexAction()
    {
        /** @var EmailNotificationDatagridManager $gridManager */
        $gridManager = $this->get('oro_notification.emailnotification.datagrid_manager');
        $datagridView = $gridManager->getDatagrid()->createView();

        if ('json' == $this->getRequest()->getRequestFormat()) {
            return $this->get('oro_grid.renderer')->renderResultsJsonResponse($datagridView);
        }

        return array('datagrid' => $datagridView);
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"}, defaults={"id"=0}))
     * @Acl(
     *      id="oro_notification_emailnotification_update",
     *      name="Edit notification rule",
     *      description="Edit notification rule",
     *      parent="oro_notification_emailnotification"
     * )
     * @Template()
     */
    public function updateAction(EmailNotification $entity)
    {
        if ($this->get('oro_notification.form.handler.email_notification')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.notification.controller.emailnotification.saved.message')
            );

            return $this->get('oro_ui.router')->actionRedirect(
                array(
                    'route' => 'oro_notification_emailnotification_update',
                    'parameters' => array('id' => $entity->getId()),
                ),
                array(
                    'route' => 'oro_notification_emailnotification_index',
                )
            );
        }

        return array(
            'form' => $this->get('oro_notification.form.email_notification')->createView(),
        );
    }

    /**
     * @Route("/create")
     * @Acl(
     *      id="oro_notification_emailnotification_create",
     *      name="Create notification rule",
     *      description="Create notification rule",
     *      parent="oro_notification_emailnotification"
     * )
     * @Template("OroNotificationBundle:EmailNotification:update.html.twig")
     */
    public function createAction()
    {
        return $this->updateAction(new EmailNotification());
    }
}
