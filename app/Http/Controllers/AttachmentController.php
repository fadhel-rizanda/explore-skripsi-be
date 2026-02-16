<?php

namespace App\Http\Controllers;

use App\Enums\AdoptionStatusEnum;
use App\Enums\AttachmentTypeEnum;
use App\Enums\ModelReferenceEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusTypeEnum;
use App\Http\Requests\GenerateDownloadUrlRequest;
use App\Http\Requests\GeneratePresignedUrlRequest;
use App\Models\Attachment;
use App\Models\Status;
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
            return $this->sendError("File extension $extension does not match content type $mimeType. Expected: " . implode(', ', $allowedTypes[$mimeType]), 400);
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
                ? "https://$this->bucket.s3." . config('filesystems.disks.s3.region') . ".amazonaws.com/$path"
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
                'reference_by' => $request->input('reference_by'),
                'reference_id' => $request->input('reference_id'),
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

    public function generateDownloadUrl(Attachment $document, GenerateDownloadUrlRequest $request)
    {
        if ($document->status !== AttachmentTypeEnum::COMPLETED->value) {
            return $this->sendError('File not found in storage.', 404);
        }

        $relatedModel = $this->getRelatedModel($document);
        $user = auth('api')->user();
        if ($document->public_url) {
            // Public document, allow access
        } elseif ($relatedModel) {
            $hasAccess = $this->checkUserAccessToModel($user, $relatedModel);

            $isOwner = $user && (string) $document->uploaded_by === (string) $user->id;
            $isAdmin = $user && $user->hasRole(RoleEnum::ADMIN);

            if (! $hasAccess && ! $isOwner && ! $isAdmin) {
                return $this->sendError('Unauthorized to access this private document.', 403);
            }
        } else {
            $isOwner = $user && (string) $document->uploaded_by === (string) $user->id;
            $isAdmin = $user && $user->hasRole(RoleEnum::ADMIN);

            if (! $isOwner && ! $isAdmin) {
                return $this->sendError('Related model not found or document is private.', 404);
            }
        }

        try {
            $mode = $request->query('mode', 'download');

            $disposition = $mode === 'preview'
                ? 'inline; filename="' . $document->filename . '"'
                : 'attachment; filename="' . $document->filename . '"';

            $url = Storage::disk('s3')->temporaryUrl(
                $document->path,
                now()->addHour(),
                [
                    'ResponseContentDisposition' => $disposition,
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
            Storage::disk('s3')->delete($document->path);
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

    private function getRelatedModel(Attachment $document)
    {
        if (! $document->reference_by || ! $document->reference_id) {
            return null;
        }

        try {
            $reference = ModelReferenceEnum::tryFrom($document->reference_by);

            if (! $reference) {
                Log::warning('Unknown reference_by: ' . $document->reference_by);

                return null;
            }

            return $reference?->resolve($document->reference_id);
        } catch (\Exception $e) {
            Log::error('Error getting related model: ' . $e->getMessage());
        }
        return null;
    }

    private function checkUserAccessToModel($user, $model)
    {
        if (! $user) {
            return false;
        }
        $userId = (string) $user->id;
        $cacheKey = "access_check_{$userId}_" . get_class($model) . "_{$model->id}";

        return cache()->remember($cacheKey, now()->addMinutes(15), function () use ($model, $userId) {
            switch (get_class($model)) {
                case \App\Models\Adoption::class:
                    return (string) $model->adopter_id === $userId ||
                        (string) $model->pet?->user_id === $userId;
                case \App\Models\Pet::class:
                    if ((string) $model->user_id === $userId) {
                        return true;
                    }

                    return \App\Models\Adoption::where('pet_id', $model->id)
                        ->where('adopter_id', $userId)
                        ->whereIn('status_id', [
                            Status::getCache(StatusTypeEnum::ADOPTION->value, AdoptionStatusEnum::NEED_AN_ACTION->value)->id,
                            Status::getCache(StatusTypeEnum::ADOPTION->value, AdoptionStatusEnum::IN_PROGRESS->value)->id,
                            Status::getCache(StatusTypeEnum::ADOPTION->value, AdoptionStatusEnum::PENDING->value)->id,
                        ])
                        ->exists();
                case \App\Models\Report::class:
                case \App\Models\Post::class:
                    return (string) $model->created_by === $userId;
                case \App\Models\Community::class:
                    return $model->members()->where('mt_user.id', $userId)->exists();
                case \App\Models\User::class:
                    return (string) $model->id === $userId;
                case \App\Models\Chat::class:
                    return $model->users()->where('mt_user.id', $userId)->exists();
                case \App\Models\MeetNGreet::class:
                case \App\Models\Handover::class:
                case \App\Models\Requirement::class:
                    $adoption = $model->adoption;

                    return (string) $adoption?->adopter_id === $userId ||
                        (string) $adoption?->pet?->user_id === $userId;
                default:
                    return false;
            }
        });
    }
}
