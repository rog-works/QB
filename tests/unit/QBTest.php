<?php

namespace QBTest\unit;

use QB\QB;
use QBTest\helpers\TestHelper;

class QBTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @test
	 */
	public function __constructTest() {
		$this->assertEquals(['root' => ['1,2']], TestHelper::getProperty(new QB([1, 2]), 'params'));
		$this->assertEquals(['root' => ['hoge', 'fuga']], TestHelper::getProperty(new QB('hoge', 'fuga'), 'params'));
	}

	/**
	 * @test
	 * @dataProvider __callTestData
	 */
	public function __callTest(string $name, array $arguments, array $expected) {
		$qb = new QB;
		call_user_func_array([$qb, $name], $arguments);
		$this->assertEquals($expected, TestHelper::getProperty($qb, 'params'));
	}

	public function __callTestData(): array {
		return [
			[
				'select',
				[['u.id', 'u.name', 'u.created']],
				['root' => [], 'root.select' => ['SELECT', 'u.id,u.name,u.created']],
			],
			[
				'left_join',
				['follows'],
				['root' => [], 'root.left_join' => ['LEFT', 'JOIN', 'follows']],
			],
			[
				'_illegal',
				[],
				['root' => [], 'root._illegal' => ['', 'ILLEGAL']],
			],
		];
	}

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
	 * @dataProvider buildData
	 */
	public function build(QB $qb, array $filter, string $expected) {

		$this->assertEquals($expected, $qb->build($filter));
	}

	public function buildData() {
		$select = (new QB)->select([
				'u.id',
				'u.name',
				'u.created',
				'f2.follow_counts',
			])
			->from('users')->as('u')
			->join('profiles')->as('p')
				->on('p.user_id = u.id')
			->left_join(
				(new QB)->select([
					'f.user_id',
					'count(*) AS follow_counts',
				])
				->from('follows')->as('f')
				->group_by('f.user_id')
			)->as('f2')
				->on('f2.user_id = u.id')
			->where('u.deleted = 0')
				->and('u.name LIKE "%:keyword%"')
				->and('p.gender = ":gender"')
			->order_by([
				'p.gender',
				'u.id desc',
			])
			->limit([0, 10]);
		$filtered = implode(' ', [
			'SELECT u.id,u.name,u.created,f2.follow_counts',
			'FROM users',
			'AS u',
			'JOIN profiles',
			'AS p',
			'ON p.user_id = u.id',
			'LEFT JOIN (SELECT f.user_id,count(*) AS follow_counts FROM follows AS f GROUP BY f.user_id)',
			'AS f2',
			'ON f2.user_id = u.id',
			'WHERE u.deleted = 0',
			'AND p.gender = ":gender"',
			'ORDER BY p.gender,u.id desc',
			'LIMIT 0,10',
		]);
		$full = implode(' ', [
			'SELECT u.id,u.name,u.created,f2.follow_counts',
			'FROM users',
			'AS u',
			'JOIN profiles',
			'AS p',
			'ON p.user_id = u.id',
			'LEFT JOIN (SELECT f.user_id,count(*) AS follow_counts FROM follows AS f GROUP BY f.user_id)',
			'AS f2',
			'ON f2.user_id = u.id',
			'WHERE u.deleted = 0',
			'AND u.name LIKE "%:keyword%"',
			'AND p.gender = ":gender"',
			'ORDER BY p.gender,u.id desc',
			'LIMIT 0,10',
		]);
		$union = (new QB(
				(new QB)->select('SQL_CALC_FOUND_ROWS', [
					'id',
					'"a" AS type',
					'name'
				])
				->from('table_a')
				->build()
			))->union_all(
				(new QB)->select([
					'id',
					'"b" AS type',
					'name'
				])
				->from('table_b')
				->build()
			)
			->order_by('id desc')
			->limit([0, 10]);
		$unionFull = implode(' ', [
			'SELECT SQL_CALC_FOUND_ROWS id,"a" AS type,name FROM table_a',
			'UNION ALL SELECT id,"b" AS type,name FROM table_b',
			'ORDER BY id desc',
			'LIMIT 0,10',
		]);
		return [
			[$select, ['where.and' => false], $filtered],
			[$select, ['select.from.as.join.as.on.left_join.as.on.where.and' => false], $filtered],
			[$select, ['where.and' => true], $full],
			[$select, ['select.from.as.join.as.on.left_join.as.on.where.and' => true], $full],
			[$union, [], $unionFull],
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
