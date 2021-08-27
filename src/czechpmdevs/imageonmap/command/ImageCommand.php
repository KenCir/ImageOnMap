<?php

/**
 * ImageOnMap - Easy to use PocketMine plugin, which allows loading images on maps
 * Copyright (C) 2021 CzechPMDevs
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace czechpmdevs\imageonmap\command;

use czechpmdevs\imageonmap\ImageOnMap;
use czechpmdevs\imageonmap\item\FilledMap;
use czechpmdevs\imageonmap\utils\ImageLoader;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use function array_map;
use function array_push;
use function array_shift;
use function basename;
use function count;
use function file_exists;
use function glob;
use function implode;
use function is_numeric;
use function str_contains;
use function strtolower;

class ImageCommand extends Command implements PluginOwned {

	public function __construct() {
		parent::__construct("image", "Image on map commands", null, ["iom", "img"]);
		$this->setPermission("imageonmap.command");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) {
			return;
		}

		if(!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can be used only in game.");
			return;
		}

		if(!isset($args[0])) {
			$sender->sendMessage("§cUsage: §7/img help");
			return;
		}

		switch (strtolower(array_shift($args))):
			case "help":
				$sender->sendMessage("§2--- §fShowing ImageOnMap Commands page 1 of 1 §2---\n" .
					"§2/img help §fShows help\n" .
					"§2/img list §fShows available images\n" .
					"§2/img obtain <image> [<scale> <x> <y>] §fObtains an image");
				break;
			case "list":
				$files = [];

				$pngFiles = glob($this->getOwningPlugin()->getDataFolder() . "images/*.png");
				if($pngFiles) {
					array_push($files, ...array_map(fn(string $file) => basename($file, ".png"), $pngFiles));
				}

				$jpgFiles = glob($this->getOwningPlugin()->getDataFolder() . "images/*.jpg");
				if($jpgFiles) {
					array_push($files, ...array_map(fn(string $file) => basename($file, ".jpg"), $jpgFiles));
				}

				$files = implode(", ", $files);
				$sender->sendMessage("§aAvailable maps: $files");
				break;
			case "obtain":
			case "o":
				if(count($args) == 0) {
					$sender->sendMessage("§cUsage: §7/img o <image> [<cropSize> <x> <y>]");
					break;
				}

				$imageName = (string) array_shift($args);
				if(!str_contains($imageName, ".png") && !str_contains($imageName, ".jpg")) {
					if(file_exists($this->getOwningPlugin()->getDataFolder() . "images/$imageName.png")) {
						$imageName .= ".png";
					} elseif(file_exists($this->getOwningPlugin()->getDataFolder() . "images/$imageName.jpg")) {
						$imageName .= ".jpg";
					} else {
						$sender->sendMessage("§cImage $imageName was not found");
						break;
					}
				}

				$file = $this->getOwningPlugin()->getDataFolder() . "images/$imageName";
				if(count($args) >= 3) {
					foreach ($args as $argument) {
						if(!is_numeric($argument)) {
							$sender->sendMessage("§cOnly numbers could be used to specify crop information");
							break 2;
						}
					}

					$cropSize = (int) array_shift($args);
					$xOffset = (int) array_shift($args);
					$yOffset = (int) array_shift($args);

					if($cropSize < 1) {
						$sender->sendMessage("§cCrop size could not be lower than 0");
						break;
					}

					if($xOffset >= $cropSize || $yOffset >= $cropSize) {
						$sender->sendMessage("§cIt is not possible to create chunk of the image with crop size $cropSize at the position of $xOffset:$yOffset");
						break;
					}
				} else {
					$cropSize = 1;
					$xOffset = $yOffset = 0;
				}

				$sender->getInventory()->addItem(FilledMap::get()->setImage(ImageLoader::loadImage($file, $cropSize, $xOffset, $yOffset)));
				$sender->sendMessage("§aMap successfully created from the image.");
				break;
			endswitch;
	}

	public function getOwningPlugin(): Plugin {
		return ImageOnMap::getInstance();
	}
}