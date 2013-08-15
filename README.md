# ORM

## Naming Conventions

**Tables** and **models** share names. A table named *blog* should have a model named *Blog*. *(This can be overridden by `$this->table_name('name')` in your `init() `method)*

The **primary key** of a table should be the table name followed by *_id*. A table named *blog* should have a primary key of *blog_id*.

**Associations** occur by matching primary key to foreign key. A model named *Article* **belongs to** a model named *Blog*. The *article* table has a *blog_id* field that matches the *blog_id* field on the *blog* table.

## Associations

The power of ORM is in associations between models. Currently only the following associations are supported:

- belongs to
- has many

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
	}
	
}

class Author extends Model {
	
	function init()
	{
		$this->has_many('articles');
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
		<h2><?=date('F d, Y', $article->created_at)?> by <?=$article->author->name?> | <?=$article->comments->count()?> comments</h2>
		<?=$article->snippet?>
	</article>
	<?
}
```