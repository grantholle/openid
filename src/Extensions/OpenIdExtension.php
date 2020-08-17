<?php

namespace Pear\OpenId\Extensions;

use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\Exceptions\OpenIdExtensionException;
use Pear\OpenId\Exceptions\OpenIdMessageException;
use Pear\OpenId\OpenIdMessage;

/**
 * OpenID_Extension
 *
 * Base class for creating OpenID message extensions.
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
abstract class OpenIdExtension
{
    const REQUEST  = 'request';
    const RESPONSE = 'response';

    /**
     *  @var array Array of reserved message keys
     */
    static protected $reserved = [
        'assoc_handle',
        'assoc_type',
        'claimed_id',
        'contact',
        'delegate',
        'dh_consumer_public',
        'dh_gen',
        'dh_modulus',
        'error',
        'identity',
        'invalidate_handle',
        'mode',
        'ns',
        'op_endpoint',
        'openid',
        'realm',
        'reference',
        'response_nonce',
        'return_to',
        'server',
        'session_type',
        'sig',
        'signed',
        'trust_root ',
    ];

    /**
     * Whether or not to use namespace alias assignments (for SREG 1.0 mostly)
     *
     * @var bool
     */
    protected $useNamespaceAlias = true;

    /**
     * Type of message - 'request' or 'response'
     *
     * @var string
     */
    protected $type = self::REQUEST;

    /**
     * Namespace URI
     *
     * @see getNamespace()
     * @var string
     */
    protected $namespace = null;

    /**
     * Namespace text, "sreg" or "ax" for example
     *
     * @var string
     */
    protected $alias = null;

    /**
     * Keys appropriate for a request.  Leave empty to allow any keys.
     *
     * @var array
     */
    protected $requestKeys = [];

    /**
     * Keys appropriate for a response.  Leave empty to allow any keys.
     *
     * @var array
     */
    protected $responseKeys = [];

    /**
     * values
     *
     * @var array
     */
    protected $values = [];

    /**
     * Sets the message type, request or response
     *
     * @param string $type Type response or type request
     * @param OpenIdMessage|null $message Optional message to get values from
     * @throws OpenIdMessageException|OpenIdExtensionException
     */
    public function __construct(string $type, OpenIdMessage $message = null)
    {
        if ($type != self::REQUEST && $type != self::RESPONSE) {
            throw new OpenIdExtensionException(
                'Invalid message type: ' . $type,
                OpenIdException::INVALID_VALUE
            );
        }
        $this->type = $type;

        if ($message !== null) {
            $this->values = $this->fromMessageResponse($message);
        }
    }

    /**
     * Sets a key value pair
     *
     * @param string $key   Key
     * @param string $value Value
     * @return OpenIdExtension
     * @throws OpenIdExtensionException on invalid key argument
     */
    public function set(string $key, string $value)
    {
        $keys = $this->responseKeys;
        if ($this->type == self::REQUEST) {
            $keys = $this->requestKeys;
        }

        if (count($keys) && !in_array($key, $keys)) {
            throw new OpenIdExtensionException(
                'Invalid key: ' . $key,
                OpenIdException::INVALID_VALUE
            );
        }

        $this->values[$key] = $value;

        return $this;
    }

    /**
     * Gets a key's value
     *
     * @param string $key Key
     *
     * @return mixed Key's value
     */
    public function get($key)
    {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        }
        return null;
    }

    /**
     * Adds the extension contents to an OpenIDMessage
     *
     * @param OpenIdMessage $message Message to add the extension contents to
     * @return void
     * @throws OpenIdExtensionException
     * @throws OpenIdMessageException
     */
    public function toMessage(OpenIdMessage $message)
    {
        // Make sure we have a valid alias name
        if (empty($this->alias) || in_array($this->alias, self::$reserved)) {
            throw new OpenIdExtensionException(
                'Invalid extension alias' . $this->alias,
                OpenIdException::INVALID_VALUE
            );
        }

        $namespaceAlias = 'openid.ns.' . $this->alias;

        // Make sure the alias doesn't collide
        if ($message->get($namespaceAlias) !== null) {
            throw new OpenIdExtensionException(
                'Extension alias ' . $this->alias . ' is already set',
                OpenIdException::INVALID_VALUE
            );
        }

        // Add alias assignment? (SREG 1.0 Doesn't use one)
        if ($this->useNamespaceAlias) {
            $message->set($namespaceAlias, $this->namespace);
        }

        foreach ($this->values as $key => $value) {
            $message->set('openid.' . $this->alias . '.' . $key, $value);
        }
    }

    /**
     * Extracts extension contents from an OpenIDMessage
     *
     * @param OpenIdMessage $message OpenIDMessage to extract the extension contents from
     * @return array An array of the extension's key/value pairs
     * @throws OpenIdMessageException
     */
    public function fromMessageResponse(OpenIdMessage $message)
    {
        $values = [];
        $alias  = null;

        foreach ($message->getArrayFormat() as $ns => $value) {
            if (!preg_match('/^openid[.]ns[.]([^.]+)$/', $ns, $matches)) {
                continue;
            }
            $nsFromMessage = $message->get('openid.ns.' . $matches[1]);
            if ($nsFromMessage !== null && $nsFromMessage != $this->namespace) {
                continue;
            }
            $alias = $matches[1];
        }

        if ($alias === null) {
            return $values;
        }

        if (count($this->responseKeys)) {
            // Only use allowed response keys
            foreach ($this->responseKeys as $key) {
                $value = $message->get('openid.' . $alias . '.' . $key);
                if ($value !== null) {
                    $values[$key] = $value;
                }
            }
        } else {
            // Just grab all message components
            foreach ($message->getArrayFormat() as $key => $value) {
                if (preg_match('/^openid[.]' . $alias . '[.](.*+)$/', $key, $matches)) {
                    $values[$matches[1]] = $value;
                }
            }
        }
        return $values;
    }

    /**
     * Gets the namespace of this extension
     *
     * @see $namespace
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }
}
