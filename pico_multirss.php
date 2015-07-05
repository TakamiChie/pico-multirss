<?php
/**
 * Pico Plugin Pico-MultiRSS
 * RSSを出力する。複数個のRSSを出力することも可能。
 *
 * @author TakamiChie
 * @link http://onpu-tamago.net/
 * @license http://opensource.org/licenses/MIT
 * @version 1.0
 */
class Pico_MultiRSS {

  private $baseurl;
  
  private $plugin_path;
  
  private $rss;
  
  private $channel;

  public function config_loaded(&$settings)
  {
    $this->rss = array(
      'generator' => 'Pico',
      'channel' => array(
        array(
          'url' => 'rss',
          'directory' => '/',
          'count' => 10,
        )
      ),
    );
    $this->base_url = $settings['base_url'];
    $this->plugin_path = dirname(__FILE__);
    if(isset($settings['multirss'])){
      $this->rss += $settings['multirss'];
      $this->rss['channel'] = $settings['multirss']['channel'];
    }
  }

  public function request_url(&$url)
  {
    $channels = $this->rss["channel"];
    // 該当するチャンネルを探す
    foreach($channels as $channel){
      if($channel['url'] == $url){
        $this->channel = $channel;
        break;
      }
    }
  }

  public function get_pages(&$pages, &$current_page, &$prev_page, &$next_page)
  {
    // 該当チャンネルがあれば処理
    if($this->channel){
      $new_pages = array();
      foreach($pages as $page){
        $path = substr($page["url"], strlen($this->base_url));
        if(!empty($page["date"])){
          if(is_array($this->channel['directory'])){
            // 配列の場合、各キーとパスを照合
            foreach($this->channel['directory'] as $key => $value){
              if(substr($path, 0, strlen($key)) == $key){
                $page["title"] = $value . $page["title"];
                array_push($new_pages, $page);
              }
            }
          }else{
            // 文字列の場合、ディレクトリとパスを照合
            $s = $this->channel['directory'];
            if(substr($path, 0, strlen($s)) == $s){
              array_push($new_pages, $page);
            }
          }
        }
      }
      $updated = array();
      foreach($new_pages as $key => $val){
        $updated[$key] = $val["date"];
      }
      array_multisort($updated, SORT_DESC, $new_pages);
      $pages = array_slice($new_pages, 0, $this->channel['count']);
    }
  }

  public function before_render(&$twig_vars, &$twig, &$template)
  {
    // from https://github.com/gilbitron/Pico-RSS-Plugin/blob/master/pico_rss/pico_rss.php#L34
		if($this->channel){
			header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
			header("Content-Type: application/rss+xml; charset=UTF-8");
			$loader = new Twig_Loader_Filesystem($this->plugin_path);
			$twig_rss = new Twig_Environment($loader, $twig_vars['config']['twig_config']);
			$this->rss['channel'] = $this->channel;
			$twig_vars += $this->rss;
			echo $twig_rss->render('rss.template', $twig_vars);
			exit;
		}
  }
}

?>
