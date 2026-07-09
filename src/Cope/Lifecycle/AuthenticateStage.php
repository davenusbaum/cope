<?php

namespace Cope\Lifecycle;

use Cope\SapiContext;

/**
 * Runs the command's authenticate script, if it declares one.
 * The script is included as-is and speaks to the context through
 * the static facade, so existing authenticate scripts work
 * unchanged.
 */
class AuthenticateStage extends Stage {

	protected function run(SapiContext $ctx): void {
		if ($script = $ctx->getCommandScript('authenticate')) {
			if (false === include($script)) {
				$ctx->sendError(500);
			}
		}
	}
}
