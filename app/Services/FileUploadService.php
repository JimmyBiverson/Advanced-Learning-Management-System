<?php

namespace App\Services;

use App\Models\ChunkedUpload;

class FileUploadService
{
    protected LocalFileUploadService|S3MultipartUploadService|R2MultipartUploadService $uploadService;

    public function __construct()
    {
        $this->uploadService = match (config('filesystems.default')) {
            's3' => new S3MultipartUploadService,
            'r2' => new R2MultipartUploadService,
            default => new LocalFileUploadService,
        };
    }

    /**
     * Get the underlying upload service instance
     */
    public function getService(): LocalFileUploadService|S3MultipartUploadService|R2MultipartUploadService
    {
        return $this->uploadService;
    }

    /**
     * Initialize multipart upload
     *
     * @param  string  $filename  Original filename
     * @param  string  $mimeType  Mime type of the file
     * @param  int  $fileSize  Total file size
     * @param  int  $userId  User ID
     * @param  array  $metadata  Additional metadata
     */
    public function initiateUpload(string $filename, string $mimeType, int $fileSize, int $userId, array $metadata = []): ChunkedUpload
    {
        return $this->uploadService->initiateUpload($filename, $mimeType, $fileSize, $userId, $metadata);
    }

    /**
     * Upload a part of the file
     *
     * @param  ChunkedUpload  $upload  Upload record
     * @param  int  $partNumber  Part number (1-based)
     * @param  string  $partContent  Content of the part
     * @return array Part information for completing the upload
     */
    public function uploadPart(ChunkedUpload $upload, int $partNumber, string $partContent): array
    {
        return $this->uploadService->uploadPart($upload, $partNumber, $partContent);
    }

    /**
     * Complete multipart upload
     *
     * @param  ChunkedUpload  $upload  Upload record
     * @param  array  $parts  Array of part information from uploadPart()
     */
    public function completeUpload(ChunkedUpload $upload, array $parts): bool
    {
        return $this->uploadService->completeUpload($upload, $parts);
    }

    /**
     * Abort multipart upload
     *
     * @param  ChunkedUpload  $upload  Upload record
     */
    public function abortUpload(ChunkedUpload $upload): bool
    {
        return $this->uploadService->abortUpload($upload);
    }

    /**
     * Delete a file
     *
     * @param  ChunkedUpload  $upload  Upload record
     */
    public function deleteFile(ChunkedUpload $upload): bool
    {
        return $this->uploadService->deleteFile($upload);
    }

    /**
     * Get current storage driver
     */
    public function getDriver(): string
    {
        return config('filesystems.default');
    }

    /**
     * Check if using local storage
     */
    public function isLocalStorage(): bool
    {
        return config('filesystems.default') === 'local';
    }

    /**
     * Check if using cloud storage (S3 or R2)
     */
    public function isS3Storage(): bool
    {
        return config('filesystems.default') === 's3';
    }

    /**
     * Check if using cloud storage (S3 or R2)
     */
    public function isR2Storage(): bool
    {
        return config('filesystems.default') === 'r2';
    }
}
