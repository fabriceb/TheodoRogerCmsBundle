<?php

/*
 * This file is part of the Thoth CMS Bundle
 *
 * (c) Theodo <contact@theodo.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Theodo\ThothCmsBundle\Extensions\Twig\Extension;

use Theodo\ThothCmsBundle\Extensions\Twig\TokenParser\SnippetTokenParser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Twig extension for Symfony actions helper
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ThothActionsExtension extends \Twig_Extension
{
    private $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container The service container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns the Response content for a given controller or URI.
     *
     * @param string $controller A controller name to execute (a string like BlogBundle:Post:index), or a relative URI
     * @param array  $attributes An array of request attributes
     * @param array  $options    An array of options
     *
     * @see Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver::render()
     */
    public function renderSnippet($name, $attributes = array())
    {
        $controller = 'TheodoThothCmsBundle:Frontend:snippet';
        $params['name'] = $name;
        $params['attributes'] = $attributes;
        $options['standalone'] = true;
        return $this->container->get('templating.helper.actions')->render($controller, $params, $options);
    }

    /**
     * Returns the token parser instance to add to the existing list.
     *
     * @return array An array of Twig_TokenParser instances
     */
    public function getTokenParsers()
    {
        return array(
            // {% render 'theodo' with { 'arg': 42 } %}
            new SnippetTokenParser(),
        );
    }

    public function getName()
    {
        return 'thoth_actions';
    }
}