<?php

namespace Cope\Lifecycle;

use Cope\SapiContext;

/**
 * Renders the command's page for a converted command.
 * View data arrives through the context state ($ctx->set() in
 * the handlers); render() extracts it so the template reads
 * plain variables, same as it always has.
 */
class PageStage extends Stage {

	protected function run(SapiContext $ctx): void {
		if ($page = $ctx->getCommand('page')) {
			$ctx->render($page);
		}
	}
}
