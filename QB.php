<?php
class QB {
	private $params = [];
	
	public function __construct(array $arguments = []) {
		$this->params['root'] = $this->parse($arguments);
	}
	
	public function __call(string $name, array $arguments): QB {
		$this->params[$this->calcRoute($name)] = $this->parse(array_merge(array_map('strtoupper', explode('_', $name)), $arguments));
		return $this;
	}
	
	private function parse(array $arguments): array {
		$params = [];
		foreach ($arguments as $arg) {
			if (is_array($arg)) {
				$params[] = implode(',', $this->parse($arg));
			} else if (is_object($arg) && get_class($arg) === get_class($this)) {
				$params[] = sprintf('(%s)', $arg->build());
			} else {
				$params[] = $arg;
			}
		}
		return $params;
	}
	
	public function build($filter = []) {
		$params = [];
		foreach ($this->params as $route => $_params) {
			$active = true;
			foreach ($filter as $target => $enabled) {
				$active &= $enabled || preg_match("/{$target}$/", $route) === 0;
			}
			if ($active) {
				$params = array_merge($params, $_params);
			}
		}
		return implode(' ', $params);
	}

	private function calcRoute($name) {
		return sprintf('%s.%s', array_keys($this->params)[count($this->params) - 1], $name);
	}
}
