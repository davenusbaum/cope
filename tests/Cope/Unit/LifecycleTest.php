<?php

use Cope\SapiContext;
use Cope\Lifecycle\GetStage;
use Cope\Lifecycle\PageStage;
use Cope\Lifecycle\PostOnlyStage;
use Cope\Lifecycle\Stage;

/**
 * A stage that records whether it ran, for asserting guards.
 */
class SpyStage extends Stage {
	public $ran = false;
	protected function run(SapiContext $ctx): void {
		$this->ran = true;
	}
}

class SpyPostStage extends PostOnlyStage {
	public $ran = false;
	protected function run(SapiContext $ctx): void {
		$this->ran = true;
	}
}

class SpyGetStage extends GetStage {
	public $ran = false;
	protected function run(SapiContext $ctx): void {
		$this->ran = true;
	}
}

class LifecycleTest extends \PHPUnit\Framework\TestCase
{
	private function webContext(string $method = 'GET'): SapiContext {
		$ctx = new SapiContext(
			[
				'REQUEST_URI' => '/web/hello.do',
				'REQUEST_METHOD' => $method,
			],
			[]
		);
		$ctx->setScopeList('web');
		return $ctx;
	}

	public function testStageRunsWhileStatusOk(): void {
		$ctx = $this->webContext();
		$stage = new SpyStage();
		$stage->handle($ctx);
		$this->assertTrue($stage->ran);
	}

	public function testStageHaltsAfterError(): void {
		$ctx = $this->webContext();
		$ctx->setStatus(403);
		$stage = new SpyStage();
		$stage->handle($ctx);
		$this->assertFalse($stage->ran);
	}

	public function testStageHaltsAfterRedirect(): void {
		$ctx = $this->webContext();
		$ctx->setStatus(303);
		$stage = new SpyStage();
		$stage->handle($ctx);
		$this->assertFalse($stage->ran);
	}

	public function testPostOnlyStageSkipsGetRequests(): void {
		$post = new SpyPostStage();
		$post->handle($this->webContext('GET'));
		$this->assertFalse($post->ran);

		$post = new SpyPostStage();
		$post->handle($this->webContext('POST'));
		$this->assertTrue($post->ran);
	}

	public function testPostOnlyStageStillHaltsAfterRedirect(): void {
		$ctx = $this->webContext('POST');
		$ctx->setStatus(303);
		$post = new SpyPostStage();
		$post->handle($ctx);
		$this->assertFalse($post->ran);
	}

	public function testGetStageRunsAfterValidationFailure(): void {
		$ctx = $this->webContext('POST');
		$ctx->setStatus(422);
		$get = new SpyGetStage();
		$get->handle($ctx);
		$this->assertTrue($get->ran);
	}

	public function testGetStageHaltsAfterRedirect(): void {
		$ctx = $this->webContext('POST');
		$ctx->setStatus(303);
		$get = new SpyGetStage();
		$get->handle($ctx);
		$this->assertFalse($get->ran);
	}

	public function testLegacyCommandSharesScriptScopeWithTemplate(): void {
		// the SchedulesPlus pattern: the get script sets plain
		// locals and the page template reads them — no context
		// state involved. LegacyCommandStage must preserve it.
		$ctx = new SapiContext(
			['REQUEST_URI' => '/web/legacy.do', 'REQUEST_METHOD' => 'GET'],
			[]
		);
		$ctx->setScopeList('web');
		$ctx->setBaseDir(__DIR__ . '/../../fixtures');

		ob_start();
		(new \Cope\Lifecycle\LegacyCommandStage())->handle($ctx);
		$out = ob_get_clean();

		$this->assertEquals("Hello, Deb!\n", $out);
	}

	public function testLegacyCommandSeesStatePublishedByMiddleware(): void {
		// middleware (e.g. a future CsrfStage) publishes state
		// before the command stage; a legacy template reads it
		// as a plain variable, same as a converted command would.
		$ctx = new SapiContext(
			['REQUEST_URI' => '/web/published.do', 'REQUEST_METHOD' => 'GET'],
			[]
		);
		$ctx->setScopeList('web');
		$ctx->setBaseDir(__DIR__ . '/../../fixtures');
		$ctx->set('csrf_token', 'tok123');

		ob_start();
		(new \Cope\Lifecycle\LegacyCommandStage())->handle($ctx);
		$out = ob_get_clean();

		$this->assertEquals("token=tok123", $out);
	}

	public function testPageStageRendersStateIntoTemplate(): void {
		$ctx = $this->webContext();
		$ctx->setBaseDir(__DIR__ . '/../../fixtures');
		$ctx->set('name', 'Dave');

		ob_start();
		(new PageStage())->handle($ctx);
		$out = ob_get_clean();

		$this->assertEquals("Hello Dave!\n", $out);
	}
}
