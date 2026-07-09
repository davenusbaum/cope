<?php

namespace Cope\Lifecycle;

use Cope\SapiContext;

/**
 * One stage in the command lifecycle.
 *
 * The lifecycle is a flat, ordered list of stages run by the
 * application's composition root (index.php):
 *
 * ```php
 * foreach ($stages as $stage) {
 *     (new $stage())->handle($ctx);
 * }
 * ```
 *
 * Each stage owns its participation rule. The default is the rule
 * nearly every stage means: run while the response is still a
 * success (no redirect, no error has been sent). Stages with a
 * different rule override shouldRun() and say so explicitly.
 */
abstract class Stage {

	/**
	 * Run the stage if its participation rule is met.
	 * @param SapiContext $ctx
	 */
	final public function handle(SapiContext $ctx): void {
		if ($this->shouldRun($ctx)) {
			$this->run($ctx);
		}
	}

	/**
	 * The participation rule for this stage.
	 * Defaults to "the response is still a success".
	 * @param SapiContext $ctx
	 * @return bool
	 */
	protected function shouldRun(SapiContext $ctx): bool {
		return $ctx->isStatusOk();
	}

	/**
	 * The work of the stage.
	 * @param SapiContext $ctx
	 */
	abstract protected function run(SapiContext $ctx): void;
}
