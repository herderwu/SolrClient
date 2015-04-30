<?php
namespace Solr;
//remove indent

class ParamParse
{

    /**
     * get multi value query
     */
    public static function getMultiValueQuery($filed_name, $multi_values, $need_upper = false) {
        $nids = array();
        foreach ($multi_values as $nid) {
            //taggroupsId/taggroups maybe empty
            if (empty($nid)) {
              continue;
            }

            if ($need_upper) {
                $nid = strtoupper($nid);
            }
            $nids[] = "{$filed_name}:\"{$nid}\"";
        }

        if (empty($nids)) {
          return '(*:*)';
        }

        return '(' . rawurlencode(implode(' OR ', $nids)) . ')';
    }

    /**
     * get Filed List
     */
    public static function getFiledList($block_setting) {
        $filed_list = 'nid,title,summary,';
        $filed_list .= 'image_url,img_alt,image_title,video_url,url,is_auto_republish,';
        $filed_list .= 'channel_name,sub_channel_name,ticker,sitedate,';
        $filed_list .= 'author_id,author_name,';
        $filed_list .= 'partner_id,partner_name,';
        $filed_list .= 'is_sponsored_content,';
        $filed_list .= 'advertising_name,sub_advertising_name,';
        $filed_list .= 'seo_standout,original_sitedate,final_time';

        //show_body
        if (!empty($block_setting['show_body'])) {
            $filed_list .= ',body';
        }

        //show_tags
        if (!empty($block_setting['show_tags'])) {
            $filed_list .= ',tag_name';
        }

        ////to do, remove it. TEST: return all fields
        //$filed_list = '*';

        return $filed_list;
    }

    /**
     * Content List
     *
     * $block_setting = array(
     *   "index"=> true,
     *   "types" => array('Video', 'Stock Analysis', 'Short Article', 'Term'),
     *   "tags"=> array('Sector - Technology','Current Events'),
     *   "primaryTag"=> 'Equity',
     *   "taggroups"=> array('Fundamentals', 'Markets'),
     *   "typesId" => array(1, 5),
     *   "tagsId"=> array(859, 1562),
     *   "primaryTagId"=> '1563',
     *   "taggroupsId"=> array(257, 2475),
     *   "fromPastXDays"=> 3000,
     *   "tickers"=> array('MSFT'),
     *   "authorId"=> 52826,
     *   "excludePartners"=> true,
     *   "excludeMFO"=> true,
     *   "mustIncludeTicker"=> true,
     *   "alphaSort"=> true,
     *   "maxCount"=> 5,
     *   "page"=> 1,
     *   "show_body" => true,
     *   "term_type" => '1',//1,A-Z,false
     *   "show_tags" => false,
     * );
     */
    public static function ContentList($block_setting) {
        $query_params = array();
        $fq_array = array();

        $query_params[] = '&q=*:*';

        //"node_type":"invcontent"
        $fq_array[] = 'node_type' . rawurlencode(':invcontent');

        //index: index
        if (!empty($block_setting['index'])) {
            $fq_array[] = 'index' . rawurlencode(':true');
        }

        //types: &fq=(type_name:"Video" OR type_name:"Stock Analysis")
        if (!empty($block_setting['types'])) {
            $fq_array[] = self::getMultiValueQuery('type_name', $block_setting['types']);
        }

        //tags: tag_name
        if (!empty($block_setting['tags'])) {
            $fq_array[] = self::getMultiValueQuery('tag_name', $block_setting['tags']);
        }

        //primaryTag(must have this tag): tag_name
        if (!empty($block_setting['primaryTag'])) {
            $fq_array[] = 'tag_name' . rawurlencode(':"' . $block_setting['primaryTag'] . '"');
        }

        //taggroups: tag_group_name
        if (!empty($block_setting['taggroups'])) {
            //ignore empty taggroups(array(0=>''))
            if (count($block_setting['taggroups']) != 1 || !empty($block_setting['taggroups'][0])) {
                $fq_array[] = self::getMultiValueQuery('tag_group_name', $block_setting['taggroups']);
            }
        }

        //typesId: &fq=(type_tid:"1" OR type_tid:"5")
        if (!empty($block_setting['typesId'])) {
            $fq_array[] = self::getMultiValueQuery('type_tid', $block_setting['typesId']);
        }

        //tagsId: tag_id
        if (!empty($block_setting['tagsId'])) {
            $fq_array[] = self::getMultiValueQuery('tag_id', $block_setting['tagsId']);
        }

        //primaryTagId(must have this tag): tag_id
        if (!empty($block_setting['primaryTagId'])) {
            $fq_array[] = 'tag_id' . rawurlencode(':"' . $block_setting['primaryTagId'] . '"');
        }

        //taggroupsId: tag_group_id
        if (!empty($block_setting['taggroupsId'])) {
            //ignore empty taggroupsId(array(0=>''))
            if (count($block_setting['taggroupsId']) != 1 || !empty($block_setting['taggroupsId'][0])) {
                $fq_array[] = self::getMultiValueQuery('tag_group_id', $block_setting['taggroupsId']);
            }
        }

        //fromPastXDays: sitedate
        $from = '*';
        if (!empty($block_setting['fromPastXDays'])) {
            $from = time() - $block_setting['fromPastXDays'] * 86400;
        }
        $to = time();
        $fq_array[] = 'sitedate' . rawurlencode(':' . "[{$from} TO {$to}]");

        //tickers: ticker_search(upper case)
        if (!empty($block_setting['tickers'])) {
            $fq_array[] = self::getMultiValueQuery('ticker_search', $block_setting['tickers'], true);
        }

        //authorId: author_id
        if (!empty($block_setting['authorId'])) {
            $fq_array[] = 'author_id' . rawurlencode(':' . $block_setting['authorId']);
        }

        //excludePartners: partner_id = Investopedia.com
        //TODO INV_DEFINE::COMPANY_INVESTOPEDIA_UID  = 52612;     //Investopedia.com
        if (!empty($block_setting['excludePartners'])) {
            $fq_array[] = 'partner_id' . rawurlencode(':' . 52612);
        }

        //excludeMFO: is_mfo_url:false
        if (!empty($block_setting['excludeMFO'])) {
            $fq_array[] = 'is_mfo_url' . rawurlencode(':false');
        }

        //mustIncludeTicker:is_include_ticker:true
        if (!empty($block_setting['mustIncludeTicker'])) {
            $fq_array[] = 'is_include_ticker' . rawurlencode(':true');
        }

        //term_type: term_type
        if (!empty($block_setting['term_type'])) {
            $fq_array[] = 'term_type' . rawurlencode(':' . $block_setting['term_type']);
        }

        //fq
        if (!empty($fq_array)) {
            $query_params[] = '&fq=' . implode('+AND+', $fq_array);
        }

        //alphaSort: sort
        if (!empty($block_setting['alphaSort'])) {
            //titleOrder(sort when dump), title(TODO)
            $query_params[] = '&sort=titleOrder' . rawurlencode(' asc');
        }
        else {
            $query_params[] = '&sort=sitedate' . rawurlencode(' desc');
        }

        //maxCount,page: start, rows
        $maxCount = 50;
        if (!empty($block_setting['maxCount'])) {
            $maxCount = $block_setting['maxCount'];
        }
        if (!empty($block_setting['page'])) {
            $query_params[] = '&start=' . (($block_setting['page'] - 1) * $maxCount);
        }
        $query_params[] = '&rows=' . $maxCount;

        //return fields: fl
        $filed_list = self::getFiledList($block_setting);
        $query_params[] = "&fl=" . rawurlencode($filed_list);

        return $query_params;
    }

    /**
     * related content
     *
     * $block_setting = array(
     *     "types" => array('Video', 'Stock Analysis', 'Short Article', 'Term'),
     *     "tags"=> array('Sector - Technology','Current Events'),
     *     "typesId" => array('1', '5'),
     *     "tagsId"=> array(859, 1562),
     *     "tickers"=> array('MSFT'),
     *     "maxCount"=> 5,
     *     "mustIncludeTicker"=> true,
     *     "excludeContentIds"=> array(136625,136817),
     *     "show_body" => true,
     * );
     */
    public static function RelatedContent($block_setting) {
        $query_params = array();
        $fq_array = array();

        $query_params[] = '&q=*:*';

        //"node_type":"invcontent"
        $fq_array[] = 'node_type' . rawurlencode(':invcontent');

        //types: &fq=(type_name:"Video" OR type_name:"Stock Analysis")
        if (!empty($block_setting['types'])) {
            $fq_array[] = self::getMultiValueQuery('type_name', $block_setting['types']);
        }

        //tags: tag_name
        if (!empty($block_setting['tags'])) {
            $tags_query = self::getMultiValueQuery('tag_name', $block_setting['tags']);
        }

        //typesId: &fq=(type_tid:"Video" OR type_tid:"Stock Analysis")
        if (!empty($block_setting['typesId'])) {
            $fq_array[] = self::getMultiValueQuery('type_tid', $block_setting['typesId']);
        }

        //tagsId: tag_id
        if (!empty($block_setting['tagsId'])) {
            $tags_query = self::getMultiValueQuery('tag_id', $block_setting['tagsId']);
        }

        //sitedate: <= now
        $from = '*';
        $to = time();
        $fq_array[] = 'sitedate' . rawurlencode(':' . "[{$from} TO {$to}]");

        //tickers: ticker_search
        if (!empty($block_setting['tickers'])) {
            $tickers_query = self::getMultiValueQuery('ticker_search', $block_setting['tickers'], true);
        }

        if ($tags_query && $tickers_query) {
          //if tags and tickers both existed, need OR
          $fq_array[] = "({$tags_query}+OR+{$tickers_query})";
        }
        else {
          //if only tags or tickers existed
          if ($tags_query) {
            $fq_array[] = $tags_query;
          }

          if ($tickers_query) {
            $fq_array[] = $tickers_query;
          }
        }

        //mustIncludeTicker:is_include_ticker:true
        if (!empty($block_setting['mustIncludeTicker'])) {
            $fq_array[] = 'is_include_ticker' . rawurlencode(':true');
        }

        //excludeContentIds:  &fq=-nid:40430 AND -nid:28447
        if (!empty($block_setting['excludeContentIds'])) {
            $nids = array();
            foreach ($block_setting['excludeContentIds'] as $nid) {
                $nids[] = "-nid:{$nid}";
            }
            $fq_array[] = rawurlencode(implode(' AND ', $nids));
        }

        //fq
        if (!empty($fq_array)) {
            $query_params[] = '&fq=' . implode('+AND+', $fq_array);
        }

        //sort: default by sitedate desc
        $query_params[] = '&sort=sitedate' . rawurlencode(' desc');

        //maxCount,page: start, rows
        $maxCount = 50;
        if (!empty($block_setting['maxCount'])) {
            $maxCount = $block_setting['maxCount'];
        }
        $query_params[] = '&rows=' . $maxCount;

        //return fields: fl
        $filed_list = self::getFiledList($block_setting);

        $query_params[] = "&fl=" . rawurlencode($filed_list);

        return $query_params;
    }

    /**
     * Contents
     *
     * $block_setting = array(
     *     "nids"=> array(136625,136817),
     *     "urls"=> array("articles/optioninvestor/09/long-straddle-strangle-earnings.asp","terms/c/c.asp"),
     *     "show_body" => true,
     * );
     */
    public static function ContentsByIDs($block_setting) {
        $query_params = array();
        $maxCount = 10;

        //sitedate: <= now
        $from = '*';
        $to = time();
        $query_params[] = '&fq=sitedate' . rawurlencode(':' . "[{$from} TO {$to}]");

        //nids:  &q=nid:136625 OR nid:136817
        if (!empty($block_setting['nids'])) {
            $maxCount = count($block_setting['nids']);

            $nids = array();
            foreach ($block_setting['nids'] as $nid) {
                $nids[] = "nid:{$nid}";
            }
            $query_params[] = '&q=' . rawurlencode(implode(' OR ', $nids));
        }

        //urls:  &q=url:"terms/c/c.asp" OR url:"articles/optioninvestor/09/long-straddle-strangle-earnings.asp"
        if (!empty($block_setting['urls'])) {
            $maxCount = count($block_setting['urls']);

            $urls = array();
            foreach ($block_setting['urls'] as $url) {
                $url = trim($url, '/');
                $urls[] = "url:\"{$url}\"";
            }
            $query_params[] = '&q=' . rawurlencode(implode(' OR ', $urls));
        }

        //sort: default by sitedate desc
        $query_params[] = '&sort=sitedate' . rawurlencode(' desc');

        $query_params[] = '&rows=' . $maxCount;

        //return fields: fl
        $filed_list = self::getFiledList($block_setting);

        //add other fields for node_solr
        $filed_list .= ',tag_id,tag_name,priority';
        $filed_list .= ',show_image';
        $filed_list .= ',partner_url,partner_image_url,partnerlinks_title,partnerlinks_url';
        $filed_list .= ',author_url,author_legal_disclaimer';
        $filed_list .= ',video_series_id,video_series_name';
        $filed_list .= ',type_tid,root_nid';

        $query_params[] = "&fl=" . rawurlencode($filed_list);

        return $query_params;
    }

    /**
     * Book Children
     *
     * $block_setting = array(
     *     "nid"=> 24132,
     *     "show_body" => true,
     * );
     */
    public static function BookChildren($block_setting, $book_type = '') {
        $query_params = array();
        $fq_query = '';

        $query_params[] = '&q=*:*';

        //nid:  &fq=-nid:24132 AND root_nid:24132
        if (!empty($block_setting['nid'])) {
            //exclude root node itself
            $fq_query = '&fq=' . rawurlencode("-nid:{$block_setting['nid']} AND root_nid:{$block_setting['nid']}");
        }

        //sort: tree_index asc, weight asc, nid asc
        $query_params[] = '&sort=' . rawurlencode('tree_index asc, weight asc, nid asc');

        //suggest max children count is 1000
        $query_params[] = '&rows=1000';

        //return fields: fl
        $filed_list = 'nid,title';
        if ($book_type == 'Slideshow') {
            $filed_list .= ',image_url,img_alt,image_title';
        }
        elseif ($book_type == 'Tutorial') {
            $filed_list .= ',image_url,img_alt,image_title';
            $filed_list .= ',url';
        }
        else {
            $filed_list .= ',url';
            $filed_list .= ',mlid,weight,depth,nav_map';

            //get all book node include root
            $fq_query = '&fq=' . rawurlencode("root_nid:{$block_setting['nid']}");
        }

        //show_body
        if (!empty($block_setting['show_body'])) {
            $filed_list .= ',body';
        }

        $query_params[] = "&fl=" . rawurlencode($filed_list);

        $query_params[] = $fq_query;

        return $query_params;
    }

    /**
     * Slideshow Children
     *
     * $block_setting = array(
     *     "nid"=> 24132,
     *     "show_body" => true,
     * );
     */
    public static function SlideshowChildren($block_setting) {
        return self::BookChildren($block_setting, 'Slideshow');
    }

    /**
     * Tutorial Children
     *
     * $block_setting = array(
     *     "nid"=> 20165,
     *     "show_body" => false,
     * );
     */
    public static function TutorialChildren($block_setting) {
        return self::BookChildren($block_setting, 'Tutorial');
    }

    /**
     * Page Taxonomy
     *
     * $block_setting = array(
     *     "url" => '/slide-show/wealthiest-presidents-of-all-time/',
     * );
     */
    public static function PageTaxonomy($block_setting) {
        $query_params = array();

        $query_params[] = '&q=*:*';

        //url
        if (!empty($block_setting['url'])) {
            $query_params[] = '&fq=url' . rawurlencode(':"' . $block_setting['url'] . '"');
        }

        //return fields: fl
        $query_params[] = "&fl=*";

        return $query_params;
    }

    //get full content by id
}
