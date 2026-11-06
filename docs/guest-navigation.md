# Guest-only routes and session navigation (Web)

The Web profile uses \`mithridatem/router:^1.7\` and the opt-in \`AuthenticationAwareGrantChecker\`. The router itself remains session-agnostic.

## Route declarations

Both GET and POST actions are guest-only. The existing CSRF middleware remains enabled on POST.

\`\`\`php
#[Route(path: '/', method: 'GET', grants: ['IS_GUEST'])]
#[Route(path: '/', method: 'POST', grants: ['IS_GUEST'], middleware: [CsrfMiddleware::class])]
\`\`\`

\`IS_AUTHENTICATED\` can be used on pages available to any authenticated user. Ordinary roles such as \`ROLE_USER\` continue to work. The role checker is still built from \`$_SESSION['user']['grant']\` by \`RouterFactory\`.

## Redirect policy (Web only)

\`GuestNavigation\` remembers the last successfully dispatched, accessible GET route in the current PHP session. It excludes guest-only pages, paths not registered as GET routes, pages denied by grants and unsafe external paths. It does not read the \`Referer\` header.

If an authenticated user requests a guest-only page (GET or POST), the router raises \`UnauthorizedException\` as usual. \`Application\` delegates to \`GuestNavigation\`, which chooses the stored valid local page or an application-configured fallback (default \`/\`) and sends HTTP 303. If no safe route is available, the regular 403 response is preserved. Other authorization denials are never redirected.

To change the fallback, instantiate \`new GuestNavigation('/your-local-route')\` at the composition root. The fallback must correspond to a GET route currently allowed by the user's grants. No dashboard URL is assumed.

This policy is deliberately application-level, not part of \`mithridatem/router\`. It does not affect \`variant/api-jwt\`, whose authentication errors remain JSON responses without session state or navigation redirects.

## Manual validation

1. As a guest, open \`/login\` and \`/register\`; submit forms with CSRF.
2. Authenticate and browse a local authorized GET route.
3. Open \`/login\` or \`/register\` again: expect a 303 to the previous page, or fallback \`/\`.
4. Try POST to these paths while authenticated: the controller must not run.
5. Check that an unauthorized role route still returns 403, and a missing route still returns 404.

Automated checks: \`php tests/guest_navigation_test.php\` and \`php tests/web_kernel_test.php\`.
