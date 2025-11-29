<?php
use Coroq\View\ViewRenderer;
use PHPUnit\Framework\TestCase;

class ViewRendererTest extends TestCase {
  private $template_dir;
  private $renderer;

  protected function setUp(): void {
    $this->template_dir = __DIR__ . '/fixtures/templates';
    $this->renderer = new ViewRenderer($this->template_dir);
  }

  public function testConstructorWithEmptyDirectoryThrowsException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Template directory cannot be empty');
    new ViewRenderer('');
  }

  public function testConstructorWithNonExistentDirectoryThrowsException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Template directory not found');
    new ViewRenderer('/does/not/exist/anywhere');
  }

  public function testConstructorWithFileInsteadOfDirectoryThrowsException() {
    // Create a file to test with
    $file = $this->template_dir . '/simple.php';
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Template directory not found');
    new ViewRenderer($file);
  }

  public function testRenderSimpleTemplate() {
    $result = $this->renderer->render('simple.php', ['message' => 'Hello World']);
    $this->assertEquals('Hello World', $result);
  }

  public function testRenderWithMultipleVariables() {
    $result = $this->renderer->render('multiple.php', [
      'title' => 'Test Title',
      'content' => 'Test Content'
    ]);
    $this->assertStringContainsString('Test Title', $result);
    $this->assertStringContainsString('Test Content', $result);
  }

  public function testRenderInSubdirectory() {
    $result = $this->renderer->render('sub/nested.php', ['value' => 'nested']);
    $this->assertEquals('nested', $result);
  }

  public function testAbsolutePathThrowsException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Absolute path not allowed');
    $this->renderer->render('/etc/passwd', []);
  }

  public function testDirectoryTraversalThrowsException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Path traversal not allowed');
    $this->renderer->render('../../../etc/passwd', []);
  }

  public function testDirectoryTraversalInMiddleThrowsException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Path traversal not allowed');
    $this->renderer->render('foo/../bar.php', []);
  }

  public function testDirectoryTraversalAtEndThrowsException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Path traversal not allowed');
    $this->renderer->render('foo/..', []);
  }

  public function testDotsInFilenameIsAllowed() {
    // File with .. in name (not traversal) should work
    $result = $this->renderer->render('file..dots.php', ['value' => 'dots']);
    $this->assertEquals('dots', $result);
  }

  public function testNonExistentTemplateThrowsException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Template not a file');
    $this->renderer->render('does_not_exist.php', []);
  }

  public function testEmptyTemplatePathThrowsException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Empty path not allowed');
    $this->renderer->render('', []);
  }

  public function testVariableIsolation() {
    // User passes 'template' key - should not break rendering
    $result = $this->renderer->render('simple.php', [
      'message' => 'Works',
      'template' => 'evil.php'
    ]);
    $this->assertEquals('Works', $result);
  }

  public function testSubclassCanAddHelperMethods() {
    $subclass = new ViewRendererSubclass($this->template_dir);
    $result = $subclass->render('with_helper.php', []);
    $this->assertStringContainsString('HEADER', $result);
    $this->assertStringContainsString('CONTENT', $result);
  }

  public function testIncludeMethodEchosOutput() {
    ob_start();
    $this->renderer->include('simple.php', ['message' => 'Echo Test']);
    $output = ob_get_clean();
    $this->assertEquals('Echo Test', $output);
  }

  public function testClosureIsolationPreservesThisAccess() {
    $subclass = new ViewRendererSubclass($this->template_dir);
    $result = $subclass->render('uses_this.php', []);
    $this->assertStringContainsString('HEADER', $result);
  }
}

// Test subclass to verify helper pattern
class ViewRendererSubclass extends ViewRenderer {
  public function header() {
    return 'HEADER';
  }
}
