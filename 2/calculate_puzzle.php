<?php

// Set second parameter to true if we want to decode input to get our play, without we just check what are we playing
$puzzle = Puzzle::calculateScore("puzzle_input.txt", true);
echo $puzzle;

class Puzzle
{
    // A is rock, B is paper, C are scissors for opponent plays
    // X is rock, Y is paper, Z are scissors for my plays

    // version 2: X is lose, Y is draw, Z is win

    const WINNABLE_PLAYS = ["X" => "C", "Y" => "A", "Z" => "B"];
    const DRAW_PLAYS = ["X" => "A", "Y" => "B", "Z" => "C"];
    const LOSE_PLAYS = ["X" => "B", "Y" => "C", "Z" => "A"];

    public function __construct(string $opponent_play, string $my_play)
    {
        $this->opponent_play = $opponent_play;
        $this->my_play = $my_play;
        $this->score = 0;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function setCorrectPlayShape(): string
    {
        $play = $this->my_play;
        // Check if we are winning losing drawing
        switch ($this->my_play) {
            case "X":
                // We need to pick from loseable play
                $this->my_play = array_flip(self::LOSE_PLAYS)[$this->opponent_play];
                break;
            case "Y":
                // We need to pick from drawable play
                $this->my_play = array_flip(self::DRAW_PLAYS)[$this->opponent_play];
                break;
            case "Z":
                // We need to pick from winnable play
                $this->my_play = array_flip(self::WINNABLE_PLAYS)[$this->opponent_play];
                break;
        }
        if ($play !== $this->my_play) {
            echo "NOT SAME PLAY FIRST IS $play CHANGED IS $this->my_play" . PHP_EOL;
        }

        return $this->my_play;
    }

    public function addGameScore()
    {
        if (self::WINNABLE_PLAYS[$this->my_play] === $this->opponent_play) {
            $this->score += GameScore::WIN;
        } else if (self::DRAW_PLAYS[$this->my_play] === $this->opponent_play) {
            $this->score += GameScore::DRAW;
        } else {
            $this->score += GameScore::LOSE;
        }

        return $this;
    }

    public function addPlayScore()
    {
        $ref = new ReflectionClass('PlayScore');
        $this->score += $ref->getConstant($this->my_play);

        return $this;
    }

    public static function calculateScore(string $file_name, bool $with_changing_play_strategy = false)
    {
        $puzzle = file_get_contents($file_name);
        $puzzle = explode(PHP_EOL, $puzzle);
        $end_score = 0;

        foreach ($puzzle as $result) {
            $result = array_map("trim", explode(" ", $result));
            $puzzle_obj = new Puzzle($result[0], $result[1]);
            if ($with_changing_play_strategy) {
                $puzzle_obj->setCorrectPlayShape();
            }

            $end_score += $puzzle_obj->addGameScore()->addPlayScore()->getScore();
        }

        return $end_score;
    }
}

class GameScore
{
    const WIN = 6;
    const DRAW = 3;
    const LOSE = 0;
}

class PlayScore
{
    const X = 1;
    const Y = 2;
    const Z = 3;
}
