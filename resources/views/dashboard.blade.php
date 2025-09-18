<x-app-layout>
    <div class="w-full py-16">
        <div class="mx-8 sm:mx-4">
            @php
                $filters = ($filters ?? []) + ['search' => $filters['search'] ?? '', 'status' => $filters['status'] ?? 'all'];
            @endphp
            @if($giftcards->isEmpty())
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-8 py-3 rounded-lg text-center">
                    No giftcards found.
                </div>
            @else
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-6 border-b">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <h1 class="text-3xl sm:text-4xl font-extrabold text-center sm:text-left">Giftcards</h1>
                            <form method="GET" action="{{ route('dashboard') }}" class="flex w-3/4 max-w-md gap-2">
                                <label for="dashboard-search" class="sr-only">Search giftcards</label>
                                @if(($filters['status'] ?? 'all') !== 'all')
                                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                @endif
                                <input id="dashboard-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="flex-1 rounded-full border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2" placeholder="Search by name, code, or email">
                                <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-full bg-indigo-600 text-white hover:bg-indigo-700 transition">Search</button>
                                @if(!empty($filters['search']))
                                    <a href="{{ ($filters['status'] ?? 'all') === 'all' ? route('dashboard') : route('dashboard', ['status' => $filters['status']]) }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 transition">Clear</a>
                                @endif
                            </form>
                        </div>
                    </div>

                    <div class="w-full overflow-x-auto">
                        <table class="min-w-full w-full table-auto divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Owner</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Code</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Balance</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Currency</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Expires</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Created</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Status</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase">Actions</th>
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
                                        $ownerName = data_get($gc->meta, 'owner_name') ?? '-';
                                    @endphp
                                    <tr class="hover:bg-gray-50 align-middle">
                                        <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap text-center">{{ $ownerName }}</td>
                                        <td class="px-6 py-4 font-mono text-sm text-gray-800 truncate text-center">{{ $gc->code }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap text-center">{{ $gc->formatAmount($gc->balance) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-800 text-center">{{ $gc->currency }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap text-center">{{ $gc->expires_at?->format('Y-m-d') ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap text-center">{{ $gc->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="px-6 py-4 text-sm text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $badge }}">
                                                <span class="h-2 w-2 rounded-full mr-2 {{ $dot }}"></span>
                                                {{ ucfirst($status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <a href="{{ route('admin.giftcards.show', $gc) }}"
                                               class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-[25px] border border-indigo-600 text-indigo-600 bg-white shadow-sm transition hover:bg-indigo-50">
                                                View Transactions
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    {{ $giftcards->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
