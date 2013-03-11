<?php

namespace Lazysess\Tests;

use Lazysess\Session as LazySession;

ob_start();

class Session extends \PHPUnit_Framework_TestCase
{
    static public function setUpBeforeClass()
    {
        session_start();
        $_SESSION['test'] = 10;
        session_write_close();
        $_SESSION = new LazySession();
    }

    public function testGet()
    {
        $this->assertEquals(10, $_SESSION['test']);
    }

    public function testExists()
    {
        $this->assertTrue(isset($_SESSION['test']));
        $this->assertFalse(isset($_SESSION['empty']));
    }

    /**
     * @depends testGet
     */
    public function testSet()
    {
        $_SESSION['test2'] = 11;
        $this->assertEquals(11, $_SESSION['test2']);
        $_SESSION['test3'] = array('1', 'arr' => array());
        $this->assertEquals(array('1', 'arr' => array()), $_SESSION['test3']);
        $_SESSION['test3']['arr'][] = 4312;
        $this->assertEquals(4312, $_SESSION['test3']['arr'][0]);
    }

    /**
     * @depends testSet
     */
    public function testUnset()
    {
        $_SESSION['test2'] = true;
        unset($_SESSION['test2']);
        $this->assertNull($_SESSION['test2']);
        $_SESSION['test3'] = array('1', 'arr' => array(4312));
        unset($_SESSION['test3'][0]);
        $this->assertEquals(array('arr' => array(4312)), $_SESSION['test3']);
        unset($_SESSION['test3']);
        $this->assertNull($_SESSION['test3']);
    }

    /**
     * @depends testUnset
     */
    public function testMerge()
    {
        /** @var $_SESSION LazySession */
        $_SESSION['merge'] = array(1, 'a' => array(1, 'a' => array(1, 2), null), 'b' => true, 'c' => array(null));
        $_SESSION['merge'][] = 2;
        $_SESSION['merge']['a'][] = 2;
        unset($_SESSION['merge']['a']['a'][1]);
        $_SESSION['merge']['b'] = null;
        unset($_SESSION['merge']['c']);
        unset($_SESSION['merge']['empty']);
        $_SESSION->close();

        session_start();
        $this->assertEquals(
            array(
                1,
                'a' => array(
                    1,
                    'a' => array(1),
                    null,
                    2
                ),
                'b' => null,
                2
            ),
            $_SESSION['merge'],
            print_r($_SESSION['merge'], true)
        );
        session_write_close();

        $_SESSION = new LazySession();
    }

    protected function tearDown()
    {
        $this->assertInstanceOf('Lazysess\Session', $_SESSION);
    }

    static public function tearDownAfterClass()
    {
        session_start();
        $_SESSION = null;
        session_write_close();
        ob_end_flush();
    }

} 