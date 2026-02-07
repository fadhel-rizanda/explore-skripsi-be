<?php

namespace App\Http\Controllers;

use App\Enums\AttachmentTypeEnum;
use App\Http\Requests\GeneratePresignedUrlRequest;
use App\Models\Attachment;
use App\Traits\ResponseAPI;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    use ResponseAPI;

    protected S3Client $s3Client;

    protected string $bucket;

    public function __construct(S3Client $s3Client)
    {
        $this->s3Client = $s3Client;
        $this->bucket = config('filesystems.disks.s3.bucket');
    }

    public function generatePresignedUrl(GeneratePresignedUrlRequest $request)
    {
        $allowedTypes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/webp' => ['webp'],
            'application/pdf' => ['pdf'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        ];

        $mimeType = $request->input('mime_type');

        if (! isset($allowedTypes[$mimeType])) {
            return $this->sendError('Unsupported file type.', 400);
        }

        $filename = $request->input('filename');
        // Extract file extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (empty($extension)) {
            return $this->sendError('Filename must include a file extension (e.g., document.pdf, image.jpg).', 400);
        }

        if (! in_array($extension, $allowedTypes[$mimeType])) {
            return $this->sendError("File extension '{$extension}' does not match content type '{$mimeType}'. Expected: " . implode(', ', $allowedTypes[$mimeType]), 400);
        }

        try {
            // Generate path
            $uuid = Str::uuid7();
            $uniqueFileName = $uuid . '.' . $extension;

            $isPublic = $request->input('is_public', false);
            $path = $isPublic ? 'public/' . $uuid . '/' . $uniqueFileName : 'private/' . $uuid . '/' . $uniqueFileName;

            // Generate presigned URL dengan command PutObject
            $command = $this->s3Client->getCommand('PutObject', [
                'Bucket' => $this->bucket,
                'Key' => $path,
                'ContentType' => $mimeType,
            ]);

            // Create presigned request (valid 15 menit)
            $presignedRequest = $this->s3Client->createPresignedRequest($command, '+15 minutes');
            $uploadUrl = (string) $presignedRequest->getUri();

            $publicUrl = $isPublic
                ? "https://{$this->bucket}.s3." . config('filesystems.disks.s3.region') . ".amazonaws.com/{$path}"
                : null;

            $user = auth('api')->user();

            $document = Attachment::create([
                'filename' => $filename,
                'path' => $path,
                'file_size' => $request->input('file_size'),
                'mime_type' => $mimeType,
                'status' => AttachmentTypeEnum::PENDING->value,
                'uploaded_by' => $user->id,
                'public_url' => $publicUrl,
            ]);

            $responseData = [
                'upload_url' => $uploadUrl,
                'id' => $document->id,
                'path' => $path,
                'mime_type' => $mimeType,
                'expires_in' => 900,
            ];

            if ($isPublic) {
                $responseData['public_url'] = $publicUrl;
            }

            return $this->sendSuccess(
                'Presigned URL generated successfully.',
                $responseData,
                201
            );
        } catch (\Exception $exception) {
            Log::error('generatePresignedUrl failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to generate presigned URL', 500);
        }
    }

    public function confirmUpload(Attachment $document)
    {
        if (! Storage::disk('s3')->exists($document->path)) {
            return $this->sendError('File not found in storage.', 404);
        }

        try {
            if ($document->uploaded_by !== auth('api')->id()) {
                return $this->sendError('Unauthorized.', 403);
            }
            $document->update([
                'status' => AttachmentTypeEnum::COMPLETED->value,
                'uploaded_at' => now(),
            ]);

            return $this->sendSuccess('Upload confirmed successfully.', $document);
        } catch (\Exception $exception) {
            Log::error('confirmUpload failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to confirm upload.', 500);
        }
    }

    //    TODO: Add rate limiting to this endpoint
    public function generateDownloadUrl(Attachment $document)
    {
        if ($document->status !== AttachmentTypeEnum::COMPLETED->value || ! Storage::disk('s3')->exists($document->path)) {
            return $this->sendError('File not found in storage.', 404);
        }

        try {
            $url = Storage::disk('s3')->temporaryUrl(
                $document->path,
                now()->addHour(),
                [
                    'ResponseContentDisposition' => 'attachment; filename="' . $document->filename . '"',
                ]
            );

            return $this->sendSuccess('Download URL generated successfully.', [
                'download_url' => $url,
                'expires_in' => 3600, // seconds
                'id' => $document->id,
                'mime_type' => $document->mime_type,
            ]);
        } catch (\Exception $exception) {
            Log::error('generateDownloadUrl failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to generate download URL.', 500);
        }
    }

    public function deleteDocument(Attachment $document)
    {
        try {
            if (Storage::disk('s3')->exists($document->path)) {
                Storage::disk('s3')->delete($document->path);
            }
        } catch (\Exception $exception) {
            Log::error('deleteDocument failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError('Failed to delete document from storage.', 500);
        }

        $document->delete();

        return $this->sendSuccess('Document deleted successfully.');
    }
}
