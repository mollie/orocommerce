<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class FileUploader
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationServices
 */
class FileUploader
{
    /**
     * @var string
     */
    private $targetDirectory;

    /**
     * FileUploader constructor.
     *
     * @param $targetDirectory
     */
    public function __construct($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    /**
     * Uploads file to a target directory and returns new file name. If operation fail, return vale will be null.
     *
     * @param UploadedFile $file
     * @param string $fileNamePrefix Prefix to use for a new file name
     *
     * @return string|null
     */
    public function upload(UploadedFile $file, $fileNamePrefix)
    {
        $fileName = uniqid("{$fileNamePrefix}-", false).'.'.$file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            Logger::logError(
                'Failed to upload payment method image',
                'Integration',
                [
                    'ExceptionMessage' => $e->getMessage(),
                    'ExceptionTrace' => $e->getTraceAsString(),
                ]
            );

            $fileName = null;
        }

        return $fileName;
    }

    /**
     * @param string $imageName
     */
    public function remove($imageName)
    {
        $imagePath = "{$this->getTargetDirectory()}/{$imageName}";
        if (is_file($imagePath)) {
            @unlink($imagePath);
        }
    }

    /**
     * @param string $prefix
     */
    public function removeAllWithPrefix($prefix)
    {
        $iterator = new \GlobIterator("{$this->getTargetDirectory()}/$prefix*.*");
        foreach ($iterator as $file) {
            @unlink($file->getRealPath());
        }
    }

    /**
     * @return string
     */
    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}
