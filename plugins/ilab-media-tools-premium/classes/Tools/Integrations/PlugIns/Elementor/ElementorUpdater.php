<?php

// Copyright (c) 2016 Interfacelab LLC. All rights reserved.
//
// Released under the GPLv3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor;

use Elementor\Plugin;
use Elementor\Widget_Base;
use MediaCloud\Plugin\Tasks\ITaskReporter;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use function MediaCloud\Plugin\Utilities\anyEmpty;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\strEndsWith;

class ElementorUpdater {
	protected static $elementorWidgets = [];

	protected static $widgetHandlers = [];
	protected static $widgetDefs = [];

	protected static $instance = null;
	protected static $instanceClass = null;

	/** @var null|ITaskReporter  */
	protected $reporter = null;

	/** @var string|null */
	private $widgetType;

	/** @var int|null */
	private $postId;

	/** @var int|null */
	private $metaId;

	//region Constructor

	protected function __construct() {
	}

	/**
	 * The current instance
	 * @return self
	 */
	public static function instance() {
		if (!empty(static::$instance)) {
			return static::$instance;
		}

		$class = empty(static::$instanceClass) ? static::class : static::$instanceClass;
		static::$instance = new $class();

		return static::$instance;
	}

	public static function reset() {
		static::$instance = null;
		static::$widgetDefs = [];
		static::$widgetHandlers = [];
	}

	/**
	 * Set Singleton Class
	 * @param $class
	 */
	public static function setInstanceClass($class) {
		static::$instanceClass = $class;

		if (!empty(static::$instance)) {
			static::$instance = null;
		}
	}

	//endregion

	//region Handlers
	/**
	 * @param string $widget
	 * @param \Closure $callback
	 */
	public static function registerHandler(string $widget, \Closure $callback) {
		static::$widgetHandlers[$widget] = $callback;
	}

	protected static function findWidgetDefImageSize($controls, $controlName) {
		$imageSizeName = isset($controls["{$controlName}_size"]) ? "{$controlName}_size" : null;
		$imageDimensionsName = isset($controls["{$controlName}_custom_dimension"]) ? "{$controlName}_custom_dimension" : null;

		if (!empty($imageSizeName)) {
			$sizeType = arrayPath($controls, "$imageSizeName/type", null);
			if ($sizeType !== 'select') {
				$imageSizeName = null;
				$imageDimensionsName = null;
			} else {
				$options = arrayPath($controls, "$imageSizeName/options", []);
				$options = array_keys(empty($options) ? [] : $options);

				if (!empty(array_intersect(['auto', 'cover', 'contain'], $options))) {
					$imageSizeName = null;
					$imageDimensionsName = null;
				} else if (!empty(array_intersect(['small', 'regular', 'large'], $options))) {
					$imageSizeName = null;
					$imageDimensionsName = null;
				} else if (!empty(array_intersect(['h1', 'h2', 'div', 'span'], $options))) {
					$imageSizeName = null;
					$imageDimensionsName = null;
				} else if (!empty(array_intersect(['xs', 'sm', 'md', 'lg'], $options))) {
					$imageSizeName = null;
					$imageDimensionsName = null;
				}
			}
		}

		if (empty($imageSizeName)) {
			$imageSizeName = (isset($controls['thumbnail_size'])) ? 'thumbnail_size' : null;
			$imageDimensionsName = isset($controls["thumbnail_custom_dimension"]) ? "thumbnail_custom_dimension" : null;

			if (empty($imageSizeName)) {
				$imageSizeName = (isset($controls['thumbnail_image_size'])) ? 'thumbnail_image_size' : null;
				$imageDimensionsName = isset($controls["thumbnail_image_custom_dimension"]) ? "thumbnail_image_custom_dimension" : null;

				if (empty($imageSizeName)) {
					$imageSizeName = (isset($controls['image_size_size'])) ? 'image_size_size' : null;
					$imageDimensionsName = isset($controls["image_size_custom_dimension"]) ? "image_size_custom_dimension" : null;

					if (empty($imageSizeName)) {
						$imageSizeName = (isset($controls["{$controlName}_size_size"])) ? "{$controlName}_size_size" : null;
						$imageDimensionsName = isset($controls["{$controlName}_size_custom_dimension"]) ? "{$controlName}_size_custom_dimension" : null;
					}

					if (empty($imageSizeName)) {
						foreach($controls as $otherControlName => $control) {
							if (is_numeric($otherControlName)) {
								$otherControlName = $control['name'];
							}

							if (strEndsWith($otherControlName, '_size')) {
								$options = arrayPath($control, 'options', []);
								$options = array_keys(empty($options) ? [] : $options);

								$sizeType = arrayPath($control, 'type', null);
								if ($sizeType !== 'select') {
									continue;
								}

								if (empty(array_diff(['auto', 'cover', 'contain'], $options))) {
									continue;
								}

								if (empty(array_diff(['small', 'regular', 'large'], $options))) {
									continue;
								}

								if (empty(array_diff(['h1', 'h2', 'div', 'span'], $options))) {
									continue;
								}

								if (!empty(array_intersect(['xs', 'sm', 'md', 'lg'], $options))) {
									continue;
								}

								if (strpos($controlName, '_font') !== false) {
									continue;
								}

								$imageSizeName = $otherControlName;
							} else if (strEndsWith($otherControlName, '_custom_dimension'))  {
								$imageDimensionsName = $otherControlName;
							}

							if (!empty($imageSizeName) && !empty($imageDimensionsName)) {
								$someoneElse = str_replace('_size', '', $imageSizeName);
								if (($someoneElse !== $controlName) && isset($controls[$someoneElse])) {
									$imageSizeName = null;
									$imageDimensionsName = null;

									continue;
								}

								break;
							}
						}

						if (!empty($imageSizeName) && empty($imageDimensionsName)) {
							$imageDimensionsName = '__not_used';
						}
					}
				}
			}
		}

		return [
			$imageSizeName,
			$imageDimensionsName
		];
	}

	private static function getWidgetElements($controls) {
		$elements = [];

		foreach($controls as $controlName => $control) {
			if (!in_array($control['type'], ['media', 'gallery', 'repeater'])) {
				continue;
			}

			$controlInfo = [
				'name' => $controlName,
				'type' => $control['type'],
			];

			list($imageSizeName, $imageDimensionsName) = static::findWidgetDefImageSize($controls, $controlName);

			if (!empty($imageSizeName)) {
				$controlInfo['imageSize'] = $imageSizeName;
				$controlInfo['imageCustomSizeName'] = (empty($imageDimensionsName)) ? "__not_used_size" : $imageDimensionsName;
				$defaultSize =  arrayPath($controls, "$imageSizeName/default", "full");
				$controlInfo['defaultSize'] = (is_array($defaultSize) || is_numeric($defaultSize)) ? 'full' : $defaultSize;
			}

			if ($control['type'] === 'media') {
				$controlInfo['media'] = arrayPath($control, 'media_type', 'image');
				if (!in_array($controlInfo['media'], ['image', 'video', 'audio'])) {
					continue;
				}
			} else if ($control['type'] === 'repeater') {
				foreach($control['fields'] as $subControlName => $subControl) {
					if (is_numeric($subControlName)) {
						$subControlName = $subControl['name'];
					}

					if (!in_array($subControl['type'], ['media', 'gallery'])) {
						continue;
					}

					$subControlInfo = [
						'name' => $subControlName,
						'type' => $subControl['type'],
					];

					list($subImageSizeName, $subImageDimensionsName) = static::findWidgetDefImageSize($control['fields'], $subControlName);

					if (!empty($subImageSizeName)) {
						$subControlInfo['imageSize'] = $subImageSizeName;
						$subControlInfo['imageCustomSizeName'] = (empty($subImageDimensionsName)) ? "__not_used_size" : $subImageDimensionsName;
						$defaultSize =  arrayPath($control['fields'], "$subImageSizeName/default", "full");
						$subControlInfo['defaultSize'] = (is_array($defaultSize) || is_numeric($defaultSize)) ? 'full' : $defaultSize;
					}

					$controlInfo['image'] = $subControlInfo;
					break;
				}

				if (empty($controlInfo['image'])) {
					continue;
				}

				if (!empty($imageSizeName)) {
					$controlInfo['imageSize'] = $imageSizeName;
					$controlInfo['imageCustomSizeName'] = (empty($imageDimensionsName)) ? "__not_used_size" : $imageDimensionsName;
					$defaultSize = arrayPath($controls, "$imageSizeName/default", "full");
					$controlInfo['defaultSize'] = is_array($defaultSize) ? 'full' : $defaultSize;
				}
			}

			$elements[] = $controlInfo;
		}

		return $elements;
	}

	protected static function loadDefs(bool $skipCache) {
		static::$elementorWidgets = [];
		$widgets = Plugin::instance()->widgets_manager->get_widget_types();
		foreach($widgets as $widget) {
			$name = $widget->get_name();
			static::$elementorWidgets[$name] = $widget;
		}

		$notWidgets = Plugin::instance()->elements_manager->get_element_types_config();
		foreach($notWidgets as $widgetName => $widget) {
			static::$elementorWidgets[$widgetName] = $widget;
		}

		if (empty($skipCache)) {
			$cacheDir = trailingslashit(trailingslashit(WP_CONTENT_DIR).'mcloud-elementor-cache');
			if (file_exists($cacheDir.'widget-defs.json')) {
				$cachedDefString = file_get_contents($cacheDir.'widget-defs.json');
				if (!empty($cachedDefString)) {
					static::$widgetDefs = json_decode($cachedDefString, true);
				}
			}
		}
	}

	protected static function saveDefs() {
		$cacheDir = trailingslashit(trailingslashit(WP_CONTENT_DIR).'mcloud-elementor-cache');
		if (!file_exists($cacheDir)) {
			mkdir($cacheDir);
		}

		$cacheDefString = json_encode(static::$widgetDefs, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS);
		file_put_contents($cacheDir.'widget-defs.json', $cacheDefString);
	}

	protected static function getDefForWidget($widgetName) {
		if (!isset(static::$elementorWidgets[$widgetName])) {
			return null;
		}

		$widget = static::$elementorWidgets[$widgetName];
		if ($widget instanceof Widget_Base) {
			$controls = $widget->get_controls();
		} else if (is_array($widget)) {
			$controls = arrayPath($widget, 'controls', []);
		} else {
			return null;
		}

		$widgetInfo = [
			'type' => $widgetName,
			'elements' => [

			],
		];

		$els = static::getWidgetElements($controls);
		if (!empty($els)) {
			$widgetInfo['elements'] = $els;
			$widgetInfo = apply_filters("media-cloud/elementor/def/all-defs", $widgetInfo);
			$widgetInfo = apply_filters("media-cloud/elementor/def/{$widgetName}", $widgetInfo);
			static::$widgetDefs[$widgetName] = $widgetInfo;
		}

		return $widgetInfo;
	}

	protected function handleDef(string $widget, array &$element) {
		if (!isset(static::$widgetDefs[$widget])) {
			$def = static::getDefForWidget($widget);
			if (empty($def)) {
				$this->report("Unknown widget '$widget'");
				return false;
			}
		} else {
			$def = static::$widgetDefs[$widget];
		}

		if ($widget === 'premium-addon-icon-box') {
			$cool = 'shit';
		}

		foreach($def['elements'] as $control) {
			if ($control['type'] === 'media') {
				if ($control['media'] === 'image') {
					$imageSize = arrayPath($control, 'imageSize', null);
					if (!empty($imageSize)) {
						$imageCustomSizeName = arrayPath($control, 'imageCustomSizeName', '__not_used');
						$defaultSize = arrayPath($control, 'defaultSize', 'full');

						$this->processImage($element, $control['name'], $imageSize, $imageCustomSizeName, $defaultSize);
					} else {
						$this->processBackgroundImage($element, $control['name']);
					}
				} else {
					$this->processAttachment($element, $control['name']);
				}
			} else if ($control['type'] === 'gallery') {
				$imageSizeName = arrayPath($control, 'imageSize', null);
				$imageCustomSizeName = arrayPath($control, 'imageCustomSizeName', '__not_used');
				$defaultSize = arrayPath($control, 'defaultSize', 'full');

				$imageSize = empty($imageSizeName) ? $defaultSize : arrayPath($element, "settings/{$imageSizeName}", $defaultSize);
				$imageCustomSize = arrayPath($element, "settings/{$imageCustomSizeName}", null);

				$galleryName = $control['name'];

				$index = 0;
				if (!empty(arrayPath($element, "settings/$galleryName", null))) {
					foreach($element['settings'][$galleryName] as &$galleryItem) {
						$index++;
						$id = arrayPath($galleryItem, 'id', null);
						if(!empty($id)) {
							$oldUrl = arrayPath($galleryItem, 'url', null);

							$custom = '';
							if ($imageSize === 'custom') {
								$custom = " with custom size {$imageCustomSize['width']} x {$imageCustomSize['height']}";
								$url = $this->generateCustomSize($id, [$imageCustomSize['width'], $imageCustomSize['height']]);
							} else {
								$url = wp_get_attachment_image_src($id, $imageSize);
							}

							if(!empty($url)) {
								$galleryItem['url'] = $url[0];

								$this->report("Replaced $galleryName item #{$index}{$custom}", $oldUrl, $url[0]);
							} else {
								$this->report("Could not replace $galleryName item #{$index}{$custom}, missing url.", $oldUrl);
							}
						}
					}
				}
			} else if ($control['type'] === 'repeater') {
				$imageType = arrayPath($control, 'image/type', null);
				if (empty($imageType)) {
					$this->report('Missing image type for repeater');
					continue;
				}

				$imageName = arrayPath($control, 'image/name', null);
				if (empty($imageName)) {
					$this->report('Missing image name for repeater');
					continue;
				}

				$imageSizeName = arrayPath($control, 'imageSize', null);
				$imageCustomSizeName = arrayPath($control, 'imageCustomSizeName', '__not_used');
				$defaultSize = arrayPath($control, 'defaultSize', 'full');

				if (empty($imageSizeName)) {
					$imageSizeName = arrayPath($control, 'image/imageSize', '__not_used');
					$imageCustomSizeName = arrayPath($control, 'image/imageCustomSizeName', '__not_used');
					$defaultSize = arrayPath($control, 'image/defaultSize', 'full');
				}

				if ($imageType === 'media') {
					$this->processImagesContainer($element, $control['name'], $imageName, $imageSizeName, $imageCustomSizeName, $defaultSize);
				} else if ($imageType === 'gallery') {
					$imageSize = empty($imageSizeName) ? $defaultSize : arrayPath($element, "settings/{$imageSizeName}", $defaultSize);
					$imageCustomSize = arrayPath($element, "settings/{$imageCustomSizeName}", null);

					$galleryName = $control['name'];
					$galleryIndex = 0;
					if (!empty(arrayPath($element, "settings/$galleryName", null))) {
						foreach($element['settings'][$galleryName] as &$gallery) {
							$galleryIndex++;
							$index = 0;
							if (isset($gallery[$imageName])) {
								foreach($gallery[$imageName] as &$galleryItem) {
									$index++;
									$id = arrayPath($galleryItem, 'id', null);
									if (!empty($id)) {
										$oldUrl = arrayPath($galleryItem, 'url', null);

										$custom = '';
										if($imageSize === 'custom') {
											$custom = " with custom size {$imageCustomSize['width']} x {$imageCustomSize['height']}";
											$url = $this->generateCustomSize($id, [$imageCustomSize['width'], $imageCustomSize['height']]);
										} else {
											$url = wp_get_attachment_image_src($id, $imageSize);
										}

										if(!empty($url)) {
											$galleryItem['url'] = $url[0];

											$this->report("Replaced $galleryName #{$galleryIndex} item #{$index}{$custom}", $oldUrl, $url[0]);
										} else {
											$this->report("Could not replace $galleryName #{$galleryIndex} item #{$index}{$custom}, missing url.", $oldUrl);
										}
									}
								}
							}
						}
					}
				} else {
					$this->report("Unknown image type '$imageType' in repeater.");
				}
			}
		}

		return true;
	}

	protected function handle(string $widget, array &$element) {
		if (!isset(static::$widgetHandlers[$widget])) {
			return $this->handleDef($widget, $element);
		}

		$callback = static::$widgetHandlers[$widget];
		$callback($this, $element);

		return true;
	}
	//endregion

	//region Properties

	/**
	 * @return int|null
	 */
	public function postId(): ?int {
		return $this->postId;
	}

	/**
	 * @return int|null
	 */
	public function metaId(): ?int {
		return $this->metaId;
	}

	/**
	 * @return string|null
	 */
	public function widgetType(): ?string {
		return $this->widgetType;
	}

	//endregion

	//region Processing
	public function report(string $note, ?string $oldUrl = null, ?string $newUrl = null) {
		if ($this->reporter) {
			$this->reporter->add([$this->postId, $this->metaId, $this->widgetType, $note, $oldUrl, $newUrl]);
		}
	}

	public function processBackgroundImage(&$element, $backgroundImageName, $defaultImageSize = 'full') {
		$id = arrayPath($element, "settings/$backgroundImageName/id", null);
		$oldUrl = arrayPath($element, "settings/$backgroundImageName/url", null);

		if (!empty($id)) {
			$url = wp_get_attachment_image_src($id, $defaultImageSize);
			if (!empty($url)) {
				$element['settings'][$backgroundImageName]['url'] = $url[0];
				$this->report("Replaced $backgroundImageName", $oldUrl, $url[0]);
			} else {
				$this->report("Could not replace $backgroundImageName, missing url.", $oldUrl);
			}
		}
	}

	public function processAttachment(&$element, $attachmentName) {
		$id = arrayPath($element, "settings/{$attachmentName}/id", null);
		$oldUrl = arrayPath($element, "settings/{$attachmentName}/url", null);
		if (!empty($id)) {
			$url = wp_get_attachment_url($id);
			if (!empty($url)) {
				$element['settings'][$attachmentName]['url'] = $url;
				$this->report("Replaced $attachmentName", $oldUrl, $url);
			} else {
				$this->report("Could not replace $attachmentName, missing url.", $oldUrl);
			}
		}
	}

	public function processImage(&$element, $imageName, $imageSizeName, $imageCustomSizeName, $defaultImageSize) {
		$imageSize = arrayPath($element, "settings/$imageSizeName", $defaultImageSize);
		if (is_array($imageSize)) {
			$imageSize = $defaultImageSize;
		}

		$imageCustomSize = arrayPath($element, "settings/$imageCustomSizeName", null);

		$id = arrayPath($element, "settings/$imageName/id", null);
		$oldUrl = arrayPath($element, "settings/$imageName/url", null);
		if (!empty($id)) {
			$custom = '';
			if ($imageSize === 'custom') {
				$custom = " with custom size {$imageCustomSize['width']} x {$imageCustomSize['height']}";
				$url = $this->generateCustomSize($id, [$imageCustomSize['width'], $imageCustomSize['height']]);
			} else {
				$url = wp_get_attachment_image_src($id, $imageSize);
			}

			if (!empty($url)) {
				$element['settings'][$imageName]['url'] = $url[0];
				$this->report("Replaced {$imageName}{$custom}", $oldUrl, $url[0]);
			} else {
				$this->report("Could not replace {$imageName}{$custom}, missing url.", $oldUrl);
			}
		}
	}

	public function processBackgroundImagesContainer(&$element, $container, $imageName, $defaultImageSize = 'full') {
		if (is_array(arrayPath($element, "settings/$container", null))) {
			$index = 0;
			foreach($element['settings'][$container] as &$child) {
				$index++;

				$id = arrayPath($child, "$imageName/id", null);
				$oldUrl = arrayPath($child, "$imageName/url", null);
				if (!empty($id)) {
					$url = wp_get_attachment_image_src($id, $defaultImageSize);

					if (!empty($url)) {
						$child[$imageName]['url'] = $url[0];
						$this->report("Replaced $container item #{$index}", $oldUrl, $url[0]);
					} else {
						$this->report("Could not replace $container item #{$index}, missing url.", $oldUrl);
					}
				}
			}
		}
	}

	public function processImagesContainer(&$element, $container, $imageName, $imageSizeName, $imageCustomSizeName, $defaultImageSize) {
		$imageSize = arrayPath($element, "settings/$imageSizeName", $defaultImageSize);
		$imageCustomSize = arrayPath($element, "settings/$imageCustomSizeName", null);
		if (is_array(arrayPath($element, "settings/$container", null))) {
			$index = 0;
			foreach($element['settings'][$container] as &$child) {
				$index++;

				if (($imageSize === $defaultImageSize) && empty($imageCustomSize)) {
					$imageSize = arrayPath($child, "$imageSizeName", $defaultImageSize);
					$imageCustomSize = arrayPath($child, "$imageCustomSizeName", null);
				}

				if (empty($imageName)) {
					$id = arrayPath($child, 'id', null);
					$oldUrl = arrayPath($child, 'url', null);
				} else {
					$id = arrayPath($child, "$imageName/id", null);
					$oldUrl = arrayPath($child, "$imageName/url", null);
				}

				if (!empty($id)) {
					$custom = '';
					if($imageSize === 'custom') {
						$custom = " with custom size {$imageCustomSize['width']} x {$imageCustomSize['height']}";
						$url = $this->generateCustomSize($id, [$imageCustomSize['width'], $imageCustomSize['height']]);
					} else {
						$url = wp_get_attachment_image_src($id, $imageSize);
					}

					if (!empty($url)) {
						if (empty($imageName)) {
							$child['url'] = $url[0];
						} else {
							$child[$imageName]['url'] = $url[0];
						}

						$this->report("Replaced $container item #{$index}{$custom}", $oldUrl, $url[0]);
					} else {
						$this->report("Could not replace $container item #{$index}{$custom}, missing url.", $oldUrl);
					}
				}
			}
		}
	}

	//endregion

	//region Image
	public function generateCustomSize($id, $size) {
		if (is_array($size)) {
			return $this->getCustomSize($id, "custom_".$size[0].'x'.$size[1]);
		} else {
			return image_downsize($id, $size);
		}
	}

	public function getCustomSize($id, $size) {
		preg_match( '/custom_(\d*)x(\d*)/', $size, $matches );

		$sizeData = [
			$matches[1],
			$matches[2],
			true,
		];

		$meta = get_post_meta($id, '_wp_attachment_metadata', true);
		if (empty($meta)) {
			$result = image_downsize($id, $sizeData);
			if (!empty($result)) {
				return $result;
			}
		}

		$sizeInfo = arrayPath($meta, "sizes/$size", null);
		if (!empty($sizeInfo)) {
			return wp_get_attachment_image_src($id, $size);
		}

		$width = arrayPath($meta, 'width', null);
		$height = arrayPath($meta, 'height', null);
		if (anyEmpty($width, $height)) {
			return null;
		}

		$result = $this->performCrop($id, $meta, $size, $sizeData, $width, $height);
		if (!empty($result)) {
			return $result;
		}

		return null;
	}

	public function performCrop($post_id, &$meta, $sizeName, $size, $imageWidth, $imageHeight)  {
		$this->suspendOptimizer();

		$img_path = _load_image_to_edit_path( $post_id );
		$img_editor = wp_get_image_editor( $img_path );
		if (is_wp_error($img_editor)) {
			return null;
		}

		$dest_width = intval($size[0]);
		$dest_height = intval($size[1]);

		$sz = sizeToFitSize($dest_width, $dest_height, floatval($imageWidth), floatval($imageHeight));
		$crop_width = intval($sz[0]);
		$crop_height = intval($sz[1]);

		$crop_x = ($imageWidth - $crop_width) / 2;
		$crop_y = ($imageHeight - $crop_height) / 2;

		$img_editor->crop($crop_x, $crop_y, $crop_width, $crop_height, $dest_width, $dest_height, false );
		$img_editor->set_quality(StorageToolSettings::instance()->cropQuality);
		$save_path_parts = pathinfo($img_path);

		$path_url=parse_url($img_path);
		if (($path_url!==false) && (isset($path_url['scheme']))) {
			$parsed_path=pathinfo($path_url['path']);
			$img_subpath=apply_filters('media-cloud/storage/process-file-name',$parsed_path['dirname']);

			$upload_dir=wp_upload_dir();
			$save_path=$upload_dir['basedir'].$img_subpath;

			if (!file_exists($save_path)) {
				if(!mkdir($save_path, 0777, true) && !is_dir($save_path)) {
					$this->resumeOptimizer();
					return null;
				}
			}
		}
		else {
			$save_path = $save_path_parts['dirname'];
		}

		$extension = $save_path_parts['extension'];
		$filename = preg_replace('#^(IL[0-9-]*)#','',$save_path_parts['filename']);
		$filename = 'IL'.date("YmdHis").'-'.$filename.'-'.$dest_width.'x'.$dest_height.'.'.$extension;

		if (isset($meta['sizes'][$sizeName]))
		{
			$meta['sizes'][$sizeName]['file']=$filename;
			$meta['sizes'][$sizeName]['width']=$dest_width;
			$meta['sizes'][$sizeName]['height']=$dest_height;
			$meta['sizes'][$sizeName]['crop']=[
				'x'=>round($crop_x),
				'y'=>round($crop_y),
				'w'=>round($crop_width),
				'h'=>round($crop_height)
			];
		}
		else
		{
			$meta['sizes'][$sizeName] = array(
				'file' => $filename,
				'width' => $dest_width,
				'height' => $dest_height,
				'mime-type' => $meta['sizes']['thumbnail']['mime-type'],
				'crop'=> [
					'x'=>round($crop_x),
					'y'=>round($crop_y),
					'w'=>round($crop_width),
					'h'=>round($crop_height)
				]
			);
		}

		$img_editor->save($save_path . '/' . $filename);

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		if ($storageTool->enabled()) {
			// Let S3 upload the new crop
			$processedSize = $storageTool->processCrop($meta['sizes'][$sizeName], $sizeName, $save_path, $filename);
			if ($processedSize) {
				$meta['sizes'][$sizeName] = $processedSize;
			}
		}

		wp_update_attachment_metadata($post_id, $meta);

		$attrs = wp_get_attachment_image_src($post_id, $size);
		list($full_src,$full_width,$full_height,$full_cropped)=$attrs;

		if ($storageTool->enabled()) {
			$canDelete = apply_filters('media-cloud/storage/delete_uploads', true);
			if(!empty($canDelete) && StorageToolSettings::deleteOnUpload() && !StorageToolSettings::queuedDeletes()) {
				$toDelete = trailingslashit($save_path).$filename;
				if (file_exists($toDelete)) {
					if (@unlink($toDelete)) {
						Logger::info("Deleted $toDelete", [], __METHOD__, __LINE__);
					} else {
						Logger::info("Error deleting $toDelete", [], __METHOD__, __LINE__);
					}
				} else {
					Logger::info("Skipping missing file: $toDelete", [], __METHOD__, __LINE__);
				}
			}
		}

		$this->resumeOptimizer();

		return [
			$full_src,
			$full_width,
			$full_height,
			true
		];
	}

	private function suspendOptimizer() {
		add_filter('media-cloud/optimizer/can-upload', '__return_false', 10);
		add_filter('media-cloud/optimizer/no-background', '__return_true', 10);
	}

	private function resumeOptimizer() {
		remove_filter('media-cloud/optimizer/can-upload', '__return_false', 10);
		remove_filter('media-cloud/optimizer/no-background', '__return_true', 10);
	}
	//endregion

	//region Update

	/**
	 * Processes Elementor elements
	 *
	 * @param array $elements
	 *
	 * @return array
	 */
	protected function updateElements(array &$elements): array {
		foreach($elements as &$element) {
			$this->updateElement($element);
		}

		return $elements;
	}


	protected function updateElement(&$element) {
		$this->widgetType = arrayPath($element, 'widgetType', null);
		if (empty($this->widgetType)) {
			$this->widgetType = arrayPath($element, 'elType', 'common');
		}

		if (!$this->handle($this->widgetType, $element)) {
			Logger::warning("Unknown widget '{$this->widgetType}'.");
		}

		if (isset($element['elements'])) {
			$this->updateElements($element['elements']);
		}
	}


	/**
	 * Processes Elementor elements
	 *
	 * @param int|null $postId
	 * @param array $elements
	 * @param ITaskReporter|null $reporter
	 *
	 * @return array
	 */
	protected function startUpdate(?int $postId, ?int $metaId, array &$elements, ?ITaskReporter $reporter): array {
		$this->postId = $postId;
		$this->metaId = $metaId;
		$this->reporter = $reporter;

		return $this->updateElements($elements);
	}


	/**
	 * Processes Elementor page data
	 *
	 * @param bool $skipCache
	 * @param int|null $postId
	 * @param int|null $metaId
	 * @param array $elements
	 * @param ITaskReporter|null $reporter
	 *
	 * @return array
	 */
	public static function update(bool $skipCache, ?int $postId, ?int $metaId, array &$elements, ?ITaskReporter $reporter): array {
		static::loadDefs($skipCache);

		$result = static::instance()->startUpdate($postId, $metaId, $elements, $reporter);

		static::saveDefs();

		return $result;
	}

	//endregion

}