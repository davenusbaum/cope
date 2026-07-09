<?php

namespace Cope\Lifecycle;

use Cope\Csrf;
use Cope\SapiContext;

/**
 * Enforces the CSRF contract, in both directions:
 * on POST, the '_csrf' parameter must match the session's token
 * or the request is refused with a 419; on every request the
 * token is published to context state as 'csrf_token' so forms
 * can embed it:
 *
 * ```html
 * <input type="hidden" name="_csrf" value="<?= e($csrf_token) ?>">
 * ```
 *
 * A command opts out with 'csrf' => false in the command map
 * (e.g. token-authenticated api commands).
 */
class CsrfStage extends Stage
{
	/** The request parameter carrying the token */
	const PARAMETER = '_csrf';

	/** The state key forms read the token from */
	const STATE_KEY = 'csrf_token';

	protected function shouldRun(SapiContext $ctx): bool {
		return parent::shouldRun($ctx)
			&& false !== $ctx->getCommand('csrf');
	}

	protected function run(SapiContext $ctx): void {
		$session = $ctx->getSession();

		if ($ctx->isMethod('POST')
			&& !Csrf::check($session, $ctx->getParameter(self::PARAMETER))) {
			$ctx->sendError(419, 'Request token mismatch');
			return;
		}

		$ctx->set(self::STATE_KEY, Csrf::token($session));
	}
}
