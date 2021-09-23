<?php

namespace Adeliom\EasyShopBundle\Locale;

use A2lix\TranslationFormBundle\Locale;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SyliusLocalProvider implements Locale\LocaleProviderInterface {

    protected $locales;

    public function __construct(RepositoryInterface $em)
    {
        $this->locales = $em->findAll();
    }

    public function getLocales(): array
    {
        return array_map(function ($local) {
            return $local->getCode();
        }, $this->locales);
    }

    public function getDefaultLocale(): string
    {
        try {
            return $this->locales[0]->getCode();
        }catch (\Exception $e){
            return "fr";
        }
    }

    public function getRequiredLocales(): array
    {
        return [
            $this->getDefaultLocale()
        ];
    }
}
