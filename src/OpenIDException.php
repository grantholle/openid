<?php

namespace Pear\OpenId;

/**
 * OpenID exceptions
 *
 * @uses      OpenID_Exception
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class OpenIDException extends \Exception
{
    /**
     * A given value does not match the expected format
     * or is not one of the allowed values
     */
    const INVALID_VALUE = 201;

    /**
     * A parameter has not been provided
     */
    const MISSING_DATA = 202;

    /**
     * A HTTPS connection is required
     */
    const HTTPS_REQUIRED = 203;

    /**
     * An object does not implement a given interface
     */
    const INVALID_DEFINITION = 204;

    /**
     * Some error on a HTTP connection
     */
    const HTTP_ERROR = 205;

    /**
     * Discovery failed for some reason
     */
    const DISCOVERY_ERROR = 206;

    /**
     * The message has already been signed
     */
    const ALREADY_SIGNED = 207;

    /**
     * A value could not be verified; it's wrong
     */
    const VERIFICATION_ERROR = 208;

    /**
     * A class or PHP extension could not be loaded
     */
    const LOAD_ERROR = 209;

    /**
     * Some OpenID error on the other side
     */
    const OPENID_ERROR = 210;
}
