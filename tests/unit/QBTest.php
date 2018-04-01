<?php

namespace QBTest\unit;

use QB\QB;
use QBTest\helpers\TestHelper;

class QBTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @test
	 * @dataProvider parseData
	 */
	public function parse(array $args, array $expected) {
		$this->assertEquals($expected, TestHelper::invokeMethod(new QB, 'parse', [$args]));
	}

	public function parseData(): array {
		return [
			[['select', ['u.id', 'u.name', 'u.created']], ['select', 'u.id,u.name,u.created']],
			[['from', 'users'], ['from', 'users']],
			[['as', 'u'], ['as', 'u']],
			[['join', 'profiles'], ['join', 'profiles']],
			[['on', 'p.user_id = u.id'], ['on', 'p.user_id = u.id']],
			[['join', (new QB)->select(['f.user_id', 'count(*) as follows'])->from('follows')->as('f')->group_by('f.user_id')], ['join', '(SELECT f.user_id,count(*) as follows FROM follows AS f GROUP BY f.user_id)']],
			[['or', (new QB('u.created < now()'))->and('p.gender = :gender')],['or', '(u.created < now() AND p.gender = :gender)']],
			[['in', new QB([1, 2, 3])],['in', '(1,2,3)']],
			[['order','by', ['p.gender', 'u.id desc']],['order', 'by', 'p.gender,u.id desc']],
			[['limit', [0, 10]],['limit', '0,10']],
		];
	}

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
