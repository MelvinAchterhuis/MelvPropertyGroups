<?php declare(strict_types=1);

namespace Melv\PropertyGroups;

use Shopware\Core\Content\Property\PropertyGroupDefinition;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class MelvPropertyGroups extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $this->createCustomFields();
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $this->deleteCustomFields();

        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }
    }

    private function createCustomFields()
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldSetUuid = Uuid::randomHex();
        $context = new Context(new SystemSource());

        $customFieldSetRepository->upsert([
            [
                'id' => $customFieldSetUuid,
                'name' => 'melv_property_group',
                'config' => [
                    'label' => [
                        'en-GB' => 'Property Groups',
                        'de-DE' => 'Gruppeneingenschaften'
                    ]
                ],
                'customFields' => [
                    [
                        'id' => Uuid::randomHex(),
                        'name' => 'melv_property_group_master',
                        'type' => CustomFieldTypes::ENTITY,
                        'config' => [
                            'type' => 'select',
                            'entity' => 'property_group',
                            'componentName' => 'sw-entity-single-select',
                            'customFieldType' => 'entity',
                            'customFieldPosition' => 1,
                            'label' => [
                                'en-GB' => 'Master property',
                                'de-DE' => 'Master eigenschaft'
                            ]
                        ]
                    ],
                ],
                'relations' => [
                    [
                        'id' => $customFieldSetUuid,
                        'entityName' => $this->container->get(PropertyGroupDefinition::class)->getEntityName()
                    ],
                ]
            ]
        ], $context);
    }

    private function deleteCustomFields()
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $context = new Context(new SystemSource());

        $entityIds = $customFieldSetRepository->search(
            (new Criteria())->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
                new EqualsFilter('name', 'melv_property_group'),
            ])),
            $context
        )->getEntities()->getIds();

        if (count($entityIds) < 1) {
            return;
        }

        $entityIds = array_map(function ($element) {
            return ['id' => $element];
        }, array_values($entityIds));

        $customFieldSetRepository->delete(
            $entityIds,
            $context
        );
    }
}
