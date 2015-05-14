<?php
/**
 * Modificators test class
 *
 * @author Mateusz Warzyński <lxnmen@gmail.com>
 */

class ModificatorsTest extends RainTPLTestCase
{
	/**
	 * Python-like in operator test for array (as RainTPL modificator)
	 *      with more complicated if statement
	 *
	 * <code>{if 'key1'|in:$arrayVariable and (2 > 1)}OK{/if}</code>
	 * <expects>OK</expects>
	 *
	 * @author Mateusz Warzyński <lxnmen@gmail.com>
	 */

	public function testInModificatorThird()
	{
		$this->setupRainTPL4();
		$this->engine->assign('arrayVariable', array('key1' => 'value1'));

		$this->autoAssertEquals();
	}

    /**
     * Python-like in operator test for array (as RainTPL modificator)
     *      with if statement
     *
     * <code>{if "key3"|in:$arrayVariable}OK{/if}</code>
     * <expects>OK</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */

    public function testInModificator()
    {
        $this->setupRainTPL4();
        $this->engine->assign('arrayVariable', array('key3' => 'value1'));

        $this->autoAssertEquals();
    }

    /**
     * Python-like in operator test for array (as RainTPL modificator)
     *      with if statement
     *
     * <code>{if "key2"|in:"$arrayVariable"}OK{/if}</code>
     * <expects>OK</expects>
     *
     * @author Mateusz Warzyński <lxnmen@gmail.com>
     */

    public function testInModificatorSecond()
    {
        $this->setupRainTPL4();
        $this->engine->assign('arrayVariable', array('key2' => 'value1'));

        $this->autoAssertEquals();
    }

	/**
	 * Test unclosed quotation character detection
	 *
	 * <code>{if 'unclosed|in:$arrayVariable and 2 > 1}OK{/if}</code>
	 * <expects>OK</expects>
	 *
	 * @expectedException Rain\Tpl\SyntaxException
	 * @author Mateusz Warzyński <lxnmen@gmail.com>
	 */

	public function testUnclosedQuotationCharacter()
	{
		$this->setupRainTPL4();
		$this->engine->assign('arrayVariable', array('key1' => 'value1'));
		$this->autoAssertEquals();
	}
}