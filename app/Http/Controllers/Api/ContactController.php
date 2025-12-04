<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Contacts', 'Manage contacts')]
#[Authenticated]
class ContactController extends Controller
{
    #[Endpoint(title: 'List Contacts', description: 'Retrieve a list of contacts for the current user.')]
    #[ResponseFromApiResource(name: ContactResource::class, model: User::class, collection: true, paginate: 15)]
    #[QueryParam(name: 'phonebook_id', type: 'integer', description: 'Filter by phonebook ID', required: false)]
    #[QueryParam(name: 'page', type: 'integer', description: 'The page number.', required: false)]
    #[QueryParam(name: 'per_page', type: 'integer', description: 'Number of items per page.', required: false)]
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

    #[Endpoint(title: 'Create Contact', description: 'Create a new contact.')]
    #[ResponseFromApiResource(name: ContactResource::class, model: Contact::class, status: 201)]
    #[Response(content: ["message" => "The given data was invalid.", "errors" => ["first_name" => ["The first name field is required."]]], status: 422)]
    public function store(#[CurrentUser] User $user, StoreContactRequest $request)
    {
        // Verify phonebook belongs to user
        $phonebook = $user->phonebooks()->findOrFail($request->phonebook_id);

        $contact = $phonebook->contacts()->create($request->validated());

        return new ContactResource($contact);
    }

    #[Endpoint(title: 'Get Contact', description: 'Retrieve a specific contact.')]
    #[ResponseFromApiResource(name: ContactResource::class, model: Contact::class)]
    #[Response(content: ["message" => "This action is unauthorized."], status: 403)]
    #[Response(content: ["message" => "Record not found."], status: 404)]
    public function show(#[CurrentUser] User $user, Contact $contact)
    {
        if ($contact->phonebook->user_id !== $user->id) {
            abort(403);
        }

        return new ContactResource($contact);
    }

    #[Endpoint(title: 'Update Contact', description: 'Update a specific contact.')]
    #[ResponseFromApiResource(name: ContactResource::class, model: Contact::class)]
    #[Response(content: ["message" => "This action is unauthorized."], status: 403)]
    #[Response(content: ["message" => "Record not found."], status: 404)]
    #[Response(content: ["message" => "The given data was invalid.", "errors" => ["first_name" => ["The first name field is required."]]], status: 422)]
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

    #[Endpoint(title: 'Delete Contact', description: 'Delete a specific contact.')]
    #[Response(content: ["message" => "This action is unauthorized."], status: 403)]
    #[Response(content: ["message" => "Record not found."], status: 404)]
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
