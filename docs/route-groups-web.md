# RouteGroup in Web variant

Login and Register controllers now use class-level `RouteGroup` prefixes while keeping the public URLs `/login` and `/register`.

The GET actions remain public without middleware. The POST actions alone retain `CsrfMiddleware`. Applying that middleware at class level would break GET requests because it requires POST.

Run `php tests/csrf_route_configuration_test.php` and the existing CSRF tests after `composer update mithridatem/router -W`.
