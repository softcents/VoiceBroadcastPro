<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SenderIdResource;
use App\Models\SenderId;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Authenticated]
#[Group(name: "Sender IDs", description: "Manage sender IDs")]
#[Response(content: ['message' => 'Unauthenticated.'], status: 401)]
class SenderIdController extends Controller
{
    #[Endpoint(title: "List Sender IDs", description: "Get a list of all sender IDs belonging to the authenticated user.")]
    #[QueryParam(name: 'page', type: 'integer', description: 'The page number.', required: false)]
    #[QueryParam(name: 'per_page', type: 'integer', description: 'Number of items per page.', required: false)]
    #[ResponseFromApiResource(name: SenderIdResource::class, model: SenderId::class, collection: true, paginate: 15)]
    public function index(#[CurrentUser] User $user)
    {
        $senderIds = $user
            ->senderIds()
            ->enabled()
            ->latest()
            ->paginate();

        return SenderIdResource::collection($senderIds);
    }
}
