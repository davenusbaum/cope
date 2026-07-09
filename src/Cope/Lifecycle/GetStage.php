<?php

namespace Cope\Lifecycle;

use Cope\SapiContext;

/**
 * Base for get handlers.
 *
 * A get stage additionally runs after a 422 validation failure,
 * so the page can be re-displayed with its data and messages.
 * This replaces the default rule rather than narrowing it.
 */
abstract class GetStage extends Stage {

	protected function shouldRun(SapiContext $ctx): bool {
		return $ctx->isStatusOk(422);
	}
}
