<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\CharteAgreementRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CharteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly CharteAgreementRepository $charteAgreementRepository,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (in_array($route, ['app_charte', 'app_charte_sign', 'app_logout'], true)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $hasSigned = $this->charteAgreementRepository->count(['user' => $user]) > 0;
        if ($hasSigned) {
            return;
        }

        $url = $this->urlGenerator->generate('app_charte');
        $event->setResponse(new RedirectResponse($url));
    }
}
