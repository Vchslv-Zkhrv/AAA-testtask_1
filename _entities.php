<?php

namespace Entity;

include '_rules.php';


/**
 * Классы - обертки над таблицами
 * Можно сделать более функциональными, но для нашей задачи достаточно и этого
 * Названия полей не по PSR, так как они провторяют имена столбцов
 */


abstract class Entity
{
    abstract static function getTableName(): string;

    /**
     * Прочитать массив как сущность
     *
     * @param array<string,mixed> $array
     * @return static
     */
    abstract public static function fromArray(array $array): static;

    /**
     * Прочитать массив массивов как массив сущностей
     *
     * @param array[] $array
     * @return static[]
     */
    public static function fromArrayMultiple(array $array): array
    {
        return array_map(
            fn (array $a) => static::fromArray($a),
            $array
        );
    }
}


class Company extends Entity
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }

    static function getTableName(): string
    {
        return 'companies';
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['id'],
            (string)$array['name'],
        );
    }
}


class Agency extends Entity
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }

    static function getTableName(): string
    {
        return 'agencies';
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['id'],
            (string)$array['name'],
        );
    }
}


class Country extends Entity
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }

    static function getTableName(): string
    {
        return 'countries';
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['id'],
            (string)$array['name'],
        );
    }
}


class City extends Entity
{
    public function __construct(
        public int $id,
        public string $name,
        public int $country_id
    ) {
    }

    static function getTableName(): string
    {
        return 'cities';
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['id'],
            (string)$array['name'],
            (int)$array['country_id'],
        );
    }
}


class Hotel extends Entity
{
    public function __construct(
        public int $id,
        public string $name,
        public int $stars,
        public int $city_id
    ) {
    }

    static function getTableName(): string
    {
        return 'hotel';
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['id'],
            (string)$array['name'],
            (int)$array['stars'],
            (int)$array['city_id'],
        );
    }
}


class AgencyHotelOptions extends Entity
{
    public function __construct(
        public int $id,
        public int $hotel_id,
        public int $agency_id,
        public int $percent,
        public bool $is_black,
        public bool $is_recomend,
        public bool $is_white
    ) {
    }

    static function getTableName(): string
    {
        return 'agency_hotel_options';
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['id'],
            (int)$array['hotel_id'],
            (int)$array['agency_id'],
            (int)$array['percent'],
            (bool)(int)$array['is_black'],
            (bool)(int)$array['is_recomend'],
            (bool)(int)$array['is_white'],
        );
    }
}


class HotelAgreements extends Entity
{
    public function __construct(
        public int $id,
        public int $hotel_id,
        public int $discount_percent,
        public int $comission_percent,
        public bool $is_default,
        public int $vat_percent,
        public int $vat1_percent,
        public int $vat1_value,
        public int $company_id,
        public \DateTimeImmutable $date_from,
        public \DateTimeImmutable $date_to,
        public bool $is_cash_payment
    ) {
    }

    static function getTableName(): string
    {
        return 'hotel_agreements';
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['id'],
            (int)$array['hotel_id'],
            (int)$array['discount_percent'],
            (int)$array['comission_percent'],
            (bool)(int)$array['is_default'],
            (int)$array['vat_percent'],
            (int)$array['vat1_percent'],
            (int)$array['vat1_value'],
            (int)$array['company_id'],
            new \DateTimeImmutable($array['date_from']),
            new \DateTimeImmutable($array['date_to']),
            (bool)(int)$array['is_cash_payment'],
        );
    }
}


class FilterRule extends Entity
{
    public function __construct(
        public int $id,
        public int $agency_id,
        public string $description,
        public \Rule\RulesSet $value
    ) {
    }

    static function getTableName(): string
    {
        return 'filer_rule';
    }

    public static function fromArray(array $array): static
    {
        return new static(
            (int)$array['id'],
            (int)$array['agency_id'],
            (string)$array['description'],
            \Rule\RulesSet::fromJSON($array['value']),
        );
    }
}


const ENTITIES = [
    'companies' => Company::class,
    'agencies' => Agency::class,
    'countries' => Country::class,
    'cities' => City::class,
    'hotels' => Hotel::class,
    'agency_hotel_options' => AgencyHotelOptions::class,
    'hotel_agreements' => HotelAgreements::class,
    'filter_rule' => FilterRule::class
];
