<?php

namespace Pear\OpenId\Discover;

use DOMDocument;
use DOMXPath;
use Pear\Http\Request2;
use Pear\Http\Request2\Response;
use Pear\OpenId\Exceptions\OpenIdDiscoverException;
use Pear\OpenId\Exceptions\OpenIdException;
use Pear\OpenId\OpenId;
use Pear\OpenId\ServiceEndpoint;
use Pear\OpenId\ServiceEndpoints;

/**
 * Discover_HTML
 *
 * PHP Version 5.2.0+
 *
 * @category  Auth
 * @package   OpenID
 * @uses      \Pear\OpenId\Discover\Discover
 * @uses      \Pear\OpenId\Discover\Discover_Interface
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Implements HTML discovery
 *
 * @category  Auth
 * @package   OpenID
 * @uses      \Pear\OpenId\Discover\Discover
 * @uses      \Pear\OpenId\Discover\Discover_Interface
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class HTML extends Discover implements DiscoverInterface
{
    /**
     * The normalized identifier
     *
     * @var string
     */
    public $identifier = null;

    /**
     * Local storage of the Request2 object
     *
     * @var Request2
     */
    protected $request = null;

    /**
     * Local storage of the Response object
     *
     * @var Response
     */
    protected $response = null;

    /**
     * Performs HTML discovery.
     *
     * @return ServiceEndpoints
     * @throws OpenIdDiscoverException on error
     * @throws Request2\Exceptions\Exception
     * @throws Request2\Exceptions\LogicException
     * @throws Request2\Exceptions\Request2Exception
     */
    public function discover()
    {
        $response = $this->sendRequest();

        $dom = new DOMDocument();
        $dom->loadHTML($response);

        $xPath = new DOMXPath($dom);
        $query = "/html/head/link[contains(@rel,'openid')]";
        $links = $xPath->query($query);

        $results = [
            'openid2.provider' => [],
            'openid2.local_id' => [],
            'openid.server' => [],
            'openid.delegate' => [],
        ];

        foreach ($links as $link) {
            $rels = explode(' ', $link->getAttribute('rel'));
            foreach ($rels as $rel) {
                if (array_key_exists($rel, $results)) {
                    $results[$rel][] = $link->getAttribute('href');
                }
            }
        }

        $services = $this->buildServiceEndpoint($results);
        $services->setExpiresHeader($this->getExpiresHeader());
        return $services;
    }

    /**
     * Gets the Expires header from the response object
     *
     * @return string
     */
    protected function getExpiresHeader()
    {
        // @codeCoverageIgnoreStart
        return $this->response->getHeader('Expires');
        // @codeCoverageIgnoreEnd
    }

    /**
     * Builds the service endpoint
     *
     * @param array $results Array of items discovered via HTML
     *
     * @return ServiceEndpoints
     * @throws OpenIdDiscoverException
     */
    protected function buildServiceEndpoint(array $results)
    {
        if (count($results['openid2.provider'])) {
            $version = OpenId::SERVICE_2_0_SIGNON;
            if (count($results['openid2.local_id'])) {
                $localID = $results['openid2.local_id'][0];
            }
            $endpointURIs = $results['openid2.provider'];
        } elseif (count($results['openid.server'])) {
            $version = OpenId::SERVICE_1_1_SIGNON;
            $endpointURIs = $results['openid.server'];
            if (count($results['openid.delegate'])) {
                $localID = $results['openid.delegate'][0];
            }
        } else {
            throw new OpenIdDiscoverException(
                'Discovered information does not conform to spec',
                OpenIdException::MISSING_DATA
            );
        }

        $opEndpoint = new ServiceEndpoint();
        $opEndpoint->setVersion($version);
        $opEndpoint->setTypes([$version]);
        $opEndpoint->setURIs($endpointURIs);
        $opEndpoint->setSource(Discover::TYPE_HTML);

        if (isset($localID)) {
            $opEndpoint->setLocalID($localID);
        }

        return new ServiceEndpoints($this->identifier, $opEndpoint);
    }

    // @codeCoverageIgnoreStart

    /**
     * Sends the request via Request2
     *
     * @return string The HTTP response body
     * @throws OpenIdDiscoverException
     * @throws Request2\Exceptions\Exception
     * @throws Request2\Exceptions\LogicException
     * @throws Request2\Exceptions\Request2Exception
     */
    protected function sendRequest()
    {
        $this->getHTTPRequest2();
        $this->response = $this->request->send();

        if ($this->response->getStatus() !== 200) {
            throw new OpenIdDiscoverException(
                'Unable to connect to OpenID Provider.',
                OpenIdException::HTTP_ERROR
            );
        }

        return $this->response->getBody();
    }

    /**
     * Instantiates Request2.  Abstracted for testing.
     *
     * @return void
     * @throws Request2\Exceptions\LogicException
     */
    protected function getHTTPRequest2()
    {
        $this->request = new Request2(
            $this->identifier, Request2::METHOD_GET, $this->requestOptions
        );
    }
    // @codeCoverageIgnoreEnd
}
