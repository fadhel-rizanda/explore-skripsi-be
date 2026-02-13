<?php

namespace App\Http\Controllers;

use App\Enums\ActionEnum;
use App\Enums\ModelReferenceEnum;
use App\Http\Requests\ModerationActionRequest;
use App\Http\Services\ModerationService;
use App\Models\Community;
use App\Models\Pet;
use App\Models\Post;
use App\Models\User;
use App\Traits\ResponseAPI;

class ModerationController extends Controller
{
    use ResponseAPI;

    public function __construct(
        private ModerationService $moderationService
    ) {}

    public function deactivateUser(ModerationActionRequest $request, User $user)
    {
        $this->moderationService->execute(
            entity: $user,
            isActive: false,
            referenceType: ModelReferenceEnum::USER,
            action: ActionEnum::DEACTIVATED,
            recipientIds: [$user->id],
            moderatorId: auth('api')->id(),
            reportId: $request->report_id,
            notes: $request->notes,
        );

        return $this->sendSuccess('User deactivated successfully.');
    }

    public function activateUser(ModerationActionRequest $request, User $user)
    {
        $this->moderationService->execute(
            entity: $user,
            isActive: true,
            referenceType: ModelReferenceEnum::USER,
            action: ActionEnum::ACTIVATED,
            recipientIds: [$user->id],
            moderatorId: auth('api')->id(),
            reportId: $request->report_id,
            notes: $request->notes
        );

        return $this->sendSuccess('User activated successfully.');
    }

    public function takeDownPost(ModerationActionRequest $request, Post $post)
    {
        $owner = $post->createdBy;

        if (! $owner) {
            return $this->sendError('Post owner not found.');
        }

        $this->moderationService->execute(
            entity: $post,
            isActive: false,
            referenceType: ModelReferenceEnum::POST,
            action: ActionEnum::TAKEDOWN,
            recipientIds: [$owner->id],
            moderatorId: auth('api')->id(),
            reportId: $request->report_id,
            notes: $request->notes
        );

        return $this->sendSuccess('Post taken down successfully.');
    }

    public function restorePost(ModerationActionRequest $request, Post $post)
    {
        $owner = $post->createdBy;

        if (! $owner) {
            return $this->sendError('Post owner not found.');
        }

        $this->moderationService->execute(
            entity: $post,
            isActive: true,
            referenceType: ModelReferenceEnum::POST,
            action: ActionEnum::RESTORED,
            recipientIds: [$owner->id],
            moderatorId: auth('api')->id(),
            reportId: $request->report_id,
            notes: $request->notes
        );

        return $this->sendSuccess('Post restored successfully.');
    }

    public function takeDownCommunity(ModerationActionRequest $request, Community $community)
    {
        $recipients = collect($community->getCommunityRecipients())->pluck('id')->all();

        if (empty($recipients)) {
            return $this->sendError('Community owners not found.');
        }

        $this->moderationService->execute(
            entity: $community,
            isActive: false,
            referenceType: ModelReferenceEnum::COMMUNITY,
            action: ActionEnum::TAKEDOWN,
            recipientIds: $recipients,
            moderatorId: auth('api')->id(),
            reportId: $request->report_id,
            notes: $request->notes
        );

        return $this->sendSuccess('Community taken down successfully.');
    }

    public function restoreCommunity(ModerationActionRequest $request, Community $community)
    {
        $recipients = collect($community->getCommunityRecipients())->pluck('id')->all();

        if (empty($recipients)) {
            return $this->sendError('Community owners not found.');
        }

        $this->moderationService->execute(
            entity: $community,
            isActive: true,
            referenceType: ModelReferenceEnum::COMMUNITY,
            action: ActionEnum::RESTORED,
            recipientIds: $recipients,
            moderatorId: auth('api')->id(),
            reportId: $request->report_id,
            notes: $request->notes
        );

        return $this->sendSuccess('Community restored successfully.');
    }

    public function takeDownPet(ModerationActionRequest $request, Pet $pet)
    {
        $owner = $pet->user;

        if (! $owner) {
            return $this->sendError('Pet owner not found.');
        }

        $this->moderationService->execute(
            entity: $pet,
            isActive: false,
            referenceType: ModelReferenceEnum::PET,
            action: ActionEnum::TAKEDOWN,
            recipientIds: [$owner->id],
            moderatorId: auth('api')->id(),
            reportId: $request->report_id,
            notes: $request->notes
        );

        return $this->sendSuccess('Pet taken down successfully.');
    }

    public function restorePet(ModerationActionRequest $request, Pet $pet)
    {
        $owner = $pet->user;

        if (! $owner) {
            return $this->sendError('Pet owner not found.');
        }

        $this->moderationService->execute(
            entity: $pet,
            isActive: true,
            referenceType: ModelReferenceEnum::PET,
            action: ActionEnum::RESTORED,
            recipientIds: [$owner->id],
            moderatorId: auth('api')->id(),
            reportId: $request->report_id,
            notes: $request->notes
        );

        return $this->sendSuccess('Pet restored successfully.');
    }
}
