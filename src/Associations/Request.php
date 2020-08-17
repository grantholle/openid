<?php

namespace Pear\OpenId\Associations;

use Pear\Crypt\DiffieHellman as CryptDiffieHellman;
use Pear\OpenId\Exceptions\OpenIdAssociationException;
use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\Exceptions\OpenIdMessageException;
use Pear\OpenId\OpenId;
use Pear\OpenId\OpenIdMessage;

/**
 * Association_Request
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Association_Request
 *
 * Request object for establishing OpenID Associations.
 *
 * @uses      OpenID
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class Request extends OpenId
{
    /**
     * OpenID provider endpoint URL
     *
     * @var string
     */
    protected $opEndpointURL = null;

    /**
     * Contains contents of the association request
     *
     * @var OpenIdMessage
     */
    protected $message = null;

    /**
     * Version of OpenID in use.  This determines which algorithms we can use.
     *
     * @var string
     */
    protected $version = null;

    /**
     * The association request response in array format
     *
     * @var array
     * @see getResponse()
     */
    protected $response = [];

    /**
     * Optional instance of DiffieHellman
     *
     * @var CryptDiffieHellman
     */
    protected $cdh = null;

    /**
     * DiffieHellman instance
     *
     * @var DiffieHellman
     */
    protected $dh = null;

    /**
     * HTTP_Request2 options
     *
     * @var array
     */
    protected $requestOptions = [];

    /**
     * Sets the arguments passed in, as well as creates the request message.
     *
     * @param string $opEndpointURL URL of OP Endpoint
     * @param string $version Version of OpenID in use
     * @param CryptDiffieHellman|null $cdh Custom DiffieHellman instance
     * @throws OpenIdMessageException
     * @throws OpenIdAssociationException
     */
    public function __construct(
        $opEndpointURL, $version, CryptDiffieHellman $cdh = null
    ) {
        if (!array_key_exists($version, OpenID::$versionMap)) {
            throw new OpenIdAssociationException(
                'Invalid version',
                OpenIdException::INVALID_VALUE
            );
        }

        $this->version = $version;
        $this->opEndpointURL = $opEndpointURL;
        $this->message = new OpenIdMessage;

        if ($cdh) {
            $this->cdh = $cdh;
        }

        // Set defaults
        $this->message->set('openid.mode', OpenID::MODE_ASSOCIATE);

        if (OpenID::$versionMap[$version] === OpenID::NS_2_0) {
            $this->message->set('openid.ns', OpenID::NS_2_0);
            $this->message->set('openid.assoc_type', self::ASSOC_TYPE_HMAC_SHA256);
            $this->message->set('openid.session_type', self::SESSION_TYPE_DH_SHA256);
        } else {
            $this->message->set('openid.assoc_type', self::ASSOC_TYPE_HMAC_SHA1);
            $this->message->set('openid.session_type', self::SESSION_TYPE_DH_SHA1);
        }
    }

    /**
     * Sends the association request.  Loops over errors and adapts to
     * 'unsupported-type' responses.
     *
     * @return mixed Association on success, false on failure
     * @throws OpenIdAssociationException
     * @throws OpenIdMessageException
     * @see buildAssociation()
     * @see sendAssociationRequest()
     */
    public function associate()
    {
        $count = 0;

        while ($count < 2) {
            // Easier to operate on array format here
            $response = $this->sendAssociationRequest()->getArrayFormat();

            if (isset($response['assoc_handle'])) {
                $this->response = $response;
                return $this->buildAssociation($response);
            }

            if (isset($response['mode'])
                && $response['mode'] == OpenID::MODE_ERROR
                && isset($response['error_code'])
                && $response['error_code'] == 'unsupported-type'
            ) {
                if (isset($response['assoc_type'])) {
                    $this->setAssociationType($response['assoc_type']);
                }
                if (isset($response['session_type'])) {
                    $this->setSessionType($response['session_type']);
                }
            }
            $count++;
        }

        return false;
    }

    /**
     * Build the Association class based on the association response
     *
     * @param array $response Association response in array format
     * @return Association
     * @throws OpenIdAssociationException
     * @throws CryptDiffieHellman\DiffieHellmanException
     * @see associate()
     */
    protected function buildAssociation(array $response)
    {
        $params = [];
        $params['created'] = time();
        $params['expiresIn'] = $response['expires_in'];
        $params['uri'] = $this->opEndpointURL;
        $params['assocType'] = $this->getAssociationType();
        $params['assocHandle'] = $response['assoc_handle'];

        if ($this->getSessionType() === self::SESSION_TYPE_NO_ENCRYPTION) {
            if (!isset($response['mac_key'])) {
                throw new OpenIdAssociationException(
                    'Missing mac_key in association response',
                    OpenIdException::MISSING_DATA
                );
            }

            $params['sharedSecret'] = $response['mac_key'];
        } else {
            $this->getDH()->getSharedSecret($response, $params);
        }

        return new Association($params);
    }

    /**
     * Actually sends the assocition request to the OP Endpoint URL.
     *
     * @return OpenIdMessage
     * @throws OpenIdMessageException
     * @throws CryptDiffieHellman\DiffieHellmanException
     * @throws \Pear\Http\Request2\Exceptions\Request2Exception
     * @see associate()
     */
    protected function sendAssociationRequest()
    {
        if ($this->message->get('openid.session_type') === self::SESSION_TYPE_NO_ENCRYPTION) {
            $this->message->delete('openid.dh_consumer_public');
            $this->message->delete('openid.dh_modulus');
            $this->message->delete('openid.dh_gen');
        } else {
            $this->initDH();
        }

        $response = $this->directRequest(
            $this->opEndpointURL, $this->message, $this->getRequestOptions()
        );

        $message  = new OpenIdMessage(
            $response->getBody(),
            OpenIdMessage::FORMAT_KV
        );
        OpenID::setLastEvent(__METHOD__, print_r($message->getArrayFormat(), true));

        return $message;
    }

    /**
     * Gets the last association response
     *
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Initialize the diffie-hellman parameters for the association request.
     *
     * @return void
     * @throws CryptDiffieHellman\DiffieHellmanException
     * @throws OpenIdMessageException
     */
    protected function initDH()
    {
        $this->getDH()->init();
    }

    /**
     * Gets an instance of DiffieHellman.  If one is not already
     * instanciated, a new one is returned.
     *
     * @return DiffieHellman
     */
    protected function getDH()
    {
        if (!$this->dh) {
            $this->dh = new DiffieHellman(
                $this->message, $this->cdh
            );
        }

        return $this->dh;
    }

    /**
     * Sets he association type for the request.  Can be sha1 or sha256.
     *
     * @param string $type sha1 or sha256
     * @throws OpenIdAssociationException|OpenIdMessageException on invalid type
     * @return void
     */
    public function setAssociationType(string $type)
    {
        switch ($type) {
        case self::ASSOC_TYPE_HMAC_SHA1:
        case self::ASSOC_TYPE_HMAC_SHA256:
            $this->message->set('openid.assoc_type', $type);
            break;
        default:
            throw new OpenIdAssociationException(
                "Invalid assoc_type: $type",
                OpenIdException::INVALID_VALUE
            );
        }
    }

    /**
     * Gets the current association type
     *
     * @return mixed
     */
    public function getAssociationType()
    {
        return $this->message->get('openid.assoc_type');
    }

    /**
     * Sets the session type.  Can be sha1, sha256, or no-encryption
     *
     * @param string $type sha1, sha256, or no-encryption
     * @return void
     *@throws OpenIdAssociationException|OpenIdMessageException on invalid type, or if you set no-encryption for an OP URL that doesn't support HTTPS
     */
    public function setSessionType(string $type)
    {
        switch ($type) {
            case self::SESSION_TYPE_NO_ENCRYPTION:
                // Make sure we're using SSL
                if (!preg_match('@^https://@i', $this->opEndpointURL)) {
                    throw new OpenIdAssociationException(
                        'Un-encrypted sessions require HTTPS',
                        OpenIdException::HTTPS_REQUIRED
                    );
                }
                $this->message->set(
                    'openid.session_type',
                    self::SESSION_TYPE_NO_ENCRYPTION
                );
                break;
            case self::SESSION_TYPE_DH_SHA1:
            case self::SESSION_TYPE_DH_SHA256:
                $this->message->set('openid.session_type', $type);
                break;
            default:
                throw new OpenIdAssociationException(
                    "Invalid session_type: $type",
                    OpenIdException::INVALID_VALUE
                );
        }
    }

    /**
     * Gets the current session type
     *
     * @return string Current session type (sha1, sha256, or no-encryption)
     */
    public function getSessionType()
    {
        return $this->message->get('openid.session_type');
    }

    /**
     * Gets the OP Endpoint URL
     *
     * @return string OP Endpoint URL
     */
    public function getEndpointURL()
    {
        return $this->opEndpointURL;
    }

    /**
     * Sets the HTTP_Request2 options to use
     *
     * @param array $options Array of HTTP_Request2 options
     *
     * @return self Fluent interface
     */
    public function setRequestOptions(array $options)
    {
        $this->requestOptions = $options;
        return $this;
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
}
