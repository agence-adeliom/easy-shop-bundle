<?php

namespace Adeliom\EasyShopBundle\Locale;

use A2lix\TranslationFormBundle\Locale;
use Sylius\Component\Resource\Repository\RepositoryInterface;

class SyliusLocalProvider implements Locale\LocaleProviderInterface
{
    protected $locales;

    public function __construct(RepositoryInterface $em)
    {
        $this->locales = $em->findAll();
    }

    public function getLocales(): array
    {
        return array_map(static fn($local) => $local->getCode(), $this->locales);
    }

    public function getDefaultLocale(): string
    {
        try {
            return $this->locales[0]->getCode();
        } catch (\Exception) {
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
