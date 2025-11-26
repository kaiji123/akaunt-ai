<?php

namespace Modules\ReceiptReader\Http\Controllers;

use App\Abstracts\Http\Controller;
use Modules\ReceiptReader\Services\AiReceiptService;  
use Illuminate\Http\Request;
use Illuminate\Http\Response;   
use Illuminate\Support\Facades\Log;


// 💡 NEW IMPORTS ADDED HERE for Bill Creation 
use App\Models\Document\Document as Bill;
use App\Models\Common\Contact as Vendor;
use App\Models\Setting\Category;
use Carbon\Carbon;

use App\Interfaces\Utility\DocumentNumber;
class Main extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return $this->response('receipt-reader::index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('receipt-reader::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('receipt-reader::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('receipt-reader::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }


    // ----------------------------------------------------------------------
    // 💰 NEW: AUTO BILL CREATION METHODS
    // ----------------------------------------------------------------------

    /**
     * Creates a new Bill in Akaunting using the data extracted by the AI.
     *
     * @param Request $request
     * @return Response
     */
    public function storeBill(Request $request)
    {
        // 1. Validate and Retrieve AI Data (passed as JSON string from the view)
        $request->validate(['ai_data' => 'required|string']);
        
        $ai = json_decode($request->input('ai_data'), true);

        if (json_last_error() !== JSON_ERROR_NONE || !$ai) {
            return redirect()->back()->with('error', 'Invalid AI data received for Bill creation.');
        }

        // 2. Map AI Data to Akaunting Bill Structure
        try {
            $billData = $this->mapAiDataToBill($ai);
        } catch (\Exception $e) {
            Log::error('AI Bill Mapping Error:', ['error' => $e->getMessage(), 'ai_data' => $ai]);
            return redirect()->back()->with('error', 'Failed to prepare bill data: ' . $e->getMessage());
        }

        // 3. Create the Bill and Items using Akaunting's core models
        try {
            // Akaunting uses `company_id()` helper to scope data
            $bill = Bill::create($billData['bill']);
            
            foreach ($billData['items'] as $itemData) {
                // Associate items directly with the newly created bill
                $bill->items()->create($itemData);
            }

            // 4. Success: Redirect to the newly created bill's edit page
            return redirect()->route('bills.edit', $bill->id) 
                             ->with('success', 'Bill successfully created from AI receipt!');

        } catch (\Exception $e) {
            Log::error('AI Bill Save Error:', ['error' => $e->getMessage(), 'bill_data' => $billData]);
            return redirect()->back()->with('error', 'Failed to save bill to database. Check logs.');
        }
    }

    /**
     * Maps the raw AI data into the structured Bill and Item arrays required by Akaunting.
     *
     * @param array $ai
     * @return array
     */
protected function mapAiDataToBill(array $ai): array
    {
        // Helper function to clean and cast numerical values from strings
        $cleanFloat = function($value) {
            // Remove common currency symbols and commas, then cast to float
            return (float) str_replace(['£', '$', ',', ' '], '', $value ?? 0);
        };

        $current_company_id = company_id(); // Get the company ID once for consistency
        
        // 1. Find or Create Vendor
        $vendorName = $ai['vendor_name'] ?? 'Unknown Vendor';
        
        // Use the imported Vendor class
        $vendor = Vendor::firstOrCreate(
            // Search array
            ['name' => $vendorName, 'company_id' => $current_company_id], 
            // Creation array (must include all NOT NULL fields)
            [
                'name' => $vendorName,
                'type' => 'vendor', 
                'currency_code' => 'GBP', // 🛑 FINAL FIX APPLIED: Set mandatory currency code
                'company_id' => $current_company_id, 
                'website' => $ai['vendor_address'] ?? '', // Use '' for safety
                'enabled' => 1,
            ] 
        );

        // 2. Find or Create Category (Type must be 'expense' for Bills)
        $categoryName = $ai['expense_category'] ?? 'General Expense';
        $defaultColor = '#94a3b8'; // A safe, neutral grey color code
        // Use the imported Category class
        $category = Category::firstOrCreate(
            // Search array (must include 'type' = 'expense')
            ['name' => $categoryName, 'company_id' => $current_company_id, 'type' => 'expense'],
            // Creation array
            [
                'name' => $categoryName,
                'type' => 'expense',
                'company_id' => $current_company_id, 
                'enabled' => 1,
                'color' => $defaultColor,
            ]
        );

        // 3. Prepare the Bill Header Data
  $billTotal = $cleanFloat($ai['total']);
    $billCurrency = $ai['currency'] ?? setting('default.currency');
    $defaultCompanyCurrency = setting('default.currency');

    // Determine the Currency Rate
    if ($billCurrency === $defaultCompanyCurrency) {
        // If the bill is in the company's base currency, the rate is 1.0
        $billCurrencyRate = 1.0;
    } else {
        // Use the Currency Model to fetch the rate (Fall back to 1.0 if the rate is not found)
        $currencyModel = \App\Models\Setting\Currency::where('code', $billCurrency)->first();
        $billCurrencyRate = $currencyModel ? $currencyModel->rate : 1.0; 
    }

    $billDate = Carbon::parse($ai['date'] ?? now());
// 💡 ADDED FIX: Get the next sequential bill number
$nextDocumentNumber = app(DocumentNumber::class)->getNextNumber('bill', null);
$billHeader = [
    'company_id' => $current_company_id, 
    'type' => 'bill',
    'document_number' => $nextDocumentNumber,
    'vendor_id' => $vendor->id, // Use vendor_id for the relational key
    'contact_id' => $vendor->id, // 🛑 ADDED: Use vendor_id as contact_id (MANDATORY)
    'contact_name' => $vendor->name, // 🛑 ADDED: Use vendor name as contact_name
    'status' => 'received', 
    'issued_at' => $billDate->format('Y-m-d'), // 🛑 FINAL MANDATORY DATE FIELD
    'billing_date' => $billDate->format('Y-m-d'), // This is often redundant if issued_at is present, but keep for safety.
    'due_at' => $billDate->copy()->addDays(30)->format('Y-m-d'), 
    'currency_code' => $billCurrency,
    'currency_rate' => $billCurrencyRate,
    'category_id' => $category->id,
    'amount' => $billTotal, 
];
 // 4. Prepare Line Items Data (FINAL FIXES APPLIED)
$billItems = [];
$lineItems = $ai['line_items'] ?? []; 
 
if (!empty($lineItems)) {
    foreach ($lineItems as $item) {
        $price = $cleanFloat($item['unit_price']);
        $qty = $cleanFloat($item['qty'] ?? 1);
        
        $billItems[] = [
            'company_id' => $current_company_id, // Added in the last step
            'type' => 'item',                    // 🛑 FINAL FIX: Added mandatory item type
            'name' => $item['description'] ?? 'Item',
            'quantity' => $qty,
            'price' => $price,
            'total' => $qty * $price,
            'tax_id' => 0, 
        ];
    }
} else {
    // Fallback for bills with no detailed line items: create one summary item
    $billSubtotal = $cleanFloat($ai['subtotal'] ?? $ai['total'] ?? 0);
    $billItems[] = [
        'company_id' => $current_company_id, // Added in the last step
        'type' => 'item',                    // 🛑 FINAL FIX: Added mandatory item type
        'name' => 'Receipt Total',
        'quantity' => 1,
        'price' => $billSubtotal,
        'total' => $billSubtotal,
        'tax_id' => 0,
    ];
}

return [
    'bill' => $billHeader,
    'items' => $billItems,
];
    }

public function process(Request $request)
{
    $request->validate([
        'receipt' => 'required|image|max:8000',
    ]);

    $file = $request->file('receipt');
    $fileContents = file_get_contents($file->getRealPath());

    $ai = new AiReceiptService();
    $data = $ai->extractDataFromContents($fileContents);
    Log::info('AI Receipt Data:', $data);
    // Redirect to results page with AI data
    return view('receipt-reader::results', [
       'ai' => $data['ai_data'] 
    ]);
}


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
