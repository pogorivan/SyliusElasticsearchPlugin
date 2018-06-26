<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace BitBag\SyliusElasticsearchPlugin\PropertyBuilder;

use FOS\ElasticaBundle\Event\TransformEvent;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Repository\ProductAttributeValueRepositoryInterface;

final class AttributeTaxonsBuilder extends AbstractBuilder
{
    /**
     * @var ProductAttributeValueRepositoryInterface
     */
    private $productAttributeValueRepository;

    /**
     * @var string
     */
    private $attributeProperty;

    /**
     * @var string
     */
    private $taxonsProperty;

    /**
     * @var array
     */
    private $excludedAttributes;

    /**
     * @param ProductAttributeValueRepositoryInterface $productAttributeValueRepository
     * @param string $attributeProperty
     * @param string $taxonsProperty
     * @param array $excludedAttributes
     */
    public function __construct(
        ProductAttributeValueRepositoryInterface $productAttributeValueRepository,
        string $attributeProperty,
        string $taxonsProperty,
        array $excludedAttributes = []
    ) {
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->attributeProperty = $attributeProperty;
        $this->taxonsProperty = $taxonsProperty;
        $this->excludedAttributes = $excludedAttributes;
    }

    /**
     * @param TransformEvent $event
     */
    public function consumeEvent(TransformEvent $event): void
    {
        $documentAttribute = $event->getObject();

        if (!$documentAttribute instanceof AttributeInterface
            || in_array($documentAttribute->getCode(), $this->excludedAttributes)
        ) {
            return;
        }

        $document = $event->getDocument();
        $productAttributes = $this->productAttributeValueRepository->findAll();
        $taxons = [];

        /** @var ProductAttributeValueInterface $attributeValue */
        foreach ($productAttributes as $attributeValue) {
            /** @var ProductInterface $product */
            $product = $attributeValue->getProduct();

            if ($documentAttribute === $attributeValue->getAttribute() && $product->isEnabled()) {
                foreach ($product->getTaxons() as $taxon) {
                    $taxons[] = $taxon->getCode();
                }
            }
        }

        $taxons = array_values(array_unique($taxons));

        $document->set($this->taxonsProperty, $taxons);
    }
}
