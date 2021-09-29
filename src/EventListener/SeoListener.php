<?php

namespace Adeliom\EasyShopBundle\EventListener;


use Sylius\Bundle\ProductBundle\Doctrine\ORM\ProductRepository;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductTaxonRepositoryInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Source;

class SeoListener implements EventSubscriberInterface
{
    /**
     * @var TaxonRepositoryInterface
     */
    private $taxonRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ChannelContextInterface
     */
    private $channelContext;

    /**
     * @var LocaleContextInterface
     */
    private $localeContext;

    public function __construct(TaxonRepositoryInterface $taxonRepository, ProductRepositoryInterface $productRepository, ChannelContextInterface $channelContext, LocaleContextInterface $localeContext)
    {
        $this->taxonRepository = $taxonRepository;
        $this->productRepository = $productRepository;
        $this->channelContext    = $channelContext;
        $this->localeContext    = $localeContext;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['setRequestLayout', 35],
        ];
    }


    public function setRequestLayout(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Get the necessary informations to check them in layout configurations
        $path = $request->getPathInfo();
        $host = $request->getHost();

        $slugsArray = array_values(preg_split('~/~', $path, -1, PREG_SPLIT_NO_EMPTY));

        $elements = [];
        foreach ($slugsArray as $slug){
            if($taxon = $this->taxonRepository->findOneBySlug($slug, $this->localeContext->getLocaleCode())){
                if(
                    $taxon->getParent() == null ||
                    (
                        isset($elements[count($elements) - 1]) &&
                        $taxon->getParent()->getId() == $elements[count($elements) - 1]->getId()
                    )
                ) {
                    $elements[] = $taxon;
                }
            }elseif ($product = $this->productRepository->findOneByChannelAndSlug($this->channelContext->getChannel(), $this->localeContext->getLocaleCode(), $slug)){
                if(
                    $product->getMainTaxon() == null ||
                    (
                        isset($elements[count($elements) - 1]) &&
                        $product->getMainTaxon()->getId() == $elements[count($elements) - 1]->getId()
                    )
                ) {
                        $elements[] = $product;
                }
            }
        }

        if(count($slugsArray) == count($elements)){
            $current = end($elements);
            if ($current instanceof ProductInterface){
                $event->getRequest()->attributes->set('_sylius_shop_product', $current);
            }elseif ($current instanceof TaxonInterface){
                $event->getRequest()->attributes->set('slug', $current->getSlug());
                $event->getRequest()->attributes->set('_sylius_shop_taxon', $current);
            }
        }

    }
}
