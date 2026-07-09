<?php

namespace Cope\Lifecycle;

use Cope\SapiContext;

/**
 * Starts the session when the command wants one.
 * A command opts out with 'session' => false; a command that
 * authenticates always gets a session.
 */
class SessionStage extends Stage {

	protected function run(SapiContext $ctx): void {
		if (false !== $ctx->getCommand('session')
			|| $ctx->getCommand('authenticate')) {
			$ctx->getSession();
		}
	}
}
