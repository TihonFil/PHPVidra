<?php

// Префиксный URL, используемый в различных запросах
const PREFIX_URL = 'https://example.com';

// URL для получения событий от вебкастера
const WEBCASTER_EVENTS_URL = 'https://example.com/api/events.json';
// URL для получения событий от Drupal
const DRUPAL_EVENTS_URL = PREFIX_URL . '/rest/export/video?_format=hal_json';

// URL для создания нового узла (node) в Drupal
const NODE_URL_POST = PREFIX_URL . '/node?_format=hal_json';
// URL для обновления узла (node) в Drupal
const NODE_URL_PATCH = PREFIX_URL . '/node/';
// Формат данных при обновлении узла (node) в Drupal
const NODE_FORMAT_PATCH = '?_format=hal_json';

// Имя пользователя для аутентификации в Drupal
const USER_NAME_DRUPAL = 'username';
// Пароль пользователя для аутентификации в Drupal
const PASSWORD_DRUPAL = 'password';

// URL для получения данных событий через вебкастер API
const API_URL = 'https://example.com/api/events.json';
// Секретный ключ для аутентификации вебкастера API
const SECRET_KEY = 'secret';
// Идентификатор клиента (CID) для вебкастера API
const CID = 'client_id';

// Базовый URL для получения данных о конкретном событии от вебкастера
const EVENT_API_URL = 'https://example.com/api/event.json?';


/**
 * Fetches JSON content from the given URL and returns it as an array
 *
 * @param string $url The URL from which to fetch the JSON content
 * @return array|null The decoded JSON content as an array, or null on error
 */
function getJsonArray($url) {
	$options = [
		"ssl" => [
			"verify_peer" => true,
			"verify_peer_name" => true,
		],
	];

	$context = stream_context_create($options);
	$content = file_get_contents($url, false, $context);

	if ($content === false) {
		// Обработка ошибки получения контента
		$errorMessage = 'Ошибка получения контента: ' . error_get_last()['message'] . "\n";
		echo $errorMessage;
	}

	$decodedContent = json_decode($content, true);

	if (json_last_error() !== JSON_ERROR_NONE) {
		// Обработка ошибки декодирования JSON
		$errorMessage = 'Ошибка декодирования JSON: ' . json_last_error_msg() . "\n";
		echo $errorMessage;
	}

	return $decodedContent;
}

/**
 * Posts node data to Drupal using the specified method (POST or PATCH)
 *
 * @param array $data The node data to be posted
 * @param int|null $nid The node ID for updating an existing node (optional)
 * @return mixed The result of the postToDrupal function
 */
function postNode(array $data, $nid = null)
{
	if (null === $nid) {
		$url = NODE_URL_POST;
		$json = json_encode($data, JSON_UNESCAPED_UNICODE);
		$method = 'POST';
	} else {
		$url = NODE_URL_PATCH . (string) $nid . NODE_FORMAT_PATCH;
		$json = json_encode($data, JSON_UNESCAPED_UNICODE);
		$method = 'PATCH';
	}
	return postToDrupal($url, $json, $method);
}

/**
 * Posts taxonomy data to Drupal
 *
 * @param array $data The taxonomy data to be posted
 * @return mixed The result of the postToDrupal function
 */
function postTaxonomy($data)
{
	$url = NODE_URL_POST;
	$json = json_encode($data, JSON_UNESCAPED_UNICODE);
	$method = 'POST';
	return postToDrupal($url, $json, $method);  
}

/**
 * Posts data to Drupal using cURL
 *
 * @param string $url The URL to send the request to
 * @param string $json The JSON data to be sent
 * @param string $method The HTTP method to be used (e.g., POST, PATCH)
 * @return mixed The response from the cURL request
 * @throws Exception If an error occurs during the cURL request
 */
function postToDrupal($url, $json, $method) 
{
	$c = curl_init();
	$username = USER_NAME_DRUPAL;
	$password = PASSWORD_DRUPAL;
	$headers = [
		'Content-Type:application/hal+json',
		'Accept:application/hal+json',
	];
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_POSTFIELDS, $json );
	curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($c, CURLOPT_VERBOSE, true);
	curl_setopt($c, CURLOPT_STDERR, fopen('php://stderr', 'w'));
	curl_setopt($c, CURLINFO_HEADER_OUT, true);
	curl_setopt($c, CURLOPT_HEADER, true);
	curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($c, CURLOPT_USERPWD, $username . ":" . $password);

	$res = curl_exec($c);

	// Обработка ошибок
	if ($res === false) {
		throw new Exception(curl_error($c));
	}

	$status_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
	if ($status_code < 200 || $status_code >= 300) {
		throw new Exception("HTTP Error: $status_code");
	}

	curl_close($c);

	return $res;
}

/**
 * Load JSON from given URL and convert to array
 *
 * @param string $url The URL to load JSON from
 * @return array|null The decoded JSON data as an array, or null on error
 * @throws Exception If an error occurs during the request or JSON decoding
 */
function loadArrayFromUrl($url)
{
	$c = curl_init();

	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);

	$res = curl_exec($c);

	// Проверяем наличие ошибок при выполнении запроса
	if ($res === false) {
		throw new Exception(curl_error($c));
	}

	$httpCode = curl_getinfo($c, CURLINFO_HTTP_CODE);

	// Проверяем HTTP-код ответа на наличие ошибок
	if ($httpCode >= 400) {
		throw new Exception("HTTP Error: $httpCode");
	}

	curl_close($c);

	$data = json_decode($res, true);

	// Проверяем наличие ошибок при декодировании JSON
	if (json_last_error() !== JSON_ERROR_NONE) {
		throw new Exception('JSON decoding error: ' . json_last_error_msg());
	}

	return $data;
}

/**
 * Loads an event via API and returns a WebcasterEvent object
 *
 * @param int $id The event ID
 * @return WebcasterEvent|null The loaded event as a WebcasterEvent object, or null on error
 * @throws Exception If an error occurs during the request or JSON decoding
 */
function loadEventViaApi(int $id): ?WebcasterEvent
{
	$url = API_URL;
	$secret = SECRET_KEY;

	$params = array(
		'cid'   => CID,
		'id'    => $id,
		'embed' => array(
			'type'  => 'iframe',
		),
	);

	ksort($params);
	$query = http_build_query($params);
	$params['sig'] = md5(urldecode($query) . $secret);

	$url .= '?' . http_build_query($params);

	try {
		$content = loadArrayFromUrl($url);
		return WebcasterEvent::fromApiData($content);
	} catch (Exception $e) {
		// Обработка ошибок
		echo 'Ошибка загрузки события: ' . $e->getMessage() . "\n";
		return null;
	}
}

/**
 * Load event data from Webcaster App API
 *
 * @param int $id
 * @return WebcasterEvent|null
 * @throws Exception If an error occurs during the request or data processing
 */
function loadEventViaApp(int $id): ?WebcasterEvent
{
	$queryParams = [
		'id' => $id,
	];
	$url = EVENT_API_URL . http_build_query($queryParams);

	$responseData = loadArrayFromUrl($url);

	if ($responseData === null) {
		throw new Exception('Failed to get response data');
	}

	return WebcasterEvent::fromAppData($responseData);
}

class WebcasterEvent
{
	/**
	 * Loads event data from the Webcaster App API based on the given event ID.
	 *
	 * @param int $id The event ID.
	 * @return WebcasterEvent|null The loaded WebcasterEvent instance, or null if the event is not found.
	 * @throws Exception If an error occurs during the request or data processing.
	 */

	protected string $urlPrefix;

	public int $id;
	public string $name;
	public string $description;
	public string $directors;
	public string $actors;
	public string $production_country;
	public int $years;
	public string $age_rating;
	public int $duration;
	public array $images;
	public int $season;
	public int $episode;
	public array $tags;
	public array $genres;
	public ?string $categories = null;

	public function __construct($urlPrefix = PREFIX_URL)
	{
		$this->urlPrefix = $urlPrefix;
	}

	/**
	 * Creates a new WebcasterEvent instance from app data.
	 *
	 * @param array $data The event data from the app.
	 * @return self The new WebcasterEvent instance.
	*/
	public static function fromAppData(array $data): self
	{
		$result = new self();
		$result->id = $data['id'];
		$result->name = $data['name'];
		$result->description = $data['description'];
		$result->directors = $data['directors'];
		$result->actors = $data['actors'];
		$result->production_country = $data['production_country'];
		$result->years = $data['year'];
		$result->age_rating = $data['age_rating'];
		$result->duration = $data['duration'];
		$result->images = $data['images'];
		$result->season = intval($data['season']);
		$result->episode = intval($data['episode']);
		$result->name_en = $data['name_en'];
		$result->tags = $data['tags'];
		$result->genres = explode(",", $data['genres']);

		return $result;
	}

	/**
	 * Creates a new WebcasterEvent instance from API data.
	 *
	 * @param array $data The event data from the API.
	 * @return self The new WebcasterEvent instance.
	 */
	public static function fromApiData(array $data): self
	{
		$result = new self();
		$result->id = $data['event']['id'];
		$result->categories = $data['event']['attributes']['type_content'] ?? null;

		return $result;
	}

	protected function fullUrl($url): string
	{
		return $this->urlPrefix . $url;
	}

	/**
	 * Generates an array representation of the event data for Drupal integration.
	 *
	 * @return array The array representation of the event data for Drupal.
	 */
	public function arrayForDrupal(): array
	{
		$result = [
			'_links' => [
				'type' => [
					'href' => $this->fullUrl('/rest/type/node/video')
				]
			],
			'_embedded' => [
				$this->fullUrl('/rest/relation/node/video/field_tags') => $this->tagsForDrupal(),
				$this->fullUrl('/rest/relation/node/video/field_genre') => $this->genresForDrupal(),
				$this->fullUrl('/rest/relation/node/video/field_category') => $this->categoriesForDrupal()
			],
			'status' => [
				$this->publishDrupal('Серия')
			]
		];
		$result['title'] = [$this->name];
		$result['field_webcaster_id'] = [$this->id];
		$result['field_name_ru'] = [$this->name];
		$result['field_name_original'] = [$this->name_en];
		$result['field_description'] = [$this->description];
		$result['field_directors'] = [$this->directors];
		$result['field_actors'] = [$this->actors];
		$result['field_production_country'] = [$this->production_country];
		$result['field_years'] = [$this->years];
		$result['field_age_rating'] = [$this->age_rating];
		$result['field_duration_seconds'] = [$this->duration];
		$result['field_images_big'] = [$this->images['big']] ?? null;
		$result['field_season'] = [$this->season];
		$result['field_episode'] = [$this->episode];

		return $result;
	}

	/**
	 * Determines the Drupal publish status based on the provided value or event category.
	 *
	 * @param mixed|null $value The value to determine the publish status or null to use the default category.
	 * @return array The array representation of the Drupal publish status.
	 */
	protected function publishDrupal($value = null)
	{
		$result = [];
		$category = $value;

		if ($value === null) {
			$result =
				[
					'value' => 1
				];
		} elseif ($this->categories === $category) {
			$result =
				[
					'value' => 0
				];
		} else {
			$result =
				[
					'value' => 1
				];
		}
		return $result;
	}

	/**
	 * Retrieves the Drupal representation of the tags associated with the event.
	 *
	 * @return array The array representation of the tags for Drupal.
	 */
	protected function tagsForDrupal(): array
	{
		$result = [];
		foreach ($this->tags as $tagName) {
			$tagData = findOrCreateDrupalTag($tagName);
			$result[] =
			[
				'_links' => [
					'self' => [
						'href' => $tagData['_links']['self']['href'],
					]
				],
				'uuid' => [
					'value' => $tagData['uuid'][0]['value'],
				]
			];
		}
		return $result;
	}

	/**
	 * Retrieves the Drupal representation of the genres associated with the event.
	 *
	 * @return array The array representation of the genres for Drupal.
	 */
	protected function genresForDrupal(): array
	{
		$arrayGenres = is_array($this->genres) ? $this->genres : explode(",", $this->genres);
		$result = [];
		foreach ($arrayGenres as $genreName) {
			$genreData = findOrCreateDrupalGenre($genreName);
			$result[] = [
				'_links' => [
					'self' => [
						'href' => $genreData['_links']['self']['href'],
					],
				],
				'uuid' => [
					'value' => $genreData['uuid'][0]['value'],
				],
			];
		}
		return $result;
	}

	/**
	 * Retrieves the Drupal representation of the category associated with the event.
	 *
	 * @return array The array representation of the category for Drupal.
	 */
	protected function categoriesForDrupal(): array
	{
		$result = [];
		$categoryData = findOrCreateDrupalCategory($this->categories);
		$result[] =
			[
				'_links' => [
					'self' => [
						'href' => $categoryData['_links']['self']['href'],
					]
				],
				'uuid' => [
					'value' => $categoryData['uuid'][0]['value'],
				]
			];

		return $result;
	}
}

/**
 * Find or create a Drupal tag.
 *
 * @param string $tagName The name of the tag.
 * @return array The Drupal tag data.
 */
function findOrCreateDrupalTag(string $tagName)
{
	$tagName = trim($tagName);
	$drupalTags = getAllDrupallTags();

	foreach ($drupalTags as $tag) {
		if ($tag['name'][0]['value'] == $tagName) {
			return $tag;
		}
	}

	$tagData = [
		'_links' => [
			'type' => [
				'href' => PREFIX_URL . '/rest/type/taxonomy_term/tags',
			],
		],
		'name' => [
			[
				'value' => $tagName,
				'lang' => 'ru',
			],
		],
	];

	postTaxonomy($tagData);

	do {
		$drupalTags = getAllDrupallTags();
		foreach ($drupalTags as $tag) {
			if ($tag['name'][0]['value'] == $tagName) {
				return $tag;
			}
		}
	} while (true);
}


function getAllDrupallTags(): array
{
	return getJsonArray(PREFIX_URL . '/rest/taxonomy/tags?_format=hal_json');
}

/**
 * Find or create a Drupal genre.
 *
 * @param string $genreName The name of the genre.
 * @return array The Drupal genre data.
 */
function findOrCreateDrupalGenre(string $genreName)
{
	$genreName = trim($genreName);
	$drupalGenres = getAllDrupallGenres();

	foreach ($drupalGenres as $genre) {
		if ($genre['name'][0]['value'] == $genreName) {
			return $genre;
		}
	}

	$genreData = [
		'_links' => [
			'type' => [
				'href' => PREFIX_URL . '/rest/type/taxonomy_term/genre',
			],
		],
		'name' => [
			[
				'value' => $genreName,
				'lang' => 'ru',
			],
		],
	];

	postTaxonomy($genreData);

	do {
		$drupalGenres = getAllDrupallGenres();
		foreach ($drupalGenres as $genre) {
			if ($genre['name'][0]['value'] == $genreName) {
				return $genre;
			}
		}
	} while (true);
}


function getAllDrupallGenres(): array
{
	return getJsonArray(PREFIX_URL . '/rest/taxonomy/genre?_format=hal_json');
}

/**
 * Find or create a Drupal category.
 *
 * @param string $categoryName The name of the category.
 * @return array The Drupal category data.
 */
function findOrCreateDrupalCategory(string $categoryName)
{
	$categoryName = trim($categoryName);
	$drupalCategories = getAllDrupallCategory();

	switch ($categoryName) {
		case 'Фильм':
			$categoryName = 'Фильмы';
			break;
		case 'Серия':
			$categoryName = 'Сериалы';
			break;
		case 'Мультфильм':
			$categoryName = 'Мультфильмы';
			break;
		case 'Трейлер':
			$categoryName = 'Трейлеры';
			break;
		default:
			$categoryName;
	}

	foreach ($drupalCategories as $category) {
		if ($category['name'][0]['value'] == $categoryName) {
			return $category;
		}
	}

	$categoryData = [
		'_links' => [
			'type' => [
				'href' => PREFIX_URL . '/rest/type/taxonomy_term/category',
			],
		],
		'name' => [
			[
				'value' => $categoryName,
				'lang' => 'ru',
			],
		],
	];

	postTaxonomy($categoryData);

	do {
		$drupalCategories = getAllDrupallCategory();
		foreach ($drupalCategories as $category) {
			if ($category['name'][0]['value'] == $categoryName) {
				return $category;
			}
		}
	} while (true);
}


function getAllDrupallCategory(): array
{
	return getJsonArray(PREFIX_URL . '/rest/taxonomy/category?_format=hal_json');
}

/**
 * Prints information about an event to the terminal.
 *
 * @param WebcasterEvent $event The event object.
 * @param string $action The action (default: 'action not defined').
 */
function printEvent(WebcasterEvent $event, string $action = 'действие не определено') 
{
	printf("%s %d %s [%d] \n",
		$action . ': ',
		$event->duration,
		$event->name,
		$event->id,
	);
}

/**
 * Finds a node by its webcaster ID.
 *
 * @param array $nodes An array of nodes to search through.
 * @param int $webcasterId The webcaster ID to search for.
 * @return array|null The node matching the webcaster ID, or null if not found.
 */
function findNodeByWebcasterId(array $nodes, int $webcasterId): ?array
{
	foreach ($nodes as $node) {
		if ($node['field_webcaster_id'][0]['value'] == $webcasterId) {
			return $node;
		}
	}
	return null;
}