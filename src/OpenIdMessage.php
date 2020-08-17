<?php

namespace Pear\OpenId;

use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\Exceptions\OpenIdMessageException;
use Pear\OpenId\Extensions\OpenIdExtension;

/**
 * OpenIdMessage
 *
 * A class that handles any OpenID protocol messages, as described in section 4.1 of
 * the {@link http://openid.net/specs/openid-authentication-2_0.html#anchor4
 * OpenID 2.0 spec}.  You can set or get messages in one of 3 formats:  Key Value
 * (KV), Array, or HTTP.  KV is described in the spec (4.1.1 of the 2.0 spec), HTTP
 * is urlencoded key value pairs, as you would see them in a query string or an HTTP
 * POST body.
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class OpenIdMessage
{
    const FORMAT_KV = 'KV';
    const FORMAT_HTTP = 'HTTP';
    const FORMAT_ARRAY = 'ARRAY';

    protected $validFormats = [
        self::FORMAT_KV,
        self::FORMAT_HTTP,
        self::FORMAT_ARRAY,
    ];

    protected $data = [];

    /**
     * Optionally instanciates this object with the contents of an OpenID message.
     *
     * @param mixed $message Message contents
     * @param string $format Source message format (KV, HTTP, or ARRAY)
     * @return void
     * @throws OpenIdMessageException
     */
    public function __construct($message = null, string $format = self::FORMAT_ARRAY)
    {
        if ($message !== null) {
            $this->setMessage($message, $format);
        }
    }

    /**
     * Gets the value of any key in this message.
     *
     * @param string $name Name of key
     * @return mixed Value of key if set, defaults to null
     */
    public function get(string $name)
    {
        return isset($this->data[$name])
            ? $this->data[$name]
            : null;
    }

    /**
     * Sets a message key value.
     *
     * @param string $name Key name
     * @param mixed $val Key value
     * @return void
     * @throws OpenIdMessageException
     */
    public function set(string $name, $val)
    {
        if ($name == 'openid.ns' && $val !== OpenID::NS_2_0) {
            throw new OpenIdMessageException(
                'Invalid openid.ns value: ' . $val,
                OpenIdException::INVALID_VALUE
            );
        }

        $this->data[$name] = $val;
    }

    /**
     * Deletes a key from a message
     *
     * @param string $name Key name
     * @return void
     */
    public function delete(string $name)
    {
        unset($this->data[$name]);
    }

    /**
     * Gets the current message in KV format
     *
     * @return string
     * @throws OpenIdMessageException
     * @see getMessage()
     */
    public function getKVFormat()
    {
        return $this->getMessage(self::FORMAT_KV);
    }

    /**
     * Gets the current message in HTTP (url encoded) format
     *
     * @return string
     * @throws OpenIdMessageException
     * @see getMessage()
     */
    public function getHTTPFormat()
    {
        return $this->getMessage(self::FORMAT_HTTP);
    }

    /**
     * Gets the current message in ARRAY format
     *
     * @return array
     * @throws OpenIdMessageException
     * @see getMessage()
     */
    public function getArrayFormat()
    {
        return $this->getMessage(self::FORMAT_ARRAY);
    }

    /**
     * Gets the message in one of three formats:
     *
     *  OpenIdMessage::FORMAT_ARRAY (default)
     *  OpenIdMessage::FORMAT_KV (KV pairs, OpenID response format)
     *  OpenIdMessage::FORMAT_HTTP (url encoded pairs, for use in a query string)
     *
     * @param string $format One of the above three formats
     * @return mixed array, kv string, or url query string parameters
     *@throws OpenIdMessageException When passed an invalid format argument
     */
    public function getMessage(string $format = self::FORMAT_ARRAY)
    {
        if ($format === self::FORMAT_ARRAY) {
            return $this->data;
        }

        if ($format === self::FORMAT_HTTP) {
            $pairs = [];

            foreach ($this->data as $k => $v) {
                $pairs[] = urlencode($k) . '=' . urlencode($v);
            }

            return implode('&', $pairs);
        }

        if ($format === self::FORMAT_KV) {
            $message = '';

            foreach ($this->data as $k => $v) {
                $message .= "$k:$v\n";
            }

            return $message;
        }

        throw new OpenIdMessageException(
            'Invalid format: ' . $format,
            OpenIdException::INVALID_VALUE
        );
    }

    /**
     * Sets message contents.  Wipes out any existing message contents.  Default
     * source format is Array, but you can also use KV and HTTP formats.
     *
     * @param mixed $message Source message
     * @param mixed $format Source message format (OpenIdMessage::FORMAT_KV, OpenIdMessage::FORMAT_ARRAY, OpenIdMessage::FORMAT_HTTP)
     * @return void
     * @throws OpenIdMessageException
     */
    public function setMessage($message, $format = self::FORMAT_ARRAY)
    {
        if (!in_array($format, $this->validFormats)) {
            throw new OpenIdMessageException(
                'Invalid format: ' . $format,
                OpenIdException::INVALID_VALUE
            );
        }

        // Flush current data
        $this->data = [];

        if ($format == self::FORMAT_ARRAY) {
            foreach ($message as $k => $v) {
                $this->set($k, $v);
            }
            return;
        }

        if ($format == self::FORMAT_KV) {
            $lines = explode("\n", $message);
            foreach ($lines as $line) {
                if ($line == '') {
                    continue;
                }
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $this->set($key, $value);
                }
            }
            return;
        }

        if ($format == self::FORMAT_HTTP) {
            $array = explode('&', $message);
            foreach ($array as $pair) {
                $parts = explode('=', $pair, 2);
                if (count($parts) < 2) {
                    continue;
                }
                $this->set(urldecode($parts[0]), urldecode($parts[1]));
            }
        }
    }

    /**
     * Adds an extension to an OpenIdMessage object.
     *
     * @param OpenIdExtension $extension Instance of OpenIdExtension
     * @return OpenIdMessage
     * @throws Exceptions\OpenIdExtensionException
     * @throws OpenIdMessageException
     * @see OpenIdExtension
     */
    public function addExtension(OpenIdExtension $extension)
    {
        $extension->toMessage($this);

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
