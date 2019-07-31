<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportService\Model\Import\Storage;

use Magento\ImportServiceApi\Api\Data\SourceCsvInterface;
use Magento\ImportServiceApi\Api\Data\ImportConfigInterface;
use Magento\ImportServiceApi\Model\ImportStartResponse;

/**
 *  Storage Processor Interface
 */
interface ProcessorInterface
{

    /**
     * @param array $mappingItemsList
     * @param ImportConfigInterface $importConfig
     * @param SourceCsvInterface $source
     * @param ImportStartResponseFactory $importResponse
     *
     * @return ImportStartResponseFactory
     */
    public function process(array $mappingItemsList, ImportConfigInterface $importConfig, SourceCsvInterface $source, ImportStartResponse $importResponse): ImportStartResponse;

}
