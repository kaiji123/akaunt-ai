<x-layouts.admin>
    <x-slot name="title">AI RECEIPT READER</x-slot>
    <x-slot name="content">

        <div class="card mt-6">
            <div class="card-body">
                <h2 class="mb-6">Upload Receipt for AI Processing</h2>

                <x-form id="receipt-form" method="POST" route="receipt-reader.process" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="receipt" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary w-100">Process with AI</button>
                    </div>
                </x-form>

                <div id="result" class="mt-4"></div>
            </div>
        </div>

    </x-slot>

    <script>
        const form = document.getElementById('receipt-form');
        const resultDiv = document.getElementById('result');

        form.addEventListener('submit', async function(e) {
            e.preventDefault(); // prevent default form submission

            const formData = new FormData(form);

            const response = await fetch('/ai-receipts/process', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.text(); // or .json() if your endpoint returns JSON
            resultDiv.innerHTML = data; // display response
        });
    </script>
</x-layouts.admin>
