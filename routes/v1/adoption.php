<?php

use App\Enums\AdoptionStageEnum;
use App\Enums\RoleEnum;
use App\Http\Controllers\AdoptionController;
use App\Http\Controllers\HandoverController;
use App\Http\Controllers\MeetNGreetController;
use App\Http\Controllers\RequirementController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'adoptions',
    'middleware' => ['auth:api', 'check.token.version', 'adoption.owner'],
], function () {
    Route::get('/', [AdoptionController::class, 'listAdoptions']);
    Route::get('/{adoption}', [AdoptionController::class, 'adoptionDetail']);

    Route::scopeBindings()->group(function () {
        Route::get('/{adoption}/requirements', [RequirementController::class, 'listRequirements']);

        Route::middleware(['adoption.stage:' . AdoptionStageEnum::REQUIREMENT->value])->group(function () {
            Route::middleware(['adoption.access:' . RoleEnum::PROVIDER->value])->group(function () {
                Route::post('/{adoption}/requirements', [RequirementController::class, 'setRequirements']);
                Route::delete('/{adoption}/requirements/{requirement}', [RequirementController::class, 'deleteRequirement']);
                Route::patch('/{adoption}/requirements/{requirement}/approve', [RequirementController::class, 'approveRequirement']);
                Route::patch('/{adoption}/requirements/{requirement}/reject', [RequirementController::class, 'rejectRequirement']);
            });
            Route::post('/{adoption}/requirements/{requirement}/fill', [RequirementController::class, 'fillRequirement'])->middleware(['adoption.access:' . RoleEnum::ADOPTER->value]);
            Route::patch('/{adoption}/requirements/finalize', [RequirementController::class, 'finalizedRequirements']);
        });

        Route::middleware(['adoption.stage:' . AdoptionStageEnum::MEET_N_GREET->value])->group(function () {
            Route::get('/{adoption}/meet-n-greet', [MeetNGreetController::class, 'meetNGreet']);
            Route::post('/{adoption}/meet-n-greet', [MeetNGreetController::class, 'purposeSchedule']);
            Route::patch('/{adoption}/meet-n-greet/{meetNGreet}/approve', [MeetNGreetController::class, 'approveSchedule']);
            Route::patch('/{adoption}/meet-n-greet/{meetNGreet}/finalize', [MeetNGreetController::class, 'finalizeMeetNGreet']);
        });

        Route::middleware(['adoption.stage:' . AdoptionStageEnum::HANDOVER->value])->group(function () {
            Route::get('/{adoption}/handover', [HandoverController::class, 'handover']);
            //            Route::post('/{adoption}/handover', [HandoverController::class, 'createHandover']);
            Route::post('/{adoption}/handover/meet-n-greet', [HandoverController::class, 'purposeMeetNGreetSchedule']);
            Route::patch('/{adoption}/handover/{handover}/meet-n-greet/approve', [HandoverController::class, 'approveMeetNGreet']);
            Route::post('/{adoption}/handover/{handover}/evidence', [HandoverController::class, 'setEvidence']);
            Route::patch('/{adoption}/handover/{handover}/finalize', [HandoverController::class, 'finalize']);
        });
    });
});
