<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\crates;

use pocketmine\item\Item;

final class Crate {
    /** @var array<int, Reward[]> weight ⇒ rewards[] */
    private array $buckets;
    /** @var int[] cumulative weights */
    private array $cumulative = [];
    /** @var Reward[][] parallel to $cumulative */
    private array $byCumulative = [];

    public function __construct(array $buckets) {       
        $this->buckets = $buckets;

        $sum = 0;
        foreach ($buckets as $bucket) {
            $weight = $bucket["chance"];
            $sum   += $weight;
            $this->cumulative[]   = $sum;
            $this->byCumulative[] = $bucket["rewards"];
        }
    }

    public function rollItem(): Item {
        $max = end($this->cumulative);
        $r   = mt_rand(1, $max);

        $idx = \array_search(true, array_map(fn($cw) => $r <= $cw, $this->cumulative), true);
        if ($idx === false) {
            $idx = count($this->cumulative) - 1;
        }

        /** @var Reward[] $entries */
        $entries = $this->byCumulative[$idx];
        /** @var Reward $reward */
        $reward  = $entries[array_rand($entries)];
        return $reward->roll();
    }

    /**
     * @return CrateReward[]  a flat list of all rewards in this crate,
     *                        in the same order as your bucket definitions
     */
    public function getAllRewards(): array {
        $out = [];
        foreach ($this->byCumulative as $bucketRewards) {
            foreach ($bucketRewards as $reward) {
                $out[] = $reward;
            }
        }
        return $out;
    }

    /**
     * Returns a flat list of [ reward => CrateReward, chance => int ]
     * in the same order as your bucket definitions.
     *
     * @return array<int,array{reward:CrateReward,chance:int}>
     */
    public function getRewardsWithChances(): array {
        $out = [];
        foreach ($this->buckets as $bucket) {
            foreach ($bucket["rewards"] as $reward) {
                $out[] = ["reward" => $reward, "chance" => $bucket["chance"]];
            }
        }
        return $out;
    }

}
