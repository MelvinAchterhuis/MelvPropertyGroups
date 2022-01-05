<?php declare(strict_types=1);

namespace Melv\PropertyGroups\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;

class ProductPageSubscriber implements EventSubscriberInterface
{
    private $propertyGroupRepository;

    public function __construct(
        EntityRepositoryInterface $propertyGroupRepository
    ) {
        $this->propertyGroupRepository = $propertyGroupRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'loadMasterPropertyGroup',
        ];
    }

    public function loadMasterPropertyGroup(ProductPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $properties = $page->getProduct()->getSortedProperties()->getElements();

        //If no properties, early return
        if(count($properties) === 0) {
            return;
        }

        foreach($properties as $property) {
            $propertyCustomFields = $property->getTranslated()['customFields'];
            foreach($propertyCustomFields as $key => $value) {
                if(str_starts_with($key, 'melv_property_group_master')) {
                    $criteria = new Criteria(array($value));
                    $propertyGroup = $this->propertyGroupRepository->search($criteria, $event->getContext());
                    $masterGroup = $propertyGroup->first();
                    $property->addExtension('melv_property_group_master', $masterGroup);
                }
            }
        }
    }
}
