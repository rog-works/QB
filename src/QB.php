<?php

namespace QB;

class QB {
	/** @var array $params クエリパラメータ */
	private $params = [];

	/**
	 * インスタンスを生成。
	 * 引数を渡すことで特定のキーワードに紐づかないクエリパラメータを生成できる。
	 *
	 * @param array $arguments 可変長引数。マジックメソッドで呼び出す際と同じ様に使える
	 */
	public function __construct() {
		$this->params['root'] = $this->parse(func_get_args());
	}

	/**
	 * 呼び出された関数名をキーワードとして引数と共にクエリパラメータに追加する。
	 *
	 * @param string $name      SQLのキーワードを指定する
	 * @param array  $arguments 可変長引数。キーワード対応する引数を指定する
	 * @return QB インスタンス自身
	 */
	public function __call(string $name, array $arguments): QB {
		$keywords = array_map('strtoupper', explode('_', $name));
		$this->params[$this->calcRoute($this->params, $name)] = $this->parse(array_merge($keywords, $arguments));
		return $this;
	}

	/**
	 * 引数を解析し、クエリパラメータに変換する。
	 *
	 * @param array $arguments マジックメソッドから渡された引数
	 * @return array 解析結果のクエリパラメータ
	 */
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

	/**
	 * 設定されたクエリパラメータからクエリをビルドする。
	 * 引数にフィルター条件を渡すことで、特定の関数呼び出しに紐づく構文を除外できる。
	 *
	 * @param array $filter ビルドのフィルター条件
	 * @return string ビルド結果のクエリ
	 */
	public function build(array $filter = []): string {
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

	/**
	 * クエリパラメータのルートを算出する。
	 *
	 * @param string $params クエリパラメータ
	 * @param string $name   マジックメソッドに指定された関数名
	 * @return string クエリパラメータのルート
	 */
	private function calcRoute(array $params, string $name): string {
		return sprintf('%s.%s', array_keys($params)[count($params) - 1], $name);
	}
}
