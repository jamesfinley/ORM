# Project Overview

The ORM and DB classes here are built framework agnostic for PHP. Easily setup models with relationships for easy access of data, or just use the DB class for easily building and running queries.

# ORM

## Naming Conventions

**Tables** and **models** share names. A table named *blog* should have a model named *Blog*. *(This can be overridden by `$this->table_name('name')` in your `init() `method)*

The **primary key** of a table should be the table name followed by *_id*. A table named *blog* should have a primary key of *blog_id*.

**Associations** occur by matching primary key to foreign key. A model named *Article* **belongs to** a model named *Blog*. The *article* table has a *blog_id* field that matches the *blog_id* field on the *blog* table.

## Associations

The power of ORM is in associations between models. Currently the following associations are supported:

- belongs to
- has many
- has many through

When setting associations up, the association name is the model name, not the table name. For **has many** associations, the model name is pluralized.

## Hooks

ORM provides a series of hooks to process data before and after events. Currently those are:

- before save
- after save
- before update
- after update
- before destroy
- after destroy

## Example

### Database Design

```mysql
CREATE TABLE `article` (
  `article_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `title` varchar(120) DEFAULT NULL,
  `article` text,
  `snippet` varchar(200) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `article_category` (
  `category_id` int(11) DEFAULT NULL,
  `article_id` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `author` (
  `author_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`author_id`)

CREATE TABLE `blog` (
  `blog_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`blog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `category` (
  `category_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(40) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `comment` (
  `comment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int(11) DEFAULT NULL,
  `author` varchar(120) DEFAULT NULL,
  `comment` text,
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

### Setup Models and Connect to Database

```php
// Connect to Database
DB::Instance('localhost', 'user', 'pass', 'cms');

// Build Blog, Article, Author, and Comment models
class Blog extends Model {
	
	function init()
	{
		$this->has_many('articles');
	}
	
}

class Article extends Model {
	
	function init()
	{
		$this->belongs_to('blog');
		$this->belongs_to('author');
		$this->has_many('comments');
		$this->has_many('categories', array(
			'through' => 'article_category' //use linking table
		));
	}
	
}

class Author extends Model {
	
	function init()
	{
		$this->has_many('articles');
	}
	
}

class Category extends Model {
	
	function init()
	{
		$this->has_many('articles', array(
			'through' => 'article_category' //use linking table
		));
	}
	
}

class Comment extends Model { }

```

### Access Data

Let's say we have a blog named "News" and we want to display a list of articles.

```php
// Find blog named "News"
$blog = (new Blog())->find(array(
	'name' => 'News'
))[0];

// Find all articles from blog
$articles = $blog->articles->find();

// Display articles
foreach ($articles as $article)
{
	?>
	<article>
		<h1><?=$article->title?></h1>
		<h2><?=date('F d, Y', $article->created)?> by <?=$article->author->name?> | <?=$article->comments->count()?> comments</h2>
		<?=$article->snippet?>
		<footer>
			Categories:
			<ul>
				<?php
					foreach ($article->categories->find() as $category)
					{
						echo "<li>{$category->name}</li>";
					}	
				?>
			</ul>
		</footer>
	</article>
	<?
}
```

# DB

The ORM outlined above is built on the back of a powerful, lightweight DB class that acts as a distributed query builder.

## Get

```php
$db = DB::Instance();
$db->get('blog');
/* Builds: SELECT * FROM blogs */
```

## Select

```php
$db = DB::Instance();
$db->select('name')->get('blog');
/* Builds: SELECT name FROM blogs */
```

## Join

```php
$db = DB::Instance();
$db->join('blog', 'article.blog_id = blog.blog_id')->get('article');
/* Builds: SELECT * FROM article JOIN blog ON article.blog_id = blog.blog_id */

// LEFT JOIN
$db->join('blog', 'article.blog_id = blog.blog_id', 'left')->get('article');
/* Builds: SELECT * FROM article LEFT JOIN blog ON article.blog_id = blog.blog_id */
```

## Where

```php
$db = DB::Instance();

// Pass key and value
$db->where('name', 'News')->get('blog');
/* Builds: SELECT * FROM blogs WHERE name = "News" */

// Manually write the where clause
$db->where('name LIKE "% News"')->get('blog');
/* Builds: SELECT * FROM blogs WHERE name LIKE "% News" */

// Pass array of key/value pairs
$db->where(array(
	'name' => 'News'
))->get('blog');
/* Builds: SELECT * FROM blogs WHERE name = "News" */
```

## Order

```php
$db = DB::Instance();
$db->order('updated', 'DESC')->get('blog');
/* Builds: SELECT * FROM blogs ORDER BY updated DESC */
```

## Limit

```php
$db = DB::Instance();
$db->limit(1)->get('article');
/* Builds: SELECT * FROM article LIMIT 1 */

// Offset by 10
$db->limit(10, 10)->get('article');
/* Builds: SELECT * FROM article LIMIT 10, 10 */
```