<?php

namespace Cope;

use Exception;

/**
 * CSRF token management.
 *
 * The session owns the token: generated once when first needed,
 * stable for the life of the session (regenerating per request
 * would break every already-open form). CsrfStage verifies it on
 * POST and publishes it to context state for forms to embed.
 */
class Csrf
{
	const KEY = 'csrf';

	/**
	 * Returns the session's CSRF token, generating it if missing.
	 * @param Session $session
	 * @return string
	 */
	public static function token(Session $session): string {
		$token = $session->getAttribute(self::KEY);
		if (empty($token)) {
			try {
				$token = bin2hex(random_bytes(32));
			} catch (Exception $e) {
				trigger_error($e->getMessage(), E_USER_WARNING);
				return '';
			}
			$session->set(self::KEY, $token);
		}
		return $token;
	}

	/**
	 * Compare a submitted token with the session's token.
	 * @param Session $session
	 * @param string|null $token
	 * @return bool
	 */
	public static function check(Session $session, ?string $token): bool {
		$known = self::token($session);
		return $known !== '' && $token !== null && hash_equals($known, $token);
	}
}
