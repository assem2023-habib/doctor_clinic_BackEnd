<?php

namespace Tests\Unit\Domains\FileManager\DTOs;

use App\Domains\FileManager\DTOs\FileData;
use App\Enums\FileCategoryEnum;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FileDataTest extends TestCase
{
    #[Test]
    public function it_creates_from_chunked_upload_data(): void
    {
        $data = [
            'medical_record_id' => '019eca5c-1234-5678-9abc-def012345678',
            'file_category' => 'lab_result',
            'original_name' => 'report.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10485760,
            'checksum' => 'abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789',
        ];

        $fileData = FileData::fromChunkedUpload($data);

        $this->assertSame($data['medical_record_id'], $fileData->medicalRecordId);
        $this->assertTrue(FileCategoryEnum::LabResult === $fileData->fileCategory);
        $this->assertSame($data['original_name'], $fileData->originalName);
        $this->assertSame($data['mime_type'], $fileData->mimeType);
        $this->assertSame($data['file_size'], $fileData->size);
        $this->assertEquals($data['checksum'], $fileData->checksum);
        $this->assertNull($fileData->file);
        $this->assertNull($fileData->path);
    }

    #[Test]
    public function it_creates_from_chunked_upload_without_checksum(): void
    {
        $data = [
            'medical_record_id' => '019eca5c-1234-5678-9abc-def012345678',
            'file_category' => 'document',
            'original_name' => 'notes.txt',
            'mime_type' => 'text/plain',
            'file_size' => 5000,
        ];

        $fileData = FileData::fromChunkedUpload($data);

        $this->assertNull($fileData->checksum);
        $this->assertEquals(FileCategoryEnum::Document, $fileData->fileCategory);
        $this->assertEquals(5000, $fileData->size);
    }

    #[Test]
    public function it_creates_from_direct_upload(): void
    {
        $file = UploadedFile::fake()->create('photo.jpg', 100);

        $validated = [
            'file' => $file,
            'medical_record_id' => '019eca5c-1234-5678-9abc-def012345678',
            'file_category' => 'xray',
        ];

        $fileData = FileData::fromDirectUpload($validated);

        $this->assertSame($validated['medical_record_id'], $fileData->medicalRecordId);
        $this->assertTrue(FileCategoryEnum::XRay === $fileData->fileCategory);
        $this->assertSame('photo.jpg', $fileData->originalName);
        $this->assertSame('image/jpeg', $fileData->mimeType);
        $this->assertSame(102400, $fileData->size);
        $this->assertSame($file, $fileData->file);
    }

    #[Test]
    public function it_handles_all_file_categories(): void
    {
        foreach (FileCategoryEnum::cases() as $category) {
            $data = [
                'medical_record_id' => '019eca5c-1234-5678-9abc-def012345678',
                'file_category' => $category->value,
                'original_name' => 'file.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 100,
            ];

            $fileData = FileData::fromChunkedUpload($data);

            $this->assertEquals($category, $fileData->fileCategory);
        }
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $data = [
            'medical_record_id' => '019eca5c-1234-5678-9abc-def012345678',
            'file_category' => 'document',
            'original_name' => 'doc.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
        ];

        $fileData = FileData::fromChunkedUpload($data);

        $reflection = new \ReflectionClass($fileData);
        $props = $reflection->getProperties(\ReflectionProperty::IS_READONLY);

        $this->assertNotEmpty($props);
        $this->assertTrue($reflection->getConstructor()->isPrivate());
    }
}
