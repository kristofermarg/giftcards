<x-app-layout>
    <div class="w-full py-16">
        <!-- Small side margins for the whole block -->
        <div class="mx-8 sm:mx-4">
            @if($giftcards->isEmpty())
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-8 py-3 rounded-lg">
                    No giftcards found.
                </div>
            @else
                <!-- Card wrapper -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">

                    <!-- Header: uses px-6 to align with table cells -->
                    <div class="px-6 py-4 border-b">
                        <h1 class="text-2xl font-bold">Giftcards</h1>
                    </div>

                    <!-- Table -->
                    <div class="w-full overflow-x-auto">
                        <table class="min-w-full w-full table-auto divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Currency</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Expires</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($giftcards as $gc)
                                    @php
                                        $status = $gc->status;
                                        $badge = match ($status) {
                                            'active', 'redeemed' => 'bg-green-100 text-green-800',
                                            'inactive', 'expired' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                        $dot = match ($status) {
                                            'active', 'redeemed' => 'bg-green-500',
                                            'inactive', 'expired' => 'bg-red-500',
                                            default => 'bg-gray-400',
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50 align-middle">
                                        <td class="px-6 py-4 font-mono text-sm text-gray-800 truncate text-center">{{ $gc->code }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap text-center">{{ $gc->formatAmount($gc->balance) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-800 text-center">{{ $gc->currency }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap text-center">{{ $gc->expires_at?->format('Y-m-d') ?? '—' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap text-center">{{ $gc->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="px-6 py-4 text-sm text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $badge }}">
                                                <span class="h-2 w-2 rounded-full mr-2 {{ $dot }}"></span>
                                                {{ ucfirst($status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <a href="{{ route('admin.giftcards.show', $gc) }}"
                                               class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md shadow-sm transition
                                                      bg-indigo-600 hover:bg-indigo-700">
                                                View Transactions
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>

                <!-- Pagination with matching side spacing -->
                <div class="mt-4">
                    {{ $giftcards->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
