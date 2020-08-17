<?php

namespace Tests\Discover;

use DateTime;
use Pear\OpenId\Discover\HTML;
use Pear\OpenId\Exceptions\OpenIdDiscoverException;
use Pear\OpenId\ServiceEndpoints;
use PHPUnit\Framework\TestCase;

/**
 * OpenID_Discover_HTMLTest
 *
 * PHP Version 5.2.0+
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */

/**
 * OpenID_Discover_HTMLTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class HTMLTest extends TestCase
{
    /**
     * testDiscoverSuccess
     *
     * @return void
     */
    public function testDiscoverSuccess()
    {
        $html = '<html>
                 <head>
                     <link rel="openid.server" href="http://www.example.com/server">
                     <link rel="openid.delegate" href="http://user.example.com">
                 </head>
                 </html>';

        $stub = $this->getMockBuilder(HTML::class)
            ->setConstructorArgs(['http://example.com'])
            ->onlyMethods(['sendRequest', 'getExpiresHeader'])
            ->getMock();
        $stub->method('sendRequest')
            ->willReturn($html);
        $stub->method('getExpiresHeader')
            ->willReturn((new DateTime(date('c', (time() + (3600 * 8)))))->format(DATE_RFC1123));

        $this->assertInstanceOf(ServiceEndpoints::class, $stub->discover());

        // Version 2.0
        $html = '<html>
                 <head>
                     <link rel="openid2.provider" href="http://example.com/server">
                     <link rel="openid2.local_id" href="http://user.example.com">
                 </head>
                 </html>';

        $stub->method('sendRequest')
            ->willReturn($html);

        $this->assertInstanceOf(ServiceEndpoints::class, $stub->discover());

        // Directed Identity
        $html = '<html>
                 <head>
                     <link rel="openid2.provider" href="http://example.com/server">
                 </head>
                 </html>';

        $stub->method('sendRequest')
            ->willReturn($html);

        $this->assertInstanceOf(ServiceEndpoints::class, $stub->discover());
    }

    public function testDiscoverFail()
    {
        $this->expectException(OpenIdDiscoverException::class);
        $html = '<html>
                 <head>
                 </head>
                 </html>';
        $stub = $this->getMockBuilder(HTML::class)
            ->setConstructorArgs(['http://example.com'])
            ->onlyMethods(['sendRequest', 'getExpiresHeader'])
            ->getMock();
        $stub->method('sendRequest')
            ->willReturn($html);
        $stub->method('getExpiresHeader')
            ->willReturn((new DateTime(date('c', (time() + (3600 * 8)))))->format(DATE_RFC1123));

        $stub->discover();
    }
}
