<?php

namespace Pear\OpenId\Extensions;

use Pear\OpenId\Exceptions\OpenIdException;

/**
 * OpenID_Extension_OAuth
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID_Extension
 * @category  Auth
 * @package   OpenID
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2009 Jeff Hodsdon
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * Provides support for the OAuth extension
 *
 * @uses      OpenID_Extension
 * @category  Auth
 * @package   OpenID
 * @author    Jeff Hodsdon <jeffhodsdon@gmail.com>
 * @copyright 2009 Jeff Hodsdon
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 * @link      http://step2.googlecode.com/svn/spec/openid_oauth_extension/latest/openid_oauth_extension.html
 */
class OAuth extends OpenIdExtension
{
    /**
     * URI of the OAuth namespace
     *
     * @var string $namespace
     */
    protected $namespace ='http://specs.openid.net/extensions/oauth/1.0';

    /**
     * Alias to use
     *
     * @var string $alias
     */
    protected $alias = 'oauth';

    /**
     * Supported keys in a request
     *
     * @var array $requestKeys
     */
    protected $requestKeys = array('consumer', 'scope');

     /**
     * Supported keys in a response
     *
     * @var array $responseKeys
     */
    protected $responseKeys = array('request_token', 'scope');

    /**
     * Fetch an OAuth access token
     *
     * Requires an request_token to be present in self::$values
     *
     * @param string $consumerKey OAuth consumer application key
     * @param string $consumerSecret OAuth consumer secret key
     * @param string $url Access token url
     * @param array  $params Parameters to include in the request for the access token
     * @param string $method HTTP Method to use
     * @return array Key => Value array of token and token secret
     *
     * @see http://step2.googlecode.com/svn/spec/openid_oauth_extension/latest/openid_oauth_extension.html
     */
    public function getAccessToken(
        $consumerKey, $consumerSecret, $url,
        array $params = array(), $method = 'GET'
    ) {
        $requestToken = $this->get('request_token');
        if ($requestToken === null) {
            throw new OpenIdException(
                'No oauth request token in OpenID message',
                OpenIdException::MISSING_DATA
            );
        }

        $consumer = new HTTP_OAuth_Consumer($consumerKey, $consumerSecret);
        $consumer->setToken($requestToken);

        // Token secret is blank per spec
        $consumer->setTokenSecret('');

        // Blank verifier
        $consumer->getAccessToken($url, '', $params, $method);

        return array('oauth_token' => $consumer->getToken(),
            'oauth_token_secret' => $consumer->getTokenSecret());
    }

}
