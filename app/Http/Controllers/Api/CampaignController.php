<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Campaigns', 'Manage voice campaigns')]
#[Authenticated]
final class CampaignController extends Controller
{
    #[Endpoint(title: 'List Campaigns')]
    #[ResponseFromApiResource(CampaignResource::class, Campaign::class, collection: true, paginate: 15)]
    public function index(#[CurrentUser] User $user)
    {
        $campaigns = Campaign::query()
            ->where('user_id', $user->id)
            ->with(['audio', 'phonebook'])
            ->latest()
            ->paginate(15);

        return CampaignResource::collection($campaigns);
    }

    #[Endpoint(title: 'Create Campaign')]
    #[ResponseFromApiResource(CampaignResource::class, Campaign::class, status: 201)]
    #[BodyParam(
        name: 'phone_numbers',
        type: 'string[]',
        description: 'Array of phone numbers in E.164 format. Required if source is Manual.',
        required: false,
        example: ['+88017XXXXXXXX', '+88016XXXXXXXX'])
    ]
    public function store(#[CurrentUser] User $user, StoreCampaignRequest $request, \App\Actions\Campaign\CreateCampaignAction $createCampaignAction)
    {
        $data = $request->validated();
        $data['status'] = \App\Enums\CampaignStatus::Pending;

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('campaigns');
            $data['file_path'] = $path;
            $data['source'] = \App\Enums\CampaignSource::Import->value; // Force source if file is present?
            // Or assume source is part of request.
            // If API uploads file, usually source=import is implied or required validation.
        }

        $campaign = $createCampaignAction->execute($user, $data);

        return new CampaignResource($campaign->load(['audio', 'phonebook']));
    }

    #[Endpoint(title: 'Get Campaign')]
    #[ResponseFromApiResource(CampaignResource::class, Campaign::class, with: ['audio', 'phonebook'])]
    public function show(#[CurrentUser] User $user, Campaign $campaign)
    {
        if ($campaign->user_id !== $user->id) {
            abort(403);
        }

        $campaign->load(['audio', 'phonebook']);

        return new CampaignResource($campaign);
    }

    #[Endpoint(title: 'Update Campaign')]
    #[ResponseFromApiResource(CampaignResource::class, Campaign::class)]
    public function update(#[CurrentUser] User $user, UpdateCampaignRequest $request, Campaign $campaign)
    {
        if ($campaign->user_id !== $user->id) {
            abort(403);
        }

        $campaign->update($request->validated());

        return new CampaignResource($campaign->load(['audio', 'phonebook']));
    }

    #[Endpoint(title: 'Delete Campaign')]
    public function destroy(#[CurrentUser] User $user, Campaign $campaign)
    {
        if ($campaign->user_id !== $user->id) {
            abort(403);
        }

        $campaign->delete();

        return response()->noContent();
    }
}
