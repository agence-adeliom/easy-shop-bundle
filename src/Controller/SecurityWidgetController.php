<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Adeliom\EasyShopBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

final class SecurityWidgetController
{
    /**
     * @param EngineInterface|Environment $templatingEngine
     */
    public function __construct(private readonly object $templatingEngine)
    {
    }

    public function renderAction(): Response
    {
        return new Response($this->templatingEngine->render('@EasyShop/front/Menu/_security.html.twig'));
    }
}
