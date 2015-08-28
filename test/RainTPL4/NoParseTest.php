<?php
/**
 * Escaping parts of code testcases
 *
 * @package RainTPL4\syntax\literal\tests
 * @author Mateusz Warzyński <lxnmen@gmail.com>
 */
class NoParseTest extends RainTPLTestCase
{
    /**
     * Testcase for tag {literal}
     *
     * <code>{literal}{if 1 > 2}{/literal}something</code>
     * <expects>{if 1 > 2}something</expects>
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
     * <expects>blablablasomething</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */
    public function testNoParse()
    {
        $this->setupRainTPL4();
        $this->autoAssertEquals();
    }

    /**
     * Testcase for comment, loading variables inside comment
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */
    public function testCommentFourth()
    {
        $this->setupRainTPL4();
        $this->assertEquals('something', $this->engine->drawString('{*}{$hiddenValue}{/*}something', true));
        $this->assertEquals('something', $this->engine->drawString("{ignore}This is a test comment #3{/ignore}something", true));
        $this->assertEquals('something', $this->engine->drawString("{*}I am special!{/ignore}something", true));
        $this->assertEquals('something', $this->engine->drawString("{*} This is a test comment #2 {/*}something", true));
        $this->assertEquals('something', $this->engine->drawString("some{* This is a test comment *}thing", true));
        $this->assertEquals('something', $this->engine->drawString("some{*}This is a multi comment test{*} this is a test{/*}{/*}thing", true));
    }
}