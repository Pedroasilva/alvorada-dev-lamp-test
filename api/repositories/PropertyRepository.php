<?php
class PropertyRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?Property
    {
        $stmt = $this->pdo->prepare('SELECT * FROM properties WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? Property::fromArray($row) : null;
    }

    public function insert(Property $p): Property
    {
        $stmt = $this->pdo->prepare('INSERT INTO properties (name,address,latitude,longitude,nominatim_data,created_at) VALUES (?,?,?,?,?,NOW())');
        $stmt->execute([
            $p->name,
            $p->address,
            $p->latitude,
            $p->longitude,
            $p->nominatimData ? json_encode($p->nominatimData) : null,
        ]);
        $id = (int)$this->pdo->lastInsertId();
        return $this->findById($id);
    }

    public function recent(int $limit = 4): array
    {
        $sqlForce = 'SELECT * FROM properties FORCE INDEX (idx_recent_props) ORDER BY created_at DESC LIMIT ' . (int)$limit;
        $sqlPlain = 'SELECT * FROM properties ORDER BY created_at DESC LIMIT ' . (int)$limit;
        try {
            $stmt = $this->pdo->prepare($sqlForce);
            $stmt->execute();
        } catch (PDOException $e) {
            // Fallback caso índice não exista ou FORCE INDEX falhe
            error_log('recent() FORCE INDEX fallback: ' . $e->getMessage());
            $stmt = $this->pdo->prepare($sqlPlain);
            $stmt->execute();
        }
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => Property::fromArray($r), $rows);
    }
}
