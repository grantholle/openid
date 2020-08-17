<?php

namespace Tests\Extension;

use Pear\OpenId\Extensions\OpenIdExtension;

/**
 * OpenID_Extension_Mock
 *
 * PHP Version 5.2.0+
 *
 * @uses      OpenID_Extension
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * OpenID_Extension_Mock
 *
 * @uses      OpenID_Extension
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class MockExtension extends OpenIdExtension
{
    protected $requestKeys = ['one', 'two', 'three'];
    protected $responseKeys = ['four', 'five', 'six'];

    protected $alias = 'mock';
    protected $namespace = 'http://example.com/mock';
}
