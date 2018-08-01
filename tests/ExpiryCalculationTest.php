<?php

require_once('../setup.php');

/*
 * Test Config class
 * Tests the expiry functions of RunUnits
 */

class RunUnitTest extends PHPUnit\Framework\TestCase {
	public function testExpiryFunctions() {
		$ru = new Survey(null, null, null);
		$this->assertFalse($ru->isExpired()); //always false before calculateExpiry is called
		$ru->setExpiry(3600);
		$this->assertTrue($ru->hasExpiry());
		$this->assertFalse($ru->isExpired()); //always false before calculateExpiry is called
		$timestamp = time();
		$this->assertEquals($ru->calculateExpiry($timestamp-3700),$timestamp-3700+3600);
		$this->assertTrue($ru->isExpired());
		$this->assertEquals($ru->calculateExpiry($timestamp-3500),$timestamp-3500+3600);
		$this->assertFalse($ru->isExpired());
	}

}

