# RouteGroup in Web variant

Login and Register controllers now use class-level `RouteGroup` prefixes while keeping the public URLs `/login` and `/register`.

The GET and POST actions require `IS_GUEST` with `mithridatem/router:^1.7`. GET actions have no middleware. The POST actions alone retain `CsrfMiddleware`. Applying that middleware at class level would break GET requests because it requires POST.

A logged-in session visiting a guest-only page redirects at the application level (see `guest-navigation.md`).

Run `php tests/csrf_route_configuration_test.php` and the existing CSRF tests after `composer update mithridatem/router -W`.
