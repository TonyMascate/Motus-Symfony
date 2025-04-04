<?php

// src/EventListener/RequestListener.php
namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestListener
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $cookies = $request->cookies->all();

        // Log l'ensemble des cookies reçus
        $this->logger->info('Cookies reçus dans la requête', $cookies);
    }
}
