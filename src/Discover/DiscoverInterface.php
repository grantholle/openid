<?php

namespace Pear\OpenId\Discover;

/**
 * Discover_Interface
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
interface DiscoverInterface
{
    /**
     * Constructor.  Sets the user supplied identifier.
     *
     * @param string $identifier User supplied identifier
     *
     * @return void
     */
    public function __construct(string $identifier);

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
     * @return Discover for fluent interface
     */
    public function setRequestOptions(array $options);
}
