<?php
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

require_once 'src/Discover/Interface.php';
require_once 'src/ServiceEndpoints.php';

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
class OpenID_Discover_Mock implements OpenID_Discover_Interface
{
    static public $opEndpoint = null;

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
     * @return void
     */
    public function discover()
    {
        $service = new OpenID_ServiceEndpoints($this->identifier);
        $service->addService(self::$opEndpoint);
        $date = new DateTime(date('c', (time() + (3600 * 8))));
        $service->setExpiresHeader($date->format(DATE_RFC1123));
        return $service;
    }

    public function setRequestOptions(array $options)
    {
    }
}

?>
