<?php

namespace App\Domains\FileManager\Models;

use App\Domains\FileManager\Services\FileStorageService;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Enums\FileCategoryEnum;
use App\Enums\FileUploadStatusEnum;
use App\Enums\StorageDiskEnum;
use App\Models\User;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasUuidV7, SoftDeletes;

    protected $fillable = [
        'user_id',
        'medical_record_id',
        'original_name',
        'mime_type',
        'size',
        'path',
        'disk',
        'checksum',
        'upload_status',
        'file_category',
        'total_chunks',
        'metadata',
    ];

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'total_chunks' => 'integer',
            'metadata' => 'array',
            'upload_status' => FileUploadStatusEnum::class,
            'file_category' => FileCategoryEnum::class,
            'disk' => StorageDiskEnum::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(FileDownload::class);
    }

    public function getFullPathAttribute(): ?string
    {
        if ($this->path === null) {
            return null;
        }

        $storage = app(FileStorageService::class);
        $driver = $storage->driver($this->disk?->value ?? 'local');

        return $driver->retrieve($this->path);
    }
}
