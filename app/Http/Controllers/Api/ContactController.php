<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Contact\StoreContactRequest;
use App\Http\Requests\Contact\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Contact', 'Manage contacts')]
#[Authenticated]
class ContactController extends Controller
{
    #[Endpoint('List contacts', 'Retrieve a list of contacts for the current user.', true)]
    #[ResponseFromApiResource(ContactResource::class, User::class, collection: true, paginate: 10)]
    #[QueryParam('contact_group_id', 'integer', required: false, description: 'Filter by contact group ID')]
    public function index(#[CurrentUser] User $user): ResourceCollection
    {
        $query = Contact::query()
            ->whereHas('contactGroup', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });

        if (request()->has('contact_group_id')) {
            $query->where('contact_group_id', request('contact_group_id'));
        }

        $contacts = $query->latest()->paginate();

        return ContactResource::collection($contacts);
    }

    #[Endpoint('Create contact', 'Create a new contact.', true)]
    #[ResponseFromApiResource(ContactResource::class, Contact::class)]
    public function store(#[CurrentUser] User $user, StoreContactRequest $request)
    {
        // Verify contact group belongs to user
        $group = $user->contactGroups()->findOrFail($request->contact_group_id);

        $contact = $group->contacts()->create($request->validated());

        return new ContactResource($contact);
    }

    #[Endpoint('Get contact', 'Retrieve a specific contact.', true)]
    #[ResponseFromApiResource(ContactResource::class, Contact::class)]
    public function show(#[CurrentUser] User $user, Contact $contact)
    {
        if ($contact->contactGroup->user_id !== $user->id) {
            abort(403);
        }

        return new ContactResource($contact);
    }

    #[Endpoint('Update contact', 'Update a specific contact.', true)]
    #[ResponseFromApiResource(ContactResource::class, Contact::class)]
    public function update(#[CurrentUser] User $user, UpdateContactRequest $request, Contact $contact)
    {
        if ($contact->contactGroup->user_id !== $user->id) {
            abort(403);
        }

        if ($request->has('contact_group_id')) {
            $user->contactGroups()->findOrFail($request->contact_group_id);
        }

        $contact->update($request->validated());

        return new ContactResource($contact);
    }

    #[Endpoint('Delete contact', 'Delete a specific contact.', true)]
    public function destroy(#[CurrentUser] User $user, Contact $contact)
    {
        if ($contact->contactGroup->user_id !== $user->id) {
            abort(403);
        }

        $contact->delete();

        return response()->noContent();
    }
}
