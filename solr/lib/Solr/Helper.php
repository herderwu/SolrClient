<?php
namespace Solr;

use Zookeeper;

class Helper
{

  static $solr_total_spend_time = 0;

  public static function Common($query_params, $config, $block_setting, $function_name) {
      //form zk get solr host array
      $zk = new Zookeeper\Zk($config['zookeeper_host']);
      //$solr_hosts = $zk->getSolr();

      list($result, $start_time, $end_time, $uri) = self::CallSolr($query_params, $config, $solr_hosts, $block_setting['timeout'] ?: FALSE);
      //retry if not get data
      if ($result == false) {
          list($result, $start_time, $end_time, $uri) = self::CallSolr($query_params, $config, $solr_hosts, $block_setting['timeout'] ?: FALSE);
      }

      $result = json_decode($result);

      $docs = array();
      $total_count = 0;
      if (isset($result->responseHeader->status) && $result->responseHeader->status == 0) {
          $total_count = $result->response->numFound;
          $docs = $result->response->docs;
      }

      //debug info
      if (isset($_GET['solr_test'])) {
          print '<!-- solr test info: -->' . PHP_EOL;
          print '<!-- solr function name: ' . var_export($function_name, TRUE) . ' -->' . PHP_EOL;
          print '<!-- solr query url: ' . var_export($uri, TRUE) . ' -->' . PHP_EOL;
          print '<!-- solr start time: ' . var_export($start_time, TRUE) . ' -->' . PHP_EOL;
          print '<!-- solr end time: ' . var_export($end_time, TRUE) . ' -->' . PHP_EOL;
          print '<!-- solr spend time(s): ' . var_export($end_time - $start_time, TRUE) . ' -->' . PHP_EOL;

          self::$solr_total_spend_time += $end_time - $start_time;
          print '<!-- solr total spend time(s): ' . var_export(self::$solr_total_spend_time, TRUE) . ' -->' . PHP_EOL;

          print '<!-- solr block setting: ' . var_export($block_setting, TRUE) . ' -->' . PHP_EOL;

          print '<!-- solr result: ' . var_export($result, TRUE) . ' -->' . PHP_EOL;
      }

      return array($total_count, $docs);
  }

  public static function CallSolr($query_params, $config, $solr_hosts, $timeout = FALSE) {
        if (!empty($solr_hosts)) {
            //rand a host from host array
            $rand = array_rand($solr_hosts, 1);
            $config['endpoint']['host'] = $solr_hosts[$rand];
        }

        $queryParse = new QueryParse();
        $uri = $queryParse->getBaseUri($config) . $queryParse->setParams($query_params)->getUri();

        $start_time = microtime(true);

        $client = Client::singleton();
        if ($timeout) {
            $client->setOptions(array(
              CURLOPT_TIMEOUT         => $timeout,
              CURLOPT_CONNECTTIMEOUT  => $timeout,
            ));
        }
        $client->addUrl($uri);
        $result = $client->execute();

        $end_time = microtime(true);

        return array($result, $start_time, $end_time, $uri);
    }

    public static function GetSiteMap($block_setting, $config) {
        $block_setting['timeout'] = 5;
        return self::GetRecentContent($block_setting, $config);
    }

    public static function GetRecentContent($block_setting, $config) {
        $query_params = ParamParse::ContentList($block_setting);
        return self::Common($query_params, $config, $block_setting, __FUNCTION__);
    }

    public static function GetRelatedContent($block_setting, $config) {
        $query_params = ParamParse::RelatedContent($block_setting);
        return self::Common($query_params, $config, $block_setting, __FUNCTION__);
    }

    public static function GetContentsByIDs($block_setting, $config) {
        $query_params = ParamParse::ContentsByIDs($block_setting);
        return self::Common($query_params, $config, $block_setting, __FUNCTION__);
    }

    public static function GetBookChildren($block_setting, $config) {
        $query_params = ParamParse::BookChildren($block_setting);
        return self::Common($query_params, $config, $block_setting, __FUNCTION__);
    }

    public static function GetSlideshowChildren($block_setting, $config) {
        $query_params = ParamParse::SlideshowChildren($block_setting);
        return self::Common($query_params, $config, $block_setting, __FUNCTION__);
    }

    public static function GetTutorialChildren($block_setting, $config) {
        $query_params = ParamParse::TutorialChildren($block_setting);
        return self::Common($query_params, $config, $block_setting, __FUNCTION__);
    }

    public static function GetPageTaxonomy($block_setting, $config) {
        $query_params = ParamParse::PageTaxonomy($block_setting);

        //use taxonomy collection
        $config['is_taxonomy'] = true;

        $result = self::Common($query_params, $config, $block_setting, __FUNCTION__);
        list($total_count, $docs) = $result;

        if ($total_count > 0) {
            $taxonomy_solr = self::_convert_doc_to_taxonomy($docs);
            return $taxonomy_solr;
        }

        return null;
    }

    /**
     * convert solr docs to taxonomy
     */
    public static function _convert_doc_to_taxonomy($docs) {
        $DrupalTaxonomy = new \stdClass;
        $DrupalTaxonomy = current($docs);

        $data = (object) array(
          'HashKey' => $DrupalTaxonomy->hash_key,
          'Path' => $DrupalTaxonomy->url,
          'Channel' => $DrupalTaxonomy->channel ?: null,
          'SubChannel' => $DrupalTaxonomy->sub_channel ?: null,
          'Advertising' => $DrupalTaxonomy->advertising ?: null,
          'SubAdvertising' => $DrupalTaxonomy->sub_advertising ?: null,
          'AdTarget' => $DrupalTaxonomy->ad_target,
          'tag_name' => $DrupalTaxonomy->tag_name ?: null,
          'tag_group_name' => $DrupalTaxonomy->tag_group_name ?: null,
          'Type' => $DrupalTaxonomy->type,
          'Timelessness' => $DrupalTaxonomy->timelessness,
          'InterestLevel' => $DrupalTaxonomy->interest_level ?: null,
          'Index' => $DrupalTaxonomy->index,
          'NoIndexParams' => $DrupalTaxonomy->no_index_params,
          'Follow' => $DrupalTaxonomy->follow,
          'Master' => $DrupalTaxonomy->master,
          'keywords' => $DrupalTaxonomy->keywords ?: null,
          'metatags_description' => $DrupalTaxonomy->metatags_description ?: null,
          'metatags_title' => $DrupalTaxonomy->metatags_title ?: null,
          'web_tool_settings' => $DrupalTaxonomy->web_tool_settings ? unserialize($DrupalTaxonomy->web_tool_settings) : null,
        );
        return $data;
    }
}
