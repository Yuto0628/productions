<?php

class Calendar{
    /**
     * カレンダーのインスタンス
     * 
     * @var Calendar 
     */
    private static $instance = null;


    /**
     * コンストラクタを一つだけ生成する
     * 
     * @param string $ym
     * @return Calendar
     */
    public static function instance($ym){
        if(is_null(self::$instance)){
            self::$instance = new Calendar($ym);
        }
        return self::$instance;
    }
    /**
     * 年月日(yyyy-dd)
     * 
     * @var string
     */
    private $ym;

    /**
     * タイムスタンプ
     * 
     * @var string
     */
    private $timeStamp;

    /**
     * カレンダーの年月(yyyy/dd)
     * 
     * @var string
     */
    private $htmlTitle;

    /**
     * 現在から一ヶ月前の年月(yyyy-dd)
     * 
     * @var string
     */
    private $prevYm;

    /**
     * 現在から一ヶ月後の年月(yyyy-dd)
     * 
     * @var string
     */
    private $nextYm;
    
    /**
     * コンストラクタ
     * 
     * @param string $ym 年月(yyyy-mm)
     */
    private function __construct($ym){
        $this->setYm($ym);
        $this->timeStamp = strtotime($this->ym.'-01');
        $this->htmlTitle = date('Y/m', $this->timeStamp);
        $this->prevYm = date('Y-m', strtotime('-1 month', $this->timeStamp));
        $this->nextYm = date('Y-m', strtotime('+1 month', $this->timeStamp));
    }

    public function setYm($ym){$this->ym = $ym;}
    public function getYm(){return $this->ym;}

    public function setHtmlTitle($htmlTitle){$this->htmlTitle = $htmlTitle;}
    public function getHtmlTitle(){return $this->htmlTitle;}

    public function getPrevYm(){return $this->prevYm;}
    
    public function getNextYm(){return $this->nextYm;}

    /**
     * 祝日の日付と祝日名を連想配列で取得
     * 
     * @param string $year 年(yyyy)
     * @return array 年月日, 祝日名
     */
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

    /**
     * データベースから指定した日付のレコードを取得
     * 
     * @param string $date 年月日(yyyy-mm-dd)
     * @param PDO $pdo 接続するデータベース
     * @return array $records 年月日, 予定
     */
    public function getRecords($date, $pdo){
        $result = false;
        if(!$pdo==null){
            $order = $pdo->prepare("SELECT * FROM todo where date = :date");
            $order -> bindValue(':date', $date, PDO::PARAM_STR);
            $result = $order -> execute();
        }else{
            return null;
        }
        if($result){
            $records = $order->fetchAll();
            return $records;
        }
    }

    /**
     * レコードから予定を取り出してカレンダー用に加工
     * 
     * @param array $records 年月日, 予定
     * @return string $contents 予定
     */
    public function getContent($records){
        $contents = '';
        foreach($records as $record){
            $contents .=  '<p>'.$record['content'].'</p>';
        }
        return $contents;
    }

    /**
     * カレンダーの表示
     * 
     * @param PDO $pdo 接続するデータベース
     */
    public function show($pdo){

        $year = date('Y', $this->timeStamp);
        $dayCount = date('t', $this->timeStamp);
        $today = date("Y-m-d");
    
        $dayOfWeek = date('w', $this->timeStamp);
        $week = '';
        $weeks = [];
        $week .= str_repeat('<td class="empty"></td>', $dayOfWeek);

        $holidaysArray = $this->getHolidays($year);
    
        for($day = 1; $day <= $dayCount; $day++, $dayOfWeek++){
            $date = $day<10? $this->ym.'-0'.$day: $this->ym.'-'.$day;
            $records = $this->getRecords($date, $pdo);
            $contents = $this->getContent($records);
        
            if($today == $date){
                $week .= '<td class="today">'.$day.$contents.'</p></td>';
            }elseif(isset($holidaysArray[$date])){
                $week .= '<td class="holiday">'.$day.$contents.'</td>';
            }elseif($dayOfWeek%7 === 0){
                $week .= '<td class="sun">'.$day.$contents.'</td>';
            }elseif($dayOfWeek%7 === 6){
                $week .= '<td class="sat">'.$day.$contents.'</td>';
            }else{
                $week .= '<td>'.$day.$contents.'</td>';
            }
        
            if($dayOfWeek%7 == 6 || $day == $dayCount){
                if($day == $dayCount){
                    $week .= str_repeat('<td class="empty"></td>', 6 - $dayOfWeek%7);
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
$pdo = new PDO('mysql:host=localhost;dbname=calendar;charset=utf8', 'root', '', array(PDO::ATTR_EMULATE_PREPARES => false));


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
        <h3><a href="?ym=<?php echo $cal->getPrevYm();?>">&lt;</a><?php echo $cal->getHtmlTitle(); ?><a href="?ym=<?php echo $cal->getNextYm(); ?>">&gt;</a></h3>
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
