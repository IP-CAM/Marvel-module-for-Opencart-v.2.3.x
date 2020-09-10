<?php
class ControllerExtensionModuleMarvel extends Controller {
	public function cron() {
		$this->load->language('extension/module/marvel');

		$result = $this->import();
		
		if (gettype($result) == 'string') {
			$this->log->write($result);
			echo $result . "\n";
		} else {
			$this->log->write(sprintf($this->language->get('text_success_import'), $result['new_products'], $result['new_categories']));
			printf($this->language->get('text_success_import') . "\n", $result['new_products']);
		}
	}

	public function import() {
		$this->db->query("SET wait_timeout = 3000000");
		
		$this->load->library('marvel');

		if ($this->config->get('marvel_caching')) {
			$response = $this->cache->get('marvel.stock');
		} else {
			$response = [];
		}

		if (empty($response)) {
			$response = $this->marvel->GetFullStock($this->config->get('marvel_username'), $this->config->get('marvel_password'), true, '');
			if (gettype($response) == 'string') {
				return $response;
			}
			if ($this->config->get('marvel_caching')) {
				$this->cache->set('marvel.stock', $response);
			}
		}

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/attribute');
		$this->load->model('catalog/marvel');

		$this->load->model('setting/store');
		$stores = $this->model_setting_store->getStores();
		$stores[] = ['store_id' => 0, 'name' => $this->language->get('text_default')];

		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();

		// Get stock data
		if ($this->config->get('marvel_remote_category')) {
			$categories = $this->config->get('marvel_remote_category');
			$stockItems = array_filter($response, function($item) use ($categories) { return in_array($item['CategoryId'], $categories); });
		} else {
			$stockItems = $response;
		}

		// /**/
		// $stockItems = array_slice($stockItems, 0, 1);
		// /**/

		// Create item list from stock data
		$itemsList = ['WareItem' => []];
		foreach($stockItems as $stockItem) {
			$itemsList['WareItem'][] = ['ItemId' => $stockItem['WareArticle']];
		}

		// Get items descriptions
		$items = $this->marvel->GetItems($this->config->get('marvel_username'), $this->config->get('marvel_password'), $itemsList);
		if (gettype($items) == 'string') {
			return $items;
		}

		// Load items
		$result = ['new_products' => 0];

		foreach($items as $item) {
			$alias = $this->model_catalog_marvel->getAlias($item['WareArticle']);

			// Manufacturer
			$manufacturer = $this->model_catalog_marvel->getManufacturerByName($item['WareVendor']);

			if (empty($manufacturer)) {
				if (empty($manufacturer)) {
					foreach($languages as $language) {
						$manufacturer['manufacturer_description'][$language['language_id']] = ['name' => $item['WareVendor'], 'meta_h1' => $item['WareVendor'], 'meta_title' => $item['WareVendor'], 'meta_description' => $item['WareVendor'], 'meta_keyword' => '', 'description' => $item['WareVendor'], 'tag' => ''];
					}
				}
				$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
			} else {
				$manufacturer_id = $manufacturer['manufacturer_id'];
			}

			// Product
			$product = $this->model_catalog_marvel->getProductByModel($item['WareArticle']);


			if (!empty($product['product_id'])) {
				$attributes = $this->model_catalog_product->getProductAttributes($product['product_id']);
				$descriptions = $this->model_catalog_product->getProductDescriptions($product['product_id']);

				$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product['product_id']);
				$product['product_filter'] = $this->model_catalog_product->getProductFilters($product['product_id']);
				$product['product_image'] = $this->model_catalog_product->getProductImages($product['product_id']);
				$product['product_option'] = $this->model_catalog_product->getProductOptions($product['product_id']);
				$product['product_related'] = $this->model_catalog_product->getProductRelated($product['product_id']);
				$product['product_reward'] = $this->model_catalog_product->getProductRewards($product['product_id']);
				$product['product_special'] = $this->model_catalog_product->getProductSpecials($product['product_id']);
				$product['product_category'] = $this->model_catalog_product->getProductCategories($product['product_id']);
				$product['product_download'] = $this->model_catalog_product->getProductDownloads($product['product_id']);
				$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product['product_id']);
				$product['product_store'] = $this->model_catalog_product->getProductStores($product['product_id']);
				$product['product_recurrings'] = $this->model_catalog_product->getRecurrings($product['product_id']);
			}

			// Attributes
			if ($this->config->get('marvel_import_attributes')) {
				if (!empty($item['ExtendedInfo']['Parameter'])) {
					foreach($item['ExtendedInfo']['Parameter'] as $parameter) {
						$attribute = $this->model_catalog_marvel->getAttributeByName($this->config->get('marvel_attribute_group_id'), $parameter['ParameterName']);
						if (empty($attribute)) {
							foreach($languages as $language) {
								$attribute['attribute_description'][$language['language_id']] = ['name' => $parameter['ParameterName'], 'attribute_group_id' => $this->config->get('marvel_attribute_group_id')];
							}
							$attribute_id = $this->model_catalog_attribute->addAttribute($attribute);
						} else {
							$attribute_id = $attribute['attribute_id'];
						}

						$product_attribute = ['attribute_id' => $attribute_id];
						foreach($languages as $language) {
							$product_attribute['product_attribute_description'][$language['language_id']] = ['text' => $parameter['ParameterValue']];
						}

						$attributes[] = $product_attribute;
					}
				}
			}

			// Descriptions
			if (!empty($item['ExtendedInfo']['ItemDesc'][0]['ItemDescContents'])) {
				$description = $item['ExtendedInfo']['ItemDesc'][0]['ItemDescContents'];
			} else {
				$description = '';
			}

			foreach($languages as $language) {
				$descriptions[$language['language_id']] = ['name' => empty($produce['product_description'][$language['language_id']]['name']) ? $item['WareFullName'] : $produce['product_description'][$language['language_id']]['name'], 'meta_h1' => empty($produce['product_description'][$language['language_id']]['meta_h1']) ? $item['WareFullName'] : $produce['product_description'][$language['language_id']]['meta_h1'], 'meta_title' => empty($produce['product_description'][$language['language_id']]['meta_title']) ? $item['WareFullName'] : $produce['product_description'][$language['language_id']]['meta_title'], 'meta_description' => empty($produce['product_description'][$language['language_id']]['meta_description']) ? $description : $produce['product_description'][$language['language_id']]['meta_description'], 'meta_keyword' => '', 'description' => empty($produce['product_description'][$language['language_id']]['description']) ? $description : $produce['product_description'][$language['language_id']]['description'], 'tag' => ''];
			}

			if (!empty($item['WarePrice' . strtoupper($this->config->get('config_currency'))])) {
				$price = $item['WarePrice' . strtoupper($this->config->get('config_currency'))];
			} else {
				$price = $item['WarePriceUSD'];
			}

			$product['model'] = $item['WareArticle'];
			$product['sku'] = $item['WareArticle'];
			$product['upc'] = !empty($item['EANUPC']['EANUPCCode']) ? $item['EANUPC']['EANUPCCode'][0] : '';
			$product['ean'] = !empty($item['EANUPC']['EANUPCCode']) ? $item['EANUPC']['EANUPCCode'][0] : '';
			$product['jan'] = '';
			$product['isbn'] = '';
			$product['mpn'] = '';
			$product['location'] = '';
			$product['price'] = isset($product['price']) ? $product['price'] : 0;
			$product['price_zak'] = $price;
			$product['tax_class_id'] = '0';
			$product['quantity'] = !empty($item['TotalInventQty']) ? $item['TotalInventQty'] : 0;
			$product['minimum'] = 1;
			$product['subtract'] = 1;
			$product['stock_status_id'] = (!isset($item['TotalInventQty']) || $item['TotalInventQty'] === 0) ? 5 : 7;
			$product['shipping'] = 1;
			$product['date_available'] = date('Y-m-d');
			$product['length'] = '';
			$product['width'] = $item['Width'];
			$product['height'] = $item['Height'];
			$product['length_class_id'] = 1;
			$product['weight'] = $item['Weight'];
			$product['weight_class_id'] = 1;
			$product['status'] = (int)$this->config->get('marvel_status_imported');
			$product['sort_order'] = 1;
			$product['manufacturer'] = '';
			$product['manufacturer_id'] = $manufacturer_id;
			$product['category'] = '';
			$product['product_category'] = [$this->config->get('marvel_local_category_id')];
			$product['main_category_id'] = $this->config->get('marvel_local_category_id');
			$product['filter'] = '';
			$product['product_store'] = [0];
			$product['download'] = '';
			$product['related'] = '';
			$product['product_related_article_input'] = '';
			$product['option'] = '';
			$product['points'] = '';
			$product['product_reward'] = [];
			$product['product_attribute'] = $attributes;
			$product['product_description'] = $descriptions;

			foreach($stores as $store) {
				foreach($languages as $language) {
					$product['product_seo_url'][$store['store_id']][$language['language_id']] = ($store['store_id'] ? $store['store_id'] . '-' : '') . (count($languages) > 0 ? $language['language_id'] . '-' : '') . $alias;
				}
			}

			$product['noindex'] = 1;
			$product['product_layout'] = [''];
			$product['image'] = ($product['image'] ? $product['image'] : ($product['product_image'] ? $product['product_image'][0]['image'] : ''));

			if (empty($product['product_id'])) {
				$this->model_catalog_product->addProduct($product);
				$result['new_products']++;
			} else {
				$this->model_catalog_product->editProduct($product['product_id'], $product);
			}
		}

		// Load images
		$photos = [];

		$itemPhotos = $this->marvel->GetItemPhotos($this->config->get('marvel_username'), $this->config->get('marvel_password'), $itemsList);
		if (gettype($itemPhotos) == 'string') {
			return $itemPhotos;
		}

		// Combine  photo list
		foreach($itemPhotos as $itemPhoto) {
			if (isset($itemPhoto['BigImage'])) {
				if (empty($photos[$itemPhoto['BigImage']['WareArticle']]) || count($photos[$itemPhoto['BigImage']['WareArticle']]) < (int)$this->config->get('marvel_image_count')) {
					$photos[$itemPhoto['BigImage']['WareArticle']][] = $itemPhoto['BigImage']['URL'];
				}
			}
		}

		// Set product images
		foreach($photos as $model => $urls) {
			$product = $this->model_catalog_marvel->getProductByModel($model);
			if ($product) {
				$product_images = [];

				foreach($urls as $url) {
					$filename = DIR_IMAGE . 'catalog/' . $model . '-' . basename($url);

					if (!is_file($filename)) {
						$response = file_get_contents($url);
						file_put_contents($filename, $response);
					}

					$product_images[] = str_replace(DIR_IMAGE, '', $filename);
				}

				$this->model_catalog_marvel->setProductImages($product['product_id'], $product_images);
			}
		}

		// Set updated time
		$this->model_catalog_marvel->setSettingValue('marvel', 'marvel_updated', time());

		return $result;
	}

	public function index() {
		$this->load->language('extension/module/marvel');

		$this->load->model('catalog/attribute_group');
		$this->load->model('setting/setting');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('marvel', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module/marvel', 'token=' . $this->session->data['token'], true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_none'] = $this->language->get('text_none');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_status_imported'] = $this->language->get('entry_status_imported');
		$data['entry_username'] = $this->language->get('entry_username');
		$data['entry_password'] = $this->language->get('entry_password');
		$data['entry_local_category'] = $this->language->get('entry_local_category');
		$data['entry_remote_category'] = $this->language->get('entry_remote_category');
		$data['entry_import_attributes'] = $this->language->get('entry_import_attributes');
		$data['entry_attribute_group'] = $this->language->get('entry_attribute_group');
		$data['entry_image_count'] = $this->language->get('entry_image_count');
		$data['entry_caching'] = $this->language->get('entry_caching');
		$data['entry_logs'] = $this->language->get('entry_logs');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_auth'] = $this->language->get('button_auth');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->session->data['warning'])) {
			$data['warning'] = $this->session->data['warning'];

			unset($this->session->data['warning']);
		} else {
			$data['warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/marvel', 'token=' . $this->session->data['token'], true)
		);

		$data['action'] = $this->url->link('extension/module/marvel', 'token=' . $this->session->data['token'], true);
		$data['auth'] = $this->url->link('extension/module/marvel/auth', 'token=' . $this->session->data['token'], true);
		$data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=module', true);

		if ($this->config->get('marvel_username') && $this->config->get('marvel_password')) {
			$data['ready_to_auth'] = true;
		} else {
			$data['ready_to_auth'] = false;
		}

		if (isset($this->request->post['marvel_status'])) {
			$data['marvel_status'] = $this->request->post['marvel_status'];
		} else {
			$data['marvel_status'] = $this->config->get('marvel_status');
		}

		if (isset($this->request->post['marvel_username'])) {
			$data['marvel_username'] = $this->request->post['marvel_username'];
		} else {
			$data['marvel_username'] = $this->config->get('marvel_username');
		}

		if (isset($this->request->post['marvel_password'])) {
			$data['marvel_password'] = $this->request->post['marvel_password'];
		} else {
			$data['marvel_password'] = $this->config->get('marvel_password');
		}

		if (isset($this->request->post['marvel_status_imported'])) {
			$data['marvel_status_imported'] = $this->request->post['marvel_status_imported'];
		} else {
			$data['marvel_status_imported'] = $this->config->get('marvel_status_imported');
		}

		if (isset($this->request->post['marvel_local_category_id'])) {
			$data['marvel_local_category_id'] = $this->request->post['marvel_local_category_id'];
		} else {
			$data['marvel_local_category_id'] = $this->config->get('marvel_local_category_id');
		}

		if (isset($this->request->post['marvel_remote_category'])) {
			$data['marvel_remote_category'] = $this->request->post['marvel_remote_category'];
		} else {
			$data['marvel_remote_category'] = (array)$this->config->get('marvel_remote_category');
		}

		if (isset($this->request->post['marvel_import_attributes'])) {
			$data['marvel_import_attributes'] = $this->request->post['marvel_import_attributes'];
		} else {
			$data['marvel_import_attributes'] = $this->config->get('marvel_import_attributes');
		}

		if (isset($this->request->post['marvel_attribute_group_id'])) {
			$data['marvel_attribute_group_id'] = $this->request->post['marvel_attribute_group_id'];
		} else {
			$data['marvel_attribute_group_id'] = $this->config->get('marvel_attribute_group_id');
		}

		if (isset($this->request->post['marvel_image_count'])) {
			$data['marvel_image_count'] = (int)$this->request->post['marvel_image_count'];
		} else {
			$data['marvel_image_count'] = (int)$this->config->get('marvel_image_count');
		}

		if (isset($this->request->post['marvel_caching'])) {
			$data['marvel_caching'] = $this->request->post['marvel_caching'];
		} else {
			$data['marvel_caching'] = $this->config->get('marvel_caching');
		}

		// Local category list
		$this->load->model('catalog/category');
		$data['local_category_list'] = $this->model_catalog_category->getCategories([]);

		// Remote category list
		if ($this->config->get('marvel_caching')) {
			$categories = $this->cache->get('marvel.categories');
		} else {
			$categories = [];
		}

		if (empty($categories)) {
			$this->load->library('marvel');
			$categories = $this->marvel->getCategories($this->config->get('marvel_username'), $this->config->get('marvel_password'));

			if ($this->config->get('marvel_caching')) {
				$this->cache->set('marvel.categories', $categories);
			}
		}

		$this->load->model('catalog/marvel');
		$data['remote_category_list'] = $this->model_catalog_marvel->getCategoryTree($categories);

		//Attribute groups
		$data['attribute_group_list'] = $this->model_catalog_attribute_group->getAttributeGroups();

		if (is_file(DIR_LOGS . 'marvel.log')) {
			$data['logs'] = file_get_contents(DIR_LOGS . 'marvel.log');
		} else {
			$data['logs'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/marvel', $data));
	}

	public function auth() {
		$this->load->language('extension/module/marvel');

		$this->load->library('marvel');
		$categories = $this->marvel->getCategories($this->config->get('marvel_username'), $this->config->get('marvel_password'));

		if (gettype($categories) == 'array' && $this->config->get('marvel_caching')) {
			$this->cache->set('marvel.categories', $categories);
		}

		if (gettype($categories) == 'array') {
			$this->session->data['success'] = $this->language->get('text_success_auth');
		} elseif (gettype($categories) == 'string') {
			$this->session->data['warning'] = $categories;
		} else {
			$this->session->data['warning'] = $this->language->get('error_undefined');
		}

		$this->response->redirect($this->url->link('extension/module/marvel', 'token=' . $this->session->data['token'], true));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/marvel')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (isset($this->error['warning'])) {
			$this->session->data['warning'] = $this->error['warning'];
		}

		return !$this->error;
	}

	public function fix() {
		$this->load->model('catalog/product');

		$query = $this->db->query("SELECT * FROM oc_product WHERE image = ''");
		foreach($query->rows as $row) {
			$images = $this->model_catalog_product->getProductImages($row['product_id']);
			if ($images) {
				$this->db->query("UPDATE " . DB_PREFIX . "product SET `image` = '" . $this->db->escape($images[0]['image']) . "' WHERE `product_id` = '" . (int)$row['product_id'] . "'");
			}
		}
	}
}