<?php
class NoteService
{
    public function __construct(private NoteRepository $notes, private PropertyRepository $properties) {}

    public function add(int $propertyId, string $text): array
    {
        if ($propertyId <= 0) {
            throw new InvalidArgumentException('Property ID is required');
        }
        $text = trim($text);
        if ($text === '') {
            throw new InvalidArgumentException('Note content is required');
        }
        $prop = $this->properties->findById($propertyId);
        if (!$prop) {
            throw new RuntimeException('Property not found');
        }
        $n = new Note();
        $n->propertyId = $propertyId;
        $n->note = $text;
        $created = $this->notes->insert($n);
        return $created->toArray();
    }
}
