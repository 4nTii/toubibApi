<?php

namespace App\Service\Helper;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FileUploadHelper
{
    private string $host;
    private string $user;
    private string $pass;
    private string $baseDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->host = $params->get('ftp_host');
        $this->user = $params->get('ftp_user');
        $this->pass = $params->get('ftp_pass');
        $this->baseDir = rtrim($params->get('ftp_base_dir'), '/');
    }

    private function connect()
    {
        $conn = \ftp_connect($this->host);

        if (!$conn) {
            throw new \Exception("Connexion FTP échouée");
        }

        if (!\ftp_login($conn, $this->user, $this->pass)) {
            throw new \Exception("Login FTP échoué");
        }

        \ftp_pasv($conn, true);

        return $conn;
    }

    /**
     * Création récursive des dossiers FTP
     */
    private function createDirectory($conn, string $path): void
    {
        $parts = explode('/', trim($path, '/'));
        $currentPath = '';

        foreach ($parts as $part) {
            $currentPath .= '/' . $part;

            if (!@ftp_chdir($conn, $currentPath)) {
                if (!\ftp_mkdir($conn, $currentPath)) {
                    throw new \Exception("Impossible de créer le dossier FTP: $currentPath");
                }
            }
        }
    }

    public function upload(
        UploadedFile $file,
        string $subDir = '',
        array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'],
        bool $checkImage = true
    ): string {

        if (!$file) {
            throw new \Exception('Aucun fichier fourni');
        }

        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new \Exception('Fichier trop volumineux (max 2MB)');
        }

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \Exception('Type de fichier non autorisé');
        }

        if ($checkImage && @getimagesize($file->getPathname()) === false) {
            throw new \Exception('Fichier invalide');
        }

        $subDir = trim($subDir, '/');

        $filename = bin2hex(random_bytes(16)) . '.' . ($file->guessExtension() ?: 'bin');

        $remotePath = $this->baseDir;
        if ($subDir !== '') {
            $remotePath .= '/' . $subDir;
        }

        $conn = $this->connect();

        // Création fiable des dossiers
        $this->createDirectory($conn, $remotePath);

        $tempPath = $file->getPathname();

        if (!file_exists($tempPath)) {
            throw new \Exception("Fichier temporaire introuvable: " . $tempPath);
        }

        $uploadPath = $remotePath . '/' . $filename;

        if (!\ftp_put($conn, $uploadPath, $tempPath, FTP_BINARY)) {
            \ftp_close($conn);
            throw new \Exception("Upload FTP échoué vers: " . $uploadPath);
        }

        \ftp_close($conn);

        return ($subDir ? $subDir . '/' : '') . $filename;
    }

    public function delete(string $relativePath): bool
    {
        $relativePath = ltrim($relativePath, '/');
        if (str_contains($relativePath, '..')) {
            throw new \Exception('Path traversal détecté');
        }

        $conn = $this->connect();
        $filePath = $this->baseDir . '/' . $relativePath;

        // si le fichier existe
        $fileSize = @ftp_size($conn, $filePath);
        if ($fileSize === -1) {
            ftp_close($conn);
            // throw error peut causé des problemes si un mouvement ftp ou aprés une purge
            //throw new \Exception("Fichier introuvable sur le FTP: $filePath");
            return true;
        }

        // gestion du warning de ftp_delete si il retourne false
        if (!@ftp_delete($conn, $filePath)) {
            ftp_close($conn);
            throw new \Exception("Échec de suppression FTP: $filePath");
        }

        ftp_close($conn);

        return true;
    }
}
