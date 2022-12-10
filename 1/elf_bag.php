<?php

$data = file_get_contents("data.txt");
$lines = explode(PHP_EOL, $data);

$elf_count = 0;
$elfs = new Elfs();
$current_elf = new ElfBag($elf_count, 0);

foreach ($lines as $index => $line) {
    $line = trim($line);

    // Check if we have empty line as this is the next elf
    if (empty($line)) {
        $elfs->addElf($current_elf);
        $current_elf = new ElfBag($elf_count, 0);
        $elf_count += 1;
    } else {
        $current_elf->addCalories(intval($line));
    }
}

$sorted_elfs = $elfs->sort()->getElfs();
echo array_sum(array_map(fn ($elf) => $elf->getCalories(), array_slice($sorted_elfs, 0, 3)));

class Elfs
{
    public function __construct()
    {
        $this->elfs = [];
    }

    public function addElf(ElfBag $elf)
    {
        $this->elfs[] = $elf;
    }

    public function sort()
    {
        usort($this->elfs, fn ($elf_1, $elf_2) => ($elf_1->getCalories() < $elf_2->getCalories()) ? 1 : -1);
        return $this;
    }

    public function getElfs(): array
    {
        return $this->elfs;
    }
}

class ElfBag
{
    public function __construct(int $elf_number, int $calories)
    {
        $this->elf_number = $elf_number;
        $this->calories = $calories;
    }

    public function addCalories(int $calories)
    {
        $this->calories += $calories;
    }

    public function getCalories(): int
    {
        return $this->calories;
    }
}
