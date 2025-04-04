<?php

namespace Modules\DocumentaryProcedure\Http\Controllers;

use App\Models\Tenant\Person;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\DocumentaryProcedure\Models\DocumentaryFile;
use Modules\DocumentaryProcedure\Http\Requests\FileRequest;
use Modules\DocumentaryProcedure\Models\DocumentaryProcess;
use Modules\DocumentaryProcedure\Models\DocumentaryDocument;

class DocumentaryFileController extends Controller
{
	public function index()
	{
		$files = DocumentaryFile::orderBy('id', 'DESC');
		if (request()->ajax()) {
			$filter = request('subject');
			if ($filter) {
				$files = $files->where('subject', 'like', "%$filter%")->get();
			}

			return response()->json(['data' => $files], 200);
		}
		$files = $files->get();

		return view('documentaryprocedure::files', compact('files'));
	}

	private function storeFile(UploadedFile $file): string
	{
		$ext = $file->getClientOriginalExtension();
		$filenameOriginal = str_replace('.' . $ext, '', $file->getClientOriginalName());
		$name = $filenameOriginal . '-' . time() . '.' . $ext;
		$path = 'storage/uploads/files/';
		$fullpath = $path . $name;
		$file->storeAs('public/uploads/files', $name);
        return $fullpath;
	}

	public function store(FileRequest $request)
	{
        $sender = json_decode($request->person);
		if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $request->merge(['attached_file' => $this->storeFile($request->file('file'))]);
		}
        $request->merge(['sender' => $sender]);
		$file = DocumentaryFile::create($request->only('documentary_document_id', 'documentary_process_id', 'number', 'year', 'invoice', 'date_register', 'time_register', 'person_id', 'sender', 'subject', 'attached_file', 'observation'));

		return response()->json([
			'data'    => $file,
			'message' => 'Expediente guardada de forma correcta.',
			'succes'  => true,
		], 200);
	}

	public function update(FileRequest $request, $id)
	{
        $sender = json_decode($request->person);
		if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $request->merge(['attached_file' => $this->storeFile($request->file('file'))]);
		}
        $request->merge(['sender' => $sender]);

        $file = DocumentaryFile::findOrFail($id);
		$file->fill($request->only('documentary_document_id', 'documentary_process_id', 'number', 'year', 'invoice', 'date_register', 'time_register', 'person_id', 'sender', 'subject', 'attached_file', 'observation'));
		$file->save();

		return response()->json([
			'data'    => $file,
			'message' => 'Expediente actualizada de forma correcta.',
			'succes'  => true,
		], 200);
	}

	public function destroy($id)
	{
		try {
			$file = DocumentaryFile::findOrFail($id);
			$file->delete();

			return response()->json([
				'data'    => null,
				'message' => 'Expediente eliminada de forma correcta.',
				'succes'  => true,
			], 200);
		} catch (\Throwable $th) {
			return response()->json([
				'success' => false,
				'data'    => 'Ocurrió un error al procesar su petición. Detalles: ' . $th->getMessage()
			], 500);
		}
	}

	public function tables()
	{
		$documentTypes = DocumentaryDocument::select('id', 'name')
			->orderBy('name')
			->whereActive(true)
			->get();

		$processes = DocumentaryProcess::select('id', 'name')
			->orderBy('name')
			->whereActive(true)
			->get();

		$customers = Person::with('addresses')
			->whereIsEnabled()
			->orderBy('name')
			->take(20)
			->get()
			->transform(function ($row) {
				return [
					'id'                          => $row->id,
					'description'                 => $row->number . ' - ' . $row->name,
					'name'                        => $row->name,
					'number'                      => $row->number,
					'identity_document_type_id'   => $row->identity_document_type_id,
					'identity_document_type_code' => $row->identity_document_type->code,
					'addresses'                   => $row->addresses,
					'address'                     => $row->address,
					'internal_code'               => $row->internal_code
				];
			});

		$lastId = DocumentaryFile::count();

		return response()->json([
			'success' => true,
			'message' => 'Información procesada de forma correcta',
			'data'    => [
				'document_types' => $documentTypes,
				'processes'      => $processes,
				'customers'      => $customers,
			]
		], 200);
	}

	public function create()
	{
		$lastId = DocumentaryFile::count();

		return response()->json([
			'success' => true,
			'message' => 'Información procesada de forma correcta',
			'data'    => [
				'next_id'      => $lastId + 1,
				'current_year' => date('Y'),
			]
		], 200);
	}

	public function getDocumentNumber()
	{
		request()->validate([
			'document_id' => 'required|numeric'
		]);

		$countForDocumentType = DocumentaryFile::where('documentary_document_id', request('document_id'))
			->count();

		return response()->json([
			'success' => true,
			'message' => 'Información procesada de forma correcta',
			'data'    => [
				'number' => $countForDocumentType + 1,
			]
		], 200);
	}
}
