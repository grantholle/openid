<?php
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
 * Required files
 */
require_once 'src/Extension.php';

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
class OpenID_Extension_AX extends OpenID_Extension
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
    protected $validModes = array(
        'fetch_request',
        'fetch_response',
        'store_request',
        'store_response_success',
        'store_response_failure',
    );

    /**
     * Adds some validation checking when setting a key, then calls the parent set()
     *
     * @param string $key   Message key
     * @param mixed  $value Key's value
     *
     * @return void
     */
    public function set($key, $value)
    {
        if (strpos($key, 'mode') === 0
            && !in_array($value, $this->validModes)
        ) {
            throw new OpenID_Extension_Exception(
                'Invalid AX mode: ' . $key,
                OpenID_Exception::INVALID_VALUE
            );
        }

        if (preg_match('/^type[.]/', $key)
            && !filter_var($value, FILTER_VALIDATE_URL)
        ) {
            throw new OpenID_Extension_Exception(
                $key . ' is not a valid URI',
                OpenID_Exception::INVALID_VALUE
            );
        }
        parent::set($key, $value);
    }
}
?>
