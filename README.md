# Cope - Command Oriented PHP Environment

Cope is a minimalist PHP framework inspired by Apache Struts and,
more recently, by Koa.

## History
The Cope framework was created in 2004 when we wanted an MVC framework
for a new web application.
This was before virtual machines were commonly available, so we were working
in shared hosting with PHP 4.
We pulled the methods we needed from the Servlet API and made them into PHP 
functions (no objects in PHP 4).
Struts actions became commands, we even kept the .do extension.
The application we were building was multi-tenant, so we baked the tenant
identifiers into our url scheme `https://<account slug>/<command scope>/<command>.do`.
There is nothing RESTful about this, but routing is extremely fast.

## Evolution
Cope provides a fast and easily extensible framework for our solutions, so
there has never been a push to move to another framework.
We have, however, updated the framework as PHP evolved.

The current shape is a deliberate mashup of its two influences:

- **From Struts**: routing is data. A command map is a plain PHP array
  that names each command's lifecycle scripts and access level. There is
  no router class — finding a command is an array lookup.
- **From Koa**: state is a context. One `SapiContext` per request holds
  everything — path parameters, input, session, response status, view
  state — and is the only door to the PHP superglobals.

## The Context

`Cope\Context` is a static facade over a single `Cope\SapiContext`
instance. In production the instance is created lazily from the PHP
superglobals on first use — one request, one context. Application code
can speak to either:

```php
// ambient, convenient — the style application scripts use
ctx::getParameter('id');
ctx::set('todo', $todo);
ctx::sendRedirect('dsptodo.do?id=42');

// explicit, injectable — the style testable handlers use
$ctx->getParameter('id');
```

Both roads lead to the same instance, so old and new styles
interoperate inside a single request.

For tests, a context is built from plain arrays and installed behind
the facade — no HTTP request required:

```php
$ctx = Context::setCurrent(new SapiContext(
    ['REQUEST_URI' => '/web/myaccount/dsptodo.do', 'REQUEST_METHOD' => 'GET'],
    ['id' => '42']
));
```

A context constructed from injected arrays is a sealed world: it does
not read the process-global response code or any superglobal.

## The Command Lifecycle

A command runs through an ordered set of stages:

> session → authenticate → authorize → validate → post → get → page

`Cope\Lifecycle` provides the stages. The pipeline itself is a loop in
the application's index.php — routing stays visible in one screen:

```php
foreach ($stages as $stage) {
    (new $stage())->handle($ctx);
}
```

Each stage owns its participation rule. The default rule, inherited
from the `Stage` base class, is *run while the response is still a
success* — a redirect or an error sent by any stage halts everything
after it. Stages with a different rule say so by overriding one method:
`PostOnlyStage` narrows the rule to POST requests, `GetStage` also runs
after a 422 validation failure so a form can be re-displayed with its
messages.

Handlers are classes with one procedure:

```php
class ShowInvoice extends Cope\Lifecycle\GetStage {
    protected function run(SapiContext $ctx): void {
        $invoice = InvoiceQuery::byId($ctx->getParameter('id'));
        $ctx->set('invoice', $invoice);
    }
}
```

…and are unit tested by handing them an array-built context and
asserting on what they published.

`LegacyCommandStage` runs an unconverted command exactly as the classic
index.php did — validate, post, get and page included in one scope, so
a get script's local variables remain visible to its page template.
Applications migrate one command at a time, or never.

## Requirements

PHP >= 7.1

## Tests

```
vendor/bin/phpunit -c tests/phpunit.xml
```
