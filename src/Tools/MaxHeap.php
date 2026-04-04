<?php

namespace Fuse\Tools;

// Max-heap by score: keeps the worst (highest) score at the top
// so we can efficiently evict it when a better result arrives.
class MaxHeap
{
    private int $limit;
    private array $heap = [];

    public function __construct(int $limit)
    {
        $this->limit = $limit;
    }
    
    public function getSize()
    {
        return sizeof($this->heap);
    }

    public function shouldInsert(float $score): bool
    {
        return $this->getSize() < $this->limit || $score < $this->heap[0]['score'];
    }

    public function insert(array $item)
    {
        if ($this->getSize() < $this->limit) {
        $this->heap[] = $item;
        $this->bubbleUp($this->getSize() - 1);
        } else if ($item['score'] < $this->heap[0]['score']) {
        $this->heap[0] = $item;
        $this->sinkDown(0);
        }
    }
    
    public function extractSorted(callable $sortFn)
    {
        usort($this->heap, $sortFn);
        return $this->heap;
    }

    private function bubbleUp(int $i): void
    {
        while ($i > 0) {
            $parent = ($i - 1) >> 1;

            if ($this->heap[$i]['score'] <= $this->heap[$parent]['score']) break;

            $tmp = $this->heap[$i];
            $this->heap[$i] = $this->heap[$parent];
            $this->heap[$parent] = $tmp;
            $i = $parent;
        }
    }

    private function sinkDown(int $i): void
    {
        $len = $this->getSize();
        $largest = $i;

        do {
            $i = $largest;
            $left = 2 * $i + 1;
            $right = 2 * $i + 2;
            
            if ($left < $len && $this->heap[$left]['score'] > $this->heap[$largest]['score']) {
                $largest = $left;
            }

            if ($right < $len && $this->heap[$right]['score'] > $this->heap[$largest]['score']) {
                $largest = $right;
            }

            if ($largest !== $i) {
                $tmp = $this->heap[$i];
                $this->heap[$i] = $this->heap[$largest];
                $this->heap[$largest] = $tmp;
            }
        } while ($largest !== $i);
    }
}
