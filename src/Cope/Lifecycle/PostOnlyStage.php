<?php

namespace Cope\Lifecycle;

use Cope\SapiContext;

/**
 * A stage that only participates in POST requests.
 * Narrows the default rule — a redirect or error still halts it.
 * Base for validate and post handlers.
 */
abstract class PostOnlyStage extends Stage {

	protected function shouldRun(SapiContext $ctx): bool {
		return $ctx->isMethod('POST') && parent::shouldRun($ctx);
	}
}
