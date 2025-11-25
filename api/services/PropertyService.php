<?php
class PropertyService
{
    public function __construct(private PropertyRepository $properties, private NoteRepository $notes, private ?GeocodeService $geocode = null) {}

    public function create(array $data): array
    {
        // Nome e endereço continuam obrigatórios
        foreach (['name','address'] as $f) {
            if (!isset($data[$f]) || trim((string)$data[$f]) === '') {
                throw new InvalidArgumentException(ucfirst($f) . ' is required');
            }
        }

        $p = new Property();
        $p->name = trim($data['name']);
        $p->address = trim($data['address']);

        $latProvided = isset($data['latitude']) && trim((string)$data['latitude']) !== '';
        $lngProvided = isset($data['longitude']) && trim((string)$data['longitude']) !== '';

        if ($latProvided && $lngProvided) {
            $lat = (float)$data['latitude'];
            $lng = (float)$data['longitude'];
            if ($lat < -90 || $lat > 90) {
                throw new InvalidArgumentException('Invalid latitude value');
            }
            if ($lng < -180 || $lng > 180) {
                throw new InvalidArgumentException('Invalid longitude value');
            }
            $p->latitude = $lat;
            $p->longitude = $lng;
            $p->nominatimData = $data['nominatim_data'] ?? null;
        } else {
            // Geocodificação automática se latitude/longitude não forem enviados
            if (!$this->geocode) {
                throw new InvalidArgumentException('Latitude and longitude missing and GeocodeService not available');
            }
            $geo = $this->geocode->lookup($p->address);
            if ($geo['status'] === 'not_found') {
                throw new InvalidArgumentException('Address not found');
            }
            if ($geo['status'] === 'error') {
                throw new InvalidArgumentException('Geocoding service unavailable');
            }
            $first = $geo['data'][0] ?? null;
            if (!$first) {
                throw new InvalidArgumentException('Address not found');
            }
            $p->latitude = (float)$first['lat'];
            $p->longitude = (float)$first['lon'];
            $p->nominatimData = $first; // array será json_encode no repository
        }

        $created = $this->properties->insert($p);
        return $created->toArray();
    }

    public function details(int $id): ?array
    {
        $p = $this->properties->findById($id);
        if (!$p) {
            return null;
        }
        $notes = $this->notes->findByProperty($id);
        return [
            'property' => $p->toArray(),
            'notes' => array_map(fn($n) => $n->toArray(), $notes)
        ];
    }

    public function recent(int $limit = 4): array
    {
        return array_map(fn($p) => $p->toArray(), $this->properties->recent($limit));
    }
}
