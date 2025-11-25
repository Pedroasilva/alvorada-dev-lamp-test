<?php
class NoteRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByProperty(int $propertyId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notes WHERE property_id = ? ORDER BY created_at DESC');
        $stmt->execute([$propertyId]);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => Note::fromArray($r), $rows);
    }

    public function insert(Note $n): Note
    {
        $stmt = $this->pdo->prepare('INSERT INTO notes (property_id,note,created_at) VALUES (?,?,NOW())');
        $stmt->execute([$n->propertyId, $n->note]);
        $id = (int)$this->pdo->lastInsertId();
        $stmt = $this->pdo->prepare('SELECT * FROM notes WHERE id = ?');
        $stmt->execute([$id]);
        return Note::fromArray($stmt->fetch());
    }
}
