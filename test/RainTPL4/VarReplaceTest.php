<?php
/**
 * Variable replacing inside loops testing
 *
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class VarReplaceTest extends RainTPLTestCase
{
    /**
     * Test variable inside brackets: {$outside.$inside} => $outside["$inside"]
     *
     * <code>{loop="$something"}{$value.$testIndex}{/loop}</code>
     * <data-testIndex>name</data-time>
     * <expects>FirstSecond</expects>
     *
     * @author xPaw <github@xpaw.me>
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testVariablesInsideBrackets()
    {
        $this->setupRainTPL4();

        $this->engine->assign('testIndex', 'name');
        $this->engine->assign('something', array(
           array(
               'name' => 'First',
           ),
           array(
               'name' => 'Second',
           ),
        ));

        $this->autoAssertEquals();
    }

    /**
     * Same test as testVariablesInsideBrackets but inside {if} block and with using isset()
     *
     * <code>{if="isset( $names.$value )"}{$names.$value}{/if}</code>
     * <data-names>{"name": "Richard"}</data-names>
     * <expects>Richard</expects>
     *
     * @author xPaw <github@xpaw.me>
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testVariablesInsideBracketsIsset()
    {
        $this->setupRainTPL4();
        $this->engine->assign(array(
            'names' => json_decode($this->getExampleDataFromPHPDoc('names'), true),
            'value' => 'name',
        ));
        $this->autoAssertEquals();
    }

    /**
     * <code>{$test.first.second.third.fourth.fifth.sixth.seventh}</code>
     * <expects>This is working</expects>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testMultipleArrayBrackets()
    {
        $this->setupRainTPL4();
        $this->engine->assign('test', array(
            'first' => array(
                'second' => array(
                    'third' => array(
                        'fourth' => array(
                            'fifth' => array(
                                'sixth' => array(
                                    'seventh' => 'This is working',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ));

        $this->autoAssertEquals();
        $this->engine->setConfigurationKey('print_parsed_code', true);
    }

    /**
     * More complicated replacement like {$var.attr1.$attr2.attr3} and other variables in near
     *
     * <code>{$before}{$test.first.second.third.$f.fifth.sixth.seventh}{$after}</code>
     * <expects>Let's, check...This is working, hmm...</expects>
     * <data-before>Let's, check...</data-before>
     * <data-after>, hmm...</data-after>
     * <data-f>fourth</data-f>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testMultipleArrayBracketsWithVariables()
    {
        $this->setupRainTPL4();
        $this->engine->assign('f', 'fourth');
        $this->engine->assign('before', $this->getExampleDataFromPHPDoc('before'));
        $this->engine->assign('after', $this->getExampleDataFromPHPDoc('after'));
        $this->engine->assign('test', array(
            'first' => array(
                'second' => array(
                    'third' => array(
                        'fourth' => array(
                            'fifth' => array(
                                'sixth' => array(
                                    'seventh' => 'This is working',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ));

        $this->autoAssertEquals();
        $this->engine->setConfigurationKey('print_parsed_code', true);
    }


    /**
     * Test built-in function modificators, on "replace" example
     * {...|replace:"from":"to"}
     *
     * <code>{"This is not working correctly."|replace:"is not working correctly":"is working perfectly"}</code>
     * <expects>This is working perfectly.</expects>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testBuiltinReplaceModifierOnString()
    {
        $this->setupRainTPL4();
        $this->autoAssertEquals();
    }

    /**
     * Test built-in function modificators, on "replace" example
     * {$testString|replace:"from":"to"}
     *
     * <code>{$testString|replace:"is not working correctly":"is working perfectly"}</code>
     * <expects>This is working perfectly.</expects>
     * <data-testString>This is not working correctly.</data-testString>
     *
     * @author Damian Kęska <damian@pantheraframework.org>
     */
    public function testBuiltinReplaceModifierOnVariable()
    {
        $this->setupRainTPL4();
        $this->engine->assign('testString', $this->getExampleDataFromPHPDoc('testString'));
        $this->autoAssertEquals();
    }
}