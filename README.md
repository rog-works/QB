QB
===

# 使い方

## SELECT

```php:select.php
<?php
$query = (new QB)->select([
		'u.id',
		'u.name',
		'u.created',
		'p.gender',
		'p.birthday',
		'c2.comment_counts',
		(new QB(
			(new QB)->select([
					'count(*)'
				])
				->from('follows')->as('f')
				->where('f.follow_user_id = u.id')
		))->as('follow_counts')->build()
	])
	->from('users')->as('u')
	->join('profiles')->as('p')
		->on('p.user_id = u.id')
	->left_join(
		(new QB)->select([
			'c.user_id',
			'count(*) as comment_counts',
		])
		->from('comments')->as('c')
		->group_by('user_id')
	)->as('c2')
		->on('c2.user_id = u.id')
	->where('u.id')->in(new QB([1, 2, 3, 4, 5]))
		->or(
			(new QB('u.created < now()'))
				->and('u.name')->like('"a%"')
		)
	->order_by([
		'p.gender',
		'u.id desc',
	])
	->limit([10, 10])
	->build();

echo $query; // SELECT u.id,u.name,u.created,p.gender,p.birthday,c2.comment_counts,(SELECT count(*) FROM follows AS f WHERE f.follow_user_id = u.id) AS follow_counts FROM users AS u JOIN profiles AS p ON p.user_id = u.id LEFT JOIN (SELECT c.user_id,count(*) as comment_counts FROM comments AS c GROUP BY user_id) AS c2 ON c2.user_id = u.id WHERE u.id IN (1,2,3,4,5) OR (u.created < now() AND u.name LIKE "a%") ORDER BY p.gender,u.id desc LIMIT 10,10
```

### フォーマット

```
SELECT
   u.id,
   u.name,
   u.created,
   p.gender,
   p.birthday,
   c2.comment_counts,
   (
      SELECT
         count(*) 
      FROM
         follows AS f 
      WHERE
         f.follow_user_id = u.id
   ) AS follow_counts 
FROM
   users AS u 
   JOIN
      profiles AS p 
      ON p.user_id = u.id 
   LEFT JOIN
      (
         SELECT
            c.user_id,
            count(*) as comment_counts 
         FROM
            comments AS c 
         GROUP BY
            user_id
      ) AS c2 
      ON c2.user_id = u.id 
WHERE
   u.id IN ( 1, 2, 3, 4, 5 )
   OR 
   (
      u.created < now() 
      AND u.name LIKE "a%"
   )
ORDER BY
   p.gender,
   u.id desc
LIMIT
   10,10
```

## INSERT

```php:insert.php
$query = (new QB)->insert_into(
	'users',
	new QB([
		'id',
		'name',
		'created',
	]))
	->values([
		new QB([1, '"1ban"', '"2018-03-31 10:00:01"']),
		new QB([2, '"2ban"', '"2018-03-31 10:00:02"']),
		new QB([3, '"3ban"', '"2018-03-31 10:00:03"']),
	])
	->build();

echo $query; // INSERT INTO users (id,name,created) VALUES (1,"1ban","2018-03-31 10:00:01"),(2,"2ban","2018-03-31 10:00:02"),(3,"3ban","2018-03-31 10:00:03")
```

### フォーマット

```sql
INSERT INTO users
	(id,name,created)
VALUES
	(1,"1ban","2018-03-31 10:00:01"),
	(2,"2ban","2018-03-31 10:00:02"),
	(3,"3ban","2018-03-31 10:00:03")
```

## UPDATE

```php:update.php
<?php
$query = (new QB)->update('users')
	->set([
		'name = :name',
		'created = now()',
	])
	->where('id = :id')
	->build();

echo $query; // UPDATE users SET name = :name,created = now() WHERE id = :id
```

### フォーマット

```sql
UPDATE users
SET 
	name = :name,
	created = now()
WHERE 
	id = :id
```

# ビルドフィルタリング

```php
<?php
$conditions = [];
$query = (new QB)->select([
		'u.id',
		'u.name',
		'u.created',
	])
	->from('users')->as('u')
	->join('profiles')->as('p')
		->on('p.user_id = u.id')
	->where('u.deleted = 0')
		->and('u.name LIKE ":keyword"')
		->and('p.gender = :gender')
	->build([
		'where.and' => isset($conditions['keyword'])
	]);

echo $query; // SELECT u.id,u.name,u.created FROM users AS u JOIN profiles AS p ON p.user_id = u.id WHERE u.deleted = 0 AND p.gender = :gender
```

## フォーマット

```sql
SELECT
   u.id,
   u.name,
   u.created 
FROM
   users AS u 
   JOIN
      profiles AS p 
      ON p.user_id = u.id 
WHERE
   u.deleted = 0 
   AND p.gender = :gender
```

# まとめ

* 関数名は大文字に置換される。また`_`で分解され、最終的にスペースに置換される
* 引数に可変長引数を渡すとスペース区切りで展開される
* 引数に配列を渡すと`,`区切りで展開される
* 引数にクエリービルダーを渡すと自動で展開され`()`で囲われる
* `build()`に連想配列を渡すことで、対象の関数で設定した構文をフィルタリングできる
* シンタックスチェックしない
* エスケープしない
* 文字列は`''` or `""`で囲う必要がある
* バインドは自分でやる(`PDO`とか)
* `->`,`()`,`''`を書く影響で、ヒアドキュメントよりも記述量が増える
