<?php

use Cope\Csrf;
use Cope\SapiContext;
use Cope\Session;
use Cope\Lifecycle\CsrfStage;

/**
 * Session double: same attribute API, no PHP session machinery
 * (session_start() cannot run under CLI PHPUnit once output has
 * started). Backed by its own array instead of $_SESSION.
 */
class FakeSession extends Session
{
	public function __construct() {
		\Cope\ArrayMap::__construct();
		$this->is_active = true;
	}
}

class CsrfTest extends \PHPUnit\Framework\TestCase
{

	private function webContext(string $method, array $request, Session $session, string $action = 'hello'): SapiContext {
		$ctx = new SapiContext(
			['REQUEST_URI' => "/web/$action.do", 'REQUEST_METHOD' => $method],
			$request,
			$session
		);
		$ctx->setScopeList('web');
		$ctx->setBaseDir(__DIR__ . '/../../fixtures');
		return $ctx;
	}

	public function testTokenIsStableForTheSession(): void {
		$session = new FakeSession();
		$token = Csrf::token($session);
		$this->assertNotEmpty($token);
		$this->assertEquals($token, Csrf::token($session));
	}

	public function testGetPublishesTokenToState(): void {
		$session = new FakeSession();
		$ctx = $this->webContext('GET', [], $session);
		(new CsrfStage())->handle($ctx);
		$this->assertEquals(Csrf::token($session), $ctx->get(CsrfStage::STATE_KEY));
		$this->assertEquals(200, $ctx->getStatus());
	}

	public function testPostWithoutTokenIsRefused(): void {
		$session = new FakeSession();
		Csrf::token($session);
		$ctx = $this->webContext('POST', [], $session);
		ob_start();
		(new CsrfStage())->handle($ctx);
		ob_end_clean();
		$this->assertEquals(419, $ctx->getStatus());
	}

	public function testPostWithWrongTokenIsRefused(): void {
		$session = new FakeSession();
		Csrf::token($session);
		$ctx = $this->webContext('POST', [CsrfStage::PARAMETER => 'forged'], $session);
		ob_start();
		(new CsrfStage())->handle($ctx);
		ob_end_clean();
		$this->assertEquals(419, $ctx->getStatus());
	}

	public function testPostWithValidTokenPasses(): void {
		$session = new FakeSession();
		$token = Csrf::token($session);
		$ctx = $this->webContext('POST', [CsrfStage::PARAMETER => $token], $session);
		(new CsrfStage())->handle($ctx);
		$this->assertEquals(200, $ctx->getStatus());
		$this->assertEquals($token, $ctx->get(CsrfStage::STATE_KEY));
	}

	public function testCommandCanOptOut(): void {
		// the 'optout' fixture command sets 'csrf' => false;
		// a tokenless POST passes untouched
		$session = new FakeSession();
		$ctx = $this->webContext('POST', [], $session, 'optout');
		(new CsrfStage())->handle($ctx);
		$this->assertEquals(200, $ctx->getStatus());
		$this->assertNull($ctx->get(CsrfStage::STATE_KEY));
	}
}
