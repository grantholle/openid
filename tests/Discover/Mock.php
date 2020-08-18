<?php

namespace Tests\Discover;

use DateTime;
use Pear\OpenId\Discover\Discover;
use Pear\OpenId\Discover\DiscoverInterface;
use Pear\OpenId\OpenId;
use Pear\OpenId\ServiceEndpoint;
use Pear\OpenId\ServiceEndpoints;

/**
 * OpenID_Discover_Mock
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
 * OpenID_Discover_Mock
 *
 * @uses      OpenID_Discover_Interface
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class Mock extends Discover
{
    static public $opEndpoint = 'http://exampleop.com';
    public $identifier;
    public $services;

    public function __construct($identifier)
    {
        parent::__construct($identifier);

        $opEndpoint = new ServiceEndpoint;
        $opEndpoint->setURIs([self::$opEndpoint]);
        $opEndpoint->setVersion(OpenId::SERVICE_2_0_SERVER);
        $this->services = new ServiceEndpoints($identifier, $opEndpoint);
    }

    public function discover()
    {
        return true;
    }

    public function setRequestOptions(array $options)
    {
    }
}
