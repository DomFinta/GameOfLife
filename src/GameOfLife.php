<?php

declare(strict_types=1);

namespace src;

require_once dirname(__DIR__) . '/vendor/autoload.php';
final class GameOfLife
{
    private array $organisms;
    private int $cells;
    private int $iterations;

    private string $filePath;

    private const LIMIT_DIE_ISOLATION = 2;
    private const LIMIT_DIE_OVERCROWDING = 3;

    private const VECTORS = [
        [1, 0],     // Right
        [0, 1],     // Down
        [-1, 0],    // Left
        [0, -1],    // Up
        [1, 1],     // Right down
        [-1, 1],    // Left down
        [1, -1],    // Right Up
        [-1, -1]    // Left Up
    ];

    public function __construct()
    {
        $this->loadData();
        $this->live();
    }

    public function live(): void
    {
        if (empty($this->organisms))
        {
            return;
        }

        $x = $y = $step = 1;
        while ($step <= $this->iterations)
        {
            $current = $this->organisms[$x][$y] ?? null;

            $surroundingTypes = [];
            $surroundingTrio = [];

            // check all surrounding positions
            foreach (self::VECTORS as $vector)
            {
                $newX = $x + $vector[0];
                $newY = $y + $vector[1];

                $neighbor = $this->organisms[$newX][$newY] ?? null;

                if ($neighbor) // new element isn't empty (wasn't set in xml file)
                {
                    if (isset($surroundingTypes[$neighbor])) // already encountered
                    {
                        $surroundingTypes[$neighbor]++;

                        if (!$current) // if current element is empty look for trios to multiply
                        {
                            if ($surroundingTypes[$neighbor] === 3)
                            {
                                $surroundingTrio[$neighbor] = $neighbor;

                            } else if ($surroundingTypes[$neighbor] > 3)
                            {
                                unset($surroundingTrio[$neighbor]);
                            }
                        }

                    } else
                    {
                        $surroundingTypes[$neighbor] = 1;
                    }
                }
            }

            if (isset($current)) // there is an organism
            {

                $this->organisms[$x][$y] = $surroundingTypes[$current] < self::LIMIT_DIE_ISOLATION
                    || $surroundingTypes[$current] > self::LIMIT_DIE_OVERCROWDING ? null : $this->organisms[$x][$y];

            } else // empty element
            {
                if (count($surroundingTrio) === 2)
                {
                    $this->organisms[$x][$y] =  array_keys($surroundingTrio)[rand(0, 1)];
                } else if (count($surroundingTrio) === 1)
                {
                    $this->organisms[$x][$y] =  array_keys($surroundingTrio)[0];
                }
            }

            $step++;
            $x++;
            if ($x > $this->cells)
            {
                $x = 1;
                $y++;
            }
        }
    }

    private function loadData(): void
    {
        try
        {
            if (!$this->filePath)
            {
                throw new \Exception('No path provided, use ?path= parameter.');
            }

            if (!file_exists($this->filePath))
            {
                throw new \Exception('XML file not found.');
            }

            if (
                !str_contains($this->filePath, '.xml')
                && !str_starts_with(file_get_contents($this->filePath), '<?xml')
            ) {
                throw new \Exception('Provided file is not a XML');
            }
            $fileData = simplexml_load_file($this->filePath);

            $this->handleXmlFileData($fileData);


        } catch (\Exception $e)
        {
            echo "There was a problem with a file (\"" . $this->filePath . "\"): " . $e->getMessage();;
        }
    }

    private function createOrganismArray(\SimpleXMLElement $fileData): void
    {
        try
        {
            foreach ($fileData->organisms->children() as $organism)
            {
                if (!isset(
                    $organism->x_pos,
                    $organism->y_pos,
                    $organism->species
                )) {

                    throw new \Exception('Organism must have set x_pos, y_pos, type values');

                } else
                {
                    if (
                        (int) $organism->x_pos < 1
                        || (int) $organism->y_pos < 1
                    ) {
                        throw new \Exception('Organism position must be 1 or more');
                    } else
                    {
                        $this->organisms[(int)$organism->x_pos][(int)$organism->y_pos] = (string)$organism->species;
                    }

                }
            }
        } catch (\Exception $e)
        {
            echo $e->getMessage();
        }
    }

    /**
     * Check if xml file has all needed data
     */
    private function handleXmlFileData(\SimpleXMLElement $fileData): void
    {
        try
        {
            if (
                !isset(
                    $fileData->world,
                    $fileData->world->cells,
                    $fileData->world->species,
                    $fileData->world->iterations,
                    $fileData->organisms
                )
                || !$fileData->organisms->children()
            ) {

                throw new \Exception('Not all needed values are provided');

            } else
            {
                $this->createOrganismArray($fileData);

                $this->cells = (int) $fileData->world->cells;
                $this->iterations = (int) $fileData->world->iterations;
            }

        } catch (\Exception $e)
        {
            echo $e->getMessage();
        }
    }

    public function transformToJson(): array
    {
        if (empty($this->organisms))
        {
            return [];
        }

        $species = [];
        $organismResult = [];
        foreach ($this->organisms as $x => $organismRow)
        {
            foreach ($organismRow as $y => $organism)
            {
                if (!isset($species[$organism])) // count species
                {
                    $species[] = $organism;
                }

                $organismResult[] = [
                    "organism" => [
                        "x_pos" => $x,
                        "y_pos" => $y,
                        "species" => $organism
                    ]
                ];
            }
        }

        return [
            "world" => [
                "cells" => $this->cells,
                "species" => count($species),
                "iterations" => $this->iterations
            ],
            "organisms" => $organismResult
        ];
    }

    public function print(): void
    {
        print_r(json_encode($this->transformToJson()));
    }

    /**
     * For tests
     */
    public function getOrganisms(): array
    {
        return $this->organisms;
    }

    public function setPath(string $path): void
    {
        $this->filePath = $path;
    }

    public function setCells(int $cells): void
    {
        $this->cells = $cells;
    }

    public function setIterations(int $iterations): void
    {
        $this->iterations = $iterations;
    }

    public function setOrganisms(array $organisms): void
    {
        $this->organisms = $organisms;
    }
}






