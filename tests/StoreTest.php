<?php

namespace Tests;

use Pear\OpenId\Exceptions\StoreException;
use Pear\OpenId\Store\Store;
use Tests\Store\NotInterface;
use Tests\Store\StoreMock;

/**
 * OpenID_StoreTest
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
 * OpenID_StoreTest
 *
 * @uses      PHPUnit_Framework_TestCase
 * @category  Auth
 * @package   OpenID
 * @author    Bill Shupp <hostmaster@shupp.org>
 * @copyright 2009 Bill Shupp
 * @license   http://www.opensource.org/licenses/bsd-license.php FreeBSD
 * @link      http://github.com/shupp/openid
 */
class StoreTest extends \PHPUnit\Framework\TestCase
{
    public function testFactorySuccess()
    {
        $object = Store::factory(StoreMock::class);
        $this->assertInstanceOf(StoreMock::class, $object);
    }

    public function testFactoryFailNotInterface()
    {
        $this->expectException(StoreException::class);
        Store::factory(NotInterface::class);
    }
}

