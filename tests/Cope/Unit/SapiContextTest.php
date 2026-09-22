<?php

use Cope\Context;
use Cope\SapiContext;

/**
 * Proves that a SapiContext can be constructed from plain arrays
 * and installed behind the static Context facade — the seam that
 * makes command scripts testable without an HTTP request.
 */
class SapiContextTest extends \PHPUnit\Framework\TestCase
{
	protected function tearDown(): void {
		Context::setCurrent(null);
	}

	private function webContext(): SapiContext {
		$context = new SapiContext(
			[
				'REQUEST_URI' => '/web/claudetest/dsptodo.do',
				'REQUEST_METHOD' => 'POST',
				'SERVER_PROTOCOL' => 'HTTP/1.1',
			],
			['id' => '42', 'descr' => 'Water the garden']
		);
		$context->setScopeList('api|web');
		return $context;
	}

	public function testPathParamsFromInjectedServer(): void {
		$ctx = $this->webContext();
		$this->assertEquals('web', $ctx->getScope());
		$this->assertEquals('claudetest', $ctx->getKiosk());
		$this->assertEquals('dsptodo', $ctx->getAction());
	}

	public function testParametersFromInjectedRequest(): void {
		$ctx = $this->webContext();
		$this->assertEquals('42', $ctx->getParameter('id'));
		$this->assertEquals('Water the garden', $ctx->getParameter('descr'));
		$this->assertEquals('missing', $ctx->getParameter('nope', 'missing'));
	}

	public function testMethodFromInjectedServer(): void {
		$ctx = $this->webContext();
		$this->assertEquals('POST', $ctx->getMethod());
		$this->assertTrue($ctx->isMethod('POST'));
		$this->assertFalse($ctx->isMethod('GET'));
	}

	public function testStaticFacadeReadsInstalledContext(): void {
		// setCurrent is chainable: install and hold in one line
		$ctx = Context::setCurrent($this->webContext());
		$this->assertSame($ctx, Context::current());

		// every static call routes to the installed instance
		$this->assertEquals('web', Context::getScope());
		$this->assertEquals('claudetest', Context::getKiosk());
		$this->assertEquals('42', Context::getParameter('id'));

		Context::set('answer', 42);
		$this->assertTrue(Context::has('answer'));
		$this->assertEquals(42, Context::get('answer'));
	}

	public function testContextsAreIndependent(): void {
		$first = $this->webContext();
		$second = new SapiContext(
			['REQUEST_URI' => '/api/build.do'],
			[]
		);
		$second->setScopeList('api|web');

		$first->set('name', 'first');

		$this->assertEquals('api', $second->getScope());
		$this->assertNull($second->getKiosk());
		$this->assertFalse($second->has('name'));

		// swapping the current context swaps what the statics see
		Context::setCurrent($first);
		$this->assertEquals('claudetest', Context::getKiosk());
		Context::setCurrent($second);
		$this->assertNull(Context::getKiosk());
	}

	public function testHostDropsThePortTheHostHeaderCarries(): void {
		$ctx = new SapiContext([
			'REQUEST_URI' => '/web/hello.do',
			'HTTP_HOST' => '127.0.0.1:8089',
			'SERVER_NAME' => '127.0.0.1',
			'SERVER_PORT' => 8089,
		]);
		$ctx->setScopeList('web');
		$this->assertEquals('127.0.0.1', $ctx->getHost());
		$this->assertEquals(8089, $ctx->getPort());
		$this->assertEquals('http://127.0.0.1:8089', $ctx->getBaseUrl());
	}

	public function testHostWithoutAPortIsUnchanged(): void {
		$ctx = new SapiContext([
			'REQUEST_URI' => '/web/hello.do',
			'HTTP_HOST' => 'ask-remi.com',
			'SERVER_NAME' => 'ask-remi.com',
			'SERVER_PORT' => 80,
		]);
		$ctx->setScopeList('web');
		$this->assertEquals('ask-remi.com', $ctx->getHost());
		$this->assertEquals('http://ask-remi.com', $ctx->getBaseUrl());
	}

	public function testAnEmptyMapIsAMapNotAMissingFile(): void {
		$ctx = new SapiContext(['REQUEST_URI' => '/empty/hello.do']);
		$ctx->setScopeList('empty');
		$ctx->setBaseDir(__DIR__ . '/../../fixtures');
		$this->assertSame([], $ctx->getCommandMap());
		$this->assertNull($ctx->getCommand());
	}

	public function testAMissingMapWarnsAndYieldsNoCommands(): void {
		$ctx = new SapiContext(['REQUEST_URI' => '/nosuch/hello.do']);
		$ctx->setScopeList('nosuch');
		$ctx->setBaseDir(__DIR__ . '/../../fixtures');
		$warned = false;
		set_error_handler(function () use (&$warned) { $warned = true; return true; }, E_USER_WARNING);
		try {
			$this->assertSame([], $ctx->getCommandMap());
		} finally {
			restore_error_handler();
		}
		$this->assertTrue($warned);
	}
}
