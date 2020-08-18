<?php

namespace Pear\OpenId;

use Pear\Net\Url2;
use Pear\OpenId\Assertions\Assertion;
use Pear\OpenId\Assertions\OpenIDAssertionResult;
use Pear\OpenId\Associations\Association;
use Pear\OpenId\Associations\Request;
use Pear\OpenId\Auth\OpenIdAuthRequest;
use Pear\OpenId\Discover\Discover;
use Pear\OpenId\Exceptions\OpenIdException;

/**
 * OpenID_RelyingParty
 *
 * OpenID_RelyingParty implements all the steps required to verify a claim in two
 * step interface: {@link prepare() prepare} and {@link verify() verify}.
 *
 * {@link prepare() prepare} sets up the request, which includes performing
 * discovery on the identifier, establishing an association with the OpenID Provider
 * (optional), and then building an OpenIdAuthRequest object.  With this object,
 * you can optionally add OpenID_Extension(s), and then perform the request.
 *
 * {@link verify() verify} takes a Url2 object as an argument, which represents
 * the URL that the end user was redirected to after communicating with the the
 * OpenID Provider.  It processes the URL, and if it was a positive response from
 * the OP, tries to verify that assertion.
 *
 * Example:
 * <code>
 * // First set up some things about your relying party:
 * $realm    = 'http://examplerp.com';
 * $returnTo = $realm . '/relyingparty.php';
 *
 * // Here is an example user supplied identifier
 * $identifier = $_POST['identifier'];
 *
 * // You might want to store it in session for use in verify()
 * $_SESSION['identifier'] = $identifier;
 *
 * // Fire up the OpenID_RelyingParty object
 * $rp = new OpenID_RelyingParty($returnTo, $realm, $identifier);
 *
 * // Here's an example of prepare() usage ...
 * // First, grab your Auth_Request_Object
 * $authRequest = $rp->prepare();
 *
 * // Then, optionally add an extension
 *  $sreg = new OpenID_Extension_SREG11(OpenID_Extension::REQUEST);
 *  $sreg->set('required', 'email');
 *  $sreg->set('optional', 'nickname,gender,dob');
 *
 *  // You'll need to add it to OpenIdAuthRequest
 *  $authRequest->addExtension($sreg);
 * // Optionally get association (from cache in this example)
 *
 * // Optionally make this a checkid_immediate request
 * $auth->setMode(OpenId::MODE_CHECKID_IMMEDIATE);
 *
 * // Send user to the OP
 * header('Location: ' . $auth->getAuthorizeURL());
 * exit;
 *
 *
 *
 *
 * // Now, when they come back, you'll want to verify the claim ...
 *
 * // Assuming your $realm is the host which they came in to, build a Url2
 * // object from this request:
 * $request = new Url2($realm . $_SERVER['REQUEST_URI']);
 *
 * if (!count($_POST)) {
 *     list(, $queryString) = explode('?', $_SERVER['REQUEST_URI']);
 * } else {
 *     $queryString = file_get_contents('php://input');
 * }
 * $message = new \OpenIdMessage($queryString, \OpenIdMessage::FORMAT_HTTP);
 *
 * // Now verify:
 * $result = $rp->verify($request, $message);
 * if ($result->success()) {
 *     echo "success! :)";
 * } else {
 *     echo "failure :(";
 * }
 * </code>
 *
 * @uses      OpenID
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class RelyingParty extends OpenId
{
    /**
     * The user supplied identifier, normalized
     *
     * @see OpenId::normalizeIdentifier()
     * @see __construct()
     * @var string
     */
    protected $normalizedID = null;

    /**
     * The URI used for the openid.return_to parameter
     *
     * @see __construct()
     * @var string
     */
    protected $returnTo = null;

    /**
     * The URI used for the openid.realm paramater
     *
     * @see __construct()
     * @var string
     */
    protected $realm = null;

    /**
     * HTTP_Request2 options
     *
     * @var array
     */
    protected $requestOptions = [
        'follow_redirects' => true,
        'timeout' => 3,
        'connect_timeout' => 3
    ];

    /**
     * Whether or not to use associations
     *
     * @see __construct()
     * @var mixed
     */
    protected $useAssociations = true;

    /**
     * How far off of the current time to allow for nonce checking
     *
     * @see setClockSkew()
     * @var int
     */
    protected $clockSkew = null;

    /**
     * Sets the identifier, returnTo, and realm to be used for messages.  The
     * identifier is normalized before being set.
     *
     * @param string $returnTo The openid.return_to parameter value
     * @param string $realm The openid.realm parameter value
     * @param string|null $identifier The user supplied identifier, defaults to null
     * @throws OpenIdException
     * @see OpenId::normalizeIdentifier
     */
    public function __construct(string $returnTo, string $realm, string $identifier = null)
    {
        $this->returnTo = $returnTo;
        $this->realm = $realm;

        if ($identifier !== null) {
            $this->normalizedID = OpenId::normalizeIdentifier($identifier);
        }
    }

    /**
     * Enables the use of associations (default)
     *
     * @return RelyingParty
     */
    public function enableAssociations()
    {
        $this->useAssociations = true;

        return $this;
    }

    /**
     * Disables the use if associations
     *
     * @return RelyingParty
     */
    public function disableAssociations()
    {
        $this->useAssociations = false;

        return $this;
    }

    /**
     * Sets the clock skew for nonce checking
     *
     * @param int $skew Skew (or timeout) in seconds
     * @return RelyingParty
     */
    public function setClockSkew(int $skew)
    {
        $this->clockSkew = $skew;

        return $this;
    }

    /**
     * Sets the HTTP_Request2 options to use
     *
     * @param array $options Array of HTTP_Request2 options
     *
     * @return RelyingParty for fluent interface
     */
    public function setRequestOptions(array $options)
    {
        $this->requestOptions = $options;

        return $this;
    }

    /**
     * Prepares an OpenIdAuthRequest and returns it.  This process includes
     * performing discovery and optionally creating an association before preparing
     * the OpenIdAuthRequest object.
     *
     * @return OpenIdAuthRequest
     * @throws OpenIdException if no identifier was passed to the constructor
     */
    public function prepare()
    {
        if ($this->normalizedID === null) {
            throw new OpenIdException(
                'No identifier provided',
                OpenIdException::MISSING_DATA
            );
        }

        // Discover
        $discover = $this->getDiscover();
        $serviceEndpoint = $discover->services[0];

        // Associate
        $assocHandle = null;
        if ($this->useAssociations) {
            $uris = $serviceEndpoint->getURIs();
            $opEndpointURL = array_shift($uris);
            $assoc = $this->getAssociation(
                $opEndpointURL, $serviceEndpoint->getVersion()
            );

            if ($assoc instanceof Association) {
                $assocHandle = $assoc->assocHandle;
            }
        }

        // Return OpenIdAuthRequest object
        return new OpenIdAuthRequest(
            $discover, $this->returnTo, $this->realm, $assocHandle
        );
    }

    /**
     * Verifies an assertion response from the OP.  If the openid.mode is error, an
     * exception is thrown.
     *
     * @param Url2 $requestedURL The requested URL (that the user was directed to by the OP) as a Url2 object
     * @param OpenIdMessage $message The OpenIdMessage instance, as extracted from the input (GET or POST)
     * @return OpenIdAssertionResult
     * @throws Exceptions\OpenIdAssociationException
     * @throws Exceptions\OpenIdMessageException
     * @throws Exceptions\StoreException
     * @throws OpenIdException on error or invalid openid.mode
     */
    public function verify(Url2 $requestedURL, OpenIdMessage $message)
    {
        // Unsolicited assertion?
        if ($this->normalizedID === null) {
            $unsolicitedID = $message->get('openid.claimed_id');
            $this->normalizedID = OpenId::normalizeIdentifier($unsolicitedID);
        }

        $mode = $message->get('openid.mode');
        $result = new OpenIdAssertionResult();

        OpenId::setLastEvent(__METHOD__, print_r($message->getArrayFormat(), true));

        switch ($mode) {
            case OpenId::MODE_ID_RES:
                if ($message->get('openid.ns') === null
                    && $message->get('openid.user_setup_url') !== null
                ) {
                    // Negative 1.1 checkid_immediate response
                    $result->setAssertionMethod($mode);
                    $result->setUserSetupURL($message->get('openid.user_setup_url'));
                    return $result;
                }
                break;
            case OpenId::MODE_CANCEL:
            case OpenId::MODE_SETUP_NEEDED:
                $result->setAssertionMethod($mode);
                return $result;
            case OpenId::MODE_ERROR:
                throw new OpenIdException(
                    $message->get('openid.error'),
                    OpenIdException::OPENID_ERROR
                );
            default:
                throw new OpenIdException(
                    'Unknown mode: ' . $mode,
                    OpenIdException::INVALID_VALUE
                );
        }

        $discover = $this->getDiscover();
        $serviceEndpoint = $discover->services[0];
        $URIs = $serviceEndpoint->getURIs();
        $opEndpointURL = array_shift($URIs);
        $assertion = $this->getAssertionObject($message, $requestedURL);

        $result->setDiscover($discover);

        // Check via associations
        if ($this->useAssociations) {
            if ($message->get('openid.invalidate_handle') === null) {
                // Don't fall back to check_authentication
                $result->setAssertionMethod(OpenId::MODE_ASSOCIATE);
                $assoc = $this->getStore()
                    ->getAssociation($opEndpointURL, $message->get('openid.assoc_handle'));
                OpenId::setLastEvent(__METHOD__, print_r($assoc, true));

                if (
                    $assoc instanceof Association &&
                    $assoc->checkMessageSignature($message)
                ) {
                    $result->setAssertionResult(true);
                }

                // If it's not an unsolicited assertion, just return
                if (!isset($unsolicitedID)) {
                    return $result;
                }
            } else {
                // Invalidate handle requested. Delete it and fall back to
                // check_authenticate
                $this->getStore()->deleteAssociation($opEndpointURL);
            }
        }

        // Check via check_authenticate
        $result->setAssertionMethod(OpenId::MODE_CHECK_AUTHENTICATION);
        $result->setCheckAuthResponse($assertion->checkAuthentication());

        if ($result->getCheckAuthResponse()->get('is_valid') == 'true') {
            $result->setAssertionResult(true);
        }

        return $result;
    }

    /**
     * Gets discovered information from cache if it exists, otherwise performs
     * discovery.
     *
     * @return Discover
     *@throws OpenIdException if discovery fails
     * @see Discover::getDiscover()
     */
    protected function getDiscover()
    {
        $discover = Discover::getDiscover(
            $this->normalizedID, $this->getStore(), $this->getRequestOptions()
        );

        if (!$discover instanceof Discover) {
            // @codeCoverageIgnoreStart
            throw new OpenIdException(
                'Unable to discover OP Endpoint URL',
                OpenIdException::DISCOVERY_ERROR
            );
            // @codeCoverageIgnoreEnd
        }

        return $discover;
    }

    /**
     * Gets an association from cache if it exists, otherwise, creates one.
     *
     * @param string $opEndpointURL The OP Endpoint URL to communicate with
     * @param string $version The version of OpenID being used
     * @return Association on success, false on failure
     * @throws Exceptions\OpenIdAssociationException
     * @throws Exceptions\OpenIdMessageException
     * @throws Exceptions\StoreException
     */
    protected function getAssociation(string $opEndpointURL, string $version)
    {
        $assocCache = $this->getStore()->getAssociation($opEndpointURL);

        if ($assocCache instanceof Association) {
            return $assocCache;
        }

        $assoc  = $this->getAssociationRequestObject($opEndpointURL, $version);
        $assoc->setRequestOptions($this->getRequestOptions());
        $result = $assoc->associate();

        if (!$result instanceof Association) {
            return null;
        }

        self::getStore()->setAssociation($result);

        return $result;
    }

    /**
     * Gets a new Request object.  Abstracted for testing.
     *
     * @param string $opEndpointURL The OP endpoint URL to communicate with
     * @param string $version The OpenID version being used
     * @return Request
     * @throws Exceptions\OpenIdAssociationException
     * @throws Exceptions\OpenIdMessageException
     * @see prepare()
     */
    protected function getAssociationRequestObject(string $opEndpointURL, string $version)
    {
        return new Request($opEndpointURL, $version);
    }

    /**
     * Gets an instance of Assertion().  Abstracted for testing purposes.
     *
     * @param OpenIdMessage $message The message passed to verify()
     * @param Url2 $requestedURL The URL requested (redirect from OP)
     * @return Assertion
     * @throws Exceptions\OpenIdAssociationException
     * @see    verify()
     */
    protected function getAssertionObject(OpenIdMessage $message, Url2 $requestedURL)
    {
        return new Assertion($message, $requestedURL, $this->clockSkew);
    }

    /**
     * Return the HTTP_Request2 options
     *
     * @return array Array of HTTP_Request2 options
     */
    public function getRequestOptions()
    {
        return $this->requestOptions;
    }

    /**
     * @return int
     */
    public function getClockSkew(): int
    {
        return $this->clockSkew;
    }
}
