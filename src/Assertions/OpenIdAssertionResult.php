<?php

namespace Pear\OpenId\Assertions;

use Pear\OpenId\Discover\Discover;
use Pear\OpenId\Exceptions\OpenIdAssertionException;
use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\OpenId;
use Pear\OpenId\OpenIdMessage;

/**
 * A class that represents the result of verifying an assertion.
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class OpenIdAssertionResult
{
    /**
     * The check_authentication response
     *
     * @var OpenIdMessage
     */
    protected $checkAuthResponse = null;

    /**
     * The value of openid.user_setup_url, which is returned on a 1.1 negative
     * response to a checkid_immediate request
     *
     * @var string
     */
    protected $userSetupURL = null;

    /**
     * What assertion method was used (association, check_authentication)
     *
     * @var string
     */
    protected $assertionMethod = null;

    /**
     * Whether the assertion was positive or negative
     *
     * @var bool
     */
    protected $assertion = false;

    /**
     * Discovered information as an instance of Discover
     *
     * @see getDiscover()
     * @see setDiscover()
     * @var Discover|null
     */
    protected $discover = null;

    /**
     * Sets the check_authentication response in the form of an OpenIDMessage
     * instance
     *
     * @param OpenIdMessage $message The response message
     *
     * @return void
     *@see getCheckAuthResponse()
     */
    public function setCheckAuthResponse(OpenIdMessage $message)
    {
        $this->checkAuthResponse = $message;
    }

    /**
     * Gets the check_authentication response
     *
     * @return OpenIdMessage
     *@see setCheckAuthResponse()
     */
    public function getCheckAuthResponse()
    {
        return $this->checkAuthResponse;
    }

    /**
     * Indicates if the assertion was successful (positive) or not (negative)
     *
     * @return bool true on if a positive assertion was verified, false otherwise
     */
    public function success()
    {
        return $this->assertion;
    }

    /**
     * Sets the result of verifying the assertion.
     *
     * @param bool $value true if successful, false otherwise
     *
     * @return void
     */
    public function setAssertionResult($value)
    {
        $this->assertion = (bool)$value;
    }

    /**
     * Gets the method used to verify the assertion
     *
     * @return string
     */
    public function getAssertionMethod()
    {
        return $this->assertionMethod;
    }

    /**
     * Sets the assertion method used to verify the assertion
     *
     * @param string $method Method used
     *
     * @throws OpenIdAssertionException on invalid assertion mode
     * @return void
     */
    public function setAssertionMethod($method)
    {
        switch ($method) {
            case OpenId::MODE_ID_RES:
            case OpenId::MODE_ASSOCIATE:
            case OpenId::MODE_CHECKID_SETUP:
            case OpenId::MODE_CHECKID_IMMEDIATE:
            case OpenId::MODE_CHECK_AUTHENTICATION:
            case OpenId::MODE_CANCEL:
            case OpenId::MODE_SETUP_NEEDED:
                $this->assertionMethod = $method;
                break;
            default:
                throw new OpenIdAssertionException(
                    'Invalid assertion method',
                    OpenIdException::INVALID_VALUE
                );
        }
    }

    /**
     * Sets the openid.user_setup_url from the OP negative response
     *
     * @param string $url The URL from openid.user_setup_url
     *
     * @return void
     */
    public function setUserSetupURL($url)
    {
        $this->userSetupURL = $url;
    }

    /**
     * Returns the openid.user_setup_url value from the response
     *
     * @return string
     */
    public function getUserSetupURL()
    {
        return $this->userSetupURL;
    }

    /**
     * Sets the discovered information about the identifier
     *
     * @param Discover $discover An instance of Discover
     *
     * @see $discover
     * @see getDiscover()
     * @return void
     */
    public function setDiscover(Discover $discover)
    {
        $this->discover = $discover;
    }

    /**
     * Returns the discovered information about the identifer
     *
     * @see $discover
     * @see setDiscover()
     * @return Discover|null
     */
    public function getDiscover()
    {
        return $this->discover;
    }
}
