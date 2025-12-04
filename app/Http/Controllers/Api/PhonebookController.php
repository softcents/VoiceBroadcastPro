<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Phonebook\StorePhonebookRequest;
use App\Http\Requests\Phonebook\UpdatePhonebookRequest;
use App\Http\Resources\PhonebookResource;
use App\Models\Phonebook;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Phonebooks', 'Manage phonebooks')]
#[Authenticated]
class PhonebookController extends Controller
{
    #[Endpoint('List Phonebooks', 'Retrieve a list of phonebooks for the current user.', true)]
    #[ResponseFromApiResource(PhonebookResource::class, User::class, collection: true, paginate: 10)]
    #[QueryParam('page', 'integer', required: false, description: 'The page number.')]
    #[QueryParam('per_page', 'integer', required: false, description: 'Number of items per page.')]
    public function index(#[CurrentUser] User $user): ResourceCollection
    {
        $phonebooks = Phonebook::whereUserId($user->id)
            ->withCount('contacts')
            ->latest()
            ->paginate();

        return PhonebookResource::collection($phonebooks);
    }

    #[Endpoint('Create Phonebook', 'Create a new phonebook.', true)]
    #[ResponseFromApiResource(PhonebookResource::class, Phonebook::class, 201)]
    #[Response(["message" => "The given data was invalid.", "errors" => ["name" => ["The name field is required."]]], 422)]
    public function store(#[CurrentUser] User $user, StorePhonebookRequest $request)
    {
        $phonebook = $user->phonebooks()->create($request->validated());

        return new PhonebookResource($phonebook);
    }

    #[Endpoint('Get Phonebook', 'Retrieve a specific phonebook.', true)]
    #[ResponseFromApiResource(PhonebookResource::class, Phonebook::class)]
    #[Response(["message" => "This action is unauthorized."], 403)]
    #[Response(["message" => "Record not found."], 404)]
    public function show(#[CurrentUser] User $user, Phonebook $phonebook)
    {
        if ($phonebook->user_id !== $user->id) {
            abort(403);
        }

        return new PhonebookResource($phonebook->loadCount('contacts'));
    }

    #[Endpoint('Update Phonebook', 'Update a specific phonebook.', true)]
    #[ResponseFromApiResource(PhonebookResource::class, Phonebook::class)]
    #[Response(["message" => "This action is unauthorized."], 403)]
    #[Response(["message" => "Record not found."], 404)]
    #[Response(["message" => "The given data was invalid.", "errors" => ["name" => ["The name field is required."]]], 422)]
    public function update(#[CurrentUser] User $user, UpdatePhonebookRequest $request, Phonebook $phonebook)
    {
        if ($phonebook->user_id !== $user->id) {
            abort(403);
        }

        $phonebook->update($request->validated());

        return new PhonebookResource($phonebook);
    }

    #[Endpoint('Delete Phonebook', 'Delete a specific phonebook.', true)]
    #[Response(["message" => "This action is unauthorized."], 403)]
    #[Response(["message" => "Record not found."], 404)]
    #[Response(status: 204)]
    public function destroy(#[CurrentUser] User $user, Phonebook $phonebook)
    {
        if ($phonebook->user_id !== $user->id) {
            abort(403);
        }

        $phonebook->delete();

        return response()->noContent();
    }
}
