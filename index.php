<?php 

//header('Content-type: text/plain; charset=UTF-8');

$dom = new DOMDocument();

libxml_use_internal_errors(true);

$links = array();
$dates = array();

$last_page = 5;
for ($page = 1; $page < $last_page; $page++) {

	$dom->loadHTML(get_page_contents('http://freake.ru/music/style/drum-bass?p='.$page));
	
	$xpath = new DomXPath($dom);
	
	$links = get_download_url($dom);
	$dates = get_posts_date($xpath);
	
	for ($i = 0; $i < count($links); $i++) {
		echo $links[$i].' - '.$dates[$i].'<br>';
	}
	$links = null;
	$dates = null;
	
}


function get_page_contents($url) {
	$ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 5);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function get_download_url($dom) {

	$h3s = $dom->getElementsByTagName("h3");
	foreach ($h3s as $h3) {
		$as = $h3->getElementsByTagName("a")->item($h3->getElementsByTagName("a")->length - 1);
		$name = $as->getAttribute('href');
		$fname = ltrim($name, '/');

		$url = 'http://freake.ru/engine/modules/ajax/music.link.php';
		$data = array('id' => $fname);
		
		$options = array(
		    'http' => array(
		        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		        'method'  => 'POST',
		        'content' => http_build_query($data),
		    ),
		);
		$context  = stream_context_create($options);
		$result = stripslashes(htmlspecialchars(file_get_contents($url, false, $context)));
		
		$string = substr($result, stripos($result, '&lt;a href=&quot;')+strlen('&lt;a href=&quot;'));
		$linki[] = current(explode('&quot; target=', $string));
	}
	return $linki;
}

function get_posts_date($xpath) {
	$datos = $xpath->query("//*[contains(@class, 'post-info')]");
	foreach ($datos as $dato) {

		$quarterfinaldate = $dato->getElementsByTagName('span')->item(0)->nodeValue;
		if (strpos($quarterfinaldate,':') !== false)
			$semifinaldate = (mb_substr($quarterfinaldate, 0, -8, "utf-8"));
		else
			$semifinaldate = $quarterfinaldate;

		$trans = array("янв" => "Jan",
						"фев" => "Feb",
						"мар" => "Mar",
						"апр" => "Apr",
						"май" => "May",
						"июн" => "Jun",
						"июл" => "Jul",
						"авг" => "Aug",
						"сен" => "Sep",
						"окт" => "Oct",
						"ноя" => "Nov",
						"дек" => "Dec");

		$finaldate = strtr($semifinaldate, $trans);

		date_default_timezone_set('Europe/Moscow');
		$date = new DateTime();

		switch ($finaldate) {
			case 'Сегодня':
				$tempdate = date('W Y', strtotime("today"));
				break;
			case 'Вчера':
				$tempdate = date('W Y', strtotime("yesterday"));
				break;
			default:
				if (mb_strlen($finaldate, "utf-8") == 6) {
					$date = date_create_from_format('d M', $finaldate);
					$tempdate = date_format($date, 'W Y');
				}
				else
					$date = date_create_from_format('d M Y', $finaldate);
					$tempdate = date_format($date, 'W Y');
				
		}
		$datki[] = $tempdate;
	}	
	return $datki;
}

?>