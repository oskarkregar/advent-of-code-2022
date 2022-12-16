<?php

$grid = (new Grid(file_get_contents("data.txt")))->findVisible();
echo "THERE ARE " . count($grid->getVisible()) . " TREES VISIBLE IN A GRID" . PHP_EOL;
echo "THERE ARE " . count(array_merge(...$grid->getGrid())) . " TREES IN A GRID" . PHP_EOL;

$flattened_grid = array_merge(...$grid->getGrid());
echo "SCENIC SCORE IS: " . max(array_map(fn ($tree) => $tree->calculateScenicScore()->getScenicScore(), $flattened_grid));

class Grid
{
    private array $row_biggest;
    private array $column_biggest;
    private array $grid;
    private array $visible_trees = [];
    private bool $reversed = false;
    private string $left_right = "left";
    private string $top_bottom = "top";

    public function __construct(string $grid)
    {
        $this->readGrid($grid);
    }

    public function findVisible()
    {
        // $this->addCorners();
        $this->scanGrid();
        $this->reverseGrid();
        $this->scanGrid();

        return $this;
    }

    private function scanGrid()
    {
        $this->setStartingBiggest();

        // Create first iterator through rows and check both sides
        foreach ($this->grid as &$row) {
            foreach ($row as &$tree) {
                $current_coordinates = ($this->reversed) ? $this->reverseCoordinates($tree->getRealCoordinates()) : $tree->getRealCoordinates();
                $tree->setCurrentCoordinates($current_coordinates);

                $this->checkIfVisible($tree);
                $this->setViewingDistance($tree);

                // Add also to visible trees if it is visible
                if (count($tree->getVisibleFrom()) > 0 && !$this->checkIfAlreadyAddedToVisible($tree->getRealCoordinates())) {
                    $this->visible_trees[] = $tree;
                }
            }
        }

        return $this;
    }

    private function reverseGrid()
    {
        // Reset current coordinates to reversed
        foreach ($this->grid as $row) {
            foreach ($row as &$tree) {
                $tree->setCurrentCoordinates($this->reverseCoordinates($tree->getRealCoordinates()));
            }
        }

        // Reverse grid
        $this->grid = array_map("array_reverse", $this->grid);
        $this->grid = array_reverse($this->grid);

        // Set sides
        $this->left_right = "right";
        $this->top_bottom = "bottom";

        $this->reversed = true;
    }

    public function getGrid()
    {
        return $this->grid;
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

        // Already added as corners
        if ($coordinates[0] === 0) {
            $tree->setVisibleFrom($this->top_bottom);
        }

        if ($coordinates[1] === 0) {
            $tree->setVisibleFrom($this->left_right);
        }

        // Check if current element is visible from left and top as this is how we iterate

        // Bigger than biggest on left
        if ($tree->getHeight() > $this->row_biggest[$coordinates[0]]->getHeight()) {
            $tree->setVisibleFrom($this->left_right);

            $this->row_biggest[$coordinates[0]] = $tree;
        }

        // Bigger than biggest on top
        if ($tree->getHeight() > $this->column_biggest[$coordinates[1]]->getHeight()) {
            $tree->setVisibleFrom($this->top_bottom);

            $this->column_biggest[$coordinates[1]] = $tree;
        }
    }

    private function setViewingDistance(Tree &$tree)
    {
        if (in_array($this->left_right, $tree->getVisibleFrom())) {
            // Set viewing distance to the end of grid
            $tree->setViewingDistance($this->left_right, $tree->getCurrentCoordinates()[1]);
        } else {
            // Go back in same row to the one that we see last
            $row = $this->grid[$tree->getCurrentCoordinates()[0]];

            // Take slice to current column
            $to_current = array_slice($row, 0, $tree->getCurrentCoordinates()[1]);

            // Reverse array and go set counter of visible trees
            foreach (array_reverse($to_current) as $index => $row_tree) {
                if ($row_tree->getHeight() >= $tree->getHeight()) {
                    $tree->setViewingDistance($this->left_right, $index + 1);
                    break;
                }
            }
        }

        if (in_array($this->top_bottom, $tree->getVisibleFrom())) {
            // Set viewing distance to the end of grid
            $tree->setViewingDistance($this->top_bottom, $tree->getCurrentCoordinates()[0]);
        } else {
            // Go back in same column to the one that we see last
            $column = array_column($this->grid, $tree->getCurrentCoordinates()[1]);

            // Take slice to current row
            $to_current = array_slice($column, 0, $tree->getCurrentCoordinates()[0]);

            // Reverse array and go set counter of visible trees
            foreach (array_reverse($to_current) as $index => $column_tree) {
                if ($column_tree->getHeight() >= $tree->getHeight()) {
                    $tree->setViewingDistance($this->top_bottom, $index + 1);
                    break;
                }
            }
        }
    }

    private function checkIfAlreadyAddedToVisible(array $coordinates)
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
    private array $real_coordinates;
    private array $viewing_distance;
    private int $scenic_score;

    private array $visible_from = [];

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

    public function getVisibleFrom()
    {
        return $this->visible_from;
    }

    public function setVisibleFrom(string $side)
    {
        $this->visible_from[] = $side;
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

    public function calculateScenicScore()
    {
        $this->scenic_score = array_reduce($this->viewing_distance, fn ($carry, $distance) => $carry * $distance, 1);
        return $this;
    }

    public function getScenicScore()
    {
        return $this->scenic_score;
    }
}
