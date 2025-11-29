# coroq/view

Renders plain PHP view template files with data.

## Requirements

- PHP 8.0+

## Installation

```bash
composer require coroq/view
```

## Quick Start

Suppose you have view templates organized like this:

```
project/
├── view/
│   ├── common/
│   │   └── header.php
│   ├── welcome.php
│   └── ...
└── index.php
```

In your code:

```php
<?php
use Coroq\View\ViewRenderer;

// Create a renderer with your view directory
$view = new ViewRenderer(__DIR__ . '/view');

// Render the template, supplying the data it needs
echo $view->render(
  'welcome.php',     // Template path, resolved to __DIR__ . '/view/welcome.php'
  [                  // Data array - keys become variable names in template
    'username' => 'Alice',
    'message' => 'Welcome back!'
  ]
);
```

Your template file at `view/welcome.php`:

```php
<?php
/**
 * @var ViewRenderer $this
 */
// Templates are executed in the context of the ViewRenderer instance.
// Include another template using $this->include().
// Path is relative to the view directory.
// Data passed as second argument becomes variables in the included template.
$this->include('common/header.php', ['title' => 'Welcome']);
?>
<h1><?= htmlspecialchars($username) ?></h1>
<p><?= htmlspecialchars($message) ?></p>
```

The included `view/common/header.php`:

```php
<?php // $title is available from the data passed to include() ?>
<header><?= htmlspecialchars($title) ?></header>
```

## Benefits

**Centralized base directory**: Define template directory once in the constructor, avoiding scattered `__DIR__ . '/../../../view/...'` paths.

**String return**: Returns rendered output as a string instead of echoing directly. Useful for creating PSR-7 responses. Eliminates manual `ob_start()` and `ob_get_clean()` wrapping.

**Variable isolation**: Template variables are isolated from controller logic. Templates receive only the variables they need. Variables are accessed directly as `$username` instead of `$data['username']`.

## Usage

### render()

**`render(string $template, array $data): string`**

Renders a template and returns the output as a string.

- `$template` - Path to the template file, relative to the base directory. Must not contain absolute paths or directory traversal (`../`).
- `$data` - Associative array of variables to pass to the template. Keys become variable names.
- Returns: The rendered HTML as a string.

### include()

**`include(string $template, array $data): void`**

Renders a template and echoes the output directly. Use within templates as `$this->include()`.

- Parameters: Same as `render()`.
- Returns: Nothing (outputs directly).

### Subclass for helpers

Define reusable components as methods in a ViewRenderer subclass:

```php
class BlogView extends ViewRenderer {
  public function __construct() {
    parent::__construct(__DIR__ . '/view/blog');
  }

  protected function header() {
    $this->include('header.php');
  }

  protected function footer() {
    $this->include('footer.php');
  }

  protected function postCard(object $post) {
    $this->include('post_card.php', ['post' => $post]);
  }
}

// Use in templates
$blog = new BlogView();
echo $blog->render('index.php', ['posts' => $posts]);
```

Template using helpers:
```php
<?php // index.php ?>
<?php $this->header(); ?>

<div class="posts">
  <?php foreach ($posts as $post): ?>
    <?php $this->postCard($post); ?>
  <?php endforeach; ?>
</div>

<?php $this->footer(); ?>
```

### Multiple view classes

Separate view classes for different areas of your application:

```php
// User-facing templates
class UserView extends ViewRenderer {
  public function __construct() {
    parent::__construct(__DIR__ . '/view/user');
  }
}

// Admin templates
class AdminView extends ViewRenderer {
  public function __construct() {
    parent::__construct(__DIR__ . '/view/admin');
  }
}

```

### HTML escaping

HTML escaping is out of scope for this library. You have three options:

**1. Use `htmlspecialchars()` directly**

```php
<h1><?= htmlspecialchars($title) ?></h1>
```

**2. Use an HTML handling library like [`coroq/html`](https://github.com/ozami/coroq-html)**

```php
<h1><?= h($title) ?></h1>
```

**3. Define escape method in ViewRenderer subclass**

```php
class UserView extends ViewRenderer {
  protected function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }
}

// In template
<h1><?= $this->e($title) ?></h1>
```

## License

MIT
