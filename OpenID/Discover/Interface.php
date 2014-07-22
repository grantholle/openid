<?php
/**
 * OpenID_Discover_Interface
 *
 * PHP Version 5.2.0+
 *
 * @category  Auth
 * @package   OpenID
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Describes the discovery driver interface
 *
 * @category  Auth
 * @package   OpenID
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
interface OpenID_Discover_Interface
{
    /**
     * Constructor.  Sets the user supplied identifier.
     *
     * @param string $identifier User supplied identifier
     *
     * @return void
     */
    public function __construct($identifier);

    /**
     * Performs discovery on the user supplied identifier
     *
     * @return bool true on success, false on failure
     */
    public function discover();

    /**
     * Sets the HTTP_Request2 options to use
     *
     * @param array $options Array of HTTP_Request2 options
     *
     * @return OpenID_Discover for fluent interface
     */
    public function setRequestOptions(array $options);
}

?>
