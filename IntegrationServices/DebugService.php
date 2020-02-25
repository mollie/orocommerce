<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Entity;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use ZipArchive;

class DebugService
{
    const PHP_INFO_FILE_NAME = 'phpinfo.html';
    const ORDER_REFERENCES_FILE_NAME = 'order-references.json';
    const LOG_FILE_DIR = 'logs';

    /**
     * @var string
     */
    private $logsPath;

    /**
     * DebugService constructor.
     * @param string $logsPath
     */
    public function __construct($logsPath)
    {
        $this->logsPath = $logsPath;
    }

    /**
     * Returns path to zip archive that contains current system info.
     *
     * @return string
     */
    public function getDebugDataFilePath()
    {
        $file = tempnam(sys_get_temp_dir(), 'mollie_debug_data');

        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::CREATE);
        $phpInfo = static::getPhpInfo();

        if (false !== $phpInfo) {
            $zip->addFromString(static::PHP_INFO_FILE_NAME, $phpInfo);
        }

        $zip->addFromString(static::ORDER_REFERENCES_FILE_NAME, $this->getOrderReferences());

        $this->addLogs($zip);

        $zip->close();

        return $file;
    }

    /**
     * Retrieves formatted php info.
     *
     * @return false | string
     */
    protected static function getPhpInfo()
    {
        ob_start();
        phpinfo();

        return ob_get_clean();
    }

    /**
     * Adds contents of log files.
     *
     * @param ZipArchive $zipArchive
     */
    protected function addLogs(ZipArchive $zipArchive)
    {
        // Store the path into the variable
        $dir = opendir($this->logsPath);

        while(false !== ($file = readdir($dir))) {
            if(is_file($this->logsPath.'/'.$file)) {
                $zipArchive->addFile($this->logsPath.'/'.$file, static::LOG_FILE_DIR.'/'.$file);
            }
        }
    }

    protected function getOrderReferences()
    {
        $result = [];

        try {
            $repository = RepositoryRegistry::getRepository(OrderReference::getClassName());
            $result = $repository->select();
        } catch (RepositoryNotRegisteredException $e) {
        }

        return $this->formatJsonOutput($result);
    }

    /**
     * Formats json output.
     *
     * @param Entity[] $items
     *
     * @return string
     */
    protected function formatJsonOutput(array $items)
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = $item->toArray();
        }

        return json_encode($result, JSON_PRETTY_PRINT);
    }
}