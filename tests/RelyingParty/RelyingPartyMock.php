<?php

namespace Tests\RelyingParty;

use Pear\Net\Url2;
use Pear\OpenId\OpenIdMessage;
use Pear\OpenId\RelyingParty;

/**
 * OpenID_RelyingParty_Mock
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID_RelyingParty
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * OpenID_RelyingParty_Mock
 *
 * @uses      OpenID_RelyingParty
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class RelyingPartyMock extends RelyingParty
{
    /**
     * Just returns an OpenID_Association_Request object, as instantiated by
     * getAssociationRequestObject().  This is just for testing it.
     *
     * @param string $opEndpointURL The OP endpoint URL to communicate with
     * @param string $version       The OpenID version in use
     * @return \Pear\OpenId\Associations\Request
     */
    public function returnGetAssociationRequestObject($opEndpointURL, $version)
    {
        return $this->getAssociationRequestObject($opEndpointURL, $version);
    }

    /**
     * Just returns an OpenID_Assertion object, as instantiated by
     * getAssertionObject().  This is just for testing it.
     *
     * @param OpenIdMessage $message The message passed to {link verify()}
     * @param Url2 $requestedURL The requested URL
     *
     * @return \Pear\OpenId\Assertions\Assertion
     * @throws \Pear\OpenId\Exceptions\OpenIdAssociationException
     */
    public function returnGetAssertionObject(OpenIdMessage $message, Url2 $requestedURL)
    {
        return $this->getAssertionObject($message, $requestedURL);
    }
}
