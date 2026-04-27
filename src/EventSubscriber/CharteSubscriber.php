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
        $route = (string) $request->attributes->get('_route', '');
        if ($route === '') {
            return;
        }

        // Ignorer les routes publiques/système
        if (in_array($route, ['app_login', 'app_logout', 'app_user_alias_setup', 'app_charte', 'app_charte_sign', 'app_charte_stepper'], true)) {
            return;
        }

        // Ignorer toutes les routes API et techniques (_wdt, _profiler...)
        if (str_starts_with($route, 'api_') || str_starts_with($route, '_')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $hasSigned = $this->charteAgreementRepository->count(['user' => $user]) > 0;
        if (!$hasSigned) {
            $url = $this->urlGenerator->generate('app_charte_stepper');
            $event->setResponse(new RedirectResponse($url));
            return;
        }

        // Obliger un alias unique après l'acceptation des conditions générales d'utilisation
        if ($user->getAlias() === null || trim($user->getAlias()) === '') {
            $url = $this->urlGenerator->generate('app_user_alias_setup');
            $event->setResponse(new RedirectResponse($url));
        }
    }
}
