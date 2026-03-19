<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Psr\Log\LoggerInterface;

/**
 * Transforms between a PHP array and a JSON string for form fields.
 * 
 * - transform(): array => JSON string
 * - reverseTransform(): JSON string => array
 */
class JsonToArrayTransformer implements DataTransformerInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * Transforms an array into a JSON string.
     *
     * @param array|null $value The PHP array
     * @return string JSON string for the form field
     */
    public function transform($value): string
    {
        if (null === $value) {
            return '';
        }

        return json_encode($value, JSON_PRETTY_PRINT);
    }

    /**
     * Transforms a JSON string from the form into a PHP array.
     *
     * @param string|null $value JSON string from the form
     * @return array Decoded PHP array
     *
     * @throws TransformationFailedException If the JSON is invalid
     */
    public function reverseTransform($value): array
    {
        if (!$value) {
            return [];
        }

        $data = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->warning('Invalid JSON entered in subscription plan features.', [
                'input' => $value,
                'error' => json_last_error_msg(),
            ]);

            throw new TransformationFailedException('Invalid JSON format.');
        }

        return $data;
    }

}