<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\ContactGroup\StoreContactGroupRequest;
use App\Http\Requests\ContactGroup\UpdateContactGroupRequest;
use App\Http\Resources\ContactGroupResource;
use App\Models\ContactGroup;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Contact Group', 'Manage contact groups')]
#[Authenticated]
class ContactGroupController extends Controller
{
    #[Endpoint('List contact groups', 'Retrieve a list of contact groups for the current user.', true)]
    #[ResponseFromApiResource(ContactGroupResource::class, User::class, collection: true, paginate: 10)]
    public function index(#[CurrentUser] User $user): ResourceCollection
    {
        $groups = ContactGroup::whereUserId($user->id)
            ->withCount('contacts')
            ->latest()
            ->paginate();

        return ContactGroupResource::collection($groups);
    }

    #[Endpoint('Create contact group', 'Create a new contact group.', true)]
    #[ResponseFromApiResource(ContactGroupResource::class, ContactGroup::class)]
    public function store(#[CurrentUser] User $user, StoreContactGroupRequest $request)
    {
        $contactGroup = $user->contactGroups()->create($request->validated());

        return new ContactGroupResource($contactGroup);
    }

    #[Endpoint('Get contact group', 'Retrieve a specific contact group.', true)]
    #[ResponseFromApiResource(ContactGroupResource::class, ContactGroup::class)]
    public function show(#[CurrentUser] User $user, ContactGroup $contactGroup)
    {
        if ($contactGroup->user_id !== $user->id) {
            abort(403);
        }

        return new ContactGroupResource($contactGroup->loadCount('contacts'));
    }

    #[Endpoint('Update contact group', 'Update a specific contact group.', true)]
    #[ResponseFromApiResource(ContactGroupResource::class, ContactGroup::class)]
    public function update(#[CurrentUser] User $user, UpdateContactGroupRequest $request, ContactGroup $contactGroup)
    {
        if ($contactGroup->user_id !== $user->id) {
            abort(403);
        }

        $contactGroup->update($request->validated());

        return new ContactGroupResource($contactGroup);
    }

    #[Endpoint('Delete contact group', 'Delete a specific contact group.', true)]
    public function destroy(#[CurrentUser] User $user, ContactGroup $contactGroup)
    {
        if ($contactGroup->user_id !== $user->id) {
            abort(403);
        }

        $contactGroup->delete();

        return response()->noContent();
    }
}
