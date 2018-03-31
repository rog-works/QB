<?php

namespace QBTest\helpers;

class TestHelper {
	public static function invokeMethod($obj, string $method, array $args) {
		$reflect = new \ReflectionObject($obj);
		$invoker = $reflect->getMethod($method);
		$invoker->setAccessible(true);
		return $invoker->invokeArgs($obj, $args);
	}
}
