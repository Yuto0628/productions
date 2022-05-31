<?php

class Calendar{
    private static $instance = null;

    public static function instance($ym){
        if(is_null(self::$instance)){
            self::$instance = new Calendar($ym);
        }
        return self::$instance;
    }

    private $ym;
    private $timeStamp;
    private $htmlTitle;
    private $prev;
    private $next;
    private function __construct($ym){
        $this->setYm($ym);
        $this->timeStamp = strtotime($this->ym.'-01');
        $this->htmlTitle = date('Y/m', $this->timeStamp);
        $this->prev = date('Y-m', strtotime('-1 month', $this->timeStamp));
        $this->next = date('Y-m', strtotime('+1 month', $this->timeStamp));
    }

    public function setYm($ym){$this->ym = $ym;}
    public function getYm(){return $this->ym;}

    public function setHtmlTitle($htmlTitle){$this->htmlTitle = $htmlTitle;}
    public function getHtmlTitle(){return $this->htmlTitle;}

    public function getPrev(){return $this->prev;}
    
    public function getNext(){return $this->next;}

    public function getHolidays($year){
        $apiKey = 'AIzaSyBB7tGP7AmpW5KrLHdirJWNlML5xb7P07M';
        $holidays = array();
        $holidays_id = 'japanese__ja@holiday.calendar.google.com';
        $url = sprintf(
            'https://www.googleapis.com/calendar/v3/calendars/%s/events?'.
            'key=%s&timeMin=%s&timeMax=%s&maxResults=%d&orderBy=startTime&singleEvents=true',
            $holidays_id,
            $apiKey,
            $year.'-01-01T00:00:00Z',
            $year.'-12-31T00:00:00Z',
            150
        );
        if($results = file_get_contents($url, true)){
                $results = json_decode($results);
                foreach($results->items as $item){
                    $date = strtotime((string)$item->start->date);
                    $title = (string)$item->summary;
                    $holidays[date('Y-m-d', $date)] = $title;
                }
                ksort($holidays);
        }
        return $holidays;
    }

    public function getDbData($date, $pdo){
        $result = false;
        if(!$pdo==null){
            $data = $pdo->prepare("SELECT * FROM todo where date = :date");
            $data -> bindValue(':date', $date, PDO::PARAM_STR);
            $result = $data -> execute();
        }else{
            return null;
        }
        if($result){
            $dbData = $data->fetchAll();
            return $dbData;
        }
    }

    public function getContent($dbData){
        $contents = '';
        foreach($dbData as $record){
            $contents .=  '<p>'.$record['content'].'</p>';
        }
        return $contents;
    }

    public function show($pdo){

        $year = date('Y', $this->timeStamp);
        $day_count = date('t', $this->timeStamp);
        $today = date("Y-m-d");
    
        $day_of_the_week = date('w', $this->timeStamp);
        $week = '';
        $weeks = [];
        $week .= str_repeat('<td class="empty"></td>', $day_of_the_week);

        $holidaysArray = $this->getHolidays($year);
    
        for($day = 1; $day <= $day_count; $day++, $day_of_the_week++){
            $date = $day<10? $this->ym.'-0'.$day: $this->ym.'-'.$day;
            $dbData = $this->getDbData($date, $pdo);
            $contents = $this->getContent($dbData);
        
            if($today == $date){
                $week .= '<td class="today">'.$day.$contents.'</p></td>';
            }elseif(isset($holidaysArray[$date])){
                $week .= '<td class="holiday">'.$day.$contents.'</td>';
            }elseif($day_of_the_week%7 === 0){
                $week .= '<td class="sun">'.$day.$contents.'</td>';
            }elseif($day_of_the_week%7 === 6){
                $week .= '<td class="sat">'.$day.$contents.'</td>';
            }else{
                $week .= '<td>'.$day.$contents.'</td>';
            }
        
            if($day_of_the_week%7 == 6 || $day == $day_count){
                if($day == $day_count){
                    $week .= str_repeat('<td class="empty"></td>', 6 - $day_of_the_week%7);
                }
                $weeks[] = '<tr>'.$week.'</tr>';
                $week = '';
            }
        }
        foreach($weeks as $week){
            echo $week;
        }
    }

}

date_default_timezone_set('Asia/Tokyo');

$ym = isset($_GET['ym'])? $_GET['ym']: date('Y-m');
$cal = Calendar::instance($ym);
$pdo = new  PDO('mysql:host=localhost;dbname=calendar;charset=utf8', 'root', '', array(PDO::ATTR_EMULATE_PREPARES => false));


?>

<!DOCTYPE html>
<html lang='ja'>
<head>
    <meta charset="utf8">
    <title>php calendar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-0evHe/X+R7YkIZDRvuzKMRqM+OrBnVFBL6DOitfPri4tjfHxaWutUpFmBp4vmVor" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> 
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@200&display=swap" rel="stylesheet">
    <!cssを適用>
    <link rel="stylesheet" type="text/css" href="index.css">
    </head>
</head>
<body>
    <div class="container">
        <h3><a href="?ym=<?php echo $cal->getPrev();?>">&lt;</a><?php echo $cal->getHtmlTitle(); ?><a href="?ym=<?php echo $cal->getNext(); ?>">&gt;</a></h3>
        <table class="table table-bordered">
            <tr>
                <th class="sun">日</th>
                <th>月</th>
                <th>火</th>
                <th>水</th>
                <th>木</th>
                <th>金</th>
                <th class="sat">土</th>
            </tr>
            <?php $cal->show($pdo)#カレンダーを表示;?>
        </table>
        <button onclick="location.href='./form.php'">予定の入力</button>
    </div>
</body>
