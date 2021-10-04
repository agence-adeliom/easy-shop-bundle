<?php

namespace Adeliom\EasyShopBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;

class ConsoleListener
{
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        switch($event->getCommand()->getName()){
            case 'sylius:fixtures:list':
            case 'sylius:fixtures:load':
            case 'sylius:install':
            case 'sylius:install:assets':
            case 'sylius:install:database':
            case 'sylius:install:sample-data':
            case 'sylius:theme:list':
            case 'ylius:theme:assets:install':
                $event->disableCommand();
                break;
        }
    }
}
