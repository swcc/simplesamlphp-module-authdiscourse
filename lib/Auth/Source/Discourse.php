<?php

namespace SimpleSAML\Module\authdiscourse\Auth\Source;

use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Utils;
use Webmozart\Assert\Assert;

$base = dirname(dirname(dirname(dirname(__FILE__))));

/**
 * Authenticate using Discourse.
 *
 * @package SimpleSAMLphp
 */

class Discourse extends Auth\Source
{
    /**
     * The string used to identify our states.
     */
    public const STAGE_INIT = 'discourse:init';

    /**
     * The key of the AuthId field in the state.
     */
    public const AUTHID = 'discourse:AuthId';

    /**
     * Discourse base URL
     *
     * @var string
     */
    private $url;

    /**
     * Discourse SSO secret
     * @var string
     */
    private $secret;

    /**
     * Constructor for this authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct(array $info, array $config)
    {
        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        $configObject = Configuration::loadFromArray(
            $config,
            'authsources[' . var_export($this->authId, true) . ']'
        );

        $this->url = $configObject->getString('url');
        $this->secret = $configObject->getString('secret');
    }


    /**
     * Log-in using Discourse platform
     *
     * @param array &$state  Information about the current authentication.
     * @return void
     */
    public function authenticate(array &$state): void
    {
        assert(is_array($state));

        // We are going to need the authId in order to retrieve this authentication source later
        $state[self::AUTHID] = $this->authId;

        $nonce = hash('sha512', mt_rand());
        $state['authdiscourse:nonce'] = $nonce;

        $stateID = Auth\State::saveState($state, self::STAGE_INIT);

        $linkback = Module::getModuleURL('authdiscourse/linkback.php', ['AuthState' => $stateID]);

        $payload =  base64_encode(
            http_build_query(
                array (
                    'nonce' => $nonce,
                    'return_sso_url' => $linkback
                )
            )
        );

        $request = array(
            'sso' => $payload,
            'sig' => hash_hmac('sha256', $payload, $this->secret)
        );

        $query = http_build_query($request);
        $discourseSsoUrl = $this->url . "/session/sso_provider?$query";

        // Redirect user to Discourse SSO
        $httpUtils = new Utils\HTTP();
        $httpUtils->redirectTrustedURL($discourseSsoUrl);
    }


    /**
     * @param array &$state
     */
    public function finalStep(array &$state): void
    {
        $sso = (string) $_REQUEST['sso'];
        $sig = (string) $_REQUEST['sig'];

        if (!isset($sso)) {
            throw new Error\BadRequest("Missing sso parameter from discourse.");
        }

        if (!isset($sig)) {
            throw new Error\BadRequest("Missing sig parameter from discourse.");
        }

        // validate sso
        if(hash_hmac('sha256', urldecode($sso), $this->secret) !== $sig){
            throw new Error\NotFound();
        }

        $sso = urldecode($sso);
        $query = array();
        parse_str(base64_decode($sso), $query);

        // verify nonce with generated nonce during authenticate function
        if($query['nonce'] != $state['authdiscourse:nonce']){
            throw new Error\NotFound();
        }

        $attributes = [];
        foreach ($query as $key => $value) {
            if (is_string($value)) {
                if ((string) $key == (string) 'groups') {
                    $attributes['discourse.groups'] = explode(",", $value);
                } else {
                    $attributes['discourse.' . $key] = [(string) $value];
                }
            }
        }

        $state['Attributes'] = $attributes;
    }
}
