<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsynchronousImport\Model\Import\ConvertingRule;

use Magento\AsynchronousImportApi\Api\Data\ConvertingRuleInterface;
use Magento\AsynchronousImportApi\Api\Data\ImportDataInterface;
use Magento\AsynchronousImportApi\Api\ImportException;
use Magento\AsynchronousImportApi\Model\ConvertingRuleProcessorInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritdoc
 */
class AttributeSetNameToId implements ConvertingRuleProcessorInterface
{
    /** @var AttributeSetRepositoryInterface */
    private $attributeSetRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var int[] */
    private $attributeSetIds = [];

    /** @var EavConfig */
    private $eavConfig;

    /**
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param EavConfig $eavConfig
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        AttributeSetRepositoryInterface $attributeSetRepository,
        EavConfig $eavConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeSetRepository = $attributeSetRepository;
        $this->eavConfig              = $eavConfig;
        $this->searchCriteriaBuilder  = $searchCriteriaBuilder;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        ImportDataInterface $importData,
        ConvertingRuleInterface $convertingRule
    ): ImportDataInterface {
        $data       = $importData->getData();
        $parameters = $convertingRule->getParameters();
        if (!array_key_exists('entity_type', $parameters)) {
            throw new ImportException(__('The "entity_type" parameter is an obligatory.'));
        }

        foreach ($convertingRule->getApplyTo() as $applyToField) {
            foreach ($data as &$row) {
                $row[$applyToField] = $this->getAttributeSetId($row[$applyToField], $parameters['entity_type']);
            }
        }
        unset($row);

        $importData->{ImportDataInterface::DATA} = $data;

        return $importData;
    }

    /**
     * @param string $name
     * @param string $entityTypeCode
     * @return int
     * @throws ImportException
     */
    private function getAttributeSetId(string $name, string $entityTypeCode): int
    {
        try {
            $entityTypeId = $this->eavConfig->getEntityType($entityTypeCode)->getEntityTypeId();
        } catch (LocalizedException $e) {
            $msg = 'There is no entity type, with the entity_type_code "%s" in the eav_entity_type table.';
            throw new ImportException(__($msg, $entityTypeCode));
        }

        if (\array_key_exists($name, $this->attributeSetIds)) {
            return $this->attributeSetIds[$entityTypeId][$name];
        }

        $search = $this->searchCriteriaBuilder
            ->addFilter('attribute_set_name', $name)
            ->addFilter('entity_type_id', $entityTypeId)
            ->create();

        $attributeSetList = $this->attributeSetRepository->getList($search)->getItems();
        if (!\count($attributeSetList)) {
            $msg = 'There is no attribute set, with the attribute_set_name "%s" in the eav_attribute_set table.';
            throw new ImportException(__($msg, $name));
        }

        $attributeSet                                = \current($attributeSetList);
        $this->attributeSetIds[$entityTypeId][$name] = $attributeSet->getEntityTypeId();

        return $this->attributeSetIds[$entityTypeId][$name];
    }
}
