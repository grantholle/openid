<?php
/**
 * OpenID_Discover_Yadis
 *
 * PHP Version 5.2.0+
 *
 * @category  Auth
 * @package   OpenID
 * @uses      OpenID_Discover_Interface
 * @uses      OpenID_Discover
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Required files
 */
require_once 'OpenID/Discover.php';
require_once 'OpenID/Discover/Interface.php';
require_once 'OpenID/ServiceEndpoint.php';
require_once 'OpenID/ServiceEndpoints.php';

/**
 * Implements YADIS discovery
 *
 * @category  Auth
 * @package   OpenID
 * @uses      OpenID_Discover_Interface
 * @uses      OpenID_Discover
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 * @see       Services_Yadis
 */
class OpenID_Discover_Yadis
extends OpenID_Discover
implements OpenID_Discover_Interface
{
    /**
     * The Services_Yadis instance
     *
     * @var Services_Yadis
     */
    protected $yadis = null;

    /**
     * Performs YADIS discovery
     *
     * @throws OpenID_Discover_Exception on error
     * @return OpenID_ServiceEndpoints
     */
    public function discover()
    {
        try {
            try {
                $discoveredServices = $this->getServicesYadis()->discover();
            } catch (Services_Yadis_Exception $e) {
                $message = 'Yadis protocol could not locate a valid XRD document';

                if ($e->getMessage() == $message) {
                    return false;
                }
                throw $e;
            }

            if (!$discoveredServices->valid()) {
                return false;
            }

            $service = new OpenID_ServiceEndpoints(
                $this->getServicesYadis()->getYadisId()
            );

            foreach ($discoveredServices as $discoveredService) {
                $types = $discoveredService->getTypes();
                if (array_key_exists($types[0], OpenID::$versionMap)) {

                    $version  = $types[0];
                    $localID  = null;
                    $localIDs = $discoveredService->getElements('xrd:LocalID');

                    if (!empty($localIDs[0])) {
                        $localID = $localIDs[0];
                    }

                    // Modify version if appropriate
                    if ($localID && $version == OpenID::SERVICE_2_0_SERVER) {
                        $version = OpenID::SERVICE_2_0_SIGNON;
                    }

                    $opEndpoint = new OpenID_ServiceEndpoint();
                    $opEndpoint->setVersion($types[0]);
                    // Choose OpenID 2.0 if it's available
                    if (count($types) > 1) {
                        foreach ($types as $type) {
                            if ($type == OpenID::SERVICE_2_0_SERVER
                                || $type == OpenID::SERVICE_2_0_SIGNON
                            ) {
                                $opEndpoint->setVersion($type);
                                break;
                            }
                        }
                    }
                    $opEndpoint->setTypes($types);
                    $opEndpoint->setURIs($discoveredService->getUris());
                    $opEndpoint->setLocalID($localID);
                    $opEndpoint->setSource(OpenID_Discover::TYPE_YADIS);

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

        } catch (Services_Yadis_Exception $e) {
            // Add logging or observer?
            throw new OpenID_Discover_Exception(
                $e->getMessage(),
                OpenID_Exception::DISCOVERY_ERROR
            );
        }

        // Did the identifier even respond to the initial HTTP request?
        if ($this->yadis->getUserResponse() === false) {
            throw new OpenID_Discover_Exception(
                'No response from identifier',
                OpenID_Exception::HTTP_ERROR
            );
        }
    }

    /**
     * Gets the Services_Yadis instance.  Abstracted for testing.
     *
     * @return Services_Yadis
     */
    public function getServicesYadis()
    {
        if ($this->yadis === null) {
            $this->yadis = new Services_Yadis($this->identifier);
            $this->yadis->setHttpRequestOptions($this->requestOptions);
            $this->yadis->addNamespace('openid', 'http://openid.net/xmlns/1.0');
        }

        return $this->yadis;
    }
}

?>
