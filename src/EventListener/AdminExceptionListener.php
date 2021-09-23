<?php

namespace Adeliom\EasyShopBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityRemoveException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminExceptionListener
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof EntityRemoveException) {
            $bag = $request->getSession()->getFlashBag();
            $newBag = [];
            foreach ($bag->all() as $type => $messages) {
                if($type == "error"){
                    $type = "danger";
                }
                if(!isset($newBag[$type])){
                    $newBag[$type] = [];
                }
                foreach ($messages as $message) {
                    $newBag[$type][] = $message;
                }
            }

            $request->getSession()->getFlashBag()->setAll($newBag);

            $entity = $exception->getContext()->getParameters()['entity_name'];
            $request->getSession()->getFlashBag()->add('danger', $this->translator->trans('sylius.resource.delete_error', [
                    '%resource%' => $entity
                ], 'flashes'));


            $event->setResponse(new RedirectResponse($request->headers->get('referer')));
        }

    }
}
