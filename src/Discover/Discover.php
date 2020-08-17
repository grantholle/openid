<?php

namespace Pear\OpenId\Discover;

use Pear\OpenId\Exceptions\OpenIdDiscoverException;
use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\Extensions\OpenIdExtension;
use Pear\OpenId\OpenId;
use Pear\OpenId\ServiceEndpoints;
use Pear\OpenId\Store\StoreInterface;

/**
 * Discover
 *
 * PHP Version 5.2.0+
 *
 * @category  Auth
 * @package   OpenID
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Discover
 *
 * Implements OpenID discovery ({@link
 * http://openid.net/specs/openid-authentication-2_0.html#discovery 7.3} of the 2.0
 * spec).  Discovery is driver based, and currently supports YADIS discovery
 * (via Services_Yadis), and HTML discovery ({@link Discover_HTML}).  Once
 * completed, it will also support {@link
 * http://www.hueniverse.com/hueniverse/2009/03/the-discovery-protocol-stack.html
 * XRD/LRDD}.
 *
 * Example usage for determining the OP Endpoint URL:
 * <code>
 * $id = 'http://user.example.com';
 *
 * $discover = new Discover($id);
 * $result   = $discover->discover();
 *
 * if (!$result) {
 *     echo "Discovery failed\n";
 * } else {
 *     // Grab the highest priority service, and get it's first URI.
 *     $endpoint      = $discover->services[0];
 *     $opEndpointURL = array_shift($serviceEndpoint->getURIs());
 * }
 * </code>
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class Discover
{
    const TYPE_YADIS = Yadis::class;
    const TYPE_HTML  = HTML::class;

    /**
     * List of supported discover types
     *
     * @var array
     */
    protected $supportedTypes = [
        self::TYPE_YADIS,
        self::TYPE_HTML
    ];

    /**
     * Order that discover should be performed
     *
     * @var array
     */
    static public $discoveryOrder = [
        0  => self::TYPE_YADIS,
        10 => self::TYPE_HTML
    ];

    /**
     * The normalized version of the user supplied identifier
     *
     * @var string
     */
    public $identifier = null;

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
     * Instance of ServiceEndpoints
     *
     * @var ServiceEndpoints
     */
    public $services = null;

    /**
     * Constructor.  Enables libxml internal errors, normalized the identifier.
     *
     * @param mixed $identifier The user supplied identifier
     * @return void
     * @throws OpenIdException
     */
    public function __construct(string $identifier)
    {
        libxml_use_internal_errors(true);
        $this->identifier = OpenId::normalizeIdentifier($identifier);
    }

    /**
     * Gets member variables
     *
     * @param string $name Name of the member variable to get
     *
     * @return mixed The member variable if it exists
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }

    /**
     * Sets the HTTP_Request2 options to use
     *
     * @param array $options Array of HTTP_Request2 options
     *
     * @return Discover for fluent interface
     */
    public function setRequestOptions(array $options)
    {
        $this->requestOptions = $options;

        return $this;
    }

    /**
     * Performs discovery
     *
     * @return bool true on success, false on failure
     */
    public function discover()
    {
        // Sort ascending
        ksort(self::$discoveryOrder);

        foreach (self::$discoveryOrder as $service) {
            $result = null;

            try {
                $discover = $this->_factory($service, $this->identifier);
                $result = $discover->discover();
            } catch (OpenIdDiscoverException $e) {
                continue;
            }

            if ($result instanceof ServiceEndpoints && isset($result[0])) {
                $this->services = $result;
                return true;
            }
        }

        return false;
    }

    /**
     * Provides the standard factory pattern for loading discovery drivers.
     *
     * @param string $discoverType The discovery type (driver) to load
     * @param string $identifier The user supplied identifier
     * @return DiscoverInterface
     * @throws OpenIdDiscoverException
     */
    protected function _factory($discoverType, $identifier)
    {
        $object = new $discoverType($identifier);

        if (!$object instanceof DiscoverInterface) {
            throw new OpenIdDiscoverException(
                'Requested driver does not conform to Discover interface',
                OpenIdException::INVALID_DEFINITION
            );
        }

        $object->setRequestOptions($this->requestOptions);

        return $object;
    }

    /**
     * Determines if discovered information supports a given OpenID extension
     *
     * @param string $extension The name of the extension to check, (SREG10, AX, etc)
     * @return bool
     */
    public function extensionSupported(string $extension)
    {
        $instance = new $extension(OpenIdExtension::REQUEST);

        foreach ($this->services as $service) {
            if (in_array($instance->getNamespace(), $service->getTypes())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Static helper method for retrieving discovered information from cache if it
     * exists, otherwise executing discovery and storing results if they are
     * positive.
     *
     * @param string $id URI Identifier to discover
     * @param StoreInterface $store Instance of OpenID_Store
     * @param array $options Options to pass to HTTP_Request2
     * @return Discover Discover on success, false on failure
     * @throws OpenIdException
     */
    static public function getDiscover(
        string $id, StoreInterface $store, array $options = []
    ) {
        $discoverCache = $store->getDiscover($id);

        if ($discoverCache instanceof Discover) {
            return $discoverCache;
        }

        $discover = new Discover($id);
        $discover->setRequestOptions($options);
        $result = $discover->discover();

        if ($result === false) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }

        $expireTime = null;
        if ($discover->services->getExpiresHeader()) {
            $expireTime = strtotime($discover->services->getExpiresHeader())
                - time();
        }

        $store->setDiscover($discover, $expireTime);

        return $discover;
    }
}
