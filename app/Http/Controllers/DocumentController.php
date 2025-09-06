<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index()
    {
        return Document::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'document' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', // 10MB max
        ]);

        // Handle file upload to public/documents directory
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();

            // Create documents directory if it doesn't exist
            $directory = public_path('documents');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Move file to public/documents
            $file->move($directory, $fileName);

            // Get full URL path
            $filePath = url('documents/' . $fileName);

            $document = Document::create([
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'file_path' => $filePath,
            ]);

            return response()->json($document, 201);
        }

        return response()->json(['message' => 'File upload failed'], 400);
    }

    public function show(Document $document)
    {
        return $document;
    }

    public function update(Request $request, Document $document)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'document' => 'sometimes|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
        ]);

        $data = $request->only(['name', 'start_date', 'end_date']);

        if ($request->hasFile('document')) {
            // Delete old file
            $oldFileName = basename($document->file_path);
            $oldFilePath = public_path('documents/' . $oldFileName);
            if (File::exists($oldFilePath)) {
                File::delete($oldFilePath);
            }

            // Upload new file
            $file = $request->file('document');
            $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('documents'), $fileName);
            $data['file_path'] = url('documents/' . $fileName);
        }

        $document->update($data);

        return response()->json($document);
    }

    public function destroy(Document $document)
    {
        // Delete file
        $fileName = basename($document->file_path);
        $filePath = public_path('documents/' . $fileName);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        $document->delete();

        return response()->json(null, 204);
    }
}
