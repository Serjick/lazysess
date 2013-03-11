<?php

namespace Lazysess\Tests;

use Lazysess\Merge as ArrMerge;

class Merge extends \PHPUnit_Framework_TestCase
{
    /** @var ArrMerge */
    private $instance;

    public function setUp()
    {
        $this->instance = new ArrMerge($this->cur, $this->new, $this->old);
    }

    public function testDiffAdd()
    {
        $this->assertEquals(
            array(
                1,
                'a' => array(
                    2,
                    'a' => array(1 => 2, array(null)),
                    false,
                ),
                array(4 => 4),
                'b' => array(
                    array(1),
                    1
                ),
                'd' => '+',
            ),
            $this->instance->getDiffAdd()
        );
    }

    public function testDiffSub()
    {
        $this->assertEquals(
            array(
                1 => array(2 => null),
                'c' => null,
                'e' => null,
            ),
            $this->instance->getDiffSub()
        );
    }

    /**
     * @depends testDiffAdd
     */
    public function testMergeAdd()
    {
        $this->assertEquals(
            array(
                1,
                'a' => array(
                    2,
                    'a' => array(1, 2, array(null)),
                    false,
                ),
                array(false, 1, 2, 3, 4),
                true,
                'b' => array(
                    array(1),
                    1
                ),
                array(true, null, false),
                'c' => null,
                'd' => '+',
                'e' => array(),
            ),
            $this->instance->getMergeAdd()
        );
        $this->assertEquals(
            array(
                $this->presentCollision('', 0, 1, 1, 2),
                $this->presentCollision('.a.a', 1, 2, 2, ArrMerge::COLLISION_VALUE_VOID),
                $this->presentCollision('.a', 1, null, false, true),
            ),
            $this->instance->getCollisions()
        );
    }

    /**
     * @depends testDiffSub
     */
    public function testMergeSub()
    {
        $this->assertEquals(
            array(
                1,
                'a' => array(
                    1,
                    'a' => array(1, 2),
                    null,
                ),
                array(false, 1, 3 => 3),
                true,
                'b' => 2,
                array(true, null, false),
            ),
            $this->instance->getMergeSub()
        );
        $this->assertEquals(
            array(
                $this->presentCollision('', 'c', null, ArrMerge::COLLISION_VALUE_VOID, false),
            ),
            $this->instance->getCollisions()
        );
    }

    public function testMerge()
    {
        $this->assertEquals(
            array(
                1,
                'a' => array(
                    2,
                    'a' => array(1, 2, array(null)),
                    false,
                ),
                array(false, 1, 3 => 3, 4),
                true,
                'b' => array(
                    array(1),
                    1
                ),
                array(true, null, false),
                'd' => '+',
            ),
            $this->instance->getMerge()
        );
        $this->assertEquals(
            array(
                $this->presentCollision('', 0, 1, 1, 2),
                $this->presentCollision('.a.a', 1, 2, 2, ArrMerge::COLLISION_VALUE_VOID),
                $this->presentCollision('.a', 1, null, false, true),
                $this->presentCollision('', 'c', null, ArrMerge::COLLISION_VALUE_VOID, false),
            ),
            $this->instance->getCollisions()
        );
    }

    public function tearDown()
    {
        $this->instance = null;
    }

    private function presentCollision($path, $key, $cur_value, $new_value, $old_value)
    {
        return array(
            'path' => $path,
            'key' => $key,
            'cur_value' => $cur_value,
            'new_value' => $new_value,
            'old_value' => $old_value,
            'page' => $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'],
        );
    }

    private $cur = array(
        1,
        'a' => array(
            1,
            'a' => array(1, 2),
            null,
        ),
        array(false, 1, 2, 3),
        true,
        'b' => 2,
        array(true, null, false),
        'c' => null,
        'e' => array(),
    );
    private $new = array(
        1,
        'a' => array(
            2,
            'a' => array(1, 2, array(null)),
            false,
        ),
        array(1 => 1, 3 => 3, 4),
        null,
        'b' => array(
            array(1),
            1
        ),
        array(true, null, false),
        'd' => '+',
    );
    private $old = array(
        2,
        'a' => array(
            1,
            'a' => array(1),
            true,
        ),
        array(1 => 1, 2, 3),
        null,
        'b' => 2,
        array(true, null, false),
        'c' => false,
        'e' => array(),
    );

}