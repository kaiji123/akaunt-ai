<?php

namespace Modules\ReceiptReader\Http\Controllers;

use App\Abstracts\Http\Controller;
use Modules\ReceiptReader\Services\AiReceiptService;  
use Illuminate\Http\Request;
use Illuminate\Http\Response;   
use Illuminate\Support\Facades\Log;
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
