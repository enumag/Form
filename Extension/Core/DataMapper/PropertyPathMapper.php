<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Extension\Core\DataMapper;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Util\VirtualFormAwareIterator;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class PropertyPathMapper implements DataMapperInterface
{
    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, array $forms)
    {
        if (!empty($data) && !is_array($data) && !is_object($data)) {
            throw new UnexpectedTypeException($data, 'Object, array or empty');
        }

        if (!empty($data)) {
            $iterator = new VirtualFormAwareIterator($forms);
            $iterator = new \RecursiveIteratorIterator($iterator);

            foreach ($iterator as $form) {
                $this->mapDataToForm($data, $form);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForm($data, FormInterface $form)
    {
        if (!empty($data)) {
            $propertyPath = $form->getPropertyPath();
            $config = $form->getConfig();

            if (null !== $propertyPath && $config->getMapped()) {
                $propertyData = $propertyPath->getValue($data);

                if (is_object($propertyData) && !$form->getAttribute('by_reference')) {
                    $propertyData = clone $propertyData;
                }

                $form->setData($propertyData);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData(array $forms, &$data)
    {
        $iterator = new VirtualFormAwareIterator($forms);
        $iterator = new \RecursiveIteratorIterator($iterator);

        foreach ($iterator as $form) {
            $this->mapFormToData($form, $data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormToData(FormInterface $form, &$data)
    {
        $propertyPath = $form->getPropertyPath();
        $config = $form->getConfig();

        // Write-back is disabled if the form is not synchronized (transformation failed)
        // and if the form is disabled (modification not allowed)
        if (null !== $propertyPath && $config->getMapped() && $form->isSynchronized() && !$form->isDisabled()) {
            // If the data is identical to the value in $data, we are
            // dealing with a reference
            $isReference = $form->getData() === $propertyPath->getValue($data);
            $byReference = $form->getAttribute('by_reference');

            if (!(is_object($data) && $isReference && $byReference)) {
                $propertyPath->setValue($data, $form->getData());
            }
        }
    }
}
