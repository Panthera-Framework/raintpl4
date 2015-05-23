<?php
/**
 * {block} tag testcases
 *
 * Notes:
 *   - This tag is supporting templates inheritence using {extends} tag
 *
 * List of arguments:
 *   - quiet
 *   - name
 *   - {dynamic} passed as $args and $_blockMeta
 *
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class BlockTest extends RainTPLTestCase
{
    /**
     * Simple base test for {block} tag
     *
     * <code>This is a test of {block name="boldedText" text="bolded text"}<b>{$args.text}</b>{/block}</code>
     * <expects>This is a test of <b>bolded text</b></expects>
     */
    public function testBlocksTag()
    {
        $this->setupRainTPL4();
        $this->autoAssertEquals();
    }

    /**
     * Argument: quiet
     *
     * <code>{block name="boldedText" quiet="true"}<b>{$args.text}</b>{/block}This is a test of {block name="boldedText" text="bolded text"}{/block}</code>
     * <expects>This is a test of <b>bolded text</b></expects>
     */
    public function testBlocksTagQuiet()
    {
        $this->setupRainTPL4();
        $this->autoAssertEquals();
    }
}