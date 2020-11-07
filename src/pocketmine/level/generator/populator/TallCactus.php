<?php

namespace pocketmine\level\generator\populator;

use pocketmine\block\Block;
use pocketmine\level\ChunkManager;
use pocketmine\utils\Random;

class TallCactus extends VariableAmountPopulator {

	private $level;

	public function populate(ChunkManager $level, $chunkX, $chunkZ, Random $random) {
		$this->level = $level;
		$amount = $this->getAmount($random);
		for ($i = 0; $i < $amount; ++$i) {
			$x = $random->nextRange($chunkX * 16, $chunkX * 16 + 15);
			$z = $random->nextRange($chunkZ * 16, $chunkZ * 16 + 15);
			$y = $this->getHighestWorkableBlock($x, $z);
			if ($y !== -1 && $this->canTallCactusStay($x, $y, $z)) {
				$this->level->setBlockIdAt($x, $y, $z, Block::CACTUS);
				$this->level->setBlockDataAt($x, $y, $z, 1);
			}
		}
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

	private function canTallCactusStay($x, $y, $z) {
		$b = $this->level->getBlockIdAt($x, $y, $z);
		return ($b === Block::AIR) && $this->level->getBlockIdAt($x, $y - 1, $z) === Block::CACTUS;
	}

}