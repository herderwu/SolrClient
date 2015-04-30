<?php

//test URL: example/example.php?solr_test

//TODO remove this loader
$loader = require_once('vendor/autoload.php');


//test create/get/delete node
$task_content = '{"data_type":"node","action":"add","nid":10001,"vid":10000,"date":1419323440572}';
$zk = new Zookeeper\Zk('solrcloud001.la1.vcinv.net:2181,solrcloud002.la1.vcinv.net:2181,solrcloud003.la1.vcinv.net:2181');

$zk->set('/solrcloud/task_queue/task_', $task_content, Zookeeper::SEQUENCE);

$zk->addTaskToQueue($task_content);
var_dump($zk->getChildren('/solrcloud/task_queue/'));
var_dump($zk->get('/solrcloud/task_queue/task_0000000086'));
exit;

//test solr client
require(__DIR__.'/config.php');

//test zk get solr
$zk = new Zookeeper\Zk($config['zookeeper_host']);
$host = $zk->getSolr();
if (!empty($host)) {
    $config['endpoint']['host'] = $host;
}

//GetTutorialChildren
$params = array(
    "nid"=> 20165,
    "show_body" => false,
);

//$result = Solr\Helper::GetTutorialChildren($params, $config);

//GetSlideshowChildren
$params = array(
    "nid"=> 24132,
    "show_body" => true,
);

//$result = Solr\Helper::GetSlideshowChildren($params, $config);

//test taxonomy
$params = array(
    "url" => '/slide-show/wealthiest-presidents-of-all-time/',
);

//$result = Solr\Helper::GetPageTaxonomy($params, $config);
// print "<pre>";
// print_r($result);
// print "</pre>";
// exit;
//test taxonomy end

//GetRecentContent
$block_setting = array(
    "index"=> true,

    //"types" => array('Video', 'Stock Analysis', 'Short Article', 'Term'),
    //"tags"=> array('Sector - Healthcare and Social Assistance','Current Events'),
    //"primaryTag"=> 'Sector - Mining Quarrying Oil and Gas Extraction',
    //"taggroups"=> array('Fundamentals', 'Markets'),

    "typesId" => array(1, 5),
    "tagsId"=> array(859, 1562),
    // "primaryTagId"=> 1563,
    // "taggroupsId"=> array(257, 2475),

    "fromPastXDays"=> 3000,
    "tickers"=> array('MSFT'),
    //"authorId"=> 52970,
    "excludePartners"=> true,
    "excludeMFO"=> true,
    "mustIncludeTicker"=> true,
    "alphaSort"=> true,
    "maxCount"=> 5,
    "page"=> 1,
    "show_body" => true,
    //"term_type" => '1',//1,A-Z
);

//sitemap google news
$block_setting = array(
    "typesId" => array(5 , 295),
    "fromPastXDays"=> 2+7,
    "excludePartners"=> true,
    "maxCount" => 100,
    "show_tags" => true,
);

//sitemap video
$block_setting = array(
  "typesId" => array(7),
  "fromPastXDays"=> 36500,
  "maxCount" => 1000,
  "show_body" => true,
);

//sitemap articles
$block_setting = array (
    "maxCount" => 1000,
    "page" => $page,
    "alphaSort" => true,
    "fromPastXDays"=> 36500,
    "index" => true,
);

$result = Solr\Helper::GetRecentContent($block_setting, $config);


//GetRelatedContent
$block_setting = array(
    "types" => array('Video', 'Stock Analysis', 'Short Article', 'Term'),
    "tags"=> array('Sector - Technology','Current Events'),
    "typesId" => array(1, 5),
    "tagsId"=> array(859, 1562),
    "tickers"=> array('MSFT'),
    "maxCount"=> 5,
    "mustIncludeTicker"=> true,
    "excludeContentIds"=> array(163348,163223),
);

//$result = Solr\Helper::GetRelatedContent($block_setting, $config);

$block_setting = array(
    //"nids"=> array(136625,136817),
    "urls"=> array("articles/optioninvestor/09/long-straddle-strangle-earnings.asp","terms/c/c.asp"),
    "show_body" => true,
);
//$result = Solr\Helper::GetContentsByIDs($block_setting, $config);

print "<pre>";
print_r($result);
print "</pre>";


//mult curl
$b = Solr\Client::multi();
$b->addUrls(array(
    $uri,
    $uri,
));
$rs = $b->execute();
print_r($rs);

//run by curl
$handler = curl_init();
curl_setopt($handler, CURLOPT_URL, $uri);
curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
$httpResponse = curl_exec($handler);
var_dump(json_decode($httpResponse));
