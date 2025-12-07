<div class="flex items-center gap-x-2">
    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
        Balance: {{ number_format(auth()->user()->balance, 2) }} BDT
    </span>
</div>