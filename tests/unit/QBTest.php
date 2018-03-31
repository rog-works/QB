<?php

namespace QBTest\unit;

use QB\QB;
use QBTest\helpers\TestHelper;

class QBTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @test
	 * @dataProvider calcRouteData
	 */
	public function calcRoute(array $params, string $name, string $expected) {
		$this->assertEquals($expected, TestHelper::invokeMethod(new QB, 'calcRoute', [$params, $name]));
	}

	public function calcRouteData() {
		return [
			[['root' => 0], 'select', 'root.select'],
			[['root.select' => 0], 'from', 'root.select.from'],
			[['root.select.from' => 0], 'as', 'root.select.from.as'],
		];
	}
}
