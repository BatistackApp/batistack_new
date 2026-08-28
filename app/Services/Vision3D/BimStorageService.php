<?php

namespace App\Services\Vision3D;

use App\Models\Vision3D\BimModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BimStorageService
{
    /**
     * Handle the upload of a 3D model file and create the BimModel record.
     */
    public function storeModel(UploadedFile $file, string $modelableType, int $modelableId, ?string $name = null): BimModel
    {
        $extension = strtolower($file->getClientOriginalExtension());

        // Validate format
        if (! in_array($extension, ['ifc', 'dxf', 'gltf', 'glb', 'obj', 'stl'])) {
            throw new \InvalidArgumentException("Format de fichier 3D non supporté: {$extension}");
        }

        // Generate a secure unique filename
        $filename = Str::uuid().'.'.$extension;
        $path = $file->storeAs('bim_models', $filename, 'public');

        return BimModel::create([
            'name' => $name ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'format' => $extension,
            'file_size' => $file->getSize(),
            'version' => 1,
            'modelable_type' => $modelableType,
            'modelable_id' => $modelableId,
        ]);
    }

    /**
     * Delete a 3D model from storage and the database.
     */
    public function deleteModel(BimModel $bimModel): bool
    {
        if (Storage::disk('public')->exists($bimModel->file_path)) {
            Storage::disk('public')->delete($bimModel->file_path);
        }

        return $bimModel->delete();
    }
}
