<?php

namespace Cope\Lifecycle;

use Cope\SapiContext;

/**
 * Runs an unconverted command exactly as the classic index.php
 * did: validate, post, get and page are included in this one
 * method scope, so a get script's local variables are visible
 * to the page template — the contract legacy commands rely on.
 *
 * A converted command replaces this single stage with handler
 * classes (Stage subclasses) plus a PageStage, and publishes its
 * view data through $ctx->set() instead of local scope.
 */
class LegacyCommandStage extends Stage {

	protected function run(SapiContext $ctx): void {

		// handle a POST
		if ($ctx->isMethod('POST')) {
			/* validate input parameters */
			if ($ctx->isStatusOk() && $script = $ctx->getCommandScript('validate')) {
				include($script);
			}
			/* process the input parameters */
			if ($ctx->isStatusOk() && $script = $ctx->getCommandScript('post')) {
				include($script);
			}
		}

		// run the get script, also after a 422 validation failure
		if ($ctx->isStatusOk(422) && $script = $ctx->getCommandScript('get')) {
			include($script);
		}

		// render the page in the same scope as the get script
		if ($ctx->isStatusOk() && $page = $ctx->getCommandPage()) {
			include($page);
		}
	}
}
