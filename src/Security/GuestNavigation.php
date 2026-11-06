<?php

declare(strict_types=1);

namespace App\Security;

use Mithridatem\Routing\Auth\ArrayGrantChecker;
use Mithridatem\Routing\Auth\AuthenticationAwareGrantChecker;
use Mithridatem\Routing\Context\RequestContextInterface;
use Mithridatem\Routing\Router;

/**
 * Web-only navigation policy. Never trusts an unvalidated Referer header.
 * The last successfully visited GET page is kept in the PHP session.
 */
final class GuestNavigation
{
    private const SESSION_KEY = '_last_safe_page';

    public function __construct(private string $fallbackPath = '/')
    {
        if (!$this->isLocalPath($fallbackPath)) {
            throw new \InvalidArgumentException('The fallback must be a local absolute path.');
        }
    }

    public function rememberSuccessfulGet(RequestContextInterface $request, Router $router): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || strtoupper($request->getMethod()) !== 'GET') {
            return;
        }

        $path = $request->getPath();
        if ($this->isAccessiblePage($path, $router)) {
            $_SESSION[self::SESSION_KEY] = $path;
        }
    }

    /**
     * Returns a safe local destination only for a logged-in user denied on
     * a guest-only route. Other unauthorized errors keep their normal 403.
     */
    public function destinationForDeniedGuest(RequestContextInterface $request, Router $router): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !self::isAuthenticated()) {
            return null;
        }

        $route = $this->matchedRoute($request->getPath(), $request->getMethod(), $router);
        if ($route === null || !in_array('IS_GUEST', $route->getGrants(), true)) {
            return null;
        }

        $previous = $_SESSION[self::SESSION_KEY] ?? null;
        if (is_string($previous) && $previous !== $request->getPath() && $this->isAccessiblePage($previous, $router)) {
            return $previous;
        }

        return $this->fallbackPath !== $request->getPath()
            && $this->isAccessiblePage($this->fallbackPath, $router)
            ? $this->fallbackPath
            : null;
    }

    private function isAccessiblePage(string $path, Router $router): bool
    {
        if (!$this->isLocalPath($path)) {
            return false;
        }

        $route = $this->matchedRoute($path, 'GET', $router);
        if ($route === null || in_array('IS_GUEST', $route->getGrants(), true)) {
            return false;
        }

        return $this->checker()->isGranted($route->getGrants());
    }

    private function matchedRoute(string $path, string $method, Router $router): ?\Mithridatem\Routing\RouteInterface
    {
        if (!$this->isLocalPath($path)) {
            return null;
        }

        $base = rtrim($router->getBasePath(), '/');
        if ($base !== '' && $path !== $base && !str_starts_with($path, $base . '/')) {
            return null;
        }

        $relativePath = $base === '' ? $path : (substr($path, strlen($base)) ?: '/');
        return $router->getRoutes()->match($method, $relativePath)?->getRoute();
    }

    private function checker(): AuthenticationAwareGrantChecker
    {
        return new AuthenticationAwareGrantChecker(
            static fn (): bool => self::isAuthenticated(),
            new ArrayGrantChecker($_SESSION['user']['grant'] ?? [])
        );
    }

    private static function isAuthenticated(): bool
    {
        return isset($_SESSION['user'])
            && is_array($_SESSION['user'])
            && isset($_SESSION['user']['id']);
    }

    private function isLocalPath(string $path): bool
    {
        return str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && !str_contains($path, '\\')
            && !preg_match('/[\x00-\x1F\x7F]/', $path)
            && !str_contains($path, '?')
            && !str_contains($path, '#');
    }
}
