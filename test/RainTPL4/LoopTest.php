<?php
/**
 * Loop testcases
 *
 * @author Mateusz Warzyński <lxnmen@gmail.com>
 */
class LoopTest extends RainTPLTestCase
{
    /**
     * Testcase for nested foreach loop
     *
     * <code>{foreach from="$arrayVariable"}{foreach from="$value"}{foreach from="$value1"}{$key2}{$value2}{/foreach}{/foreach}{/foreach}</code>
     * <expects>key1value1key2value2</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */
    public function testNestedForeach()
    {
        $this->setupRainTPL4();

        $this->engine->assign('arrayVariable', array(array('key1' => 'value1'), array('key2' => 'value2')));
        $this->autoAssertEquals();
    }

    /**
     * Testcase for {foreach from="$array" as $value}
     *
     * <code>{foreach from="$arrayVariable" as $setValue}{$setValue}{/foreach}</code>
     * <expects>value1value2</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */
    public function testForeachAs()
    {
        $this->setupRainTPL4();

        $this->engine->assign('arrayVariable', array('value1', 'value2'));
        $this->autoAssertEquals();
    }

    /**
     * Testcase for {foreach from="$array" key="keyName" item="itemValue"}
     *
     * <code>{foreach from="$arrayVariable" key="keyName" item="itemValue"}{$itemValue}{/foreach}</code>
     * <expects>value1</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */
    public function testForeachFromItemKey()
    {
        $this->setupRainTPL4();

        $this->engine->assign('arrayVariable', array('key1' => 'value1'));
        $this->autoAssertEquals();
    }

    /**
     * Testcase for {foreach from="$array" as $keyName => $itemValue}
     *
     * <code>{foreach from="$arrayVariable" as $keyName => $itemValue}{$itemValue}{/foreach}</code>
     * <expects>value1</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */
    public function testForeachAsKeyValue()
    {
        $this->setupRainTPL4();

        $this->engine->assign('arrayVariable', array('key1' => 'value1'));
        $this->autoAssertEquals();
    }

    /**
     * Testcase for {continue} in foreach loop
     *
     * <code>{foreach from="$arrayVariable" as $number}{if $number == 2}{continue}{/if}{$number}{/foreach}</code>
     * <expects>1345</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */
    public function testForeachContinue()
    {
        $this->setupRainTPL4();

        $this->engine->assign('arrayVariable', array(1, 2, 3, 4, 5));
        $this->autoAssertEquals();
    }

    /**
     * Testcase for {continue} in foreach loop
     *
     * <code>{foreach from="$arrayVariable" as $number}{if $number == 3}{break}{/if}{$number}{/foreach}</code>
     * <expects>12</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */
    public function testForeachBreak()
    {
        $this->setupRainTPL4();

        $this->engine->assign('arrayVariable', array(1, 2, 3, 4, 5));
        $this->autoAssertEquals();
    }
}