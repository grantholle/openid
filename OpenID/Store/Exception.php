<?php
/**
 * OpenID_Store_Exception
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID_Exception
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
require_once 'OpenID/Exception.php';

/**
 * OpenID_Store_Exception
 *
 * Store exceptions
 *
 * @uses      OpenID_Exception
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class OpenID_Store_Exception extends OpenID_Exception
{
    /**
     * Database connection failed
     */
    const CONNECT_ERROR = 250;

    /**
     * Database table could not be created
     */
    const CREATE_TABLE_ERROR = 251;

    /**
     * SQL statement invalid
     */
    const SQL_ERROR = 253;
}
?>
