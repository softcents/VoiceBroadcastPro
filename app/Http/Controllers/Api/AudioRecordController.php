<?php

namespace App\Http\Controllers\Api;

use App\Enums\AudioRecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AudioRecord\StoreAudioRecordRequest;
use App\Http\Resources\AudioRecordResource;
use App\Models\AudioRecord;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;
use App\Http\Requests\AudioRecord\UpdateAudioRecordRequest;

#[Group('Audio Record', 'Manage audio recordings')]
#[Authenticated]
class AudioRecordController extends Controller
{
    #[Endpoint('List audio recordings', 'Retrieve a list of audio recordings for the current user.', true)]
    #[ResponseFromApiResource(AudioRecordResource::class, User::class, collection: true, paginate: 10)]
    #[QueryParam('status', 'enum', required: false, enum: AudioRecordStatus::class)]
    public function index(#[CurrentUser] User $user): ResourceCollection
    {
        $records = AudioRecord::whereUserId($user->id)
            ->latest()
            ->paginate();

        return AudioRecordResource::collection($records);
    }

    #[Endpoint('Create audio recording', 'Create a new audio recording.', true)]
    #[ResponseFromApiResource(AudioRecordResource::class, AudioRecord::class)]
    public function store(#[CurrentUser] User $user, StoreAudioRecordRequest $request)
    {
        $audioRecord = $user->audioRecords()->create($request->safe()->only(['title']));

        foreach ($request->file('files') as $file) {
            $path = $file->store('audio-records');

            $audioRecord->audioFiles()->create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        return new AudioRecordResource($audioRecord->load('audioFiles'));
    }

    /**
     * Get audio recording
     */
    #[Endpoint('Get audio recording', 'Retrieve a specific audio recording.', true)]
    #[ResponseFromApiResource(AudioRecordResource::class, AudioRecord::class)]
    public function show(#[CurrentUser] User $user, AudioRecord $audioRecord)
    {
        if ($audioRecord->user_id !== $user->id) {
            abort(403);
        }

        return new AudioRecordResource($audioRecord);
    }

    /**
     * Update audio recording
     */
    #[Endpoint('Update audio recording', 'Update a specific audio recording.', true)]
    #[ResponseFromApiResource(AudioRecordResource::class, AudioRecord::class)]
    public function update(#[CurrentUser] User $user, UpdateAudioRecordRequest $request, AudioRecord $audioRecord)
    {
        if ($audioRecord->user_id !== $user->id || $audioRecord->status !== AudioRecordStatus::Pending) {
            abort(403);
        }

        $audioRecord->update($request->validated());

        return new AudioRecordResource($audioRecord);
    }

    /**
     * Delete audio recording
     */
    #[Endpoint('Delete audio recording', 'Delete a specific audio recording.', true)]
    public function destroy(#[CurrentUser] User $user, AudioRecord $audioRecord)
    {
        if ($audioRecord->user_id !== $user->id) {
            abort(403);
        }

        $audioRecord->delete();

        return response()->noContent();
    }
}
