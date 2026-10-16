<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadHelper
{
    protected const DISK = 'public';

    /**
     * Sube un archivo al disco público en la carpeta especificada.
     *
     * @param  UploadedFile  $file  Archivo a almacenar
     * @param  string  $folder  Carpeta destino dentro de storage/app/public/
     * @return string Ruta relativa del archivo almacenado (ej: plants/uuid.jpg)
     */
    public static function upload(UploadedFile $file, string $folder): string
    {
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $filename = Str::uuid().'.'.$extension;

        return $file->storeAs($folder, $filename, self::DISK);
    }

    /**
     * Elimina un archivo del disco público si existe.
     *
     * @param  string|null  $path  Ruta relativa del archivo
     * @return bool True si se eliminó con éxito o no existía, false en caso contrario
     */
    public static function delete(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            return Storage::disk(self::DISK)->delete($path);
        }

        return false;
    }

    /**
     * Obtiene la URL pública accesible para el archivo especificado.
     *
     * @param  string|null  $path  Ruta relativa o URL externa
     * @return string|null URL completa o null
     */
    public static function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    /**
     * Reemplaza un archivo existente eliminando el anterior y guardando el nuevo.
     *
     * @param  UploadedFile  $newFile  Nuevo archivo a subir
     * @param  string|null  $oldPath  Ruta relativa del archivo anterior
     * @param  string  $folder  Carpeta destino
     * @return string Nueva ruta relativa
     */
    public static function replace(UploadedFile $newFile, ?string $oldPath, string $folder): string
    {
        if (! empty($oldPath)) {
            self::delete($oldPath);
        }

        return self::upload($newFile, $folder);
    }
}
