<x-layouts.admin>
    <x-slot name="title">AI Receipt Results</x-slot>
    <x-slot name="content">

        <div class="card mt-6">
            <div class="card-body">
                <h2 class="mb-4">Extracted Receipt Data</h2>

                <a href="{{ route('receipt-reader.index') }}" class="btn btn-secondary mb-4">
                    ← Back to Upload Page
                </a>

                <h4>Vendor</h4>
                <p><strong>Name:</strong> {{ $ai['vendor_name'] ?? 'N/A' }}</p>
                <p><strong>Address:</strong> {{ $ai['vendor_address'] ?? 'N/A' }}</p>

                <h4 class="mt-4">Receipt Info</h4>
                <p><strong>Date:</strong> {{ $ai['date'] ?? 'N/A' }}</p>
                <p><strong>Subtotal:</strong> {{ $ai['subtotal'] ?? 'N/A' }}</p>
                <p><strong>Tax:</strong> {{ $ai['tax'] ?? 'N/A' }}</p>
                <p><strong>Total:</strong> {{ $ai['total'] ?? 'N/A' }}</p>
                <p><strong>Currency:</strong> {{ $ai['currency'] ?? 'N/A' }}</p>
                <p><strong>Expense Category:</strong> {{ $ai['expense_category'] ?? 'N/A' }}</p>

                <h4 class="mt-4">Line Items</h4>

                @if(!empty($ai['line_items']))
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ai['line_items'] as $item)
                            <tr>
                                <td>{{ $item['description'] ?? '' }}</td>
                                <td>{{ $item['qty'] ?? '' }}</td>
                                <td>{{ $item['unit_price'] ?? '' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No line items found.</p>
                @endif

            </div>
        </div>

    </x-slot>
</x-layouts.admin>
