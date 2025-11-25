<?php
class Note
{
    public int $id;
    public int $propertyId;
    public string $note;
    public string $createdAt;

    public static function fromArray(array $row): self
    {
        $n = new self();
        $n->id = (int)$row['id'];
        $n->propertyId = (int)$row['property_id'];
        $n->note = $row['note'];
        $n->createdAt = $row['created_at'];
        return $n;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->propertyId,
            'note' => $this->note,
            'created_at' => $this->createdAt,
        ];
    }
}
