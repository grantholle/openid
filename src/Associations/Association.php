<?php

namespace Pear\OpenId\Associations;

use Pear\OpenId\Exceptions\OpenIdAssociationException;
use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\OpenId;
use Pear\OpenId\OpenIdMessage;

/**
 * OpenID_Association
 *
 * PHP Version 5.2.0+
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * OpenID_Association
 *
 * A class that represents an association.  This class can be serialized for
 * storage.  It also allows you to add and check signatures of an OpenIdMessage.
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 * @see       OpenID_Association_Request::buildRequest()
 */
class Association
{
    /**
     * URI of the OP Endpoint
     *
     * @var string
     */
    protected $uri = null;

    /**
     * expires_in paramater of the association.  Time is in seconds.
     *
     * @var mixed
     */
    protected $expiresIn = null;

    /**
     * Unix timestamp of when this association was created.
     *
     * @var int
     */
    protected $created = null;

    /**
     * assoc_type parameter of the association.  Should be one of HMAC-SHA1 or
     * HMAC-SHA256
     *
     * @var string
     */
    protected $assocType = null;

    /**
     * assoc_handle parameter of the association.
     *
     * @var string
     */
    protected $assocHandle = null;

    /**
     * In the association response, this is also referred to as the "mac_key", or is
     * derived from the "enc_mac_key" if the session used encryption.
     *
     * @var mixed
     */
    protected $sharedSecret = null;

    /**
     * Required parameters for storing an association.
     *
     * @see __construct()
     * @var array
     */
    protected $requiredParams = [
        'uri',
        'expiresIn',
        'created',
        'assocType',
        'assocHandle',
        'sharedSecret',
    ];

    /**
     * Local list of supported association types.
     *
     * @see $assocType
     * @see __construct()
     * @var array
     */
    protected $supportedTypes = [
        OpenId::ASSOC_TYPE_HMAC_SHA1,
        OpenId::ASSOC_TYPE_HMAC_SHA256
    ];

    /**
     * Validates some association values before setting them as member variables.
     *
     * @param array $params Array of relevant parameters from the association response
     * @throws OpenIdAssociationException if the response is not valid
     * @return void
     */
    public function __construct(array $params)
    {
        // Make sure required params are present
        foreach ($this->requiredParams as $key) {
            if (!isset($params[$key])) {
                throw new OpenIdAssociationException(
                    "Missing parameter: $key",
                    OpenIdException::MISSING_DATA
                );
            }
        }

        // Validate URI
        if (!filter_var($params['uri'], FILTER_VALIDATE_URL)) {
            throw new OpenIdAssociationException(
                "Invalid uri: " . $params['uri'],
                OpenIdException::INVALID_VALUE
            );
        }

        // Validate assocType
        if (!in_array(strtoupper($params['assocType']), $this->supportedTypes)) {
            throw new OpenIdAssociationException(
                "Invalid association type: " . $params['assocType'],
                OpenIdException::INVALID_VALUE
            );
        }

        // Set values
        reset($this->requiredParams);
        foreach ($this->requiredParams as $key) {
            $this->$key = $params[$key];
        }
    }

    /**
     * Allows access to association data via $assoc->name
     *
     * @param string $name Name of the item to get
     *
     * @return mixed Value
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Gets the algo part of the assoc_type (strips 'HMAC-')
     *
     * @return string Algorithm part of the assoc_type handle
     */
    public function getAlgorithm()
    {
        return str_replace('HMAC-', '', $this->assocType);
    }

    /**
     * Checks the signature of an OpenIdMessage using this association
     *
     * @param OpenIdMessage $message Instance of OpenIdMessage
     * @return bool true if the signatures match, false otherwise
     * @throws \Pear\OpenId\Exceptions\OpenIdMessageException
     * @throws OpenIdAssociationException if the handles don't match
     */
    public function checkMessageSignature(OpenIdMessage $message)
    {
        // Make sure the handles match for this OP and response
        if ($this->assocHandle != $message->get('openid.assoc_handle')) {
            throw new OpenIdAssociationException(
                'Association handles do not match',
                OpenIdException::VERIFICATION_ERROR
            );
        }

        // Make sure the OP Endpoints match for this association and response
        if ($this->uri != $message->get('openid.op_endpoint')) {
            throw new OpenIdAssociationException(
                'Endpoint URLs do not match',
                OpenIdException::VERIFICATION_ERROR
            );
        }

        if (!strlen($message->get('openid.signed'))) {
            OpenId::setLastEvent(__METHOD__, 'openid.signed is empty');

            return false;
        }

        // Create a message with only keys in the signature
        $signedOnly = $this->getMessageForSigning($message);

        $signedOnlyDigest = base64_encode($this->hashHMAC($signedOnly));

        $event = [
            'assocHandle' => $this->assocHandle,
            'algo' => $this->getAlgorithm(),
            'secret' => $this->sharedSecret,
            'openid.sig' => $message->get('openid.sig'),
            'signature' => $signedOnlyDigest,
            'SignedKVFormat' => $signedOnly,
            'MessageHTTPFormat' => $message->getHTTPFormat(),
            'phpInput' => file_get_contents('php://input')
        ];

        OpenId::setLastEvent(__METHOD__, print_r($event, true));

        return $signedOnlyDigest == $message->get('openid.sig');
    }

    /**
     * Returns a KV formatted message for signing based on the contents of the
     * openid.signed key.  This allows for duplicate entries, which
     * OpenIdMessage::getKVFormat() doesn't.  (Yahoo! uses duplicates)
     *
     * @param OpenIdMessage $message An instance of the OpenIdMessage you want to sign
     * @return string The openid.signed items in KV form
     */
    public function getMessageForSigning(OpenIdMessage $message)
    {
        $list = explode(',', $message->get('openid.signed'));

        $signedOnly = '';
        foreach ($list as $key) {
            $signedOnly .= "$key:" . $message->get('openid.' . $key) . "\n";
        }
        return $signedOnly;
    }

    /**
     * Signs an OpenIdMessage instance
     *
     * @param OpenIdMessage $message Message to be signed
     * @return void
     * @throws \Pear\OpenId\Exceptions\OpenIdMessageException
     * @throws OpenIdAssociationException if the message is already signed, or the association handles do not match
     */
    public function signMessage(OpenIdMessage $message)
    {
        if ($message->get('openid.sig') !== null
            || $message->get('openid.signed') !== null
        ) {
            throw new OpenIdAssociationException(
                'This message appears to be already signed',
                OpenIdException::ALREADY_SIGNED
            );
        }

        // Make sure the handles match for this OP and response
        if ($this->assocHandle != $message->get('openid.assoc_handle')) {
            throw new OpenIdAssociationException(
                'Association handles do not match',
                OpenIdException::VERIFICATION_ERROR
            );
        }

        $keys = array('signed');
        foreach ($message->getArrayFormat() as $key => $val) {
            if (strncmp('openid.', $key, 7) == 0) {
                $keys[] = substr($key, 7);
            }
        }
        sort($keys);
        $message->set('openid.signed', implode(',', $keys));

        $signedMessage = new OpenIdMessage;

        foreach ($keys as $key) {
            $signedMessage->set($key, $message->get('openid.' . $key));
        }

        $rawSignature = $this->hashHMAC($signedMessage->getKVFormat());

        $message->set('openid.sig', base64_encode($rawSignature));
    }

    /**
     * Gets a an HMAC hash of an OpenIdMessage using this association.
     *
     * @param string $message The message format of the items to hash
     * @return string The HMAC hash
     */
    protected function hashHMAC(string $message)
    {
        return hash_hmac(
            $this->getAlgorithm(),
            $message,
            base64_decode($this->sharedSecret),
            true
        );
    }
}
