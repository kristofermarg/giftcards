<x-app-layout>
    <div class="max-full mx-auto sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Giftcard Details</h1>
                <a href="/dashboard"
                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                    ← Back to list
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mt-4">
                <div><span class="text-gray-500">Code:</span> <span class="font-mono">{{ $giftcard->code }}</span></div>
                <div><span class="text-gray-500">Balance:</span> {{ $giftcard->formatAmount($giftcard->balance) }}</div>
                <div><span class="text-gray-500">Status:</span> {{ ucfirst($giftcard->status) }}</div>
                <div><span class="text-gray-500">Expires:</span> {{ $giftcard->expires_at?->format('Y-m-d') ?? '—' }}</div>
                <div><span class="text-gray-500">Created:</span> {{ $giftcard->created_at->format('Y-m-d H:i') }}</div>
                <div><span class="text-gray-500">Owner Name:</span> {{ data_get($giftcard->meta, 'owner_name') ?? '—' }}</div>
                <div>
                    <span class="text-gray-500">Owner Email:</span>
                    @php($ownerEmail = data_get($giftcard->meta, 'owner_email'))
                    @if($ownerEmail)
                        <a href="mailto:{{ $ownerEmail }}" class="text-blue-600 hover:underline">{{ $ownerEmail }}</a>
                    @else
                        —
                    @endif
                </div>
                <div>
                    <span class="text-gray-500">PassKit URL:</span>
                    @php($passUrl = data_get($giftcard->meta, 'pass_url'))
                    @if($passUrl)
                        <a href="{{ $passUrl }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline break-all">{{ $passUrl }}</a>
                    @else
                        &mdash;
                    @endif
                </div>
            </div>
        </div>

    <div class="w-full">
        <div class="bg-white shadow-sm rounded-none">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold">Transactions</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Type</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Amount</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Reference</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($giftcard->transactions as $txn)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm text-center">{{ ucfirst($txn->type) }}</td>
                                <td class="px-6 py-3 text-sm text-center">
                                    {{ $txn->type === 'debit' ? '-' : '+' }}{{ $giftcard->formatAmount($txn->amount) }}
                                </td>
                                <td class="px-6 py-3 text-sm text-center">{{ $txn->reference ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-center text-gray-600 whitespace-nowrap">{{ $txn->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
     </div>
    </div>

</x-app-layout>
