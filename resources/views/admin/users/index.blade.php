<x-app-layout>
    <div class="max-w-5xl mx-auto py-10 px-4">
        @if (session('status'))
            <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-2">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded bg-red-100 text-red-800 px-4 py-2">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white shadow rounded p-6">
                <h2 class="text-xl font-semibold mb-4">Create User</h2>
                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input name="name" type="text" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input name="email" type="email" value="{{ old('email') }}" class="mt-1 block w-full border-gray-300 rounded" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input name="password" type="password" class="mt-1 block w-full border-gray-300 rounded" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input name="password_confirmation" type="password" class="mt-1 block w-full border-gray-300 rounded" required />
                    </div>
                    <div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded text-white">Create</button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow rounded p-6">
                <h2 class="text-xl font-semibold mb-4">Users</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Email</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($users as $u)
                                <tr>
                                    <td class="px-4 py-2">{{ $u->name }}</td>
                                    <td class="px-4 py-2">{{ $u->email }}</td>
                                    <td class="px-4 py-2 text-right">
                                        @if ($u->email === 'kristo@tactica.is')
                                            <span class="text-gray-400 text-sm">Admin</span>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Delete user {{ $u->email }}?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-sm">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

