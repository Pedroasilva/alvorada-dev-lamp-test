<?php
class Property
{
    public int $id;
    public string $name;
    public string $address;
    public float $latitude;
    public float $longitude;
    public ?array $nominatimData;
    public string $createdAt;

    public static function fromArray(array $row): self
    {
        $p = new self();
        $p->id = (int)$row['id'];
        $p->name = $row['name'];
        $p->address = $row['address'];
        $p->latitude = (float)$row['latitude'];
        $p->longitude = (float)$row['longitude'];
        $p->nominatimData = isset($row['nominatim_data']) && $row['nominatim_data'] ? json_decode($row['nominatim_data'], true) : null;
        $p->createdAt = $row['created_at'];
        return $p;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'nominatim_data' => $this->nominatimData,
            'created_at' => $this->createdAt,
        ];
    }
}
