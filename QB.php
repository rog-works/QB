<?php
class QB {
	private $params = [];
	
	public function __construct(array $arguments = []) {
		$this->params = $this->parse($arguments);
	}
	
	public function __call(string $name, array $arguments): QB {
		$this->params = array_merge($this->params, $this->parse(array_merge(array_map('strtoupper', explode('_', $name)), $arguments)));
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
	
	public function build() {
		return implode(' ', $this->params);
	}
}
