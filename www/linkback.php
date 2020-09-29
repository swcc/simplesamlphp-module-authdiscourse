<?php

use Exception;
use SimpleSAML\Auth;
use SimpleSAML\Error;
use SimpleSAML\Module\authdiscourse\Auth\Source\Discourse;

/**
 * Handle linkback() response from Discourse.
 */

if (!array_key_exists('AuthState', $_REQUEST) || empty($_REQUEST['AuthState'])) {
    throw new Error\BadRequest('Missing state parameter on discourse linkback endpoint.');
}
$state = Auth\State::loadState($_REQUEST['AuthState'], Discourse::STAGE_INIT);

// Find authentication source
if (is_null($state) || !array_key_exists(Discourse::AUTHID, $state)) {
    throw new Error\BadRequest('No data in state for ' . Discourse::AUTHID);
}
$sourceId = $state[Discourse::AUTHID];

/** @var \SimpleSAML\Module\authdiscourse\Auth\Source\Discourse|null $source */
$source = Auth\Source::getById($sourceId);
if ($source === null) {
    throw new Error\BadRequest(
        'Could not find authentication source with id ' . var_export($sourceId, true)
    );
}

try {
    $source->finalStep($state);
} catch (Error\Exception $e) {
    Auth\State::throwException($state, $e);
} catch (Exception $e) {
    Auth\State::throwException(
        $state,
        new Error\AuthSource($sourceId, 'Error on authdiscourse linkback endpoint.', $e)
    );
}

Auth\Source::completeAuth($state);
