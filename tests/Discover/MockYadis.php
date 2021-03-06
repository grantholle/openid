<?php

namespace Tests\Discover;

use Pear\OpenId\Discover\Yadis;

/**
 * OpenID_Discover_MockYadis
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID_Discover_Yadis
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * OpenID_Discover_MockYadis
 *
 * @uses      OpenID_Discover_Yadis
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class MockYadis extends Yadis
{
    static public $servicesYadisInstance = null;

    public function getServicesYadis()
    {
        return self::$servicesYadisInstance;
    }
}

