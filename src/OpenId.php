<?php

namespace Pear\OpenId;

use Pear\Http\Request2;
use Pear\Http\Request2\Response;
use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\Observers\Common;
use Pear\OpenId\Store\Store;
use Pear\OpenId\Store\StoreInterface;

/**
 * OpenID
 *
 * PHP Version 5.2.0+
 *
 * Base OpenID class.  Contains common constants and helper static methods, as well
 * as the directRequest() method, which handles direct communications.  It also
 * is a common place to assign your custom Storage class and Observers.
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 * @see       Common
 * @see       Store
 */
class OpenId
{
    /**
     *  OP identifier constants
     */
    const NS_2_0 = 'http://specs.openid.net/auth/2.0';
    const NS_1_1 = 'http://openid.net/signon/1.1';

    const NS_2_0_ID_SELECT = 'http://specs.openid.net/auth/2.0/identifier_select';

    const SERVICE_2_0_SERVER = 'http://specs.openid.net/auth/2.0/server';
    const SERVICE_2_0_SIGNON = 'http://specs.openid.net/auth/2.0/signon';
    const SERVICE_1_1_SIGNON = 'http://openid.net/signon/1.1';
    const SERVICE_1_0_SIGNON = 'http://openid.net/signon/1.0';

    /**
     * A map of which service types (versions) map to which protocol version.  1.0
     * is mapped to 1.1.  This is mostly helpful to see if openid.ns is supported.
     *
     * @var $versionMap
     */
    static public $versionMap = [
        self::SERVICE_2_0_SERVER => self::NS_2_0,
        self::SERVICE_2_0_SIGNON => self::NS_2_0,
        self::SERVICE_1_1_SIGNON => self::NS_1_1,
        self::SERVICE_1_0_SIGNON => self::NS_1_1,
    ];

    /**
     * Supported Association Hash Algorithms (preferred)
     */
    const HASH_ALGORITHM_2_0 = 'SHA256';
    const HASH_ALGORITHM_1_1 = 'SHA1';

    /**
     * OpenID Modes
     */
    const MODE_ASSOCIATE = 'associate';
    const MODE_CHECKID_SETUP = 'checkid_setup';
    const MODE_CHECKID_IMMEDIATE = 'checkid_immediate';
    const MODE_CHECK_AUTHENTICATION = 'check_authentication';
    const MODE_ID_RES = 'id_res';
    const MODE_CANCEL = 'cancel';
    const MODE_SETUP_NEEDED = 'setup_needed';
    const MODE_ERROR = 'error';

    /*
     * Association constants
     */
    const SESSION_TYPE_NO_ENCRYPTION = 'no-encryption';
    const SESSION_TYPE_DH_SHA1 = 'DH-SHA1';
    const SESSION_TYPE_DH_SHA256 = 'DH-SHA256';

    const ASSOC_TYPE_HMAC_SHA1 = 'HMAC-SHA1';
    const ASSOC_TYPE_HMAC_SHA256 = 'HMAC-SHA256';

    /**
     * Instance of StoreInterface
     *
     * @var $store
     * @see setStore()
     */
    static protected $store = null;

    /**
     * Array of attached observers
     *
     * @var $observers
     */
    static protected $observers = [];

    /**
     * Stores the last event
     *
     * @var $lastEvent
     */
    static protected $lastEvent = [
        'name' => 'start',
        'data' => null
    ];

    /**
     * Attaches an observer
     *
     * @param Common $observer Observer object
     * @see OpenID_Observer_Log
     * @return void
     */
    static public function attach(Common $observer)
    {
        foreach (self::$observers as $attached) {
            if ($attached === $observer) {
                return;
            }
        }

        self::$observers[] = $observer;
    }

    /**
     * Detaches the observer
     *
     * @param Common $observer Observer object
     * @return void
     */
    static public function detach(Common $observer)
    {
        foreach (self::$observers as $key => $attached) {
            if ($attached === $observer) {
                unset(self::$observers[$key]);
                return;
            }
        }
    }

    /**
     * Notifies all observers of an event
     *
     * @return void
     */
    static public function notify()
    {
        foreach (self::$observers as $observer) {
            $observer->update(self::getLastEvent());
        }
    }

    /**
     * Sets the last event and notifies the observers
     *
     * @param string $name Name of the event
     * @param mixed  $data The event's data
     * @return void
     */
    static public function setLastEvent(string $name, $data)
    {
        self::$lastEvent = array(
            'name' => $name,
            'data' => $data
        );
        self::notify();
    }

    /**
     * Gets the last event
     *
     * @return array
     */
    static public function getLastEvent()
    {
        return self::$lastEvent;
    }

    /**
     * Sets a custom StoreInterface object
     *
     * @param StoreInterface $store Custom storage instance
     *
     * @return void
     */
    static public function setStore(StoreInterface $store)
    {
        self::$store = $store;
    }

    /**
     * Gets the StoreInterface instance.  If none has been set, then the
     * default store is used (CacheLite).
     *
     * @return StoreInterface
     * @throws Exceptions\StoreException
     */
    static public function getStore()
    {
        if (!self::$store instanceof StoreInterface) {
            self::$store = Store::factory();
        }

        return self::$store;
    }

    /**
     * Sends a direct HTTP request.
     *
     * @param string $url URL to send the request to
     * @param OpenIdMessage $message Contains message contents
     * @param array $options Options to pass to Request2
     *
     * @return Response
     * @throws Exceptions\OpenIdMessageException
     * @throws OpenIdException if send() fails
     * @throws Request2\Exceptions\Exception
     * @throws Request2\Exceptions\LogicException
     * @see getHTTPRequest2Instance()
     */
    public function directRequest(
        $url, OpenIdMessage $message, array $options = array()
    ) {
        $request = $this->getHTTPRequest2Instance();
        $request->setConfig($options);
        $request->setURL($url);
        // Require POST, per the spec
        $request->setMethod(Request2::METHOD_POST);
        $request->setBody($message->getHTTPFormat());

        try {
            return $request->send();
        } catch (\Exception $e) {
            throw new OpenIdException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Instantiates Request2.  Abstracted for testing.
     *
     * @see directRequest()
     * @return Request2
     */
    public static function getHTTPRequest2Instance()
    {
        // @codeCoverageIgnoreStart
        return new Request2();
        // @codeCoverageIgnoreEnd
    }

    /**
     * Returns an array of the 5 XRI globals symbols
     *
     * @return string[]
     */
    static public function getXRIGlobalSymbols()
    {
        return ['=', '@', '+', '$', '!'];
    }

    /**
     * Normalizes an identifier (URI or XRI)
     *
     * @param string $identifier URI or XRI to be normalized
     * @throws OpenIdException on invalid identifier
     * @return string Normalized Identifier.
     */
    static public function normalizeIdentifier(string $identifier)
    {
        // XRI
        if (preg_match('@^xri://@i', $identifier)) {
            return preg_replace('@^xri://@i', '', $identifier);
        }

        if (in_array($identifier[0], self::getXRIGlobalSymbols())) {
            return $identifier;
        }

        // URL
        if (!preg_match('@^http[s]?://@i', $identifier)) {
            $identifier = 'http://' . $identifier;
        }


        if (strlen($identifier) < 8 || strpos($identifier, '/', 8) === false) {
            $identifier .= '/';
        }

        if (filter_var($identifier, FILTER_VALIDATE_URL)) {
            return $identifier;
        }

        throw new OpenIdException(
            'Invalid URI Identifier', OpenIdException::INVALID_VALUE
        );
    }

    /**
     * Resets internal static variables.
     * Useful for unit tests.
     *
     * @return void
     */
    public static function resetInternalData()
    {
        self::$store = null;
        self::$observers = array();
        self::$lastEvent = array(
            'name' => 'start',
            'data' => null,
        );
    }
}
