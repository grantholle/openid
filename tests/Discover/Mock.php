<?php

namespace Tests\Discover;

use DateTime;
use Pear\OpenId\Discover\DiscoverInterface;
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
class Mock implements DiscoverInterface
{
    static public $opEndpoint = null;
    public $identifier;

    /**
     * __construct
     *
     * @param mixed $identifier Identifier
     *
     * @return void
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * discover
     *
     * @return ServiceEndpoints
     */
    public function discover()
    {
        $service = new ServiceEndpoints($this->identifier);
        $service->addService(self::$opEndpoint);
        $date = new DateTime(date('c', (time() + (3600 * 8))));
        $service->setExpiresHeader($date->format(DATE_RFC1123));
        return $service;
    }

    public function setRequestOptions(array $options)
    {
    }
}
