<?php

namespace App\Command;

use App\Entity\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class SandboxCommand extends Command
{
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct();
        $this->serializer = $serializer;
    }

    protected static $defaultName = 'app:sandbox';

    protected function configure()
    {
        $this->setDescription('Serialize and deserialize a Product object.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create a product
        $product = new Product(1, 'Laptop', 1500.99);

        // Serialize the product to JSON
        $jsonContent = $this->serializer->serialize($product, 'json', [AbstractNormalizer::GROUPS => ['product:read']]);

        $output->writeln("Serialized Product in JSON:");
        $output->writeln($jsonContent);

        // Simulate a received JSON
        $jsonData = '{"name": "Smartphone", "price": 799.99}';

        // Deserialize the JSON into a Product object
        $deserializedProduct = $this->serializer->deserialize(
            $jsonData,
            Product::class,
            'json',
            [AbstractNormalizer::GROUPS => ['product:write']]
        );

        $output->writeln("\nDeserialized Product Object:");
        $output->writeln('Name: ' . $deserializedProduct->getName());
        $output->writeln('Price: ' . $deserializedProduct->getPrice());

        return Command::SUCCESS;
    }

}