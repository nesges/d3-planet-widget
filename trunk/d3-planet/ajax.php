<?php
    $rss_urls = [
        'dnd'       => 'https://planet.dnddeutsch.de/p/i/?a=rss',
        'events'    => 'https://planet.dnddeutsch.de/p/i/?a=rss&get=c_14',
        'info'      => 'https://planet.dnddeutsch.de/p/i/?a=rss&get=c_10',
        'shops'     => 'https://planet.dnddeutsch.de/p/i/?a=rss&get=c_13',
        'nondnd'    => 'https://planet.dnddeutsch.de/p/i/?a=rss&get=c_9',
    ];
    $cachetime = 15; // min
    
    if($_GET['count']*1 >= 1 && $_GET['count']*1 <= 100) {
        $count = $_GET['count']*1;
    } else {
        $count = 10;
    }
    if($_GET['srcname']==='1' || $_GET['srcname']==='0') {
        $srcname = $_GET['srcname'];
    } else {
        $srcname = 0;
    }
    if($_GET['p'] && array_key_exists($_GET['p'], $rss_urls)) {
        $feed = $_GET['p'];
    } else {
        $feed = 'dnd';
    }
    $rss_url = $rss_urls[$feed];
    
    $cache = "cache/planet_".$feed."_".$count."_".$srcname.".json";
    
    // if cache is younger than 5 minutes, just exit
    // the main module will read cache anyways and won't update if we don't return data
    // if(filemtime($cache) > time() - 60*5) {
    //     print json_encode(['html'=>'unchanged', 'crc'=>'-1']);
    //     exit;
    // }
    
    // load rss
    $cacherss = 'cache/'.md5($rss_url).'.rss';
    if(!file_exists($cacherss) || filemtime($cacherss) < time() - 60* $cachetime) {
        $rss = file_get_contents($rss_url);
        $rss = preg_replace('/&/', '&amp;', html_entity_decode($rss));
        file_put_contents($cacherss, $rss);
    } else {
        $rss = file_get_contents($cacherss);
    }
    
    $html="";
    $c=0;
    if($rss) {
        $xml = simplexml_load_string($rss);
        if($xml) {
            foreach($xml->channel->item as $item) {
                if($c<$count && !preg_match('#^D3: Dungeons#', $item->title )) {
                    $c++;
                    $date = date('d.m.Y H:i', strtotime($item->pubDate));
                    $title = explode(':', $item->title);
                    $src = array_shift($title);
                    $title = join(':', $title);
                    if($srcname) {
                        $content = '['.$src.'] ';
                    } else {
                        $content = '';
                    }
                    $content .= '<a href="'.$item->link.'">'.$title.'</a>';
                    $html .= '<li class="d3-planet-item" title="'.$src.' am '.$date.'">'.$content.'</li>';
                }
            }
        }
    }
    
    if($html) {
        $output = json_encode(['html'=>$html, 'crc'=>md5($html)]);
        file_put_contents($cache, $output);
        print $output;
    }
?>