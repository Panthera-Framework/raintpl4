<?php
/**
 * Auto escape feature tests
 *
 * @package RainTPL4\feature\escaping
 * @author Damian Kęska <damian.keska@fingo.pl>
 */
class AutoescapeTest extends RainTPLTestCase
{
	/**
	 * <code>
	 * {$code}
	 * </code>
	 *
	 * <data-code><b>test</b></data-code>
	 * <expects>&lt;b&gt;test&lt;/b&gt;</expects>
	 */
	public function testAutoEscape()
	{
		$this->setupRainTPL4();
		$this->engine->setConfigurationKey('auto_escape', true);

		$this->engine->assign([
			'code' => $this->getExampleDataFromPHPDoc('code'),
		]);

		$this->autoAssertEquals();

		/**
		 * Now turn off auto escaping
		 */
		$this->engine->setConfigurationKey('auto_escape', false);
		$this->assertEquals('<b>test</b>', trim($this->engine->drawString($this->getTestCodeFromPHPDoc(), true)));
	}

	/**
	 * Test {autoescape} tag
	 *
	 * <data-code><b>test</b></data-code>
	 */
	public function testAutoEscapeTag()
	{
		$this->setupRainTPL4();
		$this->engine->setConfigurationKey('auto_escape', true);
		$this->engine->assign([
			'code' => $this->getExampleDataFromPHPDoc('code'),
		]);

		$this->assertEquals('&lt;b&gt;test&lt;/b&gt;', $this->engine->drawString('{autoescape="true"}{$code}{/autoescape}', true));
		$this->assertEquals('<b>test</b>', $this->engine->drawString('{autoescape="false"}{$code}{/autoescape}', true));

		// now without auto_escape configuration key enabled
		$this->engine->setConfigurationKey('auto_escape', true);

		$this->assertEquals('&lt;b&gt;test&lt;/b&gt;', $this->engine->drawString('{autoescape="true"}{$code}{/autoescape}', true));
		$this->assertEquals('<b>test</b>', $this->engine->drawString('{autoescape="false"}{$code}{/autoescape}', true));
	}
}