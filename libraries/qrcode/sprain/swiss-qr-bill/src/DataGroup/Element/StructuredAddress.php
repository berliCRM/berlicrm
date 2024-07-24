<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\DataGroup\Element;

use Sprain\SwissQrBill\DataGroup\AddressInterface;
use Sprain\SwissQrBill\DataGroup\QrCodeableInterface;
use Sprain\SwissQrBill\Validator\SelfValidatableInterface;
use Sprain\SwissQrBill\Validator\SelfValidatableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

final class StructuredAddress implements AddressInterface, SelfValidatableInterface, QrCodeableInterface
{
    use SelfValidatableTrait;

    public const ADDRESS_TYPE = 'S';

    /**
     * Name or company
     */
    private string $name;

    /**
     * Street / P.O. box
     *
     * May not include building or house number.
     */
    private ?string $street;

    /**
     * Building number
     */
    private ?string $buildingNumber;

    /**
     * Postal code without country code
     */
    private string $postalCode;

    /**
     * City
     */
    private string $city;

    /**
     * Country (ISO 3166-1 alpha-2)
     */
    private string $country;

    /**
     * Organisation
     */
    private string $organisation;

    private function __construct(
        string $name,
        ?string $street,
        ?string $buildingNumber,
        string $postalCode,
        string $city,
        string $country,
        string $organisation
    ) {
        $this->name = $name;
        $this->street = $street;
        $this->buildingNumber = $buildingNumber;
        $this->postalCode = $postalCode;
        $this->city = $city;
        $this->country = strtoupper($country);
        $this->organisation = $organisation;
    }

    public static function createWithoutStreet(
        string $name,
        string $postalCode,
        string $city,
        string $country,
        string $organisation
    ): self {
        return new self(
            $name,
            null,
            null,
            $postalCode,
            $city,
            $country,
            $organisation
        );
    }

    public static function createWithStreet(
        string $name,
        string $street,
        ?string $buildingNumber,
        string $postalCode,
        string $city,
        string $country,
        string $organisation
    ): self {
        return new self(
            $name,
            $street,
            $buildingNumber,
            $postalCode,
            $city,
            $country,
            $organisation
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getBuildingNumber(): ?string
    {
        return $this->buildingNumber;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getOrganisation(): string
    {
        return $this->organisation;
    }

    public function getFullAddress(bool $forReceipt = false): string
    {
        if(!empty($this->getOrganisation()))
        {
            $address = $this->getOrganisation();
        }

        $address .=  "\n" . $this->getName();

        if ($this->getStreet()) {
            $address .= "\n" . $this->getStreet();

            if ($this->getBuildingNumber()) {
                $address .= " " . $this->getBuildingNumber();
            }
        }

        if (in_array($this->getCountry(), ['CH', 'FL'])) {
            $address .= sprintf("\n%s %s", $this->getPostalCode(), $this->getCity());
        } else {
            $address .= sprintf("\n%s-%s %s", $this->getCountry(), $this->getPostalCode(), $this->getCity());
        }

        return $address;
    }

    public function getQrCodeData(): array
    {
        return [
            $this->getCity() ? self::ADDRESS_TYPE : '',
            $this->getName(),
            $this->getStreet(),
            $this->getBuildingNumber(),
            $this->getPostalCode(),
            $this->getCity(),
            $this->getCountry(),
            $this->getOrganisation()
        ];
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank(),
            new Assert\Length([
                'max' => 70
            ])
        ]);

        $metadata->addPropertyConstraints('street', [
            new Assert\Length([
                'max' => 70
            ])
        ]);

        $metadata->addPropertyConstraints('buildingNumber', [
            new Assert\Length([
                'max' => 16
            ])
        ]);

        $metadata->addPropertyConstraints('postalCode', [
            new Assert\NotBlank(),
            new Assert\Length([
                'max' => 16
            ])
        ]);

        $metadata->addPropertyConstraints('city', [
            new Assert\NotBlank(),
            new Assert\Length([
                'max' => 35
            ])
        ]);

        $metadata->addPropertyConstraints('country', [
            new Assert\NotBlank(),
            new Assert\Country()
        ]);

        $metadata->addPropertyConstraints('organisation', [
            new Assert\Length([
                'max' => 20
            ])
        ]);
    }
}
