<?php

namespace Tests\Unit\Domains\FileManager\Services;

use App\Domains\FileManager\Services\LocalFileStorage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalFileStorageTest extends TestCase
{
    private LocalFileStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->storage = new LocalFileStorage('local');
    }

    #[Test]
    public function it_stores_a_file_and_returns_relative_path(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'storage_test');
        file_put_contents($tempPath, 'test content for storage');
        $relativePath = 'files/test/document.pdf';

        $result = $this->storage->store($tempPath, $relativePath);
        unlink($tempPath);

        $this->assertSame($relativePath, $result);
        Storage::disk('local')->assertExists($relativePath);
    }

    #[Test]
    public function it_retrieves_full_path_for_existing_file(): void
    {
        Storage::disk('local')->put('files/test/existing.pdf', 'content');

        $result = $this->storage->retrieve('files/test/existing.pdf');

        $this->assertNotNull($result);
        $this->assertFileExists($result);
        $this->assertStringContainsString('files/test/existing.pdf', $result);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_file(): void
    {
        $result = $this->storage->retrieve('files/test/nonexistent.pdf');

        $this->assertNull($result);
    }

    #[Test]
    public function it_deletes_existing_file(): void
    {
        Storage::disk('local')->put('files/test/to-delete.pdf', 'content');

        $result = $this->storage->delete('files/test/to-delete.pdf');

        $this->assertTrue($result);
        Storage::disk('local')->assertMissing('files/test/to-delete.pdf');
    }

    #[Test]
    public function it_returns_false_when_deleting_nonexistent_file(): void
    {
        $result = $this->storage->delete('files/test/nonexistent.pdf');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_checks_if_file_exists(): void
    {
        Storage::disk('local')->put('files/test/exists.pdf', 'content');

        $this->assertTrue($this->storage->exists('files/test/exists.pdf'));
        $this->assertFalse($this->storage->exists('files/test/not-exists.pdf'));
    }

    #[Test]
    public function it_returns_mime_type(): void
    {
        Storage::disk('local')->put('files/test/doc.pdf', 'content');

        $mime = $this->storage->mimeType('files/test/doc.pdf');

        $this->assertIsString($mime);
    }

    #[Test]
    public function it_returns_file_size(): void
    {
        Storage::disk('local')->put('files/test/sized.pdf', '0123456789');

        $size = $this->storage->size('files/test/sized.pdf');

        $this->assertEquals(10, $size);
    }

    #[Test]
    public function it_stores_and_retrieves_with_correct_content(): void
    {
        $content = 'Hello World Content';
        $relativePath = 'files/test/hello.txt';

        $tempPath = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempPath, $content);

        $this->storage->store($tempPath, $relativePath);
        unlink($tempPath);

        $fullPath = $this->storage->retrieve($relativePath);
        $retrievedContent = file_get_contents($fullPath);

        $this->assertEquals($content, $retrievedContent);
    }

    #[Test]
    public function it_handles_files_in_subdirectories(): void
    {
        Storage::disk('local')->put('nested/deep/path/file.pdf', 'nested content');

        $this->assertTrue($this->storage->exists('nested/deep/path/file.pdf'));

        $deleted = $this->storage->delete('nested/deep/path/file.pdf');
        $this->assertTrue($deleted);
        Storage::disk('local')->assertMissing('nested/deep/path/file.pdf');
    }
}
