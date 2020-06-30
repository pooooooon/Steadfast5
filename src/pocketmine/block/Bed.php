<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
 */

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\level\Explosion;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Bed as TileBed;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use pocketmine\Player;


class Bed extends Transparent {
	
	const NIGHT_START = 16000;
	const NIGHT_END = 29000;
	const FULL_DAY = 30000;

	protected $id = self::BED_BLOCK;

	public function __construct($meta = 0) {
		$this->meta = $meta;
	}

	public function canBeActivated() {
		return true;
	}

	public function getHardness() {
		return 0.2;
	}

	public function getName(){ 
		return "Bed Block";
	}

	protected function recalculateBoundingBox() {
		return new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + 0.5625,
			$this->z + 1
		);
	}

	public function onActivate(Item $item, Player $player = null) {
		$dimension = $this->getLevel()->getDimension();
		if ($dimension == Level::DIMENSION_NETHER || $dimension == Level::DIMENSION_END) {
			$explosion = new Explosion($this, 6, $this);
			$explosion->explodeA();
			return true;
		}

		$time = $this->getLevel()->getTime() % self::FULL_DAY;

		$isNight = ($time >= self::NIGHT_START && $time < self::NIGHT_END);

		if ($player instanceof Player && !$isNight) {
			$player->sendMessage(TextFormat::GRAY . "You can only sleep at night");
			return true;
		}

		$blockNorth = $this->getSide(2); // Gets the blocks around them
		$blockSouth = $this->getSide(3);
		$blockEast = $this->getSide(5);
		$blockWest = $this->getSide(4);
		if (($this->meta & 0x08) === 0x08) { // This is the Top part of bed
			$b = $this;
		} else { // Bottom Part of Bed
			if ($blockNorth->getId() === $this->id && ($blockNorth->meta & 0x08) === 0x08) {
				$b = $blockNorth;
			} else if ($blockSouth->getId() === $this->id && ($blockSouth->meta & 0x08) === 0x08) {
				$b = $blockSouth;
			} else if ($blockEast->getId() === $this->id && ($blockEast->meta & 0x08) === 0x08) {
				$b = $blockEast;
			} else if ($blockWest->getId() === $this->id && ($blockWest->meta & 0x08) === 0x08) {
				$b = $blockWest;
			} else {
				if ($player instanceof Player) {
					$player->sendMessage(TextFormat::GRAY . "This bed is incomplete");
				}

				return true;
			}
		}

		if ($player instanceof Player and $player->sleepOn($b) === false) {
			$player->sendMessage(TextFormat::GRAY . "This bed is occupied");
		}

		return true;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		$down = $this->getSide(0);
		if ($down->isTransparent() === false) {
			$faces = [
				0 => 3,
				1 => 4,
				2 => 2,
				3 => 5,
			];
			$d = $player instanceof Player ? $player->getDirection() : 0;
			$next = $this->getSide($faces[(($d + 3) % 4)]);
			$downNext = $this->getSide(0);
			if ($next->canBeReplaced() === true && $downNext->isTransparent() === false) {
				$meta = (($d + 3) % 4) & 0x03;
				$this->getLevel()->setBlock($block, Block::get($this->id, $meta), true, true);
				$this->getLevel()->setBlock($next, Block::get($this->id, $meta | 0x08), true, true);

				$nbt = new Compound("", [
					new StringTag("id", Tile::BED),
					new IntTag("x", (int) $this->x),
					new IntTag("y", (int) $this->y),
					new IntTag("z", (int) $this->z),
					new ByteTag("color", $item->getDamage() & 0x0f),
					new ByteTag("isMovable", (int) 1)
				]);
				Tile::createTile("Bed", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);

				$nbtNext = new Compound("", [
					new StringTag("id", Tile::BED),
					new IntTag("x", (int) $next->x),
					new IntTag("y", (int) $next->y),
					new IntTag("z", (int) $next->z),
					new ByteTag("color", $item->getDamage() & 0x0f),
					new ByteTag("isMovable", (int) 1)
				]);
				Tile::createTile("Bed", $this->getLevel()->getChunk($next->x >> 4, $next->z >> 4), $nbtNext);

				return true;
			}
		}

		return false;
	}

	public function onBreak(Item $item) {
		$blockNorth = $this->getSide(2); // Gets the blocks around them
		$blockSouth = $this->getSide(3);
		$blockEast = $this->getSide(5);
		$blockWest = $this->getSide(4);

		if (($this->meta & 0x08) === 0x08) { // This is the Top part of bed
			if ($blockNorth->getId() === $this->id && $blockNorth->meta !== 0x08) { // Checks if the block ID and meta are right
				$this->getLevel()->setBlock($blockNorth, new Air(), true, true);
			} else if ($blockSouth->getId() === $this->id && $blockSouth->meta !== 0x08) {
				$this->getLevel()->setBlock($blockSouth, new Air(), true, true);
			} else if ($blockEast->getId() === $this->id && $blockEast->meta !== 0x08) {
				$this->getLevel()->setBlock($blockEast, new Air(), true, true);
			} else if ($blockWest->getId() === $this->id && $blockWest->meta !== 0x08) {
				$this->getLevel()->setBlock($blockWest, new Air(), true, true);
			}
		} else { // Bottom Part of Bed
			if ($blockNorth->getId() === $this->id && ($blockNorth->meta & 0x08) === 0x08) {
				$this->getLevel()->setBlock($blockNorth, new Air(), true, true);
			} else if ($blockSouth->getId() === $this->id && ($blockSouth->meta & 0x08) === 0x08) {
				$this->getLevel()->setBlock($blockSouth, new Air(), true, true);
			} else if ($blockEast->getId() === $this->id && ($blockEast->meta & 0x08) === 0x08) {
				$this->getLevel()->setBlock($blockEast, new Air(), true, true);
			} else if ($blockWest->getId() === $this->id && ($blockWest->meta & 0x08) === 0x08) {
				$this->getLevel()->setBlock($blockWest, new Air(), true, true);
			}
		}

		$this->getLevel()->setBlock($this, new Air(), true, true);

		return true;
	}

	public function getDrops(Item $item) {
		$tile = $this->getLevel()->getTile($this);
		if ($tile instanceof TileBed) {
			return [
				[Item::BED, $tile->getColor(), 1],
			];
		} else {
			return [
				[Item::BED, 14, 1], // red
			];
		}
	}

}
