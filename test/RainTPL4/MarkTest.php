<?php
/**
 * Mark testcases
 *
 * @author Mateusz Warzyński <lxnmen@gmail.com>
 */
class MarkTest extends RainTPLTestCase
{
    /**
     * Testcase for nested foreach loop
     *
     * <code>{goto test}This code should not execute.{mark test}mark works</code>
     * <expects>mark works</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */
    public function testGotoMark()
    {
        $this->setupRainTPL4();
        $this->autoAssertEquals();
    }
}