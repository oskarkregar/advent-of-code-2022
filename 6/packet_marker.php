<?php

echo "START OF PACKET IS: " . (new PacketMarker(file_get_contents("data.txt"), 4))->findMarker() . PHP_EOL;
echo "START OF MESSAGE IS: " . (new PacketMarker(file_get_contents("data.txt"), 14))->findMarker();

class PacketMarker
{
    public function __construct(string $datastream, int $distinct_chars)
    {
        $this->datastream = str_split($datastream);
        $this->characters = array_splice($this->datastream, 0, $distinct_chars);
        $this->marker_index = $distinct_chars;
    }

    public function findMarker(): ?int
    {
        while (count($this->datastream) > 0) {
            if ($this->checkIfAllDifferent()) {
                return $this->marker_index;
            }

            $this->goNext();
        }

        return null;
    }

    private function goNext()
    {
        // Append next character from datastream and remove first one
        $this->characters[] = array_shift($this->datastream);
        array_shift($this->characters);
        $this->marker_index++;
    }

    private function checkIfAllDifferent(): bool
    {
        return count(array_unique($this->characters)) === count($this->characters);
    }
}
