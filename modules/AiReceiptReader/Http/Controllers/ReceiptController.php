<?php

namespace Modules\AiReceiptReader\Http\Controllers;


use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\AiReceiptReader\Services\AiReceiptService;

class ReceiptController extends Controller
{
    public function index()
    {
        return view('aireceipt::upload');
    }

    public function process(Request $request)
    {
        $request->validate([
            'receipt' => 'required|image|max:8000',
        ]);

        $filePath = $request->file('receipt')->store('ai_receipts');

        $ai = new AiReceiptService();
        $data = $ai->extractData(storage_path('app/' . $filePath));

        return response()->json([
            'status' => 'success',
            'ai_data' => $data
        ]);
    }
}
