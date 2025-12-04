<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Template\StoreTemplateRequest;
use App\Http\Requests\Template\UpdateTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

use Knuckles\Scribe\Attributes\Response as ResponseAttribute;

#[Group("Templates", "Manage message templates")]
#[Authenticated]
class TemplateController extends Controller
{
    /**
     * List Templates
     *
     * Get a list of all templates belonging to the authenticated user.
     */
    #[Endpoint("List Templates", "Get a list of all templates belonging to the authenticated user.")]
    #[ResponseFromApiResource(TemplateResource::class, Template::class, collection: true, paginate: 15)]
    #[QueryParam('page', 'integer', required: false, description: 'The page number.')]
    #[QueryParam('per_page', 'integer', required: false, description: 'Number of items per page.')]
    public function index(#[CurrentUser] User $user): AnonymousResourceCollection
    {
        $templates = $user->templates()->latest()->paginate();

        return TemplateResource::collection($templates);
    }

    /**
     * Create Template
     *
     * Create a new template.
     */
    #[Endpoint("Create Template", "Create a new template.")]
    #[ResponseFromApiResource(TemplateResource::class, Template::class, 201)]
    #[ResponseAttribute(["message" => "The given data was invalid.", "errors" => ["name" => ["The name field is required."]]], 422)]
    public function store(#[CurrentUser] User $user, StoreTemplateRequest $request): TemplateResource
    {
        $template = $user->templates()->create($request->validated());

        return new TemplateResource($template);
    }

    /**
     * Get Template
     *
     * Get details of a specific template.
     */
    #[Endpoint("Get Template", "Get details of a specific template.")]
    #[ResponseFromApiResource(TemplateResource::class, Template::class)]
    #[ResponseAttribute(["message" => "This action is unauthorized."], 403)]
    #[ResponseAttribute(["message" => "Record not found."], 404)]
    public function show(#[CurrentUser] User $user, Template $template): TemplateResource
    {
        if ($template->user_id !== $user->id) {
            abort(403);
        }

        return new TemplateResource($template);
    }

    /**
     * Update Template
     *
     * Update an existing template.
     */
    #[Endpoint("Update Template", "Update an existing template.")]
    #[ResponseFromApiResource(TemplateResource::class, Template::class)]
    #[ResponseAttribute(["message" => "This action is unauthorized."], 403)]
    #[ResponseAttribute(["message" => "Record not found."], 404)]
    #[ResponseAttribute(["message" => "The given data was invalid.", "errors" => ["name" => ["The name field is required."]]], 422)]
    public function update(#[CurrentUser] User $user, UpdateTemplateRequest $request, Template $template): TemplateResource
    {
        if ($template->user_id !== $user->id) {
            abort(403);
        }

        $template->update($request->validated());

        return new TemplateResource($template);
    }

    /**
     * Delete Template
     *
     * Delete a template.
     */
    #[Endpoint("Delete Template", "Delete a template.")]
    #[ResponseAttribute(["message" => "This action is unauthorized."], 403)]
    #[ResponseAttribute(["message" => "Record not found."], 404)]
    #[ResponseAttribute(status: 204)]
    public function destroy(#[CurrentUser] User $user, Template $template): Response
    {
        if ($template->user_id !== $user->id) {
            abort(403);
        }

        $template->delete();

        return response()->noContent();
    }
}
