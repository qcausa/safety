<?php

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\GravityForms;

use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\GravityForms\Tasks\GravityFormsMigrateTask;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\Prefixer;

class GravityFormsIntegration {
	/** @var GravityFormsIntegration|null  */
	protected $settings = null;


	public function __construct() {
		$this->settings = GravityFormSettings::instance();

		if (ToolsManager::instance()->toolEnabled('storage') && $this->settings->enabled) {
			if ($this->settings->enabled) {
				TaskManager::registerTask(GravityFormsMigrateTask::class);

				if ($this->settings->deleteFromStorage) {
					add_action('gform_delete_entry', function($entryId) {
						$this->deleteEntry($entryId);
					}, 10, 2);
				}

				add_action('gform_after_submission', function($entry, $form) {
					self::processFormSubmission($this->settings, $entry, $form);
				}, 10, 2);


				add_filter('gform_secure_file_download_url', function($url, $field) {
					return $this->secureFileDownloadUrl($url, $field);
				}, 10, 2);
			}
		}
	}

	private function deleteEntry($entryId) {
		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		global $wpdb;
		$metaTable = $wpdb->prefix.'gf_entry_meta';

		$result = $wpdb->get_row("select * from $metaTable where meta_key='s3' and entry_id='$entryId'", ARRAY_A);
		if ($result) {
			$s3Info = json_decode($result['meta_value']);
			foreach($s3Info as $key => $s3) {
				try {
					$storageTool->client()->delete($key);
				} catch(\Exception $ex) {
					Logger::error("Error deleting entry file $key, ".$ex->getMessage(), [], __METHOD__, __LINE__);
				}
			}
		}
	}

	private function secureFileDownloadUrl($url, $field) {
		try {
			/** @var StorageTool $storageTool */
			$storageTool = ToolsManager::instance()->tools['storage'];

			$uploadDir = wp_upload_dir();
			$uploadDir = $uploadDir['baseurl'];

			if (parse_url($url, PHP_URL_HOST) !== parse_url($uploadDir, PHP_URL_HOST)) {
				$key = ltrim(parse_url($url, PHP_URL_PATH), '/');
				$newUrl = $storageTool->client()->presignedUrl($key, 60 * 15);
			}

			return $newUrl;
		} catch (\Exception $ex) {
			Logger::error("Error signing url.  ".$ex->getMessage(), [], __METHOD__, __LINE__);
		}

		return $url;
	}

	private static function processFileUpload($settings, $entry, $form, $field, $url, $canDelete = true) {
		try {
			/** @var StorageTool $storageTool */
			$storageTool = ToolsManager::instance()->tools['storage'];
			$storageSettings = StorageToolSettings::instance();

			global $wpdb;
			$metaTable = $wpdb->prefix.'gf_entry_meta';

			$hash = \GF_Field_FileUpload::get_file_upload_path_meta_key_hash($url);
			$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $metaTable WHERE meta_key = %s and entry_id = %d and form_id = %d", $hash, $entry['id'], $form['id']), ARRAY_A);
			if (!empty($result)) {
				$meta = maybe_unserialize($result['meta_value']);
				if (!empty($meta)) {
					$filename = trailingslashit($meta['path']).$meta['file_name'];
					if (file_exists($filename)) {
						$key = trailingslashit(ltrim(parse_url($meta['url'], PHP_URL_PATH), '/')).$meta['file_name'];
						if ($settings->randomFilename) {
							$uniquePath = Prefixer::genUUIDPath();
							$ext = pathinfo($filename, PATHINFO_EXTENSION);
							$key = ltrim(trailingslashit($uniquePath), '/').Prefixer::genUUID().'.'.$ext;
						}

						$newUrl = $storageTool->client()->upload($key, $filename, $settings->acl);
						$meta['url'] = $newUrl;

						$wpdb->update($metaTable, ['meta_value' => maybe_serialize($meta)], ['id' => $result['id']]);

						if ($canDelete && $settings->deleteUploads) {
							@unlink($filename);
						}

						return $newUrl;
					}
				}
			}
		} catch (\Exception $ex) {
			Logger::error("Error uploading $url.  ".$ex->getMessage(), [], __METHOD__, __LINE__);
		}

		return $url;
	}

	public static function processFormSubmission($settings, $entry, $form, $canDelete = true) {
		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		global $wpdb;
		$metaTable = $wpdb->prefix.'gf_entry_meta';

		$s3Info = [];

		foreach($form['fields'] as $field) {
			if ($field instanceof \GF_Field_FileUpload) {
				if (!empty($field['multipleFiles'])) {
					$files = json_decode($entry[$field['id']]);

					if (!empty($files)) {
						$changed = false;
						for($i = 0; $i < count($files); $i++) {
							$file = $files[$i];
							$newUrl = self::processFileUpload($settings, $entry, $form, $field, $file, $canDelete);
							if (!empty($newUrl)) {
								$key = ltrim(parse_url($newUrl, PHP_URL_PATH), '/');
								$s3Info[$key] = [
									'url' => $newUrl,
									'bucket' => $storageTool->client()->bucket(),
									'privacy' => $settings->acl,
									'key' => $key,
									'provider' =>  StorageToolSettings::driver(),
									'v' => MEDIA_CLOUD_INFO_VERSION,
									'optimized' => false,
									'options' => [],
									'formats' => [],
								];

								$changed = true;
								$files[$i] = $newUrl;
							}
						}

						if ($changed) {
							$wpdb->update($metaTable, ['meta_value' => json_encode($files)], [
								'form_id' => $form['id'],
								'entry_id' => $entry['id'],
								'meta_key' => $field['id']
							]);
						}
					}
				} else {
					$file = $entry[$field['id']];
					$newUrl = self::processFileUpload($settings, $entry, $form, $field, $file, $canDelete);
					if (!empty($newUrl)) {
						$key = ltrim(parse_url($newUrl, PHP_URL_PATH), '/');
						$s3Info[$key] = [
							'url' => $newUrl,
							'bucket' => $storageTool->client()->bucket(),
							'privacy' => $settings->acl,
							'key' => $key,
							'provider' =>  StorageToolSettings::driver(),
							'v' => MEDIA_CLOUD_INFO_VERSION,
							'optimized' => false,
							'options' => [],
							'formats' => [],
						];

						$wpdb->update($metaTable, ['meta_value' => $newUrl ], [
							'form_id' => $form['id'],
							'entry_id' => $entry['id'],
							'meta_key' => $field['id']
						]);
					}
				}
			}
		}

		if (!empty($s3Info)) {
			$wpdb->insert($metaTable, [
				'form_id' => $form['id'],
				'entry_id' => $entry['id'],
				'meta_key' => 's3',
				'meta_value' => json_encode($s3Info),
			]);
		}
	}

}