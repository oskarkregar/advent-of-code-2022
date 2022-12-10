<?php

echo "TOPMOST OLD CRANE REORDERING: " . implode((new Crates(file_get_contents("data.txt")))->executeCommands()->getTopCrates()) . PHP_EOL;
echo "TOPMOST NEW CRANE REORDERING: " . implode((new Crates(file_get_contents("data.txt"), false))->executeCommands()->getTopCrates());

class Crates
{
    public function __construct(string $crates_data, bool $old_crane = true)
    {
        $this->old_crane = $old_crane;

        // Loop through lines and extract initial position data and then moving data
        $lines = explode(PHP_EOL, $crates_data);
        $this->initial_data_lines = [];
        foreach ($lines as $index => $line) {
            // Add to initial data lines all lines before an empty line
            if (trim($line) === "") {
                break;
            }

            $this->initial_data_lines[] = $line;
        }

        $this->commands = array_slice($lines, $index + 1);

        $this->setInitialPosition();
    }

    public function getStacks()
    {
        return $this->stacks;
    }

    public function executeCommands()
    {
        foreach ($this->commands as $command) {
            $this->moveCrates($command);
        }

        return $this;
    }

    public function getTopCrates()
    {
        return array_map(fn ($stack) => $stack->getTopCrate(), $this->stacks);
    }

    private function moveCrates(string $command)
    {
        // Parse command and move crates
        preg_match('/move (\d+) from (\d+) to (\d+)/', trim($command), $matches);

        $quantity = $matches[1];
        $crate_from = $matches[2];
        $crate_to = $matches[3];

        // Take crate from given position
        $moved_crates = $this->stacks[$crate_from - 1]->takeCrates($quantity);

        // Reverse order of crates if we are using old crane because we do one by one addition to stack
        if ($this->old_crane) {
            $moved_crates = array_reverse($moved_crates);
        }

        // Append crates to given position
        $this->stacks[$crate_to - 1]->addCrates($moved_crates);
    }

    private function setInitialPosition()
    {
        $this->stacks = [];
        foreach ($this->initial_data_lines as $line_index => $initial_data_line) {
            // Split initial data by characters and chunk by 4 characters as this is the space where crate on stack is stored
            $splitted_line = str_split($initial_data_line);
            $line_chunks = array_chunk($splitted_line, 4);

            // Create stack for each chunk and add crate to it if it is there
            foreach ($line_chunks as $chunk_index => $line_chunk) {
                // Check if we already created stack for
                preg_match("/\[(.*)\]/", trim(implode($line_chunk)), $matches);

                // Get crates on chunk
                $crates_on_chunk = (count($matches) > 0) ? [$matches[1]] : [];
                // Check if we are on first line and we have to create stacks else only add to them
                if ($line_index === 0) {
                    $this->stacks[] = new Stack($crates_on_chunk);
                } else {
                    $this->stacks[$chunk_index]->addCrates($crates_on_chunk);
                }
            }
        }

        // Reverse crates positions as we want to have the top most crate on stack at the end of array
        array_map(fn ($stack) => $stack->reverseCrates(), $this->stacks);
    }
}

class Stack
{
    public function __construct(array $crates)
    {
        $this->crates = $crates;
    }

    public function takeCrates(int $quantity): array
    {
        return array_splice($this->crates, -$quantity);
    }

    public function addCrates(array $crates)
    {
        $this->crates = array_merge($this->crates, $crates);
    }

    public function reverseCrates()
    {
        $this->crates = array_reverse($this->crates);
    }

    public function getTopCrate(): string
    {
        return $this->crates[count($this->crates) - 1];
    }
}
