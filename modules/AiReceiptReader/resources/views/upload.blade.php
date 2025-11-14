@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-body">
        <h2>Upload Receipt for AI Processing</h2>

        <form action="/ai-receipts/process" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="receipt" class="form-control mb-3" required>
            <button class="btn btn-primary">Process with AI</button>
        </form>
    </div>
</div>
@endsection
