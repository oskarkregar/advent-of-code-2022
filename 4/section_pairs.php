<?php

$data = file_get_contents("data.txt");
$lines = explode(PHP_EOL, $data);

$counter = 0;
$counter_overlap = 0;
foreach ($lines as $line) {
    $section_pair = new SectionPair($line);
    if ($section_pair->checkIfFullyContains()) {
        $counter++;
    }

    if ($section_pair->checkIfOverlap()) {
        $counter_overlap++;
    }
}

echo "FULLY CONTAINING: " . $counter . PHP_EOL;
echo "OVERLAPPING: " . $counter_overlap;

class SectionPair
{
    public function __construct(string $section_pair)
    {
        $this->section_string = $section_pair;

        // Parse strings and create sections
        $this->sections = array_map(
            function ($pair_section) {
                $section_range = explode("-", $pair_section);
                return range($section_range[0], $section_range[1]);
            },
            explode(",", $this->section_string)
        );
    }

    public function checkIfFullyContains(): bool
    {
        return (count(array_diff($this->sections[0], $this->sections[1])) === 0 || count(array_diff($this->sections[1], $this->sections[0])) === 0);
    }

    public function checkIfOverlap(): bool
    {
        return count(array_intersect($this->sections[0], $this->sections[1])) > 0;
    }
}
