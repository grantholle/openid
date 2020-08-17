<?php

namespace Tests\Discover;

use Pear\OpenId\Discover\DiscoverInterface;

/**
 * OpenID_Discover_MockFail
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID_Discover_Interface
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * OpenID_Discover_MockFail
 *
 * @uses      OpenID_Discover_Interface
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class MockFail implements DiscoverInterface
{
    public function __construct($identifier)
    {
    }

    public function discover()
    {
        return false;
    }

    public function setRequestOptions(array $options)
    {
    }
}

