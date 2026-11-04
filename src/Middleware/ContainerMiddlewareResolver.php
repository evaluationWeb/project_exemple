<?php

declare(strict_types=1);

namespace App\Middleware;

use Mithridatem\Autowiring\Container;
use Mithridatem\Routing\Middleware\MiddlewareInterface;
use Mithridatem\Routing\Middleware\MiddlewareResolverInterface;

/** Keeps routing independent of the application's autowiring implementation. */
final class ContainerMiddlewareResolver implements MiddlewareResolverInterface
{
    public function __construct(private Container $container) {}

    public function resolve(string $middleware): MiddlewareInterface
    {
        $instance = $this->container->get($middleware);

        if (!$instance instanceof MiddlewareInterface) {
            throw new \LogicException(sprintf(
                'Service %s does not implement MiddlewareInterface.',
                $middleware
            ));
        }

        return $instance;
    }
}
