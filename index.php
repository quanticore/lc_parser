<?php 

//header('Content-type: text/plain; charset=UTF-8');
//omg
$dom = new DOMDocument();

libxml_use_internal_errors(true);

$links = array();
$dates = array();
//test
//test2
$last_page = 6; //номер последней страницы
echo '[Links]'.'<br>';
$k = 0;
for ($page = 1; $page <= $last_page; $page++) { //делаем цикл прохода по всем страницам до последней

	$dom->loadHTML(get_page_contents('http://freake.ru/music/style/drum-bass?p='.$page));
	
	$xpath = new DomXPath($dom);
	
	$links = get_download_url($dom); //запрос в функцию, ответом будет массив ссылок скачивания для одной страницы
	$dates = get_posts_date($xpath); //запрос в функцию, ответом будет массив дат для одной страницы
	
	for ($i = 0; $i < count($links); $i++) { //проходимся циклом по одному из массивов
		if (strlen($links[$i]) < 30){
		    echo 'item'.$k.'='.$links[$i].'<br>';
			echo 'item'.$k.'_SaveTo=I:\temp\USD\\'.$dates[$i].'<br>'; //выводим на экран собранные массивы ссылок и дат для одной страницы
			$k++;
		}
	}
	$links = null; //очищаем массив, получается что каждый цикл
	$dates = null; //возможно очищение лишнее, проверим потом
	
}


function get_page_contents($url) { //функция запрашивает страницу с параметром номера страницы
	$ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 5);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result; //и возвращает
}

function get_download_url($dom) {

	$h3s = $dom->getElementsByTagName("h3");
	foreach ($h3s as $h3) {
		$as = $h3->getElementsByTagName("a")->item($h3->getElementsByTagName("a")->length - 1);
		$name = $as->getAttribute('href');
		$fname = ltrim($name, '/');

		
		$url = 'http://freake.ru/engine/modules/ajax/music.link.php'; //отсюда и ниже формируем POST запрос
		$data = array('id' => $fname);
		
		$options = array(
		    'http' => array(
		        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		        'method'  => 'POST',
		        'content' => http_build_query($data),
		    ),
		);

		$context  = stream_context_create($options);
		$result = stripslashes(htmlspecialchars(file_get_contents($url, false, $context))); //отсылаем и получаем ответ на POST запрос
		

		$string = substr($result, stripos($result, 'rusfolder')); //удаляем контент до первой ссылки - символы записаны в HTML Entity кодах 
		$linki[] = current(explode('&quot; target=', $string)); //удаляем контент после ссылки, символы записаны так же и добавляем в массив по циклу
	}
	return $linki; //функция возвращает заполненный массив
}

function get_posts_date($xpath) {
	$datos = $xpath->query("//*[contains(@class, 'post-info')]");
	foreach ($datos as $dato) {

		$quarterfinaldate = $dato->getElementsByTagName('span')->item(0)->nodeValue; //достаем дату-время из поста
		if (strpos($quarterfinaldate,':') !== false) //проверяем, указано ли время - если нет, то указан год
			$semifinaldate = (mb_substr($quarterfinaldate, 0, -8, "utf-8")); //удаляем символы в дате со временем, дата без года
		else
			$semifinaldate = $quarterfinaldate; //эта дата с годом

		$trans = array("янв" => "Jan",
						"фев" => "Feb",
						"мар" => "Mar",
						"апр" => "Apr",
						"мая" => "May",
						"июн" => "Jun",
						"июл" => "Jul",
						"авг" => "Aug",
						"сен" => "Sep",
						"окт" => "Oct",
						"ноя" => "Nov",
						"дек" => "Dec"); //подготавливаем массив для замен месяцев

		$finaldate = strtr($semifinaldate, $trans); //переписываем русские месяцы на Англ.

		date_default_timezone_set('Europe/Moscow');
		$date = new DateTime();

		switch ($finaldate) { //делаем обработку вариантов даты
			case 'Сегодня':
				$tempdate = date('yW', strtotime("today")); //если Сегодня
				break;
			case 'Вчера':
				$tempdate = date('yW', strtotime("yesterday")); //если Вчера
				break;
			default:
				if (mb_strlen($finaldate, "utf-8") == 6) {
					$date = date_create_from_format('d M', $finaldate); //если без года и удаленным временем
					$tempdate = date_format($date, 'yW'); //сохраняем в переменную
				}
				else
					$date = date_create_from_format('d M Y', $finaldate); //если с годом
					$tempdate = date_format($date, 'yW');
				
		}
		$datki[] = $tempdate; //добавляем в массив по циклу
	}	
	return $datki; //функция возвращает заполненный массив
}

?>