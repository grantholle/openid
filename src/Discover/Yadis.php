<?php

namespace Pear\OpenId\Discover;

use Pear\OpenId\Exceptions\OpenIdDiscoverException;
use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\OpenId;
use Pear\OpenId\ServiceEndpoint;
use Pear\OpenId\ServiceEndpoints;
use Pear\Services\Yadis\Exceptions\YadisException;
use Pear\Services\Yadis\Yadis as YadisService;

/**
 * Discover_Yadis
 *
 * PHP Version 5.2.0+
 *
 * @category  Auth
 * @package   OpenID
 * @uses      \Pear\OpenId\Discover\Discover_Interface
 * @uses      \Pear\OpenId\Discover\Discover
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Implements YADIS discovery
 *
 * @category  Auth
 * @package   OpenID
 * @uses      \Pear\OpenId\Discover\Discover_Interface
 * @uses      \Pear\OpenId\Discover\Discover
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 * @see       \Pear\Services\Yadis\Yadis
 */
class Yadis extends Discover implements DiscoverInterface
{
    /**
     * The \Pear\Services\Yadis\Yadis instance
     *
     * @var YadisService
     */
    protected $yadis = null;

    /**
     * Performs YADIS discovery
     *
     * @return ServiceEndpoints
     * @throws OpenIdDiscoverException on error
     * @throws \Pear\Http\Request2\Exceptions\Exception
     * @throws \Pear\Http\Request2\Exceptions\LogicException
     * @throws \Pear\Http\Request2\Exceptions\Request2Exception
     */
    public function discover()
    {
        try {
            try {
                $discoveredServices = $this->getServicesYadis()->discover();
            } catch (YadisException $e) {
                $message = 'Yadis protocol could not locate a valid XRD document';

                if ($e->getMessage() === $message) {
                    return null;
                }

                throw $e;
            }

            if (!$discoveredServices->valid()) {
                return null;
            }

            $service = new ServiceEndpoints(
                $this->getServicesYadis()->getYadisId()
            );

            foreach ($discoveredServices as $discoveredService) {
                $types = $discoveredService->getTypes();
                if (array_key_exists($types[0], OpenId::$versionMap)) {
                    $localID = null;
                    $localIDs = $discoveredService->getElements('xrd:LocalID');

                    if (!empty($localIDs[0])) {
                        $localID = $localIDs[0];
                    }

                    $opEndpoint = new ServiceEndpoint();
                    $opEndpoint->setVersion($types[0]);

                    // Choose OpenID 2.0 if it's available
                    if (count($types) > 1) {
                        foreach ($types as $type) {
                            if (
                                $type == OpenID::SERVICE_2_0_SERVER ||
                                $type == OpenID::SERVICE_2_0_SIGNON
                            ) {
                                $opEndpoint->setVersion($type);
                                break;
                            }
                        }
                    }

                    $opEndpoint->setTypes($types);
                    $opEndpoint->setURIs($discoveredService->getUris());
                    $opEndpoint->setLocalID($localID);
                    $opEndpoint->setSource(Discover::TYPE_YADIS);
                    $service->addService($opEndpoint);
                }
            }

            // Add in expires information
            $service->setExpiresHeader(
                $this->getServicesYadis()
                    ->getHTTPResponse()
                    ->getHeader('Expires')
            );

            return $service;
        } catch (YadisException $e) {
            // Add logging or observer?
            throw new OpenIdDiscoverException(
                $e->getMessage(),
                OpenIdException::DISCOVERY_ERROR
            );
        }

        // Did the identifier even respond to the initial HTTP request?
        if ($this->yadis->getUserResponse() === false) {
            throw new OpenIdDiscoverException(
                'No response from identifier',
                OpenIdException::HTTP_ERROR
            );
        }
    }

    /**
     * Gets the \Pear\Services\Yadis\Yadis instance.  Abstracted for testing.
     *
     * @return YadisService
     * @throws YadisException
     */
    public function getServicesYadis()
    {
        if ($this->yadis === null) {
            $this->yadis = new YadisService($this->identifier);
            $this->yadis->setHttpRequestOptions($this->requestOptions);
            $this->yadis->addNamespace('openid', 'http://openid.net/xmlns/1.0');
        }

        return $this->yadis;
    }
}
