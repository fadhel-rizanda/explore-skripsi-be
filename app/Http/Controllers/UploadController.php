<?php

namespace App\Http\Controllers;

use App\Http\Requests\generatePresignedUrlRequest;
use App\Models\Attachment;
use App\Traits\ResponseAPI;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    use ResponseAPI;

    protected S3Client $s3Client;
    protected string $bucket;

    public function __construct(S3Client $s3Client)
    {
        $this->s3Client = $s3Client;
        $this->bucket = config('filesystems.disks.s3.bucket');
    }

    public function generatePresignedUrl(generatePresignedUrlRequest $request)
    {
        $allowedTypes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/webp' => ['webp'],
            'application/pdf' => ['pdf'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx']
        ];

        $contentType = $request->input('content_type');

        if (!isset($allowedTypes[$contentType])) {
            return $this->sendError('Unsupported file type.', 400);
        }

        $filename = $request->input('filename');
        // Extract file extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (empty($extension)) {
            return $this->sendError('Filename must include a file extension (e.g., document.pdf, image.jpg).', 400);
        }

        if (!in_array($extension, $allowedTypes[$contentType])) {
            return $this->sendError("File extension '{$extension}' does not match content type '{$contentType}'. Expected: " . implode(', ', $allowedTypes[$contentType]), 400);
        }

        // Generate path
        $uniqueFileName = Str::uuid() . '.' . $extension;
        $uuid = Str::uuid();

        $isPublic = $request->input('is_public', false);
        $path = $isPublic ? 'public/' . $uuid . '/' . $uniqueFileName : 'private/' . $uuid . '/' . $uniqueFileName;

        // Generate presigned URL dengan command PutObject
        $command = $this->s3Client->getCommand('PutObject', [
            'Bucket' => $this->bucket,
            'Key' => $path,
            'ContentType' => $contentType,
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
            'mime_type' => $contentType,
            'status' => 'pending',
            'uploaded_by' => $user->id,
            'is_public' => $isPublic,
            'public_url' => $publicUrl,
        ]);

        $responseData = [
            'upload_url' => $uploadUrl,
            'document_id' => $document->id,
            'path' => $path,
            'content_type' => $contentType,
            'expires_in' => 900
        ];

        if ($isPublic) {
            $responseData['public_url'] = $publicUrl;
        }

        return $this->sendSuccess('Presigned URL generated successfully.',
            $responseData,
            201
        );
    }

    public function confirmUpload($documentId)
    {
        $document = Attachment::find($documentId);

        if (!Storage::disk('s3')->exists($document->path)) {
            return $this->sendError('File not found in storage.', 404);
        }

        $document->update([
            'status' => 'completed',
            'uploaded_at' => now()
        ]);

        return $this->sendSuccess('Upload confirmed successfully.', ['document' => $document]);
    }

    public function generateDownloadUrl($documentId)
    {
        $document = Attachment::findOrFail($documentId);

        if (!$document || $document->status !== 'completed' || !Storage::disk('s3')->exists($document->path)) {
            return $this->sendError('File not found in storage.', 404);
        }

        $url = Storage::disk('s3')->temporaryUrl(
            $document->path,
            now()->addHour(),
            [
                'ResponseContentDisposition' => 'attachment; filename="' . $document->filename . '"',
            ]
        );

        return $this->sendSuccess('Download URL generated successfully.', [
            'download_url' => $url,
            'expires_in' => 3600 // seconds
        ]);
    }

    public function deleteDocument($documentId)
    {
        $document = Attachment::findOrFail($documentId);

        if (Storage::disk('s3')->exists($document->path)) {
            Storage::disk('s3')->delete($document->path);
        }

        $document->delete();

        return $this->sendSuccess('Document deleted successfully.');
    }
}
