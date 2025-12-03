<?php
declare(strict_types=1);
namespace Coroq\View;

use InvalidArgumentException;

class ViewRenderer {
  private string $templateDirectory;

  public function __construct(string $templateDirectory) {
    // Validate empty
    if ($templateDirectory === '') {
      throw new InvalidArgumentException("Template directory cannot be empty");
    }

    // Validate exists and is directory
    if (!is_dir($templateDirectory)) {
      throw new InvalidArgumentException("Template directory not found: $templateDirectory");
    }

    // Validate readable
    if (!is_readable($templateDirectory)) {
      throw new InvalidArgumentException("Template directory not readable: $templateDirectory");
    }

    $this->templateDirectory = $templateDirectory;
  }

  public function templateExists(string $template): bool {
    return is_file($this->resolveTemplatePath($template));
  }

  protected function resolveTemplatePath(string $template): string {
    if ($template === '') {
      throw new InvalidArgumentException("Empty path not allowed");
    }
    if ($template[0] === '/') {
      throw new InvalidArgumentException("Absolute path not allowed: $template");
    }
    $parts = explode('/', $template);
    if (in_array('..', $parts, true)) {
      throw new InvalidArgumentException("Path traversal not allowed: $template");
    }
    return "$this->templateDirectory/$template";
  }

  public function render(string $template, array $arguments = []): string {
    $fullPath = $this->resolveTemplatePath($template);

    if (!is_file($fullPath)) {
      throw new InvalidArgumentException("Template not a file: $template");
    }

    if (!is_readable($fullPath)) {
      throw new InvalidArgumentException("Template not readable: $template");
    }

    // Render with closure isolation
    $closure = function() {
      try {
        ob_start();
        extract(func_get_arg(1));
        include func_get_arg(0);
        return ob_get_contents();
      }
      finally {
        ob_end_clean();
      }
    };
    $closure = $closure->bindTo($this, $this);
    return $closure($fullPath, $arguments);
  }

  public function include(string $template, array $arguments = []): void {
    echo $this->render($template, $arguments);
  }
}
