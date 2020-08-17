<?php

namespace Tests\Discover;

use Pear\OpenId\Discover\Discover;
use Pear\OpenId\ServiceEndpoints;

/**
 * OpenID_Discover_MockSubClass
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID_Discover
 * @category  Authenticateion
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * OpenID_Discover_MockSubClass
 *
 * @uses      OpenID_Discover
 * @category  Authenticateion
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class MockSubClass extends Discover
{
    /**
     * setServices
     *
     * @param ServiceEndpoints $services ServiceEndpoints instance
     *
     * @return void
     */
    public function setServices(ServiceEndpoints $services)
    {
        $this->services = $services;
    }
}

