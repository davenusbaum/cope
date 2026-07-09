<?php

namespace Cope\Lifecycle;

use Cope\SapiContext;

/**
 * Runs the command's authorize script, if it declares one.
 * The script returns the caller's access level; the request is
 * refused when it falls below the command's required level.
 */
class AuthorizeStage extends Stage {

	protected function run(SapiContext $ctx): void {
		if ($script = $ctx->getCommandScript('authorize')) {
			$access_level = include($script);
			if ($access_level < $ctx->getCommand('access_level')) {
				$ctx->sendNotAuthorized();
			}
		}
	}
}
