<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Campaigns', 'Manage voice campaigns')]
#[Authenticated]
class CampaignController extends Controller
{
    #[Endpoint(title: 'List Campaigns')]
    #[ResponseFromApiResource(CampaignResource::class, Campaign::class, collection: true)]
    public function index(#[CurrentUser] User $user)
    {
        $campaigns = Campaign::query()
            ->where('user_id', $user->id)
            ->with(['audio', 'phonebook'])
            ->latest()
            ->paginate(10);

        return CampaignResource::collection($campaigns);
    }

    #[Endpoint(title: 'Create Campaign')]
    #[ResponseFromApiResource(CampaignResource::class, Campaign::class, status: 201)]
    public function store(#[CurrentUser] User $user, StoreCampaignRequest $request)
    {
        $campaign = $user->campaigns()->create($request->validated());

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
