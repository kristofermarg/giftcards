@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Active Giftcards</h1>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Code</th>
                <th>Balance</th>
                <th>Currency</th>
                <th>Expires</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($giftcards as $gc)
                <tr>
                    <td>{{ $gc->code }}</td>
                    <td>{{ number_format($gc->balance / 100, 2) }}</td>
                    <td>{{ $gc->currency }}</td>
                    <td>{{ $gc->expires_at?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $gc->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.giftcards.show', $gc) }}" class="btn btn-sm btn-primary">
                            View Transactions
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $giftcards->links() }}
</div>
@endsection
