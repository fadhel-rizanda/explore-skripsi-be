<?php

namespace App\Rules;

use App\Models\Attachment;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OwnsAttachment implements ValidationRule
{
    public function __construct(
        private ?string $referenceType = null,
        private ?string $referenceId = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ids = is_array($value) ? $value : [$value];
        $ids = array_filter($ids);

        if (empty($ids)) {
            return;
        }

        $attachments = Attachment::whereIn('id', $ids)
            ->where('uploaded_by', auth('api')->id())
            ->get();

        if ($attachments->count() !== count($ids)) {
            $fail('The selected :attribute is invalid or does not belong to you.');

            return;
        }

        foreach ($attachments as $attachment) {
            if (is_null($this->referenceId)) {
                if (! is_null($attachment->reference_by)) {
                    $fail('One or more attachments are already used by another resource.');

                    return;
                }
            } else {
                if (
                    ! is_null($attachment->reference_by) &&
                    ! (
                        $attachment->reference_by === $this->referenceType &&
                        $attachment->reference_id === $this->referenceId
                    )
                ) {
                    $fail('One or more attachments are already used by another resource.');

                    return;
                }
            }
        }
    }
}
