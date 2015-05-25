<?php
/**
 * Mark testcases
 *
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

    /**
     * Testcase for tag {literal}
     *
     * <code>{literal}blablabla{/literal}something</code>
     * <expects>something</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */
    public function testLiteral()
    {
        $this->setupRainTPL4();
        $this->autoAssertEquals();
    }

    /**
     * Testcase for tag {noparse}
     *
     * <code>{noparse}blablabla{/noparse}something</code>
     * <expects>something</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */
    public function testNoparse()
    {
        $this->setupRainTPL4();
        $this->autoAssertEquals();
    }
}