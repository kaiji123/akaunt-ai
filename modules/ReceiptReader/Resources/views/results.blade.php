<x-layouts.admin>
    <x-slot name="title">AI Receipt Results</x-slot>
    <x-slot name="content">

        <div class="card mt-6">
            <div class="card-body">

                <form method="POST" action="{{ route('receipt-reader.store-bill') }}">
                    @csrf
                    
                    <input type="hidden" name="ai_data" value="{{ json_encode($ai) }}">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">🧾 Extracted Receipt Data</h2>
                        
                        <div>
                            <a href="{{ route('receipt-reader.index') }}" class="btn btn-outline-secondary mr-2">
                                <i class="fas fa-arrow-left"></i> Back to Upload
                            </a>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-invoice-dollar"></i> Auto Create Bill
                            </button>
                        </div>
                    </div>
                </form>
                <hr>
             

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h4 class="text-primary mb-3">🏢 Vendor Details</h4>
                            <div class="mb-2">
                                <strong>Name:</strong>
                                <p class="mb-0 text-dark">{{ $ai['vendor_name'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <strong>Address:</strong>
                                <p class="mb-0 text-muted">{{ $ai['vendor_address'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 bg-light">
                            <h4 class="text-success mb-3">💸 Financial Summary</h4>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Date:</span>
                                <strong>{{ $ai['date'] ?? 'N/A' }}</strong>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal:</span>
                                <strong>{{ $ai['subtotal'] ?? 'N/A' }}</strong>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Savings/Discount:</span>
                                <strong class="text-success">({{ $ai['savings'] ?? 'N/A' }})</strong>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tax:</span>
                                <strong class="text-danger">{{ $ai['tax'] ?? 'N/A' }}</strong>
                            </div>

                            <hr class="my-2">

                            <div class="d-flex justify-content-between mb-3">
                                <span class="h5 text-primary">Total:</span>
                                <span class="h5 text-primary">
                                    <strong>{{ $ai['total'] ?? 'N/A' }} {{ $ai['currency'] ?? '' }}</strong>
                                </span>
                            </div>

                            <div class="mt-3">
                                <strong>Category:</strong>
                                <span class="badge badge-info">{{ $ai['expense_category'] ?? 'Uncategorized' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <h4 class="mt-5 mb-3">🛒 Line Items</h4>

                @if(!empty($ai['line_items']))
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-bordered mb-4">
                            <thead class="thead-dark">
                                <tr>
                                    <th class="text-left py-3">Description</th>
                                    
                                    <th class="text-center py-3" style="width: 100px;">Quantity</th>
                                    <th class="text-right py-3" style="width: 120px;">Unit Price</th>
                                    <th class="text-right py-3" style="width: 120px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ai['line_items'] as $item)
                                    <tr>
                                        <td class="px-3 py-2">{{ $item['description'] ?? '-' }}</td>
                                        <td class="text-center px-3 py-2">{{ $item['qty'] ?? '1' }}</td>
                                        <td class="text-right px-3 py-2">
                                            {{ number_format((float) ($item['unit_price'] ?? 0), 2) }}
                                        </td>
                                        <td class="text-right px-3 py-2 font-weight-bold text-success">
                                            @php
                                                $qty = (float) ($item['qty'] ?? 0);
                                                $price = (float) ($item['unit_price'] ?? 0);
                                                $amount = is_numeric($qty) && is_numeric($price) ? number_format($qty * $price, 2) : 'N/A';
                                            @endphp
                                            {{ $amount }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning" role="alert">
                        No detailed line items were extracted for this receipt.
                    </div>
                @endif

            </div>
        </div>

    </x-slot>
</x-layouts.admin>