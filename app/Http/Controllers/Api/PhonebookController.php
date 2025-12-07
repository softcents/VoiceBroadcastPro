<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Phonebooks', 'Manage phonebooks')]
#[Authenticated]
final class PhonebookController extends Controller
{
    #[Endpoint(title: 'List Phonebooks', description: 'Retrieve a list of phonebooks for the current user.')]
    #[ResponseFromApiResource(name: PhonebookResource::class, model: User::class, collection: true, paginate: 15)]
    #[QueryParam(name: 'page', type: 'integer', description: 'The page number.', required: false)]
    #[QueryParam(name: 'per_page', type: 'integer', description: 'Number of items per page.', required: false)]
    public function index(#[CurrentUser] User $user): ResourceCollection
    {
        $phonebooks = Phonebook::whereUserId($user->id)
            ->withCount('contacts')
            ->latest()
            ->paginate();

        return PhonebookResource::collection($phonebooks);
    }

    #[Endpoint(title: 'Create Phonebook', description: 'Create a new phonebook.')]
    #[ResponseFromApiResource(name: PhonebookResource::class, model: Phonebook::class, status: 201)]
    #[Response(content: ['message' => 'The given data was invalid.', 'errors' => ['name' => ['The name field is required.']]], status: 422)]
    public function store(#[CurrentUser] User $user, StorePhonebookRequest $request)
    {
        $phonebook = $user->phonebooks()->create($request->validated());

        return new PhonebookResource($phonebook);
    }

    #[Endpoint(title: 'Get Phonebook', description: 'Retrieve a specific phonebook.')]
    #[ResponseFromApiResource(name: PhonebookResource::class, model: Phonebook::class)]
    #[Response(content: ['message' => 'This action is unauthorized.'], status: 403)]
    #[Response(content: ['message' => 'Record not found.'], status: 404)]
    public function show(#[CurrentUser] User $user, Phonebook $phonebook)
    {
        if ($phonebook->user_id !== $user->id) {
            abort(403);
        }

        return new PhonebookResource($phonebook->loadCount('contacts'));
    }

    #[Endpoint(title: 'Update Phonebook', description: 'Update a specific phonebook.')]
    #[ResponseFromApiResource(PhonebookResource::class, Phonebook::class)]
    #[Response(['message' => 'This action is unauthorized.'], 403)]
    #[Response(['message' => 'Record not found.'], 404)]
    #[Response(['message' => 'The given data was invalid.', 'errors' => ['name' => ['The name field is required.']]], 422)]
    public function update(#[CurrentUser] User $user, UpdatePhonebookRequest $request, Phonebook $phonebook)
    {
        if ($phonebook->user_id !== $user->id) {
            abort(403);
        }

        $phonebook->update($request->validated());

        return new PhonebookResource($phonebook);
    }

    #[Endpoint(title: 'Delete Phonebook', description: 'Delete a specific phonebook.')]
    #[Response(content: ['message' => 'This action is unauthorized.'], status: 403)]
    #[Response(content: ['message' => 'Record not found.'], status: 404)]
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
