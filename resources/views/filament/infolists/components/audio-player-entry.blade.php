<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div class="flex flex-col gap-y-2">
        @if ($getState())
            <audio controls class="w-full">
                <source src="{{ \Illuminate\Support\Facades\Storage::url($getState()) }}" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        @else
            <div class="text-sm text-gray-500 dark:text-gray-400">
                No audio file available.
            </div>
        @endif
    </div>
</x-dynamic-component>