<?php

include 'web_import_functions.php';

$webcasterEvents = getJsonArray(WEBCASTER_EVENTS_URL);
$drupalEvents = getJsonArray(DRUPAL_EVENTS_URL);

// $counter = 1;

try {
	foreach ($webcasterEvents as $eventShort) {
		if ($eventShort['duration'] < 155) {
			continue;
		}
	
		$eventFull = loadEventViaApp($eventShort['id']);
		$eventApi = loadEventViaApi($eventShort['id']);
		
		foreach ($eventApi as $item => $categories) {
			$eventFull->{$item} = $categories;
		}
	
		if (empty($eventFull->categories)) {
			continue;
		}
		
		$node = findNodeByWebcasterId($drupalEvents, $eventFull->id);
		if (null === $node) {
			postNode($eventFull->arrayForDrupal());
			printEvent($eventFull, 'добавлено');
		} else {
			$drupalNid = $node['nid'][0]['value'];
			postNode($eventFull->arrayForDrupal(), $drupalNid);
			printEvent($eventFull, 'обновлено');
		}
		// $counter++;
		// if (15 < $counter) {
		// 	die;
		// }
	}
} catch (Exception $e) {
	echo "Произошла ошибка: " . $e->getMessage() . "\n";
}