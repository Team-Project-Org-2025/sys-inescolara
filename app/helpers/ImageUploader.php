<?php
namespace SysInescolara\helpers;

class ImageUploader {
    private $uploadDir;
    private $relativeDir;
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $maxFileSize = 5242880; // 5MB en bytes

    public function __construct(string $subDir = 'products') {
        $projectRoot = dirname(dirname(__DIR__));
        $this->relativeDir = 'public/' . ltrim($subDir, '/');
        $this->uploadDir = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $this->relativeDir) . DIRECTORY_SEPARATOR;

        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0777, true)) {
                throw new \Exception("No se pudo crear el directorio: " . $this->uploadDir);
            }
        }

        if (!is_writable($this->uploadDir)) {
            throw new \Exception("No hay permisos de escritura en el directorio de imágenes");
        }
    }

    public function upload($file, $prefix = 'product') {
        $errors = [];

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No se ha subido ningún archivo';
            return ['success' => false, 'errors' => $errors];
        }

        if ($file['size'] > $this->maxFileSize) {
            $errors[] = 'El archivo excede el tamaño máximo permitido (5MB)';
            return ['success' => false, 'errors' => $errors];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $errors[] = 'Tipo de archivo no permitido. Permitidos: ' . implode(', ', $this->allowedExtensions);
            return ['success' => false, 'errors' => $errors];
        }

        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = 'El archivo no es una imagen válida';
            return ['success' => false, 'errors' => $errors];
        }

        $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $this->uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errors[] = 'Error al guardar el archivo. Verifica permisos del directorio.';
            return ['success' => false, 'errors' => $errors];
        }

        $relativePath = $this->relativeDir . '/' . $filename;

        return [
            'success' => true,
            'data' => [
                'url' => $relativePath,
                'filename' => $filename,
                'size' => $file['size'],
                'mime' => $imageInfo['mime'],
                'absolute_path' => $targetPath
            ],
            'errors' => []
        ];
    }

    public function delete($imagePath) {
        $fullPath = __DIR__ . '/../../' . ltrim($imagePath, '/');
        
        if (file_exists($fullPath)) {
            return @unlink($fullPath);
        }
        
        return false;
    }
}