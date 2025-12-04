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

use Knuckles\Scribe\Attributes\Response;

#[Group('Contacts', 'Manage contacts')]
#[Authenticated]
class ContactController extends Controller
{
    #[Endpoint('List Contacts', 'Retrieve a list of contacts for the current user.', true)]
    #[ResponseFromApiResource(ContactResource::class, User::class, collection: true, paginate: 10)]
    #[QueryParam('phonebook_id', 'integer', required: false, description: 'Filter by phonebook ID')]
    #[QueryParam('page', 'integer', required: false, description: 'The page number.')]
    #[QueryParam('per_page', 'integer', required: false, description: 'Number of items per page.')]
    public function index(#[CurrentUser] User $user): ResourceCollection
    {
        $query = Contact::query()
            ->whereHas('phonebook', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });

        if (request()->has('phonebook_id')) {
            $query->where('phonebook_id', request('phonebook_id'));
        }

        $contacts = $query->latest()->paginate();

        return ContactResource::collection($contacts);
    }

    #[Endpoint('Create Contact', 'Create a new contact.', true)]
    #[ResponseFromApiResource(ContactResource::class, Contact::class, 201)]
    #[Response(["message" => "The given data was invalid.", "errors" => ["first_name" => ["The first name field is required."]]], 422)]
    public function store(#[CurrentUser] User $user, StoreContactRequest $request)
    {
        // Verify phonebook belongs to user
        $phonebook = $user->phonebooks()->findOrFail($request->phonebook_id);

        $contact = $phonebook->contacts()->create($request->validated());

        return new ContactResource($contact);
    }

    #[Endpoint('Get Contact', 'Retrieve a specific contact.', true)]
    #[ResponseFromApiResource(ContactResource::class, Contact::class)]
    #[Response(["message" => "This action is unauthorized."], 403)]
    #[Response(["message" => "Record not found."], 404)]
    public function show(#[CurrentUser] User $user, Contact $contact)
    {
        if ($contact->phonebook->user_id !== $user->id) {
            abort(403);
        }

        return new ContactResource($contact);
    }

    #[Endpoint('Update Contact', 'Update a specific contact.', true)]
    #[ResponseFromApiResource(ContactResource::class, Contact::class)]
    #[Response(["message" => "This action is unauthorized."], 403)]
    #[Response(["message" => "Record not found."], 404)]
    #[Response(["message" => "The given data was invalid.", "errors" => ["first_name" => ["The first name field is required."]]], 422)]
    public function update(#[CurrentUser] User $user, UpdateContactRequest $request, Contact $contact)
    {
        if ($contact->phonebook->user_id !== $user->id) {
            abort(403);
        }

        if ($request->has('phonebook_id')) {
            $user->phonebooks()->findOrFail($request->phonebook_id);
        }

        $contact->update($request->validated());

        return new ContactResource($contact);
    }

    #[Endpoint('Delete Contact', 'Delete a specific contact.', true)]
    #[Response(["message" => "This action is unauthorized."], 403)]
    #[Response(["message" => "Record not found."], 404)]
    #[Response(status: 204)]
    public function destroy(#[CurrentUser] User $user, Contact $contact)
    {
        if ($contact->phonebook->user_id !== $user->id) {
            abort(403);
        }

        $contact->delete();

        return response()->noContent();
    }
}
