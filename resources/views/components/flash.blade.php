@if(session('status'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-4 text-green-800 flex items-start gap-3">
        <svg class="h-5 w-5 mt-0.5 text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16Zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.172 7.707 8.879A1 1 0 106.293 10.293l2 2a1 1 0 001.414 0l4-4Z" clip-rule="evenodd"/>
        </svg>
        <div class="text-sm">{{ session('status') }}</div>
    </div>
@endif

@if(session('error'))
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-4 text-red-800 flex items-start gap-3">
        <svg class="h-5 w-5 mt-0.5 text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0ZM9 5a1 1 0 112 0v5a1 1 0 11-2 0V5Zm1 8a1.25 1.25 0 100 2.5A1.25 1.25 0 0010 13Z" clip-rule="evenodd"/>
        </svg>
        <div class="text-sm">{{ session('error') }}</div>
    </div>
@endif
