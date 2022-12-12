<?php

$grid = (new Grid(file_get_contents("data.txt")))->findVisible();
echo "THERE ARE " . count($grid->getVisible()) . " TREES VISIBLE IN A GRID" . PHP_EOL;
echo "THERE ARE " . count($grid->getTrees()) . " TREES IN A GRID";
// print_r($grid->getTrees());

class Grid
{
    private array $row_biggest;
    private array $column_biggest;
    private array $grid;
    private array $trees = [];
    private array $visible_trees = [];
    private bool $reversed = false;

    public function __construct(string $grid)
    {
        $this->readGrid($grid);
    }

    public function findVisible()
    {
        $this->addCorners();
        $this->scanGrid();
        $this->reverseGrid();
        $this->scanGrid();

        return $this;
    }

    private function scanGrid()
    {
        $this->setStartingBiggest();

        // Create first iterator through rows and check both sides
        foreach ($this->grid as $row) {
            foreach ($row as $tree) {
                $current_coordinates = ($this->reversed) ? $this->reverseCoordinates($tree->getRealCoordinates()) : $tree->getRealCoordinates();
                $tree->setCurrentCoordinates($current_coordinates);

                $this->checkIfVisible($tree);

                // Add tree only once
                if (!$this->reversed) {
                    $this->trees[] = $tree;
                }

                // Add also to visible trees if it is visible
                if ($tree->getVisible()) {
                    $this->visible_trees[] = $tree;
                }
            }
        }

        return $this;
    }

    private function reverseGrid()
    {
        // Reverse grid
        $this->grid = array_map("array_reverse", $this->grid);
        $this->grid = array_reverse($this->grid);

        $this->reversed = true;
    }

    public function getGrid()
    {
        return $this->grid;
    }

    public function getTrees()
    {
        return $this->trees;
    }

    public function getVisible()
    {
        return $this->visible_trees;
    }

    private function readGrid(string $grid_string)
    {
        $this->grid = [];
        $grid_rows = explode(PHP_EOL, $grid_string);

        foreach ($grid_rows as $row_index => $row) {
            $row_trees = [];
            $row = str_split($row);

            foreach ($row as $column_index => $el) {
                $row_trees[] = new Tree([$row_index, $column_index], $el, [$row_index, $column_index]);
            }

            $this->grid[] = $row_trees;
        }
    }

    private function setStartingBiggest()
    {
        $this->row_biggest = [];
        foreach (array_column($this->grid, 0) as $tree) {
            $current_coordinates = ($this->reversed) ? $this->reverseCoordinates($tree->getRealCoordinates()) : $tree->getRealCoordinates();
            $tree->setCurrentCoordinates($current_coordinates);

            $this->row_biggest[] = $tree;
        }

        $this->column_biggest = [];
        foreach ($this->grid[0] as $tree) {
            $current_coordinates = ($this->reversed) ? $this->reverseCoordinates($tree->getRealCoordinates()) : $tree->getRealCoordinates();
            $tree->setCurrentCoordinates($current_coordinates);

            $this->column_biggest[] = $tree;
        }
    }

    private function checkIfVisible(Tree &$tree)
    {
        $coordinates = $tree->getCurrentCoordinates();
        $tree->setVisible(false);

        // Already added as corners
        if ($coordinates[0] === 0 || $coordinates[1] === 0 || $coordinates[0] === (count($this->grid) - 1) || $coordinates[1] === (count($this->grid[0]) - 1)) {
            return;
        }

        // Check if current element is visible from left and top as this is how we iterate

        // Bigger than biggest on left

        $side = ($this->reversed) ? "right" : "left";

        if ($tree->getHeight() > $this->row_biggest[$coordinates[0]]->getHeight()) {
            if (!$this->checkIfAlreadyInVisible($tree->getRealCoordinates())) {
                $tree->setVisible(true);
            }

            // Set viewing distance to the end of grid
            $tree->setViewingDistance($side, $coordinates[0]);

            $this->row_biggest[$coordinates[0]] = $tree;
        } else {
            $highest_in_row = $this->row_biggest[$coordinates[0]];
            $highest_in_row_coordinates = $highest_in_row->getCurrentCoordinates();

            // Set viewing distance to the highest in the row
            $tree->setViewingDistance($side, $coordinates[0] - $highest_in_row_coordinates[0]);
        }

        // Bigger than biggest on top

        $side = ($this->reversed) ? "bottom" : "top";

        if ($tree->getHeight() > $this->column_biggest[$coordinates[1]]->getHeight()) {
            if (!$this->checkIfAlreadyInVisible($tree->getRealCoordinates())) {
                $tree->setVisible(true);
            }

            // Set viewing distance to the end of grid
            $tree->setViewingDistance($side, $coordinates[1]);

            $this->column_biggest[$coordinates[1]] = $tree;
        } else {
            $highest_in_column = $this->column_biggest[$coordinates[1]];
            $highest_in_column_coordinates = $highest_in_column->getCurrentCoordinates();

            // Set viewing distance to the highest in the column
            $tree->setViewingDistance($side, $coordinates[1] - $highest_in_column_coordinates[1]);
        }
    }

    private function addCorners()
    {
        for ($i = 0; $i < count($this->grid); $i++) {
            // Column corners
            $this->visible_trees[] = $this->grid[0][$i]->setVisible(true);
            $this->visible_trees[] = $this->grid[count($this->grid) - 1][$i]->setVisible(true);

            // Skip last and first as they were already added as column corners
            if ($i === 0 || $i === count($this->grid) - 1) {
                continue;
            }

            // Row corners
            $this->visible_trees[] = $this->grid[$i][0]->setVisible(true);
            $this->visible_trees[] = $this->grid[$i][count($this->grid) - 1]->setVisible(true);
        }
    }

    private function checkIfAlreadyInVisible(array $coordinates)
    {
        foreach ($this->visible_trees as $tree) {
            if ($coordinates[0] === $tree->getRealCoordinates()[0] && $coordinates[1] === $tree->getRealCoordinates()[1]) {
                return true;
            }
        }

        return false;
    }

    private function reverseCoordinates(array $coordinates)
    {
        return [count($this->grid) - $coordinates[0] - 1, count($this->grid[0]) - $coordinates[1] - 1];
    }
}

class Tree
{
    private array $coordinates;
    private int $height;
    private bool $visible;
    private array $real_coordinates;
    private array $viewing_distance;

    public function __construct(array $coordinates, int $height, array $real_coordinates)
    {
        $this->coordinates = $coordinates;
        $this->height = $height;
        $this->real_coordinates = $real_coordinates;
    }

    public function getCurrentCoordinates()
    {
        return $this->coordinates;
    }

    public function setCurrentCoordinates(array $coordinates)
    {
        $this->coordinates = $coordinates;
    }

    public function getRealCoordinates()
    {
        return $this->real_coordinates;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getVisible()
    {
        return $this->visible;
    }

    public function setVisible(bool $visible)
    {
        $this->visible = $visible;
        return $this;
    }

    public function setViewingDistance(string $side, int $distance)
    {
        $this->viewing_distance[$side] = $distance;
    }

    public function getViewingDistance()
    {
        return $this->viewing_distance;
    }
}
