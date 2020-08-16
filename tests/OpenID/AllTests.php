<?php
/**
 * OpenID_AllTests
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

require_once 'OpenIDTest.php';
require_once 'src/MessageTest.php';
require_once 'src/Auth/RequestTest.php';
require_once 'src/ExtensionTest.php';
require_once 'src/Extensions/AXTest.php';
require_once 'src/Extensions/SREGTest.php';
require_once 'src/Extensions/UITest.php';
require_once 'src/AssociationTest.php';
require_once 'src/Associations/RequestTest.php';
require_once 'src/StoreTest.php';
require_once 'src/ServiceEndpointTest.php';
require_once 'src/ServiceEndpointsTest.php';
require_once 'src/Observers/LogTest.php';
require_once 'src/NonceTest.php';
require_once 'src/AssertionTest.php';
require_once 'src/Assertions/ResultTest.php';
require_once 'src/RelyingPartyTest.php';
require_once 'src/DiscoverTest.php';
require_once 'src/Discover/HTMLTest.php';
require_once 'src/Discover/YadisTest.php';
require_once 'src/Store/CacheLiteTest.php';

/**
 * OpenID_AllTests
 *
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class OpenID_AllTests
{
    /**
     * suite
     *
     * @return PHPUnit_Framework_TestSuite
     */
    static public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('OpenID Unit Test Suite');
        $suite->addTestSuite('OpenIDTest');
        $suite->addTestSuite('OpenID_MessageTest');
        $suite->addTestSuite('OpenID_Auth_RequestTest');
        $suite->addTestSuite('OpenID_ExtensionTest');
        $suite->addTestSuite('OpenID_Extension_AXTest');
        $suite->addTestSuite('OpenID_Extension_SREGTest');
        $suite->addTestSuite('OpenID_Extension_UITest');
        $suite->addTestSuite('OpenID_AssociationTest');
        $suite->addTestSuite('OpenID_Association_RequestTest');
        $suite->addTestSuite('OpenID_StoreTest');
        $suite->addTestSuite('OpenID_ServiceEndpointTest');
        $suite->addTestSuite('OpenID_ServiceEndpointsTest');
        $suite->addTestSuite('OpenID_Observer_LogTest');
        $suite->addTestSuite('OpenID_NonceTest');
        $suite->addTestSuite('OpenID_AssertionTest');
        $suite->addTestSuite('OpenID_Assertion_ResultTest');
        $suite->addTestSuite('OpenID_RelyingPartyTest');
        $suite->addTestSuite('OpenID_DiscoverTest');
        $suite->addTestSuite('OpenID_Discover_HTMLTest');
        $suite->addTestSuite('OpenID_Discover_YadisTest');
        $suite->addTestSuite('OpenID_Store_CacheLiteTest');
        return $suite;
    }
}

?>
