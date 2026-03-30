<?php

namespace App\Traits;

use App\Enums\AttachmentTypeEnum;
use App\Models\Attachment;

trait InteractsWithAttachments
{
    public function syncAttachmentsWithMetadata(string $relation, array $newIds, string $modelReference): void
    {
        $oldIds = $this->$relation()->pluck('mt_attachment.id')->toArray();

        $this->$relation()->sync($newIds);

        $removedIds = array_diff($oldIds, $newIds);
        if (! empty($removedIds)) {
            Attachment::whereIn('id', $removedIds)->update([
                'reference_id' => null,
                'reference_by' => null,
                'status' => AttachmentTypeEnum::PENDING->value,
            ]);
        }

        if (! empty($newIds)) {
            Attachment::whereIn('id', $newIds)->update([
                'reference_id' => $this->id,
                'reference_by' => $modelReference,
                'status' => AttachmentTypeEnum::COMPLETED->value,
            ]);
        }
    }

    public function setAttachmentMetadata(
        ?string $attachmentId,
        string $modelReference,
        ?string $referenceId = null
    ): void {
        Attachment::where('reference_id', $referenceId ?? $this->id)
            ->where('reference_by', $modelReference)
            ->where('id', '!=', $attachmentId)
            ->update([
                'reference_id' => null,
                'reference_by' => null,
                'status' => AttachmentTypeEnum::PENDING->value,
            ]);

        if ($attachmentId) {
            Attachment::where('id', $attachmentId)->update([
                'reference_id' => $referenceId ?? $this->id,
                'reference_by' => $modelReference,
                'status' => AttachmentTypeEnum::COMPLETED->value,
            ]);
        }
    }
}
