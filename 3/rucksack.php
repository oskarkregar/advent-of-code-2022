<?php

$data = file_get_contents("data.txt");
$lines = explode(PHP_EOL, $data);

$priorities = 0;
$compartment_priorities = 0;

$alphabet = new Alphabet();
foreach ($lines as $index => $line) {
    $rucksack = new Rucksack($line, $alphabet);
    $compartment_priorities += $rucksack->getPriority();

    if ($index % 3 === 0) {
        $rucksack_group = new RucksackGroup([$rucksack], $alphabet);
    } else if ($index % 3 === 2) {
        $rucksack_group->addRucksack($rucksack);
        $priorities += $rucksack_group->getPriority();
    } else {
        $rucksack_group->addRucksack($rucksack);
    }
}

echo "BADGE PRIORITIES SUM IS " . $priorities . PHP_EOL;
echo "COMPARTMENT PRIORITIES SUM IS " . $compartment_priorities;

class RucksackGroup
{
    public function __construct(array $rucksacks, Alphabet $alphabet)
    {
        $this->rucksacks = $rucksacks;
        $this->alphabet = $alphabet;
    }

    public function addRucksack(Rucksack $rucksack)
    {
        $this->rucksacks[] = $rucksack;
    }

    public function getPriority(): int
    {
        $this->common_item = $this->findCommonItem();
        return $this->alphabet->getPriority()[$this->common_item];
    }

    private function findCommonItem()
    {
        $elf_items = array_map(fn ($elf) => $elf->getItems(), $this->rucksacks);
        return array_values(array_intersect($elf_items[0], ...array_slice($elf_items, 1)))[0];
    }
}

class Alphabet
{
    public function __construct()
    {
        $alphabet_lower = range('a', 'z');
        $alphabet_upper = range('A', 'Z');

        $this->alphabet = array_merge($alphabet_lower, $alphabet_upper);
        $this->priority_alphabet = array_map(fn ($priority) => $priority + 1, array_flip($this->alphabet));
    }

    public function getAlphabet(): array
    {
        return $this->alphabet;
    }

    public function getPriority(): array
    {
        return $this->priority_alphabet;
    }
}

class Rucksack
{
    public function __construct(string $items, Alphabet $alphabet)
    {
        $this->items = str_split($items);
        $this->divideCompartments();
        $this->alphabet = $alphabet;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getCompartments()
    {
        return $this->compartments;
    }

    public function getPriority(): int
    {
        return $this->alphabet->getPriority()[$this->findSameItem()];
    }

    private function findSameItem(): string
    {
        return array_values(array_intersect($this->compartments[0], ...array_slice($this->compartments, 1)))[0];
    }

    private function divideCompartments()
    {
        $this->compartments = [];
        $this->compartments[] = array_slice($this->items, 0, count($this->items) / 2);
        $this->compartments[] = array_slice($this->items, count($this->items) / 2, count($this->items));
    }
}
