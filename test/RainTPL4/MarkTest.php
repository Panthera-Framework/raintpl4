<?php
/**
 * Mark testcases
 *
 * @package RainTPL4\syntax\goto\tests
 * @author Mateusz Warzyński <lxnmen@gmail.com>
 */
class MarkTest extends RainTPLTestCase
{
    /**
     * Testcase for goto -> mark
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