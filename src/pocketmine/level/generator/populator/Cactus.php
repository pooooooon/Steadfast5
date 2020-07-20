<?php

namespace pocketmine\level\generator\populator;

use pocketmine\block\Block;
use pocketmine\level\ChunkManager;
use pocketmine\utils\Random;

class Cactus extends Populator {

	/** @var ChunkManager */
	private $level;
	private $randomAmount;
	private $baseAmount;

	public function setRandomAmount($amount) {
		$this->randomAmount = $amount;
	}

	public function setBaseAmount($amount) {
		$this->baseAmount = $amount;
	}

	public function populate(ChunkManager $level, $chunkX, $chunkZ, Random $random) {
		$this->level = $level;
		$amount = $random->nextRange(0, $this->randomAmount + 1) + $this->baseAmount;
		for ($i = 0; $i < $amount; ++$i) {
			$x = $random->nextRange($chunkX * 16, $chunkX * 16 + 15);
			$z = $random->nextRange($chunkZ * 16, $chunkZ * 16 + 15);
			$y = $this->getHighestWorkableBlock($x, $z);
			$tallRand = $random->nextRange(0, 17);
			$yMax = $y + 1 + (int) ($tallRand > 10) + (int) ($tallRand > 15);

			if ($y !== -1) {
				for (; $y < 127 && $y < $yMax; $y++) {
					if ($this->canCactusStay($x, $y, $z)) {
						$this->level->setBlockIdAt($x, $y, $z, Block::CACTUS);
						$this->level->setBlockDataAt($x, $y, $z, 1);
					}
				}
			}
		}
	}

	private function canCactusStay($x, $y, $z) {
		$b = $this->level->getBlockIdAt($x, $y, $z);
		$below = $this->level->getBlockIdAt($x, $y - 1, $z);
		foreach (array($this->level->getBlockIdAt($x + 1, $y, $z), $this->level->getBlockIdAt($x - 1, $y, $z), $this->level->getBlockIdAt($x, $y, $z + 1), $this->level->getBlockIdAt($x, $y, $z - 1)) as $adjacent) {
			if ($adjacent !== Block::AIR) {
				return false;
			}
		}
		return ($b === Block::AIR) && ($below === Block::SAND || $below === Block::CACTUS);
	}

	private function getHighestWorkableBlock($x, $z) {
		for ($y = 127; $y >= 0; --$y) {
			$b = $this->level->getBlockIdAt($x, $y, $z);
			if ($b !== Block::AIR && $b !== Block::LEAVES && $b !== Block::LEAVES2 && $b !== Block::SNOW_LAYER) {
				break;
			}
		}
		return $y === 0 ? -1 : ++$y;
	}

}