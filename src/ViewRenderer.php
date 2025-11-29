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

  public function render(string $template, array $arguments = []): string {
    try {
      ob_start();
      $this->include($template, $arguments);
      return ob_get_contents();
    }
    finally {
      ob_end_clean();
    }
  }

  public function include(string $template, array $arguments = []): void {
    // Reject empty path
    if ($template === '') {
      throw new InvalidArgumentException("Empty path not allowed");
    }

    // Reject absolute paths
    if ($template[0] === '/') {
      throw new InvalidArgumentException("Absolute path not allowed: $template");
    }

    // Reject directory traversal
    $parts = explode('/', $template);
    if (in_array('..', $parts, true)) {
      throw new InvalidArgumentException("Path traversal not allowed: $template");
    }

    // Build full path
    $fullPath = "$this->templateDirectory/$template";

    // Must be a file (not directory)
    if (!is_file($fullPath)) {
      throw new InvalidArgumentException("Template not a file: $template");
    }

    // File must be readable
    if (!is_readable($fullPath)) {
      throw new InvalidArgumentException("Template not readable: $template");
    }

    // Include with closure isolation
    $closure = function() {
      extract(func_get_arg(1));
      include func_get_arg(0);
    };
    $closure = $closure->bindTo($this, $this);
    $closure($fullPath, $arguments);
  }
}
