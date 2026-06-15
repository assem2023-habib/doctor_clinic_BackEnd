<?php

namespace App\Http\Controllers\Api\V1\File;

use App\Domains\FileManager\Actions\AssembleChunksAction;
use App\Domains\FileManager\Actions\DeleteFileAction;
use App\Domains\FileManager\Actions\InitChunkedUploadAction;
use App\Domains\FileManager\Actions\RequestDownloadLinkAction;
use App\Domains\FileManager\Actions\StoreFileAction;
use App\Domains\FileManager\Actions\UploadChunkAction;
use App\Domains\FileManager\DTOs\FileData;
use App\Domains\FileManager\Models\File;
use App\Domains\FileManager\Models\FileDownload;
use App\Domains\FileManager\Requests\CompleteUploadRequest;
use App\Domains\FileManager\Requests\InitUploadRequest;
use App\Domains\FileManager\Requests\RequestDownloadLinkRequest;
use App\Domains\FileManager\Requests\StoreFileRequest;
use App\Domains\FileManager\Requests\UploadChunkRequest;
use App\Domains\FileManager\Resources\FileResource;
use App\Domains\Shared\Exceptions\ApiServiceException;
use App\Domains\Shared\Responses\ApiResponse;
use App\Enums\HttpStatusEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController
{
    public function __construct(
        private readonly InitChunkedUploadAction $initChunkedUpload,
        private readonly UploadChunkAction $uploadChunk,
        private readonly AssembleChunksAction $assembleChunks,
        private readonly StoreFileAction $storeFile,
        private readonly DeleteFileAction $deleteFile,
        private readonly RequestDownloadLinkAction $requestDownloadLink,
    ) {}

    public function initUpload(InitUploadRequest $request): JsonResponse
    {
        $dto = FileData::fromChunkedUpload($request->validated());
        $file = $this->initChunkedUpload->execute($dto, auth()->id());

        return ApiResponse::success(
            data: [
                'upload_id' => $file->id,
                'chunk_size' => config('files.chunk_size', 5242880),
                'total_chunks' => $request->input('total_chunks'),
            ],
            message: __('Chunked upload initialized'),
            status: HttpStatusEnum::Created,
        );
    }

    public function uploadChunk(UploadChunkRequest $request, File $file): JsonResponse
    {
        if ($file->upload_status?->value !== 'uploading') {
            return ApiResponse::error(
                message: __('Upload is not in progress'),
                status: HttpStatusEnum::BadRequest,
            );
        }

        $this->uploadChunk->execute(
            $file,
            $request->file('chunk'),
            (int) $request->input('chunk_index'),
        );

        return ApiResponse::success(
            data: ['received_index' => (int) $request->input('chunk_index')],
            message: __('Chunk uploaded successfully'),
        );
    }

    public function completeUpload(CompleteUploadRequest $request, File $file): JsonResponse
    {
        if ($file->upload_status?->value !== 'uploading') {
            return ApiResponse::error(
                message: __('Upload is not in progress'),
                status: HttpStatusEnum::BadRequest,
            );
        }

        try {
            $file = $this->assembleChunks->execute($file, $request->input('checksum'));
        } catch (\RuntimeException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                status: HttpStatusEnum::BadRequest,
            );
        }

        return ApiResponse::success(
            data: new FileResource($file),
            message: __('File uploaded and assembled successfully'),
        );
    }

    public function store(StoreFileRequest $request): JsonResponse
    {
        $dto = FileData::fromDirectUpload($request->validated());
        $file = $this->storeFile->execute($dto, auth()->id());

        return ApiResponse::created(
            data: new FileResource($file),
            message: __('File uploaded successfully'),
        );
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = File::with(['medicalRecord']);

        if ($user->hasRole('patient')) {
            $query->whereHas('medicalRecord.patient', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->hasRole('doctor')) {
            $query->whereHas('medicalRecord.doctor', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif (! $user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        if ($request->boolean('mine')) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('medical_record_id')) {
            $query->where('medical_record_id', $request->input('medical_record_id'));
        }

        if ($request->filled('file_category')) {
            $query->where('file_category', $request->input('file_category'));
        }

        $perPage = $request->input('per_page', 15);
        $files = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return ApiResponse::success(
            data: FileResource::collection($files->items()),
            message: __('Files retrieved successfully'),
            pagination: ApiResponse::pagination($files),
        );
    }

    public function show(File $file): JsonResponse
    {
        $file->load(['medicalRecord.patient.user', 'medicalRecord.doctor.user']);

        if (! app(\App\Domains\FileManager\Services\FileAccessService::class)->canAccess(auth()->user(), $file)) {
            return ApiResponse::forbidden(__('You do not have access to this file'));
        }

        return ApiResponse::success(
            data: new FileResource($file),
            message: __('File retrieved successfully'),
        );
    }

    public function destroy(File $file): JsonResponse
    {
        $this->deleteFile->execute($file);

        return ApiResponse::noContent(__('File deleted successfully'));
    }

    public function requestDownloadLink(RequestDownloadLinkRequest $request, File $file): JsonResponse
    {
        try {
            $linkData = $this->requestDownloadLink->execute($file, auth()->user());
        } catch (\RuntimeException $e) {
            return ApiResponse::forbidden($e->getMessage());
        }

        return ApiResponse::success(
            data: $linkData,
            message: __('Download link generated'),
        );
    }

    public function download(Request $request, File $file): BinaryFileResponse
    {
        if ($file->path === null || ! $file->full_path) {
            throw new ApiServiceException(
                errorCode: 'FILE_NOT_FOUND',
                message: __('File not found'),
                status: HttpStatusEnum::NotFound,
            );
        }

        FileDownload::create([
            'file_id' => $file->id,
            'user_id' => $request->input('user'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'downloaded_at' => now(),
        ]);

        return response()->file($file->full_path, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'attachment; filename="' . $file->original_name . '"',
            'Accept-Ranges' => 'bytes',
        ]);
    }
}
