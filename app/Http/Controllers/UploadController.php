<?php

namespace App\Http\Controllers;

use App\Http\Requests\generatePresignedUrlRequest;
use App\Models\AdoptionDocument;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    use ResponseAPI;

    public function generatePresignedUrl(generatePresignedUrlRequest $request)
    {
        $allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($request->input('content_type'), $allowedTypes)) {
            return $this->sendError('Unsupported file type.', 400);
        }

        // Logic to generate presigned URL goes here.
        $extension = pathinfo($request->input('filename'), PATHINFO_EXTENSION);
        $uniqueFileName = Str::uuid() . '.' . $extension;
        $path = 'adoptions/' . $request->input('adoption_id') . '/' . $uniqueFileName;

        $url = Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addMinutes(15),
            [
                'ContentType' => $request->content_type,
            ]
        );

        $document = AdoptionDocument::create([
            'adoption_id' => $request->input('adoption_id'),
            'filename' => $request->input('filename'),
            'path' => $path,
            'file_size' => $request->input('file_size'),
            'mime_type' => $request->input('content_type'),
            'status' => 'pending',
            'uploaded_by' => auth()->id(),
        ]);

        return $this->sendSuccess('Presigned URL generated successfully.',
            [
                'upload_url' => $url,
                'document_id' => $document->id,
                'path' => $path,
                'expires_in' => 600 // seconds
            ],
            201
        );
    }

    public function confirmUpload(Request $request)
    {
        $request->validate([
            'document_id' => 'required|exists:adoption_documents,id',
        ]);

        $document = AdoptionDocument::find($request->input('document_id'));

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
        $document = AdoptionDocument::findOrFail($documentId);

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
        $document = AdoptionDocument::findOrFail($documentId);

        if (Storage::disk('s3')->exists($document->path)) {
            Storage::disk('s3')->delete($document->path);
        }

        $document->delete();

        return $this->sendSuccess('Document deleted successfully.');
    }

    public function getAdoptionDocuments($adoptionId)
    {
        $documents = AdoptionDocument::where('adoption_id', $adoptionId)
            ->where('status', 'completed')
            ->get();

        return $this->sendSuccess('Adoption documents retrieved successfully.', ['documents' => $documents]);
    }
}
