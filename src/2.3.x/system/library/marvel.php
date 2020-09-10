<?php
class Marvel {
	protected $lastRequestTime = 0;

	protected function request($url, $json = false, $data = [], $headers = []) {
		if ($this->lastRequestTime > time() - 2) {
			//sleep(1);
		} else {
			$this->lastRequestTime = time();
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		if ($data) {
			curl_setopt($ch, CURLOPT_POST, 1);
			if ($json) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
		}

		if ($headers) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		$response = curl_exec($ch);
		curl_close($ch);

		if ($json) {
			$response = @json_decode($response, true);
		}

		return $response;
	}

	public function GetCategories($username, $password) {
		$data = $this->request('https://b2b.marvel.ru/Api/GetCatalogCategories', true, ['user' => $username, 'password' => $password, 'responseFormat' => 1]);

		if (gettype($data) == 'array') {
			if (isset($data['Header'])) {
				if ($data['Header']['Code'] == 0) {
					return $data['Body']['Categories'];
				} else {
					return $data['Header']['Message'] . '(GetCatalogCategories)';
				}
			}
		}
	}

	public function GetFullStock($username, $password, $in_stock = true, $updated_since = '') {
		$data = $this->request('https://b2b.marvel.ru/Api/GetFullStock', true, ['user' => $username, 'password' => $password, 'instock' => $in_stock, 'updatedSince' => $updated_since, 'responseFormat' => 1]);

		if (gettype($data) == 'array') {
			if (isset($data['Header'])) {
				if ($data['Header']['Code'] == 0) {
					return $data['Body']['CategoryItem'];
				} else {
					return $data['Header']['Message'] . '(GetFullStock)';
				}
			}
		}
	}

	public function GetItems($username, $password, $items) {
		$data = $this->request('https://b2b.marvel.ru/Api/GetItems', true, ['user' => $username, 'password' => $password, 'items' => json_encode($items, true), 'packStatus' => 1, 'getExtendedItemInfo' => 1, 'responseFormat' => 1]);

		if (gettype($data) == 'array') {
			if (isset($data['Header'])) {
				if ($data['Header']['Code'] == 0) {
					return $data['Body']['CategoryItem'];
				} else {
					return $data['Header']['Message'] . '(GetItems)';
				}
			}
		}
	}

	public function GetItemPhotos($username, $password, $items) {
		$data = $this->request('https://b2b.marvel.ru/Api/GetItemPhotos', true, ['user' => $username, 'password' => $password, 'items' => json_encode($items, true), 'responseFormat' => 1]);

		if (gettype($data) == 'array') {
			if (isset($data['Header'])) {
				if ($data['Header']['Code'] == 0) {
					return $data['Body']['Photo'];
				} else {
					return $data['Header']['Message'] . '(GetItemPhotos)';
				}
			}
		}
	}
	
	public function logout($username, $password) {
		$this->request('https://b2b.marvel.ru/Api/Logout', true, ['user' => $username, 'password' => $password, 'responseFormat' => 1]);
	}
}