<?php

$visible = (new Grid(file_get_contents("data.txt")))->findVisible()->getVisible();
echo "THERE ARE " . count($visible) . " TREES VISIBLE IN A GRID";

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
        foreach ($this->grid as $row_index => $row) {
            foreach ($row as $column_index => $element) {
                $reversed_coordinates = ($this->reversed) ? $this->reverseCoordinates([$row_index, $column_index]) : [$row_index, $column_index];
                $tree = new Tree([$row_index, $column_index], $element, $reversed_coordinates);

                $this->checkIfVisible($tree);
                $this->trees[] = $tree;

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
        $grid_rows = explode(PHP_EOL, $grid_string);
        $this->grid = array_map("str_split", $grid_rows);
    }

    private function setStartingBiggest()
    {
        $this->row_biggest = [];
        foreach (array_column($this->grid, 0) as $column_index => $height) {
            $reversed_coordinates = ($this->reversed) ? $this->reverseCoordinates([0, $column_index]) : [0, $column_index];
            $this->row_biggest[] = new Tree([0, $column_index], $height, $reversed_coordinates);
        }

        $this->column_biggest = [];
        foreach ($this->grid[0] as $row_index => $height) {
            $reversed_coordinates = ($this->reversed) ? $this->reverseCoordinates([$row_index, 0]) : [$row_index, 0];
            $this->column_biggest[] = new Tree([$row_index, 0], $height, $reversed_coordinates);
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
        if ($tree->getHeight() > $this->row_biggest[$coordinates[0]]->getHeight()) {
            if (!$this->checkIfAlready($tree->getRealCoordinates())) {
                $tree->setVisible(true);
            }

            $this->row_biggest[$coordinates[0]] = $tree;
        }

        // Bigger than biggest on top
        if ($tree->getHeight() > $this->column_biggest[$coordinates[1]]->getHeight()) {
            if (!$this->checkIfAlready($tree->getRealCoordinates())) {
                $tree->setVisible(true);
            }

            $this->column_biggest[$coordinates[1]] = $tree;
        }
    }

    private function addCorners()
    {
        for ($i = 0; $i < count($this->grid); $i++) {
            // Column corners
            $this->visible_trees[] = (new Tree([0, $i], $this->grid[0][$i], [0, $i]))->setVisible(true);
            $this->visible_trees[] = (new Tree([count($this->grid) - 1, $i], $this->grid[count($this->grid) - 1][$i], [count($this->grid) - 1, $i]))->setVisible(true);

            // Skip last and first as they were already added as column corners
            if ($i === 0 || $i === count($this->grid) - 1) {
                continue;
            }

            // Row corners
            $this->visible_trees[] = (new Tree([$i, 0], $this->grid[$i][0], [$i, 0]))->setVisible(true);
            $this->visible_trees[] = (new Tree([0, $i], $this->grid[$i][count($this->grid) - 1], [0, $i]))->setVisible(true);
        }
    }

    private function checkIfAlready(array $coordinates)
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
}
