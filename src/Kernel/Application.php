<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Security\GuestNavigation;
use Mithridatem\Autowiring\Container;
use Mithridatem\Routing\Exception\UnauthorizedException;
use Mithridatem\Routing\Http\Request;
use Throwable;

/**
 * A small composition root, not a framework kernel.
 * Boot the web environment, construct dependencies, then dispatch.
 */
final class Application
{
    public function __construct(private string $rootPath) {}

    public function run(): void
    {
        (new WebBootstrap())->boot($this->rootPath);

        $container = (new Container())->load(
            $this->rootPath . '/config/service.php'
        );

        $router = (new RouterFactory($container, $this->rootPath))->create();

        $request = Request::fromGlobals();
        $navigation = new GuestNavigation();

        try {
            $router->dispatch($request);
            $navigation->rememberSuccessfulGet($request, $router);
        } catch (Throwable $error) {
            if ($error instanceof UnauthorizedException) {
                $destination = $navigation->destinationForDeniedGuest($request, $router);
                if ($destination !== null) {
                    header('Location: ' . $destination, true, 303);
                    return;
                }
            }
            (new WebErrorHandler())->handle($error);
        }
    }
}
