<?php
/**
 * Custom tags testcase
 *
 * @package RainTPL4\syntax\customTags\tests
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class CustomTagsTest extends RainTPLTestCase
{
    /**
     * Regexp tags test
     *
     * <code>This test was created at {%1433449348%} :)</code>
     * <expects>This test was created at 2015-06-04 22:22:28 :)</expects>
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testRegexpTags()
    {
        $this->setupRainTPL4();
        $this->engine->registerRegexpTag('formatDate', '/\{\%(.*)\%\}/i', function(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart, $matches) {
            $part = date('Y-m-d H:i:s', $matches[1]);
            return true;
        });

        $this->autoAssertEquals();
    }
}