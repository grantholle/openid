<?php

namespace Pear\OpenId\Extensions;

use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\Exceptions\OpenIdExtensionException;

/**
 * OpenID_Extension_AX
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID_Extension
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Support for the AX extension
 *
 * @uses      OpenID_Extension
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class AX extends OpenIdExtension
{
    /**
     * URL for the openid.ns.ax parameter
     *
     * @var string
     */
    protected $namespace ='http://openid.net/srv/ax/1.0';

    /**
     * Alias string to use
     *
     * @var string
     */
    protected $alias = 'ax';

    /**
     * Valid modes for AX requests/responses
     *
     * @var array
     */
    protected $validModes = [
        'fetch_request',
        'fetch_response',
        'store_request',
        'store_response_success',
        'store_response_failure',
    ];

    /**
     * Adds some validation checking when setting a key, then calls the parent set()
     *
     * @param string $key Message key
     * @param string $value Key's value
     * @return AX
     * @throws OpenIdExtensionException
     */
    public function set(string $key, string $value)
    {
        if (strpos($key, 'mode') === 0
            && !in_array($value, $this->validModes)
        ) {
            throw new OpenIdExtensionException(
                'Invalid AX mode: ' . $key,
                OpenIdException::INVALID_VALUE
            );
        }

        if (preg_match('/^type[.]/', $key)
            && !filter_var($value, FILTER_VALIDATE_URL)
        ) {
            throw new OpenIdExtensionException(
                $key . ' is not a valid URI',
                OpenIdException::INVALID_VALUE
            );
        }

        parent::set($key, $value);

        return $this;
    }
}
